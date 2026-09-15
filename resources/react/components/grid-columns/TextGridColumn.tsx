import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { useNotification } from '@laravilt/notifications/composables/useNotification';
import { Copy } from 'lucide-react';
import { toDisplayString } from '../../lib/display';
import { resolveColumnIcon } from '../../lib/icons';

export interface TextGridColumnProps {
    value: any;
    limit?: number;
    wrap?: boolean;
    copyable?: string | null;
    badge?: boolean;
    dateTimeFormat?: string | null;
    dateFormat?: string | null;
    icon?: string | null;
    weight?: string | null;
    moneyFormat?: { currency: string; divideBy: number } | null;
    color?: string | null;
    description?: string | null;
    descriptionPosition?: 'above' | 'below';
}

// Map color to badge variant
const badgeColorMap: Record<string, string> = {
    primary: 'default',
    success: 'success',
    danger: 'destructive',
    warning: 'warning',
    info: 'secondary',
    gray: 'secondary',
    secondary: 'secondary',
};

const weightClasses: Record<string, string> = {
    thin: 'font-thin',
    extralight: 'font-extralight',
    light: 'font-light',
    normal: 'font-normal',
    medium: 'font-medium',
    semibold: 'font-semibold',
    bold: 'font-bold',
    extrabold: 'font-extrabold',
    black: 'font-black',
};

export default function TextGridColumn({
    value,
    limit,
    wrap = false,
    copyable = null,
    badge = false,
    dateTimeFormat = null,
    dateFormat = null,
    icon = null,
    weight = null,
    moneyFormat = null,
    color = null,
    description = null,
    descriptionPosition = 'below',
}: TextGridColumnProps) {
    const { notify } = useNotification();

    // Check if value is an array (for badge rendering of many-to-many relationships)
    const isArray = Array.isArray(value);

    const badgeVariant = !color ? 'secondary' : badgeColorMap[color] || 'secondary';

    const formattedValue = ((): string => {
        if (value === null || value === undefined) {
            return '';
        }

        // If it's an array for badges, don't format it here - we'll render badges individually
        if (isArray && badge) {
            return '';
        }

        // If it's an object or array (not for badges), format as JSON (same as TextColumn)
        if (typeof value === 'object' && !badge) {
            try {
                return JSON.stringify(value, null, 2);
            } catch {
                return String(value);
            }
        }

        let result = String(value);

        // Format as date/datetime
        if (dateTimeFormat && value) {
            try {
                const date = new Date(value);
                // Check if date is valid
                if (!isNaN(date.getTime())) {
                    result = date.toLocaleString('en-US', {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                    });
                }
            } catch {
                result = String(value);
            }
        } else if (dateFormat && value) {
            try {
                const date = new Date(value);
                // Check if date is valid
                if (!isNaN(date.getTime())) {
                    result = date.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                    });
                }
            } catch {
                result = String(value);
            }
        }

        // Format as money
        if (moneyFormat) {
            const numValue = Number(value) / moneyFormat.divideBy;
            result = new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: moneyFormat.currency,
            }).format(numValue);
        }

        // Apply character limit
        if (limit && result.length > limit) {
            result = result.substring(0, limit) + '...';
        }

        return result;
    })();

    const LucideIconComponent = icon ? resolveColumnIcon(icon) : null;

    const handleCopy = () => {
        if (copyable && formattedValue) {
            navigator.clipboard.writeText(formattedValue);
            notify(copyable, 'Copied to clipboard', 'success', {
                duration: 1500,
            });
        }
    };

    const weightClass = (weight && weightClasses[weight]) || '';

    return (
        <div className="flex flex-col gap-1">
            {/* Description above */}
            {description && descriptionPosition === 'above' && (
                <div className="text-[11px] text-muted-foreground/70">{description}</div>
            )}

            {/* Main content */}
            <div className="flex items-start gap-2">
                {/* Icon outside badge when not using badge */}
                {LucideIconComponent && !badge && <LucideIconComponent className="h-4 w-4 shrink-0 mt-0.5" />}

                {badge && isArray ? (
                    // Multiple badges for array values (many-to-many relationships)
                    <div className="flex flex-wrap gap-1.5">
                        {(value as any[]).map((item: any, index: number) => (
                            <Badge key={index} variant={badgeVariant as any} className="font-normal flex items-center gap-1.5">
                                {LucideIconComponent && <LucideIconComponent className="h-3 w-3 shrink-0" />}
                                {toDisplayString(item)}
                            </Badge>
                        ))}
                    </div>
                ) : badge ? (
                    // Single badge
                    <Badge variant={badgeVariant as any} className="font-normal flex items-center gap-1.5">
                        {LucideIconComponent && <LucideIconComponent className="h-3 w-3 shrink-0" />}
                        {formattedValue}
                    </Badge>
                ) : (
                    // Regular text
                    <span className={cn(weightClass, wrap ? 'whitespace-normal' : 'truncate')}>{formattedValue}</span>
                )}

                {copyable && (
                    <Button variant="ghost" size="icon" className="h-6 w-6 shrink-0" onClick={handleCopy}>
                        <Copy className="h-3 w-3" />
                    </Button>
                )}
            </div>

            {/* Description below */}
            {description && descriptionPosition === 'below' && (
                <div className="text-[11px] text-muted-foreground/70">{description}</div>
            )}
        </div>
    );
}
