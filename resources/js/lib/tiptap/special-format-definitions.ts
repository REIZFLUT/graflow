import type { TranslateFn } from '@/lib/i18n';

export type SpecialFormatDefinition = {
    id: string;
    label: string;
    description: string;
    className: string;
};

export const NORMAL_FORMAT_ID = 'normal';

export function createNormalParagraphFormat(
    t: TranslateFn,
): SpecialFormatDefinition {
    return {
        id: NORMAL_FORMAT_ID,
        label: t('editor.format.normal_paragraph.label'),
        description: t('editor.format.normal_paragraph.description'),
        className: '',
    };
}

export function createNormalCharacterFormat(
    t: TranslateFn,
): SpecialFormatDefinition {
    return {
        id: NORMAL_FORMAT_ID,
        label: t('editor.format.normal_character.label'),
        description: t('editor.format.normal_character.description'),
        className: '',
    };
}

export function createParagraphFormats(t: TranslateFn): SpecialFormatDefinition[] {
    return [
        {
            id: 'autorenkommentar',
            label: t('editor.format.author_comment.label'),
            description: t('editor.format.author_comment.description'),
            className: 'autorenkommentar',
        },
    ];
}

export function createCharacterFormats(t: TranslateFn): SpecialFormatDefinition[] {
    return [
        {
            id: 'text-red',
            label: t('editor.format.red_text.label'),
            description: t('editor.format.red_text.description'),
            className: 'text-red',
        },
    ];
}

export type BlockElementDefinition = SpecialFormatDefinition & {
    nodeType: string;
};

export function createBlockElements(t: TranslateFn): BlockElementDefinition[] {
    return [
        {
            id: 'infokasten',
            label: t('editor.format.info_box.label'),
            description: t('editor.format.info_box.description'),
            className: 'infokasten',
            nodeType: 'infoBox',
        },
    ];
}

export function getParagraphFormatById(
    id: string,
    t: TranslateFn,
): SpecialFormatDefinition | undefined {
    if (id === NORMAL_FORMAT_ID) {
        return createNormalParagraphFormat(t);
    }

    return createParagraphFormats(t).find((format) => format.id === id);
}

export function getCharacterFormatById(
    id: string,
    t: TranslateFn,
): SpecialFormatDefinition | undefined {
    if (id === NORMAL_FORMAT_ID) {
        return createNormalCharacterFormat(t);
    }

    return createCharacterFormats(t).find((format) => format.id === id);
}

export function getBlockElementById(
    id: string,
    t: TranslateFn,
): BlockElementDefinition | undefined {
    return createBlockElements(t).find((element) => element.id === id);
}
