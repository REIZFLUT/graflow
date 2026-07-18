import type { Node as ProseMirrorNode } from '@tiptap/pm/model';
import type { Editor } from '@tiptap/react';
import {
    clearEditorBlockHighlight,
    highlightEditorBlockAtPos,
} from '@/lib/tiptap/version-diff-highlight';

type EditorTextBlock = {
    pos: number;
    text: string;
};

const BLOCK_TYPES = new Set([
    'paragraph',
    'heading',
    'blockquote',
    'listItem',
    'bulletList',
    'orderedList',
    'codeBlock',
    'tableRow',
    'blockMath',
]);

let titleHighlightTimeout: ReturnType<typeof setTimeout> | null = null;
let highlightedTitleElement: HTMLElement | null = null;

function clearTitleHighlight(): void {
    if (titleHighlightTimeout) {
        clearTimeout(titleHighlightTimeout);
    }

    highlightedTitleElement?.classList.remove('version-diff-highlight');
    highlightedTitleElement = null;
    titleHighlightTimeout = null;
}

function collectTextBlocks(
    node: ProseMirrorNode,
    pos: number,
    parts: string[],
    blocks: EditorTextBlock[],
): void {
    if (node.isText) {
        parts.push(node.text ?? '');

        return;
    }

    if (!node.isLeaf) {
        node.forEach((child, offset) => {
            collectTextBlocks(child, pos + offset + 1, parts, blocks);
        });
    }

    if (BLOCK_TYPES.has(node.type.name)) {
        blocks.push({
            pos,
            text: parts.join('').trim(),
        });
        parts.length = 0;
    }
}

function getEditorTextBlocks(editor: Editor): EditorTextBlock[] {
    const blocks: EditorTextBlock[] = [];

    editor.state.doc.forEach((node, pos) => {
        collectTextBlocks(node, pos, [], blocks);
    });

    return blocks;
}

function findBlockPosition(
    editor: Editor,
    lineIndex: number,
    preferredText: string,
): number | null {
    const blocks = getEditorTextBlocks(editor);
    const normalizedPreferred = preferredText.trim();

    if (normalizedPreferred === '') {
        return null;
    }

    const blockAtIndex = blocks[lineIndex];

    if (blockAtIndex?.text === normalizedPreferred) {
        return blockAtIndex.pos;
    }

    const exactMatch = blocks.find(
        (block) => block.text === normalizedPreferred,
    );

    if (exactMatch) {
        return exactMatch.pos;
    }

    const partialMatch = blocks.find(
        (block) =>
            block.text.includes(normalizedPreferred) ||
            normalizedPreferred.includes(block.text),
    );

    return partialMatch?.pos ?? blockAtIndex?.pos ?? null;
}

export function scrollEditorToPlainTextLine(
    editor: Editor,
    lineIndex: number,
    preferredText: string,
): boolean {
    const pos = findBlockPosition(editor, lineIndex, preferredText);

    if (pos === null) {
        return false;
    }

    return highlightEditorBlockAtPos(editor, pos);
}

export function scrollEditorToTitle(titleElement: HTMLElement | null): boolean {
    if (!titleElement) {
        return false;
    }

    clearTitleHighlight();
    titleElement.classList.add('version-diff-highlight');
    titleElement.scrollIntoView({ block: 'center', behavior: 'smooth' });
    titleElement.focus({ preventScroll: true });
    highlightedTitleElement = titleElement;
    titleHighlightTimeout = setTimeout(clearTitleHighlight, 3000);

    return true;
}

export function clearEditorNavigationHighlight(editor: Editor | null): void {
    clearTitleHighlight();

    if (editor) {
        clearEditorBlockHighlight(editor);
    }
}
