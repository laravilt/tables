import { Checkbox } from '@/components/ui/checkbox';
import { cn } from '@/lib/utils';
import type { MouseEvent } from 'react';
import { useColumnUpdate } from '../../composables/useColumnUpdate';

export interface CheckboxColumnProps {
    value: any;
    name: string;
    label?: string | null;
    recordId: number | string;
    columnUpdateRoute?: string | null;
    editable?: boolean;
    disabled?: boolean;
    description?: string | null;
    descriptionPosition?: 'above' | 'below';
}

export default function CheckboxColumn({
    value,
    name,
    label = null,
    recordId,
    columnUpdateRoute = null,
    editable = true,
    disabled = false,
    description = null,
    descriptionPosition = 'below',
}: CheckboxColumnProps) {
    const { localValue, isSaving, isDisabled, save } = useColumnUpdate<boolean>(Boolean(value), {
        name,
        recordId,
        columnUpdateRoute,
        editable,
        disabled,
    });

    return (
        // Editing a cell must not trigger the row's click-to-open behaviour
        <div className="flex flex-col gap-0.5" onClick={(event: MouseEvent) => event.stopPropagation()}>
            {description && descriptionPosition === 'above' && (
                <div className="text-[10px] text-muted-foreground/60 leading-tight">{description}</div>
            )}

            <div className="flex items-center">
                <Checkbox
                    checked={Boolean(localValue)}
                    disabled={isDisabled}
                    aria-label={label || name}
                    aria-busy={isSaving}
                    className={cn(isSaving && 'opacity-60 cursor-wait')}
                    onCheckedChange={(checked) => void save(checked === true)}
                />
            </div>

            {description && descriptionPosition === 'below' && (
                <div className="text-[10px] text-muted-foreground/60 leading-tight">{description}</div>
            )}
        </div>
    );
}
