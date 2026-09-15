import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import { useNotification } from '@laravilt/notifications/composables/useNotification';
import { Copy } from 'lucide-react';
import type { MouseEvent } from 'react';
import { toDisplayString } from '../../lib/display';
import { resolveColumnIcon } from '../../lib/icons';

export interface TextColumnProps {
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
    html?: boolean;
    placeholder?: string | null;
    // New FilamentPHP v4 compatible props
    alignment?: 'start' | 'center' | 'end' | 'justify';
    tooltip?: string | null;
    url?: string | null;
    openUrlInNewTab?: boolean;
    prefix?: string | null;
    suffix?: string | null;
    grow?: boolean;
    size?: 'xs' | 'sm' | 'base' | 'lg' | 'xl' | null;
}

// Map color to badge variant
const badgeColorMap: Record<string, string> = {
    primary: 'primary',
    success: 'success',
    danger: 'danger',
    warning: 'warning',
    info: 'info',
    gray: 'gray',
    secondary: 'secondary',
    // Legacy mappings
    destructive: 'danger',
    default: 'primary',
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

const alignmentClasses: Record<string, string> = {
    start: 'text-start',
    center: 'text-center',
    end: 'text-end',
    justify: 'text-justify',
};

const sizeClasses: Record<string, string> = {
    xs: 'text-xs',
    sm: 'text-sm',
    base: 'text-base',
    lg: 'text-lg',
    xl: 'text-xl',
};

export default function TextColumn({
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
    html = false,
    placeholder = null,
    alignment = 'start',
    tooltip = null,
    url = null,
    openUrlInNewTab = false,
    prefix = null,
    suffix = null,
    grow = false,
    size = null,
}: TextColumnProps) {
    const { notify } = useNotification();

    // Check if value is an array (for badge rendering of many-to-many relationships)
    const isArray = Array.isArray(value);

    // Check if value is empty (null, undefined, empty string, or empty array)
    const isEmpty = value === null || value === undefined || value === '' || (Array.isArray(value) && value.length === 0);

    // Check if value is an object or array (for JSON formatting)
    const isObjectOrArray = typeof value === 'object' && value !== null;

    const badgeVariant = !color ? 'secondary' : badgeColorMap[color] || 'secondary';

    const formattedValue = ((): string => {
        if (value === null || value === undefined || value === '') {
            return '';
        }

        // If it's an array for badges, don't format it here - we'll render badges individually
        if (isArray && badge) {
            return '';
        }

        // If it's an object or array (not for badges), format as JSON
        if (isObjectOrArray && !badge) {
            try {
                return JSON.stringify(value, null, 2);
            } catch {
                return String(value);
            }
        }

        let result = String(value);

        // Format as date/datetime
        if (dateTimeFormat) {
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
        } else if (dateFormat) {
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

        // Apply character limit (not for HTML or JSON)
        if (limit && result.length > limit && !html && !isObjectOrArray) {
            result = result.substring(0, limit) + '...';
        }

        return result;
    })();

    const LucideIconComponent = icon ? resolveColumnIcon(icon) : null;

    const handleCopy = () => {
        if (copyable && formattedValue) {
            navigator.clipboard.writeText(formattedValue);
            notify(copyable, 'Copied to clipboard', 'success', {
                duration: 2000,
            });
        }
    };

    const weightClass = (weight && weightClasses[weight]) || '';
    const alignmentClass = alignmentClasses[alignment] || 'text-start';
    const sizeClass = (size && sizeClasses[size]) || '';

    const containerClass = ['flex flex-col gap-1 max-w-full overflow-hidden', grow ? 'flex-1' : '', alignmentClass]
        .filter(Boolean)
        .join(' ');

    // Combine prefix and suffix with value
    const displayValue = ((): string => {
        const current = formattedValue;
        if (!current) return current;

        let result = current;
        if (prefix) result = prefix + result;
        if (suffix) result = result + suffix;
        return result;
    })();

    const textClass = cn(weightClass, sizeClass, wrap ? 'whitespace-normal break-words' : limit ? 'truncate' : 'break-words');

    // Vue renders `<component :is="url ? 'a' : 'div'">`
    const Tag = (url ? 'a' : 'div') as 'a';

    const content = (
        <Tag
            href={url || undefined}
            target={url && openUrlInNewTab ? '_blank' : undefined}
            rel={url && openUrlInNewTab ? 'noopener noreferrer' : undefined}
            className={cn('flex items-start gap-2', url ? 'hover:underline cursor-pointer text-primary' : '')}
        >
            {/* Icon outside badge when not using badge */}
            {LucideIconComponent && !badge && <LucideIconComponent className="h-4 w-4 shrink-0 mt-0.5" />}

            {isEmpty && placeholder ? (
                // Show placeholder when empty
                <span className="text-muted-foreground">{placeholder}</span>
            ) : badge && isArray && !isEmpty ? (
                // Multiple badges for array values (many-to-many relationships)
                <div className="flex flex-wrap gap-1.5">
                    {(value as any[]).map((item: any, index: number) => (
                        <Badge key={index} variant={badgeVariant as any} className="font-normal flex items-center gap-1.5">
                            {LucideIconComponent && <LucideIconComponent className="h-3 w-3 shrink-0" />}
                            {toDisplayString(item)}
                        </Badge>
                    ))}
                </div>
            ) : badge && !isEmpty ? (
                // Single badge
                <Badge variant={badgeVariant as any} className="font-normal flex items-center gap-1.5">
                    {LucideIconComponent && <LucideIconComponent className="h-3 w-3 shrink-0" />}
                    {displayValue}
                </Badge>
            ) : html ? (
                // HTML content
                <div className={textClass} dangerouslySetInnerHTML={{ __html: displayValue }} />
            ) : isObjectOrArray && !badge ? (
                // JSON/Object/Array formatting
                <pre className={cn('text-xs bg-muted/50 rounded px-2 py-1 overflow-x-auto', weightClass)}>
                    <code>{displayValue}</code>
                </pre>
            ) : (
                // Regular text
                <span className={textClass}>{displayValue}</span>
            )}

            {copyable && (
                <Button
                    variant="ghost"
                    size="icon"
                    className="h-6 w-6 shrink-0"
                    onClick={(event: MouseEvent<HTMLButtonElement>) => {
                        event.stopPropagation();
                        event.preventDefault();
                        handleCopy();
                    }}
                >
                    <Copy className="h-3 w-3" />
                </Button>
            )}
        </Tag>
    );

    return (
        <div className={containerClass}>
            {/* Description above */}
            {description && descriptionPosition === 'above' && (
                <div className="text-[11px] text-muted-foreground/70">{description}</div>
            )}

            {/* Wrap with tooltip if provided */}
            {tooltip ? (
                <TooltipProvider>
                    <Tooltip>
                        <TooltipTrigger asChild>{content}</TooltipTrigger>
                        <TooltipContent>
                            <p>{tooltip}</p>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>
            ) : (
                content
            )}

            {/* Description below */}
            {description && descriptionPosition === 'below' && (
                <div className="text-[11px] text-muted-foreground/70">{description}</div>
            )}
        </div>
    );
}
