import { X } from 'lucide-react';
import { useEffect, useId, useMemo, useRef, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

export type SearchableMultiSelectOption = {
    value: number;
    label: string;
};

type SearchableMultiSelectProps = {
    options: SearchableMultiSelectOption[];
    value: number[];
    onChange: (value: number[]) => void;
    placeholder?: string;
    searchPlaceholder?: string;
    emptyMessage?: string;
    removeAriaLabel?: (label: string) => string;
    disabled?: boolean;
    id?: string;
};

export default function SearchableMultiSelect({
    options,
    value,
    onChange,
    placeholder,
    searchPlaceholder,
    emptyMessage,
    removeAriaLabel,
    disabled = false,
    id,
}: SearchableMultiSelectProps) {
    const generatedId = useId();
    const inputId = id ?? generatedId;
    const containerRef = useRef<HTMLDivElement>(null);
    const inputRef = useRef<HTMLInputElement>(null);
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');

    const selectedOptions = useMemo(
        () =>
            value
                .map((selectedValue) =>
                    options.find((option) => option.value === selectedValue),
                )
                .filter(
                    (option): option is SearchableMultiSelectOption =>
                        option !== undefined,
                ),
        [options, value],
    );

    const availableOptions = useMemo(() => {
        const query = search.trim().toLowerCase();

        return options.filter((option) => {
            if (value.includes(option.value)) {
                return false;
            }

            if (query === '') {
                return true;
            }

            return option.label.toLowerCase().includes(query);
        });
    }, [options, search, value]);

    useEffect(() => {
        if (!open) {
            return;
        }

        const handlePointerDown = (event: MouseEvent) => {
            if (
                containerRef.current &&
                !containerRef.current.contains(event.target as Node)
            ) {
                setOpen(false);
                setSearch('');
            }
        };

        document.addEventListener('mousedown', handlePointerDown);

        return () => {
            document.removeEventListener('mousedown', handlePointerDown);
        };
    }, [open]);

    const addOption = (optionValue: number) => {
        if (value.includes(optionValue)) {
            return;
        }

        onChange([...value, optionValue]);
        setSearch('');
        inputRef.current?.focus();
    };

    const removeOption = (optionValue: number) => {
        onChange(value.filter((item) => item !== optionValue));
        inputRef.current?.focus();
    };

    return (
        <div ref={containerRef} className="relative max-w-lg">
            <div
                className={cn(
                    'flex min-h-9 flex-wrap items-center gap-1.5 rounded-md border border-input bg-transparent px-2 py-1.5 shadow-xs transition-[color,box-shadow]',
                    open && 'border-ring ring-[3px] ring-ring/50',
                    disabled && 'pointer-events-none cursor-not-allowed opacity-50',
                )}
                onClick={() => {
                    if (!disabled) {
                        setOpen(true);
                        inputRef.current?.focus();
                    }
                }}
            >
                {selectedOptions.map((option) => (
                    <Badge
                        key={option.value}
                        variant="secondary"
                        className="gap-1 pr-1"
                    >
                        {option.label}
                        <button
                            type="button"
                            className="rounded-sm opacity-70 transition-opacity hover:opacity-100"
                            onClick={(event) => {
                                event.stopPropagation();
                                removeOption(option.value);
                            }}
                            aria-label={
                                removeAriaLabel
                                    ? removeAriaLabel(option.label)
                                    : option.label
                            }
                        >
                            <X className="size-3" />
                        </button>
                    </Badge>
                ))}

                <input
                    ref={inputRef}
                    id={inputId}
                    type="text"
                    value={search}
                    disabled={disabled}
                    placeholder={
                        selectedOptions.length === 0
                            ? placeholder
                            : searchPlaceholder
                    }
                    className="min-w-[8rem] flex-1 bg-transparent text-sm outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed"
                    onFocus={() => setOpen(true)}
                    onChange={(event) => {
                        setSearch(event.target.value);
                        setOpen(true);
                    }}
                    onKeyDown={(event) => {
                        if (event.key === 'Escape') {
                            setOpen(false);
                            setSearch('');
                            inputRef.current?.blur();
                        }

                        if (
                            event.key === 'Backspace' &&
                            search === '' &&
                            value.length > 0
                        ) {
                            onChange(value.slice(0, -1));
                        }
                    }}
                    aria-expanded={open}
                    aria-autocomplete="list"
                    role="combobox"
                />
            </div>

            {open && !disabled && (
                <div
                    role="listbox"
                    className="absolute z-50 mt-1 max-h-60 w-full overflow-y-auto rounded-md border bg-popover p-1 text-popover-foreground shadow-md"
                >
                    {availableOptions.length > 0 ? (
                        availableOptions.map((option) => (
                            <button
                                key={option.value}
                                type="button"
                                role="option"
                                className="flex w-full rounded-sm px-2 py-1.5 text-left text-sm outline-none hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground"
                                onMouseDown={(event) => {
                                    event.preventDefault();
                                }}
                                onClick={() => addOption(option.value)}
                            >
                                {option.label}
                            </button>
                        ))
                    ) : (
                        <p className="px-2 py-1.5 text-sm text-muted-foreground">
                            {emptyMessage}
                        </p>
                    )}
                </div>
            )}
        </div>
    );
}
