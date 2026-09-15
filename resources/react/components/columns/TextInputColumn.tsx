import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { Loader2 } from 'lucide-react';
import { useEffect, useState, type KeyboardEvent, type MouseEvent } from 'react';
import { useColumnUpdate } from '../../composables/useColumnUpdate';

export interface TextInputColumnProps {
    value: any;
    name: string;
    label?: string | null;
    placeholder?: string | null;
    recordId: number | string;
    columnUpdateRoute?: string | null;
    editable?: boolean;
    disabled?: boolean;
    type?: string;
    prefix?: string | null;
    suffix?: string | null;
    description?: string | null;
    descriptionPosition?: 'above' | 'below';
}

const toText = (value: any): string => (value === null || value === undefined ? '' : String(value));

export default function TextInputColumn({
    value,
    name,
    label = null,
    placeholder = null,
    recordId,
    columnUpdateRoute = null,
    editable = true,
    disabled = false,
    type = 'text',
    prefix = null,
    suffix = null,
    description = null,
    descriptionPosition = 'below',
}: TextInputColumnProps) {
    const { localValue, isSaving, isDisabled, save } = useColumnUpdate<any>(value ?? null, {
        name,
        recordId,
        columnUpdateRoute,
        editable,
        disabled,
    });

    // The draft is what the user is typing; it's saved on Enter/blur and resynced when the stored value changes
    const [draft, setDraft] = useState(() => toText(value));
    useEffect(() => {
        setDraft(toText(localValue));
    }, [localValue]);

    const commit = () => {
        if (draft === toText(localValue)) return;
        void save(draft === '' ? null : type === 'number' ? Number(draft) : draft);
    };

    const onKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            commit();
        } else if (event.key === 'Escape') {
            setDraft(toText(localValue));
        }
    };

    return (
        // Editing a cell must not trigger the row's click-to-open behaviour
        <div className="flex flex-col gap-0.5" onClick={(event: MouseEvent) => event.stopPropagation()}>
            {description && descriptionPosition === 'above' && (
                <div className="text-[10px] text-muted-foreground/60 leading-tight">{description}</div>
            )}

            <div className="flex items-center gap-1.5">
                {prefix && <span className="text-sm text-muted-foreground">{prefix}</span>}
                <Input
                    type={type}
                    value={draft}
                    placeholder={placeholder ?? undefined}
                    disabled={isDisabled}
                    aria-label={label || name}
                    aria-busy={isSaving}
                    className={cn('h-8 min-w-24', isSaving && 'opacity-60 cursor-wait')}
                    onChange={(event) => setDraft(event.target.value)}
                    onBlur={commit}
                    onKeyDown={onKeyDown}
                />
                {suffix && <span className="text-sm text-muted-foreground">{suffix}</span>}
                {isSaving && <Loader2 aria-hidden="true" className="size-3.5 shrink-0 animate-spin text-muted-foreground" />}
            </div>

            {description && descriptionPosition === 'below' && (
                <div className="text-[10px] text-muted-foreground/60 leading-tight">{description}</div>
            )}
        </div>
    );
}
