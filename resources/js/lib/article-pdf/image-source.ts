import { Buffer } from 'buffer';
import type { SourceObject } from '@react-pdf/types';

export function toPdfImageSource(src: string): SourceObject {
    const match = /^data:image\/(png|jpe?g);base64,(.+)$/i.exec(src);

    if (!match) {
        return src;
    }

    const format = match[1].toLowerCase() === 'png' ? 'png' : 'jpg';

    return {
        data: Buffer.from(match[2], 'base64'),
        format,
    };
}
