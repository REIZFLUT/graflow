import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import ArticlePdfViewer from '@/components/articles/article-pdf-viewer';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { edit as editArticle } from '@/routes/articles';
import { index } from '@/routes/articles';
import { show as showArticlePdf } from '@/routes/articles/pdfs';
import { store as storeAnnotatedPdf } from '@/routes/articles/pdfs/annotated';
import type { ArticlePdf } from '@/types';

type PageProps = {
    article: {
        id: number;
        title: string;
    };
    pdf: ArticlePdf;
    pdfs: ArticlePdf[];
};

export default function ArticlePdfShow({ article, pdf, pdfs }: PageProps) {
    const { t } = useTranslation();

    const handleSaveAnnotated = async (file: File) => {
        const formData = new FormData();
        formData.append('file', file);

        await router.post(
            storeAnnotatedPdf.url({
                article: article.id,
                pdf: pdf.id,
            }),
            formData,
            {
                forceFormData: true,
            },
        );
    };

    return (
        <>
            <Head
                title={t('articles.pdf.viewer_title', {
                    title: article.title,
                })}
            />

            <div className="mx-auto flex w-full max-w-7xl flex-col gap-4 p-4 md:p-6">
                <div className="flex flex-wrap items-center gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={editArticle({ article: article.id })} prefetch>
                            <ArrowLeft className="size-4" />
                            {t('articles.pdf.back_to_editor')}
                        </Link>
                    </Button>

                    <Button variant="ghost" size="sm" asChild>
                        <Link href={index()} prefetch>
                            {t('articles.editor.back')}
                        </Link>
                    </Button>
                </div>

                <div className="space-y-1">
                    <h1 className="text-xl font-semibold tracking-tight">
                        {pdf.title}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {article.title}
                    </p>
                </div>

                <ArticlePdfViewer
                    fileUrl={pdf.file_url}
                    onSaveAnnotated={handleSaveAnnotated}
                />

                {pdfs.length > 1 && (
                    <div className="rounded-lg border border-border/60 p-4">
                        <h2 className="mb-3 text-sm font-medium">
                            {t('articles.pdf.history')}
                        </h2>
                        <ul className="space-y-2 text-sm">
                            {pdfs.map((item) => (
                                <li key={item.id}>
                                    <Link
                                        href={showArticlePdf.url({
                                            article: article.id,
                                            pdf: item.id,
                                        })}
                                        className={
                                            item.id === pdf.id
                                                ? 'font-medium text-foreground'
                                                : 'text-muted-foreground hover:text-foreground'
                                        }
                                    >
                                        {item.title}
                                        <span className="ml-2 text-xs uppercase tracking-wide">
                                            {t(
                                                `articles.pdf.kind.${item.kind}`,
                                            )}
                                        </span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}
            </div>
        </>
    );
}

ArticlePdfShow.layout = () => ({
    breadcrumbs: [],
});
