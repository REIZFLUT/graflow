import { useEditorState } from '@tiptap/react';
import type { Editor } from '@tiptap/react';
import { MessageSquarePlus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';

type CommentSelectionButtonProps = {
    editor: Editor;
    onClick: () => void;
};

export default function CommentSelectionButton({
    editor,
    onClick,
}: CommentSelectionButtonProps) {
    const { t } = useTranslation();
    const hasTextSelection = useEditorState({
        editor,
        selector: ({ editor: currentEditor }) =>
            !currentEditor.state.selection.empty,
    });

    return (
        <Button
            type="button"
            variant="ghost"
            size="sm"
            className="h-7 px-2 text-xs"
            disabled={!hasTextSelection}
            onClick={onClick}
        >
            <MessageSquarePlus className="size-3.5" />
            {t('editor.toolbar.comment')}
        </Button>
    );
}
