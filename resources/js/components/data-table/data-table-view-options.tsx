import type { Table, VisibilityState } from '@tanstack/react-table';
import { SlidersHorizontal } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useTranslation } from '@/hooks/use-translation';

type DataTableViewOptionsProps<TData> = {
    table: Table<TData>;
    /**
     * Current visibility state. Passing it explicitly ensures this component
     * re-renders when visibility changes, even though the `table` instance
     * keeps a stable reference across renders.
     */
    columnVisibility: VisibilityState;
};

export function DataTableViewOptions<TData>({
    table,
    columnVisibility,
}: DataTableViewOptionsProps<TData>) {
    const { t } = useTranslation();

    const hideableColumns = table
        .getAllColumns()
        .filter(
            (column) =>
                typeof column.accessorFn !== 'undefined' && column.getCanHide(),
        );

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="outline" size="sm" className="ml-auto">
                    <SlidersHorizontal className="size-4" />
                    {t('articles.table.columns')}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-48">
                <DropdownMenuLabel>
                    {t('articles.table.columns')}
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                {hideableColumns.map((column) => (
                    <DropdownMenuCheckboxItem
                        key={column.id}
                        className="capitalize"
                        checked={columnVisibility[column.id] ?? true}
                        onCheckedChange={(value) =>
                            column.toggleVisibility(!!value)
                        }
                    >
                        {column.columnDef.meta?.label ?? column.id}
                    </DropdownMenuCheckboxItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
