const CSS_PIXEL_TO_POINT = 0.75;
const PDF_BODY_FONT_SIZE = 11;
const PDF_CONTENT_WIDTH = 511;
const KATEX_INLINE_HEIGHT_FACTOR = 1.21;

export function mathImageDimensions(
    width: number,
    height: number,
    displayMode: boolean,
): { width: number; height: number } {
    const naturalWidth = width * CSS_PIXEL_TO_POINT;
    const naturalHeight = height * CSS_PIXEL_TO_POINT;

    if (displayMode) {
        const scale = Math.min(1, PDF_CONTENT_WIDTH / naturalWidth);

        return {
            width: naturalWidth * scale,
            height: naturalHeight * scale,
        };
    }

    const targetHeight = PDF_BODY_FONT_SIZE * KATEX_INLINE_HEIGHT_FACTOR;
    const scale = targetHeight / naturalHeight;

    return {
        width: naturalWidth * scale,
        height: targetHeight,
    };
}
