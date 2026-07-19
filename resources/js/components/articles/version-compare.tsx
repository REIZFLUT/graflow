import type { Editor } from '@tiptap/react';
import { useMemo } from 'react';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslation } from '@/hooks/use-translation';
import { getArticleStatusLabel } from '@/lib/article-status';
import { formatDateTime } from '@/lib/i18n';
import { scrollEditorToPlainTextLine, scrollEditorToTitle } from '@/lib/tiptap';
import {
    segmentsToPlainText,
    tiptapToPlainText,
    tiptapToPlainTextLines,
} from '@/lib/tiptap-text';
import { cn } from '@/lib/utils';
import { buildSideBySideDiff, sideBySideHasChanges } from '@/lib/version-diff';
import type { DiffSegment, SideBySideRow } from '@/lib/version-diff';
import type { ArticleStatus, ArticleVersion } from '@/types';

type VersionCompareProps = {
    versions: ArticleVersion[];
    editor: Editor | null;
    baseId: number | null;
    compareId: number | null;
    onBaseChange: (id: number) => void;
    onCompareChange: (id: number) => void;
    onNavigateToEditor?: () => void;
};

type DiffSection = 'title' | 'content';

function findLatestByStatus(
    versions: ArticleVersion[],
    status: ArticleStatus,
): ArticleVersion | undefined {
    return versions
        .filter((version) => version.status === status)
        .reduce<ArticleVersion | undefined>((latest, version) => {
            if (!latest || version.version_number > latest.version_number) {
                return version;
            }

            return latest;
        }, undefined);
}

function findLatestUnpublished(
    versions: ArticleVersion[],
): ArticleVersion | undefined {
    return versions
        .filter((version) => version.status !== 'published')
        .reduce<ArticleVersion | undefined>((latest, version) => {
            if (!latest || version.version_number > latest.version_number) {
                return version;
            }

            return latest;
        }, undefined);
}

function DiffCell({
    segments,
    side,
    onClick,
    clickable,
}: {
    segments: DiffSegment[] | null;
    side: 'left' | 'right';
    onClick?: () => void;
    clickable?: boolean;
}) {
    if (segments === null) {
        return (
            <div className="min-h-[1.5rem] bg-muted/40 px-3 py-1" aria-hidden />
        );
    }

    return (
        <button
            type="button"
            disabled={!clickable}
            onClick={onClick}
            className={cn(
                'min-h-[1.5rem] w-full px-3 py-1 text-left text-sm leading-relaxed whitespace-pre-wrap',
                clickable &&
                    'cursor-pointer transition-colors hover:bg-muted/50 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                !clickable && 'cursor-default',
            )}
        >
            {segments.length === 0 ? (
                <span>&nbsp;</span>
            ) : (
                segments.map((segment, index) => {
                    if (segment.removed && side === 'left') {
                        return (
                            <span
                                key={index}
                                className="rounded-sm bg-red-500/25 text-red-700 dark:text-red-300"
                            >
                                {segment.value}
                            </span>
                        );
                    }

                    if (segment.added && side === 'right') {
                        return (
                            <span
                                key={index}
                                className="rounded-sm bg-emerald-500/25 text-emerald-700 dark:text-emerald-300"
                            >
                                {segment.value}
                            </span>
                        );
                    }

                    return <span key={index}>{segment.value}</span>;
                })
            )}
        </button>
    );
}

function rowBackground(
    type: SideBySideRow['type'],
    side: 'left' | 'right',
): string {
    if (type === 'removed' || (type === 'modified' && side === 'left')) {
        return side === 'left' ? 'bg-red-500/10' : '';
    }

    if (type === 'added' || (type === 'modified' && side === 'right')) {
        return side === 'right' ? 'bg-emerald-500/10' : '';
    }

    return '';
}

function DiffColumns({
    rows,
    leftLabel,
    rightLabel,
    editor,
    section,
    onNavigate,
}: {
    rows: SideBySideRow[];
    leftLabel?: string;
    rightLabel?: string;
    editor: Editor | null;
    section: DiffSection;
    onNavigate: (
        section: DiffSection,
        side: 'left' | 'right',
        row: SideBySideRow,
    ) => void;
}) {
    const canNavigate = editor !== null;

    return (
        <div className="overflow-hidden rounded-lg border border-border">
            <div className="grid grid-cols-[2.5rem_minmax(0,1fr)_2.5rem_minmax(0,1fr)] font-mono text-sm">
                {(leftLabel || rightLabel) && (
                    <div className="contents">
                        <div className="border-b border-border bg-muted/50" />
                        <div className="truncate border-b border-border bg-muted/50 px-3 py-2 font-sans text-xs font-medium">
                            {leftLabel}
                        </div>
                        <div className="border-b border-l border-border bg-muted/50" />
                        <div className="truncate border-b border-border bg-muted/50 px-3 py-2 font-sans text-xs font-medium">
                            {rightLabel}
                        </div>
                    </div>
                )}
                {rows.map((row, index) => (
                    <div key={index} className="contents">
                        <div
                            className={cn(
                                'border-b border-border/50 px-1 py-1 text-right text-xs text-muted-foreground select-none',
                                rowBackground(row.type, 'left'),
                            )}
                        >
                            {row.leftLineNumber ?? ''}
                        </div>
                        <div
                            className={cn(
                                'border-b border-border/50',
                                rowBackground(row.type, 'left'),
                            )}
                        >
                            <DiffCell
                                segments={row.left}
                                side="left"
                                clickable={canNavigate && row.left !== null}
                                onClick={() => onNavigate(section, 'left', row)}
                            />
                        </div>
                        <div
                            className={cn(
                                'border-b border-l border-border/50 px-1 py-1 text-right text-xs text-muted-foreground select-none',
                                rowBackground(row.type, 'right'),
                            )}
                        >
                            {row.rightLineNumber ?? ''}
                        </div>
                        <div
                            className={cn(
                                'border-b border-border/50',
                                rowBackground(row.type, 'right'),
                            )}
                        >
                            <DiffCell
                                segments={row.right}
                                side="right"
                                clickable={canNavigate && row.right !== null}
                                onClick={() =>
                                    onNavigate(section, 'right', row)
                                }
                            />
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}

export default function VersionCompare({
    versions,
    editor,
    baseId,
    compareId,
    onBaseChange,
    onCompareChange,
    onNavigateToEditor,
}: VersionCompareProps) {
    const { t, locale } = useTranslation();

    const sortedVersions = useMemo(
        () => [...versions].sort((a, b) => b.version_number - a.version_number),
        [versions],
    );

    const baseVersion = sortedVersions.find((version) => version.id === baseId);
    const compareVersion = sortedVersions.find(
        (version) => version.id === compareId,
    );

    const latestUnpublished = useMemo(
        () => findLatestUnpublished(versions),
        [versions],
    );
    const latestPublished = useMemo(
        () => findLatestByStatus(versions, 'published'),
        [versions],
    );

    const optionLabel = (version: ArticleVersion): string =>
        t('articles.versions.option_label', {
            number: version.version_number,
            status: version.status
                ? getArticleStatusLabel(version.status, t)
                : t('common.unknown'),
            date: formatDateTime(version.created_at, locale),
        });

    const titleRows = useMemo<SideBySideRow[]>(() => {
        if (!baseVersion || !compareVersion) {
            return [];
        }

        return buildSideBySideDiff(baseVersion.title, compareVersion.title);
    }, [baseVersion, compareVersion]);

    const contentRows = useMemo<SideBySideRow[]>(() => {
        if (!baseVersion || !compareVersion) {
            return [];
        }

        return buildSideBySideDiff(
            tiptapToPlainText(baseVersion.content),
            tiptapToPlainText(compareVersion.content),
        );
    }, [baseVersion, compareVersion]);

    const handleNavigate = (
        section: DiffSection,
        side: 'left' | 'right',
        row: SideBySideRow,
    ) => {
        if (!editor) {
            return;
        }

        // Close the (modal) sheet first so the editor is visible, then scroll
        // and highlight once the overlay has finished animating away.
        onNavigateToEditor?.();

        if (section === 'title') {
            window.setTimeout(() => {
                scrollEditorToTitle(
                    document.getElementById('title') as HTMLInputElement | null,
                );
            }, 350);

            return;
        }

        const lineNumber =
            side === 'left' ? row.leftLineNumber : row.rightLineNumber;
        const version = side === 'left' ? baseVersion : compareVersion;
        const segments = side === 'left' ? row.left : row.right;

        if (!lineNumber || !version || !segments) {
            return;
        }

        const lines = tiptapToPlainTextLines(version.content);
        const lineText = lines[lineNumber - 1] ?? segmentsToPlainText(segments);

        window.setTimeout(() => {
            scrollEditorToPlainTextLine(editor, lineNumber - 1, lineText);
        }, 350);
    };

    if (sortedVersions.length < 2) {
        return (
            <p className="pt-4 text-sm text-muted-foreground">
                {t('articles.versions.need_two_versions')}
            </p>
        );
    }

    const canQuickCompare = Boolean(latestUnpublished && latestPublished);

    const applyQuickCompare = () => {
        if (!latestUnpublished || !latestPublished) {
            return;
        }

        onBaseChange(latestUnpublished.id);
        onCompareChange(latestPublished.id);
    };

    const showSameVersionHint =
        baseVersion && compareVersion && baseVersion.id === compareVersion.id;

    return (
        <div className="space-y-5 pt-4">
            <div>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={applyQuickCompare}
                    disabled={!canQuickCompare}
                    data-test="quick-compare-workflow-published"
                >
                    {t('articles.versions.quick_workflow_vs_published')}
                </Button>
                {!latestUnpublished && (
                    <p className="mt-2 text-xs text-muted-foreground">
                        {t('articles.versions.no_unpublished')}
                    </p>
                )}
                {!latestPublished && (
                    <p className="mt-1 text-xs text-muted-foreground">
                        {t('articles.versions.no_published')}
                    </p>
                )}
            </div>

            <div className="grid gap-3 sm:grid-cols-2">
                <div className="space-y-1.5">
                    <label className="text-xs font-medium text-muted-foreground">
                        {t('articles.versions.select_base')}
                    </label>
                    <Select
                        value={baseId ? String(baseId) : undefined}
                        onValueChange={(value) => onBaseChange(Number(value))}
                    >
                        <SelectTrigger size="sm" className="w-full">
                            <SelectValue
                                placeholder={t(
                                    'articles.versions.select_placeholder',
                                )}
                            />
                        </SelectTrigger>
                        <SelectContent>
                            {sortedVersions.map((version) => (
                                <SelectItem
                                    key={version.id}
                                    value={String(version.id)}
                                >
                                    {optionLabel(version)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="space-y-1.5">
                    <label className="text-xs font-medium text-muted-foreground">
                        {t('articles.versions.select_compare')}
                    </label>
                    <Select
                        value={compareId ? String(compareId) : undefined}
                        onValueChange={(value) =>
                            onCompareChange(Number(value))
                        }
                    >
                        <SelectTrigger size="sm" className="w-full">
                            <SelectValue
                                placeholder={t(
                                    'articles.versions.select_placeholder',
                                )}
                            />
                        </SelectTrigger>
                        <SelectContent>
                            {sortedVersions.map((version) => (
                                <SelectItem
                                    key={version.id}
                                    value={String(version.id)}
                                >
                                    {optionLabel(version)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            </div>

            {showSameVersionHint ? (
                <p className="text-sm text-muted-foreground">
                    {t('articles.versions.same_version')}
                </p>
            ) : baseVersion && compareVersion ? (
                <div className="space-y-4">
                    <div className="flex flex-wrap items-center gap-4 text-xs text-muted-foreground">
                        <span className="flex items-center gap-1.5">
                            <span className="inline-block size-3 rounded-sm bg-red-500/30" />
                            {baseVersion.version_number} ·{' '}
                            {t('articles.versions.legend_removed')}
                        </span>
                        <span className="flex items-center gap-1.5">
                            <span className="inline-block size-3 rounded-sm bg-emerald-500/30" />
                            {compareVersion.version_number} ·{' '}
                            {t('articles.versions.legend_added')}
                        </span>
                        {editor && (
                            <span>
                                {t('articles.versions.click_to_navigate')}
                            </span>
                        )}
                    </div>

                    <div className="space-y-4">
                        <div className="space-y-1.5">
                            <p className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                {t('articles.versions.title_heading')}
                            </p>
                            <DiffColumns
                                rows={titleRows}
                                leftLabel={optionLabel(baseVersion)}
                                rightLabel={optionLabel(compareVersion)}
                                editor={editor}
                                section="title"
                                onNavigate={handleNavigate}
                            />
                        </div>

                        <div className="space-y-1.5">
                            <p className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                {t('articles.versions.content_heading')}
                            </p>
                            {sideBySideHasChanges(contentRows) ? (
                                <DiffColumns
                                    rows={contentRows}
                                    editor={editor}
                                    section="content"
                                    onNavigate={handleNavigate}
                                />
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    {t('articles.versions.no_changes')}
                                </p>
                            )}
                        </div>
                    </div>
                </div>
            ) : (
                <p className="text-sm text-muted-foreground">
                    {t('articles.versions.select_two')}
                </p>
            )}
        </div>
    );
}
