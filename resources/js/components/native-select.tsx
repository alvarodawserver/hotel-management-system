import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

export type SelectOption = {
    value: string;
    label: string;
};

/**
 * A native <select> styled like the shadcn Input, so its value is submitted
 * with regular <Form> components without extra state.
 */
export default function NativeSelect({
    options,
    placeholder,
    className,
    ...props
}: ComponentProps<'select'> & {
    options: SelectOption[];
    placeholder?: string;
}) {
    return (
        <select
            className={cn(
                'flex h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm dark:bg-input/30',
                'focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50',
                'aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40',
                className,
            )}
            {...props}
        >
            {placeholder !== undefined && (
                <option value="">{placeholder}</option>
            )}
            {options.map((option) => (
                <option key={option.value} value={option.value}>
                    {option.label}
                </option>
            ))}
        </select>
    );
}
