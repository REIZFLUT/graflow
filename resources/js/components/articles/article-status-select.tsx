import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslation } from '@/hooks/use-translation';
import {
    articleStatuses,
    getArticleStatusLabel,
    type ArticleStatusValue,
} from '@/lib/article-status';

type ArticleStatusSelectProps = {
    value: string;
    onChange: (status: ArticleStatusValue) => void;
    disabled?: boolean;
};

export default function ArticleStatusSelect({
    value,
    onChange,
    disabled = false,
}: ArticleStatusSelectProps) {
    const { t } = useTranslation();

    return (
        <Select
            value={value}
            onValueChange={(nextValue) =>
                onChange(nextValue as ArticleStatusValue)
            }
            disabled={disabled}
        >
            <SelectTrigger size="sm" className="w-auto min-w-32">
                <SelectValue />
            </SelectTrigger>
            <SelectContent align="end">
                {articleStatuses.map((status) => (
                    <SelectItem key={status} value={status}>
                        {getArticleStatusLabel(status, t)}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
