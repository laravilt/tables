import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { Loader2 } from 'lucide-react';
import type { MouseEvent } from 'react';
import { useColumnUpdate } from '../../composables/useColumnUpdate';

export interface SelectColumnProps {
    value: any;
    name: string;
    label?: string | null;
    placeholder?: string | null;
    recordId: number | string;
    columnUpdateRoute?: string | null;
    editable?: boolean;
    disabled?: boolean;
    options?: Record<string, string> | string[];
    selectablePlaceholder?: boolean;
    description?: string | null;
    descriptionPosition?: 'above' | 'below';
}

// Radix/Reka select items can't use an empty value, so "no value" gets a sentinel item
const NULL_VALUE = '__laravilt_null__';

const toKey = (value: any): string | null => (value === null || value === undefined || value === '' ? null : String(value));

export default function SelectColumn({
    value,
    name,
    label = null,
    placeholder = null,
    recordId,
    columnUpdateRoute = null,
    editable = true,
    disabled = false,
    options,
    selectablePlaceholder = true,
    description = null,
    descriptionPosition = 'below',
}: SelectColumnProps) {
    const { localValue, isSaving, isDisabled, save } = useColumnUpdate<string | null>(toKey(value), {
        name,
        recordId,
        columnUpdateRoute,
        editable,
        disabled,
    });

    const entries = Object.entries(options ?? {}).map(([key, optionLabel]) => ({ key, label: String(optionLabel) }));

    const onValueChange = (next: string) => {
        const newValue = next === NULL_VALUE ? null : next;
        if (newValue !== localValue) void save(newValue);
    };

    return (
        // Editing a cell must not trigger the row's click-to-open behaviour
        <div className="flex flex-col gap-0.5" onClick={(event: MouseEvent) => event.stopPropagation()}>
            {description && descriptionPosition === 'above' && (
                <div className="text-[10px] text-muted-foreground/60 leading-tight">{description}</div>
            )}

            <div className="flex items-center gap-1.5">
                <Select value={localValue ?? ''} onValueChange={onValueChange} disabled={isDisabled}>
                    <SelectTrigger
                        aria-label={label || name}
                        aria-busy={isSaving}
                        className={cn('h-8 min-w-32', isSaving && 'opacity-60 cursor-wait')}
                    >
                        <SelectValue placeholder={placeholder || '—'} />
                    </SelectTrigger>
                    <SelectContent>
                        {selectablePlaceholder && <SelectItem value={NULL_VALUE}>{placeholder || '—'}</SelectItem>}
                        {entries.map((option) => (
                            <SelectItem key={option.key} value={option.key}>
                                {option.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                {isSaving && <Loader2 aria-hidden="true" className="size-3.5 shrink-0 animate-spin text-muted-foreground" />}
            </div>

            {description && descriptionPosition === 'below' && (
                <div className="text-[10px] text-muted-foreground/60 leading-tight">{description}</div>
            )}
        </div>
    );
}
