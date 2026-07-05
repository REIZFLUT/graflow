export type DocumentStats = {
    words: number;
    letters: number;
};

export function countWords(text: string): number {
    const trimmed = text.trim();

    if (trimmed === '') {
        return 0;
    }

    return trimmed.split(/\s+/u).length;
}

export function countLetters(text: string): number {
    return [...text.matchAll(/\p{L}/gu)].length;
}

export function getDocumentStats(text: string): DocumentStats {
    return {
        words: countWords(text),
        letters: countLetters(text),
    };
}

export function combineDocumentText(
    title: string,
    body: string,
): string {
    return [title.trim(), body.trim()].filter(Boolean).join('\n');
}
