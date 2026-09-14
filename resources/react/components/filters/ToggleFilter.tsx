import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';

export interface ToggleFilterProps {
    name: string;
    label: string;
    modelValue?: boolean | null;
    description?: string;
    onUpdateModelValue?: (value: boolean | null) => void;
}

export default function ToggleFilter({ name, label, modelValue = null, description, onUpdateModelValue }: ToggleFilterProps) {
    const checked = modelValue === true;

    return (
        <div className="flex items-center justify-between gap-4 py-2">
            <div className="flex flex-col gap-1">
                <Label htmlFor={name} className="text-sm font-medium cursor-pointer">
                    {label}
                </Label>
                {description && <p className="text-xs text-muted-foreground">{description}</p>}
            </div>
            <Switch id={name} checked={checked} onCheckedChange={(value: boolean) => onUpdateModelValue?.(value)} />
        </div>
    );
}
