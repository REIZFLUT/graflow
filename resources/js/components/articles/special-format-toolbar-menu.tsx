import type { Editor } from '@tiptap/react';
import { CheckIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { SpecialFormatDefinition } from '@/lib/tiptap/special-format-definitions';
import { cn } from '@/lib/utils';

type SpecialFormatToolbarMenuProps = {
    editor: Editor;
    label: string;
    icon: ReactNode;
    formats: SpecialFormatDefinition[];
    activeId: string | null;
    onSelect: (formatId: string) => void;
    disabled?: boolean;
    isActive?: boolean;
};

export function SpecialFormatToolbarMenu({
    editor,
    label,
    icon,
    formats,
    activeId,
    onSelect,
    disabled = false,
    isActive = false,
}: SpecialFormatToolbarMenuProps) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <button
                    type="button"
                    aria-label={label}
                    title={label}
                    disabled={disabled}
                    onMouseDown={(event) => {
                        event.preventDefault();
                    }}
                    className={cn(
                        'inline-flex size-8 shrink-0 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted/80 hover:text-foreground disabled:pointer-events-none disabled:opacity-40 data-[state=open]:bg-muted data-[state=open]:text-foreground',
                        isActive && 'bg-muted text-foreground',
                    )}
                >
                    {icon}
                </button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start" className="w-72">
                {formats.map((format) => {
                    const isSelected = activeId === format.id;

                    return (
                        <DropdownMenuItem
                            key={format.id}
                            className="items-start gap-2 py-2"
                            onSelect={() => {
                                onSelect(format.id);
                                editor.chain().focus().run();
                            }}
                        >
                            <span className="mt-0.5 flex size-4 shrink-0 items-center justify-center">
                                {isSelected ? (
                                    <CheckIcon className="size-4" />
                                ) : null}
                            </span>
                            <span className="flex min-w-0 flex-col gap-0.5">
                                <span className="font-medium leading-snug">
                                    {format.label}
                                </span>
                                <span className="text-xs leading-snug text-muted-foreground">
                                    {format.description}
                                </span>
                            </span>
                        </DropdownMenuItem>
                    );
                })}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
