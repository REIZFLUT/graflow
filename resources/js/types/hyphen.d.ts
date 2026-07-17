declare module 'hyphen/de' {
    export function hyphenateSync(text: string): string;

    export function hyphenate(text: string): Promise<string>;
}

declare module 'hyphen/en' {
    export function hyphenateSync(text: string): string;

    export function hyphenate(text: string): Promise<string>;
}
