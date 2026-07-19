import type { Editor } from '@tiptap/react';

export function getCommentThreadIdsInEditor(editor: Editor | null): string[] {
    if (!editor) {
        return [];
    }

    const markType = editor.state.schema.marks.comment;

    if (!markType) {
        return [];
    }

    const ids: string[] = [];

    editor.state.doc.descendants((node) => {
        if (!node.isText) {
            return;
        }

        const commentMark = node.marks.find((mark) => mark.type === markType);
        const threadId = commentMark?.attrs.threadId as string | undefined;

        if (threadId && !ids.includes(threadId)) {
            ids.push(threadId);
        }
    });

    return ids;
}

export function getCommentMarkRange(
    editor: Editor | null,
    threadId: string,
): { from: number; to: number } | null {
    if (!editor) {
        return null;
    }

    const markType = editor.state.schema.marks.comment;

    if (!markType) {
        return null;
    }

    let range: { from: number; to: number } | null = null;

    editor.state.doc.descendants((node, pos) => {
        if (!node.isText) {
            return;
        }

        const commentMark = node.marks.find(
            (mark) =>
                mark.type === markType &&
                (mark.attrs.threadId as string) === threadId,
        );

        if (!commentMark) {
            return;
        }

        const from = pos;
        const to = pos + node.nodeSize;

        range =
            range === null
                ? { from, to }
                : { from: Math.min(range.from, from), to: Math.max(range.to, to) };
    });

    return range;
}

export function focusCommentThreadInEditor(
    editor: Editor | null,
    threadId: string,
): boolean {
    if (!editor) {
        return false;
    }

    const range = getCommentMarkRange(editor, threadId);

    if (!range) {
        return false;
    }

    editor.chain().setTextSelection(range).run();

    const dom = editor.view.dom.querySelector<HTMLElement>(
        `[data-comment-thread-id="${CSS.escape(threadId)}"]`,
    );

    dom?.scrollIntoView({ block: 'center', behavior: 'smooth' });

    return true;
}
