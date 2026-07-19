import type { Node as ProseMirrorNode } from '@tiptap/pm/model';
import { extractPlainTextWithMap } from '@/lib/tiptap/spellcheck-utils';

export type ProofreadCategory =
    | 'unfinished_sentence'
    | 'illogical_sentence'
    | 'word_repetition'
    | 'colloquialism'
    | 'language_pattern'
    | 'other';

export type ProofreadSeverity = 'info' | 'warning';

/**
 * A raw issue as returned by the AI proofreading endpoint.
 */
export type ProofreadIssue = {
    category: ProofreadCategory;
    quote: string;
    message: string;
    suggestion: string;
    severity: ProofreadSeverity;
};

/**
 * An issue mapped onto document positions. When the quote could not be located
 * in the document, `from`/`to` are null and the issue is shown in the panel
 * without an inline marker.
 */
export type MappedProofreadIssue = {
    id: string;
    from: number | null;
    to: number | null;
    category: ProofreadCategory;
    quote: string;
    message: string;
    suggestion: string;
    severity: ProofreadSeverity;
};

const PROOFREAD_CATEGORIES: ProofreadCategory[] = [
    'unfinished_sentence',
    'illogical_sentence',
    'word_repetition',
    'colloquialism',
    'language_pattern',
    'other',
];

export function normalizeProofreadCategory(value: string): ProofreadCategory {
    return (PROOFREAD_CATEGORIES as string[]).includes(value)
        ? (value as ProofreadCategory)
        : 'other';
}

type PlainTextRange = { start: number; end: number };

function escapeRegExp(value: string): string {
    return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function overlapsUsed(
    range: PlainTextRange,
    usedRanges: PlainTextRange[],
): boolean {
    return usedRanges.some(
        (used) => range.start < used.end && used.start < range.end,
    );
}

/**
 * Locate a verbatim quote within the plain text. Falls back to a
 * whitespace-tolerant match when the exact substring cannot be found. Ranges
 * that were already consumed by earlier issues are skipped so repeated quotes
 * map onto distinct occurrences.
 */
function locateQuote(
    text: string,
    quote: string,
    usedRanges: PlainTextRange[],
): PlainTextRange | null {
    const trimmed = quote.trim();

    if (trimmed === '') {
        return null;
    }

    let searchFrom = 0;

    while (searchFrom <= text.length) {
        const index = text.indexOf(trimmed, searchFrom);

        if (index === -1) {
            break;
        }

        const range: PlainTextRange = {
            start: index,
            end: index + trimmed.length,
        };

        if (!overlapsUsed(range, usedRanges)) {
            return range;
        }

        searchFrom = index + 1;
    }

    const pattern = trimmed.split(/\s+/).map(escapeRegExp).join('\\s+');

    try {
        const regex = new RegExp(pattern, 'g');
        let match: RegExpExecArray | null;

        while ((match = regex.exec(text)) !== null) {
            const range: PlainTextRange = {
                start: match.index,
                end: match.index + match[0].length,
            };

            if (!overlapsUsed(range, usedRanges)) {
                return range;
            }

            if (match.index === regex.lastIndex) {
                regex.lastIndex += 1;
            }
        }
    } catch {
        return null;
    }

    return null;
}

export function mapIssuesToPositions(
    doc: ProseMirrorNode,
    issues: ProofreadIssue[],
): MappedProofreadIssue[] {
    const { text, offsetToPos } = extractPlainTextWithMap(doc);
    const usedRanges: PlainTextRange[] = [];
    const docSize = doc.content.size;

    return issues.map((issue, index) => {
        const base: MappedProofreadIssue = {
            id: `proofread-${index}-${issue.category}`,
            from: null,
            to: null,
            category: normalizeProofreadCategory(issue.category),
            quote: issue.quote,
            message: issue.message,
            suggestion: issue.suggestion,
            severity: issue.severity === 'info' ? 'info' : 'warning',
        };

        const located = locateQuote(text, issue.quote, usedRanges);

        if (!located) {
            return base;
        }

        const from = offsetToPos(located.start);
        const to = offsetToPos(located.end);

        if (from === null || to === null || from >= to || to > docSize + 1) {
            return base;
        }

        usedRanges.push(located);

        return { ...base, from, to };
    });
}
