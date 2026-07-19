import {
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslation } from '@/hooks/use-translation';

const PER_PAGE_OPTIONS = [10, 15, 25, 50] as const;

type DataTablePaginationProps = {
    currentPage: number;
    lastPage: number;
    perPage: number;
    onPageChange: (page: number) => void;
    onPerPageChange: (perPage: number) => void;
};

export function DataTablePagination({
    currentPage,
    lastPage,
    perPage,
    onPageChange,
    onPerPageChange,
}: DataTablePaginationProps) {
    const { t } = useTranslation();

    const canPreviousPage = currentPage > 1;
    const canNextPage = currentPage < lastPage;

    return (
        <div className="flex flex-wrap items-center justify-between gap-4">
            <div className="flex items-center gap-2">
                <p className="text-sm font-medium whitespace-nowrap">
                    {t('articles.table.rows_per_page')}
                </p>
                <Select
                    value={`${perPage}`}
                    onValueChange={(value) => onPerPageChange(Number(value))}
                >
                    <SelectTrigger size="sm" className="w-[4.5rem]">
                        <SelectValue placeholder={`${perPage}`} />
                    </SelectTrigger>
                    <SelectContent>
                        {PER_PAGE_OPTIONS.map((option) => (
                            <SelectItem key={option} value={`${option}`}>
                                {option}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            <div className="flex items-center gap-4">
                <p className="text-sm font-medium whitespace-nowrap">
                    {t('articles.pagination_page_of', {
                        current: currentPage,
                        last: Math.max(lastPage, 1),
                    })}
                </p>
                <div className="flex items-center gap-1">
                    <Button
                        variant="outline"
                        size="icon"
                        className="size-8"
                        onClick={() => onPageChange(1)}
                        disabled={!canPreviousPage}
                        aria-label={t('articles.pagination')}
                    >
                        <ChevronsLeft className="size-4" />
                    </Button>
                    <Button
                        variant="outline"
                        size="icon"
                        className="size-8"
                        onClick={() => onPageChange(currentPage - 1)}
                        disabled={!canPreviousPage}
                        aria-label={t('articles.pagination')}
                    >
                        <ChevronLeft className="size-4" />
                    </Button>
                    <Button
                        variant="outline"
                        size="icon"
                        className="size-8"
                        onClick={() => onPageChange(currentPage + 1)}
                        disabled={!canNextPage}
                        aria-label={t('articles.pagination')}
                    >
                        <ChevronRight className="size-4" />
                    </Button>
                    <Button
                        variant="outline"
                        size="icon"
                        className="size-8"
                        onClick={() => onPageChange(lastPage)}
                        disabled={!canNextPage}
                        aria-label={t('articles.pagination')}
                    >
                        <ChevronsRight className="size-4" />
                    </Button>
                </div>
            </div>
        </div>
    );
}
