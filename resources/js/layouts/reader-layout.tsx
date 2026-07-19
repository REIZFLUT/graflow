import type { ReactNode } from 'react';

export default function ReaderLayout({
    children,
}: {
    children: ReactNode;
}) {
    return (
        <div className="min-h-svh bg-muted/30 text-foreground">{children}</div>
    );
}
