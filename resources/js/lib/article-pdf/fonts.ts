import { Font } from '@react-pdf/renderer';
import { hyphenateSync as hyphenateDe } from 'hyphen/de';
import { hyphenateSync as hyphenateEn } from 'hyphen/en';

let fontsRegistered = false;

type FontVariant = {
    file: string;
    fontWeight: number;
    fontStyle?: 'italic';
};

const spectralVariants: FontVariant[] = [
    { file: 'spectral-latin-300-normal.woff', fontWeight: 300 },
    { file: 'spectral-latin-300-italic.woff', fontWeight: 300, fontStyle: 'italic' },
    { file: 'spectral-latin-600-normal.woff', fontWeight: 600 },
    { file: 'spectral-latin-600-italic.woff', fontWeight: 600, fontStyle: 'italic' },
    { file: 'spectral-latin-700-normal.woff', fontWeight: 700 },
    { file: 'spectral-latin-700-italic.woff', fontWeight: 700, fontStyle: 'italic' },
];

const robotoVariants: FontVariant[] = [
    { file: 'roboto-latin-300-normal.woff', fontWeight: 300 },
    { file: 'roboto-latin-300-italic.woff', fontWeight: 300, fontStyle: 'italic' },
    { file: 'roboto-latin-400-normal.woff', fontWeight: 400 },
    { file: 'roboto-latin-400-italic.woff', fontWeight: 400, fontStyle: 'italic' },
    { file: 'roboto-latin-600-normal.woff', fontWeight: 600 },
    { file: 'roboto-latin-600-italic.woff', fontWeight: 600, fontStyle: 'italic' },
    { file: 'roboto-latin-700-normal.woff', fontWeight: 700 },
    { file: 'roboto-latin-700-italic.woff', fontWeight: 700, fontStyle: 'italic' },
];

function registerFontFamily(family: string, baseUrl: string, variants: FontVariant[]): void {
    Font.register({
        family,
        fonts: variants.map((variant) => ({
            src: `${baseUrl}/${variant.file}`,
            fontWeight: variant.fontWeight,
            ...(variant.fontStyle ? { fontStyle: variant.fontStyle } : {}),
        })),
    });
}

export function registerArticlePdfFonts(): void {
    if (fontsRegistered) {
        return;
    }

    registerFontFamily(
        'Spectral',
        'https://fonts.bunny.net/spectral/files',
        spectralVariants,
    );

    registerFontFamily(
        'Roboto',
        'https://fonts.bunny.net/roboto/files',
        robotoVariants,
    );

    fontsRegistered = true;
}

export function registerArticlePdfHyphenation(locale: string): void {
    const hyphenate = locale.startsWith('de') ? hyphenateDe : hyphenateEn;

    Font.registerHyphenationCallback((word) => {
        if (word.length < 5) {
            return [word];
        }

        return hyphenate(word).split('\u00AD');
    });
}
