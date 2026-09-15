import { cn } from '@/lib/utils';
import { resolveColumnIcon } from '../../lib/icons';

export interface IconColumnProps {
    value: any;
    boolean?: boolean;
    wrap?: boolean;
    icon?: string | null;
    color?: string | null;
    size?: string | null;
}

// Map color to Tailwind classes
const colorMap: Record<string, string> = {
    primary: 'text-primary',
    success: 'text-green-500',
    danger: 'text-destructive',
    warning: 'text-yellow-500',
    info: 'text-blue-500',
    gray: 'text-muted-foreground',
    secondary: 'text-muted-foreground',
};

// Map size to icon classes
const sizeMap: Record<string, string> = {
    xs: 'h-3 w-3',
    sm: 'h-4 w-4',
    md: 'h-5 w-5',
    lg: 'h-6 w-6',
    xl: 'h-8 w-8',
    '2xl': 'h-10 w-10',
    'extra-small': 'h-3 w-3',
    small: 'h-4 w-4',
    medium: 'h-5 w-5',
    large: 'h-6 w-6',
    'extra-large': 'h-8 w-8',
    'two-extra-large': 'h-10 w-10',
};

export default function IconColumn({ value, wrap = false, icon = null, color = null, size = null }: IconColumnProps) {
    // Use evaluated icon from backend, or fallback to value (column data)
    const LucideIconComponent = resolveColumnIcon(icon || value);

    const colorClass = !color ? 'text-muted-foreground' : colorMap[color] || 'text-muted-foreground';
    const sizeClass = sizeMap[size || 'large'] || 'h-6 w-6';

    return (
        <div className={wrap ? 'flex flex-wrap gap-1' : 'flex items-center'}>
            {LucideIconComponent && <LucideIconComponent className={cn(sizeClass, colorClass, 'shrink-0')} />}
        </div>
    );
}
