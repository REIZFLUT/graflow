import { XIcon } from 'lucide-react';
import type { ReactNode } from 'react';

import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';

type EditorSidePanelProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: ReactNode;
    description?: ReactNode;
    children: ReactNode;
    className?: string;
};

export default function EditorSidePanel({
    open,
    onOpenChange,
    title,
    description,
    children,
    className,
}: EditorSidePanelProps) {
    const { t } = useTranslation();

    if (!open) {
        return null;
    }

    return (
        <aside
            role="complementary"
            aria-label={typeof title === 'string' ? title : undefined}
            className={cn(
                'flex w-full max-h-[60dvh] shrink-0 animate-in flex-col overflow-hidden border-t border-border/60 bg-background duration-300 slide-in-from-right',
                'sm:h-full sm:max-h-none sm:w-[28rem] sm:border-t-0 sm:border-l',
                className,
            )}
        >
            <div className="relative shrink-0 border-b border-border/60 p-4">
                <div className="space-y-1 pr-8">
                    <h2 className="font-semibold text-foreground">{title}</h2>
                    {description !== undefined && (
                        <p className="text-sm text-muted-foreground">
                            {description}
                        </p>
                    )}
                </div>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="absolute top-4 right-4 size-8"
                    onClick={() => onOpenChange(false)}
                >
                    <XIcon className="size-4" />
                    <span className="sr-only">{t('common.close')}</span>
                </Button>
            </div>
            <div className="min-h-0 flex-1 overflow-y-auto overscroll-y-contain">
                {children}
            </div>
        </aside>
    );
}
