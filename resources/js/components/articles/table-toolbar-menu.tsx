import type { Editor } from '@tiptap/react';
import { useEditorState } from '@tiptap/react';
import {
    Table,
    TableColumnsSplit,
    TableRowsSplit,
    Trash2,
} from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';

type TableToolbarMenuProps = {
    editor: Editor;
};

export function TableToolbarMenu({ editor }: TableToolbarMenuProps) {
    const { t } = useTranslation();
    const { isInTable } = useEditorState({
        editor,
        selector: ({ editor: currentEditor }) => ({
            isInTable: currentEditor.isActive('table'),
        }),
    });

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <button
                    type="button"
                    aria-label={t('editor.toolbar.table')}
                    title={t('editor.toolbar.table')}
                    onMouseDown={(event) => {
                        event.preventDefault();
                    }}
                    className={cn(
                        'inline-flex size-8 shrink-0 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted/80 hover:text-foreground data-[state=open]:bg-muted data-[state=open]:text-foreground',
                        isInTable && 'bg-muted text-foreground',
                    )}
                >
                    <Table className="size-4 stroke-[1.75]" />
                </button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start" className="w-52">
                {!isInTable ? (
                    <DropdownMenuItem
                        onSelect={() =>
                            editor
                                .chain()
                                .focus()
                                .insertTable({
                                    rows: 3,
                                    cols: 3,
                                    withHeaderRow: true,
                                })
                                .run()
                        }
                    >
                        <Table className="size-4" />
                        {t('editor.toolbar.table_insert')}
                    </DropdownMenuItem>
                ) : (
                    <>
                        <DropdownMenuItem
                            disabled={!editor.can().addRowBefore()}
                            onSelect={() =>
                                editor.chain().focus().addRowBefore().run()
                            }
                        >
                            <TableRowsSplit className="size-4" />
                            {t('editor.toolbar.table_add_row_before')}
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            disabled={!editor.can().addRowAfter()}
                            onSelect={() =>
                                editor.chain().focus().addRowAfter().run()
                            }
                        >
                            <TableRowsSplit className="size-4 rotate-180" />
                            {t('editor.toolbar.table_add_row_after')}
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            disabled={!editor.can().addColumnBefore()}
                            onSelect={() =>
                                editor.chain().focus().addColumnBefore().run()
                            }
                        >
                            <TableColumnsSplit className="size-4" />
                            {t('editor.toolbar.table_add_column_before')}
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            disabled={!editor.can().addColumnAfter()}
                            onSelect={() =>
                                editor.chain().focus().addColumnAfter().run()
                            }
                        >
                            <TableColumnsSplit className="size-4 rotate-180" />
                            {t('editor.toolbar.table_add_column_after')}
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            disabled={!editor.can().deleteRow()}
                            onSelect={() =>
                                editor.chain().focus().deleteRow().run()
                            }
                        >
                            {t('editor.toolbar.table_delete_row')}
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            disabled={!editor.can().deleteColumn()}
                            onSelect={() =>
                                editor.chain().focus().deleteColumn().run()
                            }
                        >
                            {t('editor.toolbar.table_delete_column')}
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            disabled={!editor.can().toggleHeaderRow()}
                            onSelect={() =>
                                editor.chain().focus().toggleHeaderRow().run()
                            }
                        >
                            {t('editor.toolbar.table_toggle_header_row')}
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            variant="destructive"
                            disabled={!editor.can().deleteTable()}
                            onSelect={() =>
                                editor.chain().focus().deleteTable().run()
                            }
                        >
                            <Trash2 className="size-4" />
                            {t('editor.toolbar.table_delete')}
                        </DropdownMenuItem>
                    </>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
