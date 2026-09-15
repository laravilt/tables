import { cn } from '@/lib/utils';
import { useNotification } from '@laravilt/notifications/composables/useNotification';

export interface ColorGridColumnProps {
    value: any;
    copyable?: boolean;
    copyMessage?: string | null;
    copyMessageDuration?: number | null;
    wrap?: boolean;
    description?: string | null;
    descriptionPosition?: 'above' | 'below';
}

export default function ColorGridColumn({
    value,
    copyable = false,
    copyMessage = null,
    copyMessageDuration = null,
    wrap = false,
    description = null,
    descriptionPosition = 'below',
}: ColorGridColumnProps) {
    const { notify } = useNotification();

    const colors: any[] = !value ? [] : Array.isArray(value) ? value : [value];

    const handleCopy = (color: string) => {
        if (copyable && color) {
            navigator.clipboard.writeText(color);
            notify(copyMessage || 'Copied!', `Color ${color} copied to clipboard`, 'success', {
                duration: copyMessageDuration || 1500,
            });
        }
    };

    return (
        <div className="flex flex-col gap-1">
            {/* Description above */}
            {description && descriptionPosition === 'above' && (
                <div className="text-[11px] text-muted-foreground/70">{description}</div>
            )}

            {/* Main content */}
            <div className={wrap ? 'flex flex-wrap gap-1.5' : 'flex items-center gap-1.5'}>
                {colors.map((color: any, index: number) => (
                    <button
                        key={index}
                        type="button"
                        className={cn(
                            'h-6 w-6 rounded border border-border shrink-0',
                            copyable ? 'cursor-pointer hover:ring-2 hover:ring-ring transition-all' : 'cursor-default',
                        )}
                        style={{ backgroundColor: color }}
                        title={color}
                        onClick={() => {
                            if (copyable) handleCopy(color);
                        }}
                    />
                ))}
            </div>

            {/* Description below */}
            {description && descriptionPosition === 'below' && (
                <div className="text-[11px] text-muted-foreground/70">{description}</div>
            )}
        </div>
    );
}
