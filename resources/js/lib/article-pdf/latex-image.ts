import { toCanvas } from 'html-to-image';
import katex from 'katex';
import 'katex/dist/katex.min.css';

/** Matches PDF body font size; KaTeX scales math to 1.21× em per spec. */
const KATEX_INLINE_FONT_SIZE_PX = 11;
const KATEX_DISPLAY_FONT_SIZE_PX = 15;

export type LatexImageResult = {
    src: string;
    width: number;
    height: number;
};

async function waitForLayout(element: HTMLElement): Promise<void> {
    await document.fonts.ready;

    await new Promise<void>((resolve) => {
        requestAnimationFrame(() => {
            requestAnimationFrame(() => resolve());
        });
    });

    void element.offsetHeight;
}

export async function latexToImageDataUrl(
    latex: string,
    displayMode: boolean,
): Promise<LatexImageResult> {
    const element = document.createElement('div');
    element.style.position = 'fixed';
    element.style.left = '-10000px';
    element.style.top = '0';
    element.style.display = displayMode ? 'block' : 'inline-block';
    element.style.margin = '0';
    element.style.padding = displayMode ? '2px 0' : '0 1px';
    element.style.lineHeight = '1';
    element.style.fontSize = `${displayMode ? KATEX_DISPLAY_FONT_SIZE_PX : KATEX_INLINE_FONT_SIZE_PX}px`;
    element.style.background = '#ffffff';
    element.style.color = '#18181b';
    document.body.appendChild(element);

    try {
        katex.render(latex, element, {
            throwOnError: false,
            displayMode,
            output: 'html',
        });

        const katexElement =
            (element.querySelector('.katex-html') as HTMLElement | null) ??
            (element.querySelector('.katex') as HTMLElement | null) ??
            element;

        await waitForLayout(katexElement);

        const { width, height } = katexElement.getBoundingClientRect();

        if (width < 1 || height < 1) {
            throw new Error(
                `KaTeX formula rendered with zero size: ${latex}`,
            );
        }

        const roundedWidth = Math.ceil(width);
        const roundedHeight = Math.ceil(height);

        const canvas = await toCanvas(katexElement, {
            pixelRatio: 2,
            backgroundColor: '#ffffff',
            width: roundedWidth,
            height: roundedHeight,
            cacheBust: true,
        });

        assertCanvasHasContent(canvas);

        const src = canvas.toDataURL('image/png');

        if (!src.startsWith('data:image/png;base64,')) {
            throw new Error('KaTeX image conversion failed');
        }

        return {
            src,
            width: roundedWidth,
            height: roundedHeight,
        };
    } finally {
        document.body.removeChild(element);
    }
}

function assertCanvasHasContent(canvas: HTMLCanvasElement): void {
    const context = canvas.getContext('2d');

    if (!context) {
        throw new Error('KaTeX image canvas is unavailable');
    }

    const { data } = context.getImageData(0, 0, canvas.width, canvas.height);

    for (let index = 0; index < data.length; index += 4) {
        const red = data[index];
        const green = data[index + 1];
        const blue = data[index + 2];
        const alpha = data[index + 3];

        if (alpha > 0 && (red < 250 || green < 250 || blue < 250)) {
            return;
        }
    }

    throw new Error('KaTeX formula rendered as a blank image');
}
