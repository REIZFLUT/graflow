import { cn } from '@/lib/utils';

type GraflowLogoProps = {
    className?: string;
    size?: 'sm' | 'md' | 'lg';
};

const sizeClasses = {
    sm: 'text-[1.6875rem]',
    md: 'text-[1.875rem]',
    lg: 'text-[2.8125rem]',
} as const;

export default function GraflowLogo({
    className,
    size = 'md',
}: GraflowLogoProps) {
    return (
        <span
            className={cn(
                'inline-flex items-baseline tracking-tight',
                sizeClasses[size],
                className,
            )}
        >
            <span className="font-roboto font-black">Gra</span>
            <span className="font-spectral font-light italic">flow</span>
        </span>
    );
}
