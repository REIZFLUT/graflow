import {
    AnnotationPlugin,
    DocumentManagerPlugin,
    ExportPlugin,
    PDFViewer
    
    
} from '@embedpdf/react-pdf-viewer';
import type {PDFViewerRef, PluginRegistry} from '@embedpdf/react-pdf-viewer';
import { Save } from 'lucide-react';
import { useCallback, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/hooks/use-translation';

type ArticlePdfViewerProps = {
    fileUrl: string;
    onSaveAnnotated: (file: File) => Promise<void>;
};

export default function ArticlePdfViewer({
    fileUrl,
    onSaveAnnotated,
}: ArticlePdfViewerProps) {
    const { t } = useTranslation();
    const viewerRef = useRef<PDFViewerRef>(null);
    const registryRef = useRef<PluginRegistry | null>(null);
    const [saving, setSaving] = useState(false);

    const handleSave = useCallback(async () => {
        const registry =
            registryRef.current ?? (await viewerRef.current?.registry);

        if (!registry) {
            return;
        }

        const documentManager = registry
            .getPlugin(DocumentManagerPlugin.id)
            ?.provides?.();
        const documentId = documentManager?.getActiveDocumentId?.();

        if (!documentId) {
            return;
        }

        setSaving(true);

        try {
            const annotationPlugin = registry
                .getPlugin(AnnotationPlugin.id)
                ?.provides?.();

            if (annotationPlugin) {
                await annotationPlugin.forDocument(documentId).commit().toPromise();
            }

            const exportPlugin = registry
                .getPlugin(ExportPlugin.id)
                ?.provides?.();

            const bytes = await exportPlugin
                ?.forDocument(documentId)
                .saveAsCopy()
                .toPromise();

            if (!bytes) {
                return;
            }

            const file = new File([bytes], 'annotated.pdf', {
                type: 'application/pdf',
            });

            await onSaveAnnotated(file);
        } finally {
            setSaving(false);
        }
    }, [onSaveAnnotated]);

    return (
        <div className="flex h-[calc(100vh-10rem)] flex-col overflow-hidden rounded-lg border border-border/60">
            <div className="flex items-center justify-end border-b border-border/60 bg-background px-3 py-2">
                <Button
                    type="button"
                    size="sm"
                    disabled={saving}
                    onClick={() => void handleSave()}
                >
                    {saving ? (
                        <Spinner className="size-4" />
                    ) : (
                        <Save className="size-4" />
                    )}
                    {saving
                        ? t('articles.pdf.saving_annotated')
                        : t('articles.pdf.save_annotated')}
                </Button>
            </div>

            <div className="min-h-0 flex-1">
                <PDFViewer
                    key={fileUrl}
                    ref={viewerRef}
                    config={{
                        src: fileUrl,
                        theme: { preference: 'system' },
                        tabBar: 'never',
                        annotations: {
                            annotationAuthor: 'Graflow',
                        },
                    }}
                    onReady={(registry) => {
                        registryRef.current = registry;
                    }}
                    style={{ width: '100%', height: '100%' }}
                />
            </div>
        </div>
    );
}
