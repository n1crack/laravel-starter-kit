import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { CheckIcon, ChevronsUpDownIcon, XIcon } from 'lucide-react';
import { useState } from 'react';

export interface MultiSelectOption {
    value: string;
    label: string;
}

interface MultiSelectProps {
    options: MultiSelectOption[];
    values?: string[];
    onValuesChange?: (values: string[]) => void;
    placeholder?: string;
    searchPlaceholder?: string;
    emptyText?: string;
    className?: string;
    disabled?: boolean;
    maxVisible?: number;
}

export function MultiSelect({
    options,
    values = [],
    onValuesChange,
    placeholder = 'Select options...',
    searchPlaceholder = 'Search...',
    emptyText = 'No results found.',
    className,
    disabled,
    maxVisible = 3,
}: MultiSelectProps) {
    const [open, setOpen] = useState(false);

    const selected = options.filter((option) => values.includes(option.value));

    const toggle = (value: string) => {
        onValuesChange?.(values.includes(value) ? values.filter((v) => v !== value) : [...values, value]);
    };

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    disabled={disabled}
                    className={cn('h-auto min-h-9 w-full justify-between font-normal', className)}
                >
                    <div className="flex flex-wrap items-center gap-1">
                        {selected.length === 0 && <span className="text-muted-foreground">{placeholder}</span>}
                        {selected.slice(0, maxVisible).map((option) => (
                            <Badge key={option.value} variant="secondary" className="gap-1">
                                {option.label}
                                <span
                                    role="button"
                                    tabIndex={0}
                                    aria-label={`Remove ${option.label}`}
                                    className="hover:text-destructive rounded-sm outline-none"
                                    onClick={(event) => {
                                        event.stopPropagation();
                                        toggle(option.value);
                                    }}
                                    onKeyDown={(event) => {
                                        if (event.key === 'Enter' || event.key === ' ') {
                                            event.preventDefault();
                                            event.stopPropagation();
                                            toggle(option.value);
                                        }
                                    }}
                                >
                                    <XIcon className="size-3" />
                                </span>
                            </Badge>
                        ))}
                        {selected.length > maxVisible && (
                            <Badge variant="secondary">+{selected.length - maxVisible} more</Badge>
                        )}
                    </div>
                    <ChevronsUpDownIcon className="ml-2 size-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-(--radix-popover-trigger-width) p-0" align="start">
                <Command>
                    <CommandInput placeholder={searchPlaceholder} />
                    <CommandList>
                        <CommandEmpty>{emptyText}</CommandEmpty>
                        <CommandGroup>
                            {options.map((option) => {
                                const isSelected = values.includes(option.value);

                                return (
                                    <CommandItem key={option.value} value={option.label} onSelect={() => toggle(option.value)}>
                                        <CheckIcon className={cn('mr-2 size-4', isSelected ? 'opacity-100' : 'opacity-0')} />
                                        {option.label}
                                    </CommandItem>
                                );
                            })}
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}
