import type { Node as ProseMirrorNode } from '@tiptap/pm/model';

export type LanguageToolMatch = {
    message: string;
    shortMessage: string;
    offset: number;
    length: number;
    replacements: Array<{ value: string }>;
    context: {
        text: string;
        offset: number;
        length: number;
    };
    rule: {
        id: string;
        description: string;
        category: {
            id: string;
            name: string;
        };
    };
};

export type SpellCheckCategoryClass = 'typos' | 'grammar' | 'style';

export type MappedSpellCheckMatch = {
    id: string;
    from: number;
    to: number;
    message: string;
    shortMessage: string;
    replacements: string[];
    categoryId: string;
    categoryClass: SpellCheckCategoryClass;
    context: string;
    ruleId: string;
};

type PlainTextRange = {
    plainFrom: number;
    plainTo: number;
    posFrom: number;
    posTo: number;
};

export type PlainTextExtraction = {
    text: string;
    offsetToPos: (offset: number) => number | null;
};

/**
 * Controls how KaTeX math nodes (`inlineMath`/`blockMath`) are represented in
 * the extracted plain text.
 *
 * - `skip`: formulas are omitted (default). Used for LanguageTool spell checking
 *   so raw LaTeX is never flagged as a spelling mistake.
 * - `latex`: formulas are rendered as their LaTeX source wrapped in `\(...\)`
 *   (inline) or `\[...\]` (block). This keeps sentences syntactically complete
 *   for the AI proofreader, which would otherwise treat a sentence with a
 *   removed formula as unfinished.
 */
export type MathExtractionMode = 'skip' | 'latex';

export type ExtractPlainTextOptions = {
    math?: MathExtractionMode;
};

function formatMathLatex(node: ProseMirrorNode, displayMode: boolean): string | null {
    const latex =
        typeof node.attrs?.latex === 'string' ? node.attrs.latex.trim() : '';

    if (latex === '') {
        return null;
    }

    return displayMode ? `\\[${latex}\\]` : `\\(${latex}\\)`;
}

export function categoryClassFromId(categoryId: string): SpellCheckCategoryClass {
    const normalized = categoryId.toUpperCase();

    if (normalized === 'TYPOS' || normalized === 'CASING') {
        return 'typos';
    }

    if (normalized === 'GRAMMAR') {
        return 'grammar';
    }

    return 'style';
}

export function extractPlainTextWithMap(
    doc: ProseMirrorNode,
    options: ExtractPlainTextOptions = {},
): PlainTextExtraction {
    const mathMode: MathExtractionMode = options.math ?? 'skip';
    const ranges: PlainTextRange[] = [];
    let text = '';
    let needsSeparator = false;

    doc.descendants((node, pos) => {
        if (node.isTextblock) {
            if (needsSeparator && text.length > 0) {
                text += '\n\n';
            }

            needsSeparator = true;

            node.forEach((child, offset) => {
                if (child.isText && child.text) {
                    const posFrom = pos + 1 + offset;
                    const plainFrom = text.length;
                    text += child.text;

                    ranges.push({
                        plainFrom,
                        plainTo: text.length,
                        posFrom,
                        posTo: posFrom + child.text.length,
                    });

                    return;
                }

                if (mathMode === 'latex' && child.type.name === 'inlineMath') {
                    const formatted = formatMathLatex(child, false);

                    if (formatted !== null) {
                        text += formatted;
                    }
                }
            });

            return false;
        }

        if (node.isAtom && !node.isText) {
            if (needsSeparator && text.length > 0) {
                text += '\n\n';
            }

            needsSeparator = true;

            if (mathMode === 'latex' && node.type.name === 'blockMath') {
                const formatted = formatMathLatex(node, true);

                if (formatted !== null) {
                    text += formatted;
                }
            }

            return false;
        }

        return true;
    });

    const offsetToPos = (offset: number): number | null => {
        for (const range of ranges) {
            if (offset >= range.plainFrom && offset <= range.plainTo) {
                return range.posFrom + (offset - range.plainFrom);
            }
        }

        return null;
    };

    return { text, offsetToPos };
}

export function mapMatchesToPositions(
    doc: ProseMirrorNode,
    matches: LanguageToolMatch[],
): MappedSpellCheckMatch[] {
    const { offsetToPos } = extractPlainTextWithMap(doc);
    const mapped: MappedSpellCheckMatch[] = [];

    matches.forEach((match, index) => {
        if (match.length <= 0) {
            return;
        }

        const from = offsetToPos(match.offset);
        const to = offsetToPos(match.offset + match.length);

        if (from === null || to === null || from >= to) {
            return;
        }

        const docSize = doc.content.size;

        if (from < 0 || to > docSize + 1) {
            return;
        }

        mapped.push({
            id: `spellcheck-${index}-${match.offset}-${match.length}-${match.rule.id}`,
            from,
            to,
            message: match.message,
            shortMessage: match.shortMessage || match.message,
            replacements: match.replacements
                .map((replacement) => replacement.value)
                .filter((value) => value.length > 0)
                .slice(0, 8),
            categoryId: match.rule.category.id,
            categoryClass: categoryClassFromId(match.rule.category.id),
            context: match.context.text,
            ruleId: match.rule.id,
        });
    });

    return mapped;
}
