import type { Editor } from '@tiptap/react';
import { Plus } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import {
    getTopLevelBlockAtSelection,
    getTopLevelBlocksWithMarginalNotes,
    setMarginalNoteAtPosition,
} from '@/lib/tiptap/block-utils';

type PositionedMarginalBlock = {
    id: string;
    pos: number;
    marginalNote: string | null;
    top: number;
    node: ReturnType<typeof getTopLevelBlocksWithMarginalNotes>[number]['node'];
};

type MarginalNotesColumnProps = {
    editor: Editor;
};

const MARGINAL_NOTE_FOCUS_EVENT = 'marginal-note-focus';

function MarginalNoteField({
    block,
    isActive,
    isEditing,
    onStartEditing,
    onStopEditing,
    onChange,
    addAriaLabel,
}: {
    block: PositionedMarginalBlock;
    isActive: boolean;
    isEditing: boolean;
    onStartEditing: () => void;
    onStopEditing: () => void;
    onChange: (value: string) => void;
    addAriaLabel: string;
}) {
    const textareaRef = useRef<HTMLTextAreaElement>(null);
    const hasContent = Boolean(block.marginalNote?.trim());
    const showEditor = hasContent || isEditing;

    useEffect(() => {
        if (showEditor && isEditing && textareaRef.current) {
            textareaRef.current.focus();
        }
    }, [isEditing, showEditor]);

    if (!showEditor) {
        return (
            <button
                type="button"
                data-marginal-trigger={block.id}
                aria-label={addAriaLabel}
                onClick={onStartEditing}
                className={cn(
                    'inline-flex size-5 shrink-0 items-center justify-center text-muted-foreground/40 transition-opacity hover:text-muted-foreground/70',
                    isActive
                        ? 'opacity-100'
                        : 'opacity-0 group-hover/margin:opacity-100',
                )}
            >
                <Plus className="size-3.5 stroke-[1.75]" />
            </button>
        );
    }

    return (
        <textarea
            ref={textareaRef}
            data-marginal-for={block.id}
            value={block.marginalNote ?? ''}
            onChange={(event) => onChange(event.target.value)}
            onBlur={() => {
                if (!block.marginalNote?.trim()) {
                    onStopEditing();
                }
            }}
            rows={2}
            className="marginal-note-field w-full resize-none border-0 bg-transparent px-0 py-0.5 focus:outline-none"
        />
    );
}

export default function MarginalNotesColumn({ editor }: MarginalNotesColumnProps) {
    const { t } = useTranslation();
    const columnRef = useRef<HTMLDivElement>(null);
    const [blocks, setBlocks] = useState<PositionedMarginalBlock[]>([]);
    const [activeBlockId, setActiveBlockId] = useState<string | null>(null);
    const [editingBlockId, setEditingBlockId] = useState<string | null>(null);

    const syncBlocks = useCallback(() => {
        if (editor.isDestroyed) {
            return;
        }

        const columnEl = columnRef.current;

        if (!columnEl) {
            return;
        }

        const columnRect = columnEl.getBoundingClientRect();
        const marginalBlocks = getTopLevelBlocksWithMarginalNotes(editor);

        const positionedBlocks: PositionedMarginalBlock[] = marginalBlocks
            .map((block) => {
                const dom = editor.view.nodeDOM(block.pos) as HTMLElement | null;

                if (!dom) {
                    return null;
                }

                const domRect = dom.getBoundingClientRect();

                return {
                    id: block.id,
                    pos: block.pos,
                    marginalNote: block.marginalNote,
                    top: domRect.top - columnRect.top + columnEl.scrollTop,
                    node: block.node,
                };
            })
            .filter((block): block is PositionedMarginalBlock => block !== null);

        setBlocks(positionedBlocks);

        const currentBlock = getTopLevelBlockAtSelection(editor);
        const currentId = (currentBlock?.node.attrs.id as string | undefined) ?? null;
        setActiveBlockId(currentId);
    }, [editor]);

    useEffect(() => {
        syncBlocks();

        editor.on('update', syncBlocks);
        editor.on('selectionUpdate', syncBlocks);

        const resizeObserver = new ResizeObserver(syncBlocks);
        resizeObserver.observe(editor.view.dom);

        window.addEventListener('scroll', syncBlocks, true);
        window.addEventListener('resize', syncBlocks);

        return () => {
            editor.off('update', syncBlocks);
            editor.off('selectionUpdate', syncBlocks);
            resizeObserver.disconnect();
            window.removeEventListener('scroll', syncBlocks, true);
            window.removeEventListener('resize', syncBlocks);
        };
    }, [editor, syncBlocks]);

    useEffect(() => {
        const handleFocusRequest = (event: Event) => {
            const customEvent = event as CustomEvent<{ id: string }>;
            const { id } = customEvent.detail;

            setEditingBlockId(id);

            requestAnimationFrame(() => {
                const field = document.querySelector<HTMLTextAreaElement>(
                    `[data-marginal-for="${id}"]`,
                );

                field?.focus();
            });
        };

        window.addEventListener(MARGINAL_NOTE_FOCUS_EVENT, handleFocusRequest);

        return () => {
            window.removeEventListener(
                MARGINAL_NOTE_FOCUS_EVENT,
                handleFocusRequest,
            );
        };
    }, []);

    const handleChange = (block: PositionedMarginalBlock, value: string) => {
        setMarginalNoteAtPosition(editor, block.pos, block.node, value);
    };

    const visibleBlocks = blocks.filter(
        (block) =>
            block.marginalNote?.trim() ||
            block.id === activeBlockId ||
            block.id === editingBlockId,
    );

    return (
        <>
            <div
                className="hidden min-h-full lg:block"
                aria-hidden
            >
                <div
                    ref={columnRef}
                    className="marginal-notes-column group/margin relative min-h-full"
                    aria-label={t('editor.marginal.column_aria')}
                >
                    {blocks.map((block) => (
                        <div
                            key={block.id}
                            className="marginal-note-slot"
                            style={{ top: block.top }}
                        >
                            <MarginalNoteField
                                block={block}
                                isActive={block.id === activeBlockId}
                                isEditing={block.id === editingBlockId}
                                onStartEditing={() =>
                                    setEditingBlockId(block.id)
                                }
                                onStopEditing={() => setEditingBlockId(null)}
                                onChange={(value) =>
                                    handleChange(block, value)
                                }
                                addAriaLabel={t('editor.marginal.add_aria')}
                            />
                        </div>
                    ))}
                </div>
            </div>

            {visibleBlocks.length > 0 && (
                <div className="marginal-notes-mobile space-y-3 pt-6 lg:hidden">
                    {visibleBlocks.map((block) => (
                        <MarginalNoteField
                            key={block.id}
                            block={block}
                            isActive={block.id === activeBlockId}
                            isEditing={block.id === editingBlockId}
                            onStartEditing={() => setEditingBlockId(block.id)}
                            onStopEditing={() => setEditingBlockId(null)}
                            onChange={(value) => handleChange(block, value)}
                            addAriaLabel={t('editor.marginal.add_aria')}
                        />
                    ))}
                </div>
            )}
        </>
    );
}

export function focusMarginalNoteForSelection(editor: Editor): boolean {
    const block = getTopLevelBlockAtSelection(editor);

    if (!block) {
        return false;
    }

    const id = block.node.attrs.id as string | undefined;

    if (!id) {
        return false;
    }

    window.dispatchEvent(
        new CustomEvent(MARGINAL_NOTE_FOCUS_EVENT, { detail: { id } }),
    );

    const trigger = document.querySelector<HTMLButtonElement>(
        `[data-marginal-trigger="${id}"]`,
    );

    if (trigger) {
        trigger.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }

    return true;
}
