import { diffLines, diffWordsWithSpace } from 'diff';

export type DiffSegment = {
    value: string;
    added?: boolean;
    removed?: boolean;
};

export type SideBySideRowType = 'equal' | 'added' | 'removed' | 'modified';

export type SideBySideRow = {
    type: SideBySideRowType;
    leftLineNumber: number | null;
    rightLineNumber: number | null;
    left: DiffSegment[] | null;
    right: DiffSegment[] | null;
};

/**
 * Produce a word-level diff between two plain-text strings.
 * Segments flagged `added` exist only in the new text, `removed` only in the old.
 */
export function diffText(oldText: string, newText: string): DiffSegment[] {
    return diffWordsWithSpace(oldText, newText).map((part) => ({
        value: part.value,
        added: part.added,
        removed: part.removed,
    }));
}

export function hasChanges(segments: DiffSegment[]): boolean {
    return segments.some((segment) => segment.added || segment.removed);
}

function splitLines(value: string): string[] {
    const lines = value.split('\n');

    if (lines.length > 1 && lines[lines.length - 1] === '') {
        lines.pop();
    }

    return lines;
}

function wordSegments(
    oldLine: string,
    newLine: string,
): { left: DiffSegment[]; right: DiffSegment[] } {
    const parts = diffWordsWithSpace(oldLine, newLine);

    return {
        left: parts
            .filter((part) => !part.added)
            .map((part) => ({ value: part.value, removed: part.removed })),
        right: parts
            .filter((part) => !part.removed)
            .map((part) => ({ value: part.value, added: part.added })),
    };
}

/**
 * Build an aligned, line-based side-by-side diff. Modified lines additionally
 * carry word-level highlighting so intra-line changes remain visible.
 */
export function buildSideBySideDiff(
    oldText: string,
    newText: string,
): SideBySideRow[] {
    const parts = diffLines(oldText, newText);
    const rows: SideBySideRow[] = [];

    let leftLine = 0;
    let rightLine = 0;

    for (let index = 0; index < parts.length; index++) {
        const part = parts[index];

        if (part.removed) {
            const removedLines = splitLines(part.value);
            const next = parts[index + 1];

            if (next?.added) {
                const addedLines = splitLines(next.value);
                const max = Math.max(removedLines.length, addedLines.length);

                for (let row = 0; row < max; row++) {
                    const oldLine = removedLines[row];
                    const newLine = addedLines[row];

                    if (oldLine !== undefined && newLine !== undefined) {
                        const { left, right } = wordSegments(oldLine, newLine);
                        leftLine++;
                        rightLine++;
                        rows.push({
                            type: 'modified',
                            leftLineNumber: leftLine,
                            rightLineNumber: rightLine,
                            left,
                            right,
                        });
                    } else if (oldLine !== undefined) {
                        leftLine++;
                        rows.push({
                            type: 'removed',
                            leftLineNumber: leftLine,
                            rightLineNumber: null,
                            left: [{ value: oldLine, removed: true }],
                            right: null,
                        });
                    } else if (newLine !== undefined) {
                        rightLine++;
                        rows.push({
                            type: 'added',
                            leftLineNumber: null,
                            rightLineNumber: rightLine,
                            left: null,
                            right: [{ value: newLine, added: true }],
                        });
                    }
                }

                index++;

                continue;
            }

            for (const oldLine of removedLines) {
                leftLine++;
                rows.push({
                    type: 'removed',
                    leftLineNumber: leftLine,
                    rightLineNumber: null,
                    left: [{ value: oldLine, removed: true }],
                    right: null,
                });
            }

            continue;
        }

        if (part.added) {
            for (const newLine of splitLines(part.value)) {
                rightLine++;
                rows.push({
                    type: 'added',
                    leftLineNumber: null,
                    rightLineNumber: rightLine,
                    left: null,
                    right: [{ value: newLine, added: true }],
                });
            }

            continue;
        }

        for (const line of splitLines(part.value)) {
            leftLine++;
            rightLine++;
            rows.push({
                type: 'equal',
                leftLineNumber: leftLine,
                rightLineNumber: rightLine,
                left: [{ value: line }],
                right: [{ value: line }],
            });
        }
    }

    return rows;
}

export function sideBySideHasChanges(rows: SideBySideRow[]): boolean {
    return rows.some((row) => row.type !== 'equal');
}
