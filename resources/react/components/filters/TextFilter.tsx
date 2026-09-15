import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useState, type KeyboardEvent } from 'react';
import { useWatch } from '../../composables/useWatch';

export interface TextFilterProps {
    name: string;
    label: string;
    modelValue?: string | null;
    placeholder?: string;
    type?: 'text' | 'email' | 'url' | 'number';
    onUpdateModelValue?: (value: string | null) => void;
}

export default function TextFilter({
    name,
    label,
    modelValue = null,
    placeholder = 'Enter text...',
    type = 'text',
    onUpdateModelValue,
}: TextFilterProps) {
    const [localValue, setLocalValue] = useState<string>(modelValue || '');

    useWatch(modelValue, (newValue) => {
        setLocalValue(newValue || '');
    });

    const handleInput = () => {
        const value = localValue.trim();
        onUpdateModelValue?.(value || null);
    };

    return (
        <div className="flex flex-col gap-2">
            <Label htmlFor={name} className="text-sm font-medium">
                {label}
            </Label>
            <Input
                id={name}
                value={localValue}
                type={type}
                placeholder={placeholder}
                onChange={(event) => setLocalValue(event.target.value)}
                onBlur={handleInput}
                onKeyUp={(event: KeyboardEvent<HTMLInputElement>) => {
                    if (event.key === 'Enter') handleInput();
                }}
            />
        </div>
    );
}
