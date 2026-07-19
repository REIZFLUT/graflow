import { useCallback, useEffect, useMemo, useState } from 'react';
import { useTranslation } from '@/hooks/use-translation';
import type { ArticleMedia } from '@/types';

const STAGING_TOKEN_KEY = 'article-media-staging-token';

function getCsrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

function getOrCreateStagingToken(): string {
    const existing = sessionStorage.getItem(STAGING_TOKEN_KEY);

    if (existing) {
        return existing;
    }

    const token = crypto.randomUUID();
    sessionStorage.setItem(STAGING_TOKEN_KEY, token);

    return token;
}

type MediaResponse = {
    data: ArticleMedia;
};

type MediaListResponse = {
    data: ArticleMedia[];
};

type UseArticleMediaOptions = {
    articleId?: number;
    initialMedia?: ArticleMedia[];
};

export type ArticleMediaFormData = {
    alt_text: string;
    copyright: string;
    caption: string;
};

export function useArticleMedia({
    articleId,
    initialMedia = [],
}: UseArticleMediaOptions) {
    const { t } = useTranslation();

    const stagingToken = useMemo(
        () => (articleId ? null : getOrCreateStagingToken()),
        [articleId],
    );

    const [mediaItems, setMediaItems] = useState<ArticleMedia[]>(initialMedia);
    const [uploading, setUploading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const stagingIndexUrl = stagingToken
        ? `/articles/media/staging?staging_token=${stagingToken}`
        : null;
    const stagingStoreUrl = '/articles/media/staging';
    const articleIndexUrl = articleId
        ? `/articles/${articleId}/media`
        : null;
    const articleStoreUrl = articleId
        ? `/articles/${articleId}/media`
        : null;

    const fetchMedia = useCallback(async (): Promise<void> => {
        const url = articleId ? articleIndexUrl : stagingIndexUrl;

        if (!url) {
            return;
        }

        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error(t('messages.media.load_failed'));
        }

        const payload = (await response.json()) as MediaListResponse;
        setMediaItems(payload.data);
    }, [articleId, articleIndexUrl, stagingIndexUrl, t]);

    useEffect(() => {
        if (articleId && initialMedia.length > 0) {
            setMediaItems(initialMedia);

            return;
        }

        if (!articleId && stagingToken) {
            void fetchMedia().catch(() => {
                // Staging list may be empty on first visit.
            });
        }
    }, [articleId, initialMedia, stagingToken, fetchMedia]);

    const upload = useCallback(
        async (
            file: File,
            metadata: ArticleMediaFormData,
        ): Promise<ArticleMedia> => {
            setUploading(true);
            setError(null);

            try {
                const formData = new FormData();
                formData.append('file', file);
                formData.append('alt_text', metadata.alt_text);
                formData.append('copyright', metadata.copyright);

                if (metadata.caption) {
                    formData.append('caption', metadata.caption);
                }

                if (articleId) {
                    formData.append('article', String(articleId));
                } else if (stagingToken) {
                    formData.append('staging_token', stagingToken);
                }

                const url = articleId ? articleStoreUrl : stagingStoreUrl;

                if (!url) {
                    throw new Error(t('messages.media.upload_unavailable'));
                }

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-XSRF-TOKEN': getCsrfToken(),
                    },
                    credentials: 'same-origin',
                    body: formData,
                });

                if (!response.ok) {
                    const payload = (await response.json()) as {
                        message?: string;
                        errors?: Record<string, string[]>;
                    };
                    const firstError = payload.errors
                        ? Object.values(payload.errors)[0]?.[0]
                        : null;

                    throw new Error(
                        firstError ??
                            payload.message ??
                            t('messages.media.upload_failed'),
                    );
                }

                const payload = (await response.json()) as MediaResponse;
                setMediaItems((current) => [payload.data, ...current]);

                return payload.data;
            } catch (uploadError) {
                const message =
                    uploadError instanceof Error
                        ? uploadError.message
                        : t('messages.media.upload_failed');
                setError(message);

                throw uploadError;
            } finally {
                setUploading(false);
            }
        },
        [articleId, articleStoreUrl, stagingStoreUrl, stagingToken, t],
    );

    const update = useCallback(
        async (
            mediaId: string,
            metadata: Partial<ArticleMediaFormData>,
        ): Promise<ArticleMedia> => {
            setError(null);

            const url = articleId
                ? `/articles/${articleId}/media/${mediaId}`
                : `/articles/media/staging/${mediaId}`;

            const response = await fetch(url, {
                method: 'PATCH',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'same-origin',
                body: JSON.stringify(metadata),
            });

            if (!response.ok) {
                throw new Error(t('messages.media.save_metadata_failed'));
            }

            const payload = (await response.json()) as MediaResponse;
            setMediaItems((current) =>
                current.map((item) =>
                    item.id === mediaId ? payload.data : item,
                ),
            );

            return payload.data;
        },
        [articleId, t],
    );

    const remove = useCallback(
        async (mediaId: string): Promise<void> => {
            setError(null);

            const url = articleId
                ? `/articles/${articleId}/media/${mediaId}`
                : `/articles/media/staging/${mediaId}`;

            const response = await fetch(url, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const payload = (await response.json()) as { message?: string };

                throw new Error(
                    payload.message ?? t('messages.media.delete_failed'),
                );
            }

            setMediaItems((current) =>
                current.filter((item) => item.id !== mediaId),
            );
        },
        [articleId, t],
    );

    return {
        mediaItems,
        stagingToken,
        uploading,
        error,
        upload,
        update,
        remove,
        refresh: fetchMedia,
        setMediaItems,
    };
}

export function clearStagingToken(): void {
    sessionStorage.removeItem(STAGING_TOKEN_KEY);
}
