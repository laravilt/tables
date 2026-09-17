import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';
import { router } from '@inertiajs/react';
import RecordActions from '@laravilt/actions/components/RecordActions';
import {
    AlertCircle,
    AlertTriangle,
    Archive,
    Calendar,
    CheckCircle2,
    Circle,
    Clock,
    FileText,
    Inbox,
    Loader2,
    Package,
    PenLine,
    Play,
    Star,
    User,
    XCircle,
    type LucideIcon,
} from 'lucide-react';
import { useState, type ChangeEvent, type ComponentType, type MouseEvent } from 'react';
import { useWatch } from '../composables/useWatch';
import { toDisplayString } from '../lib/display';
import { resolveColumnIcon } from '../lib/icons';
import ColorGridColumn from './grid-columns/ColorGridColumn';
import IconGridColumn from './grid-columns/IconGridColumn';
import ImageGridColumn from './grid-columns/ImageGridColumn';
import TextGridColumn from './grid-columns/TextGridColumn';
import ToggleGridColumn from './grid-columns/ToggleGridColumn';

export interface CardGridProps {
    grid: any;
    records: any[];
    recordActions?: any[];
    loading?: boolean;
    loadingMore?: boolean;
    bulkActionsAvailable?: boolean;
    resourceSlug: string;
    modelClass?: string;
    clearSelections?: number;
    onUpdateSelectedRecords?: (records: (number | string)[]) => void;
}

const columnComponents: Record<string, ComponentType<any>> = {
    text_grid_column: TextGridColumn,
    image_grid_column: ImageGridColumn,
    color_grid_column: ColorGridColumn,
    icon_grid_column: IconGridColumn,
    toggle_grid_column: ToggleGridColumn,
};

const getColumnComponent = (columnType: string): ComponentType<any> => columnComponents[columnType] || TextGridColumn;

const skeletonCount = 12;
const SKELETON_INDEXES = Array.from({ length: skeletonCount }, (_, index) => index + 1);
const LOADING_MORE_INDEXES = Array.from({ length: 12 }, (_, index) => index + 1);
const FIVE = [1, 2, 3, 4, 5];

// Helper to get nested value using dot notation (e.g., 'category.name')
const getNestedValue = (record: any, field: string) => {
    if (!field) return null;

    // First check if the flattened key exists (from backend processing)
    if (record[field] !== undefined) {
        return record[field];
    }

    // Otherwise, traverse nested objects
    if (field.includes('.')) {
        const parts = field.split('.');
        let value = record;
        for (const part of parts) {
            if (value === null || value === undefined) return null;
            value = value[part];
        }
        return value;
    }

    return record[field];
};

const formatBadgeText = (text: string) => {
    if (!text) return '';
    // Replace underscores with spaces and convert to title case
    return String(text)
        .replace(/_/g, ' ')
        .split(' ')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
        .join(' ');
};

// Get badge variant based on status value
const getBadgeVariant = (status: string) => {
    const variantMap: Record<string, 'default' | 'secondary' | 'destructive' | 'outline' | 'success' | 'warning'> = {
        active: 'success',
        draft: 'warning',
        pending: 'warning',
        out_of_stock: 'destructive',
        archived: 'secondary',
        inactive: 'secondary',
    };
    return variantMap[status] || 'default';
};

// Get relative time from created_at or updated_at
const getRelativeTime = (record: any) => {
    const dateField = record.created_at || record.updated_at;
    if (!dateField) return null;

    const date = new Date(dateField);
    const now = new Date();
    const diffInSeconds = Math.floor((now.getTime() - date.getTime()) / 1000);

    if (diffInSeconds < 60) return 'Just now';
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
    if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)}d ago`;
    if (diffInSeconds < 2592000) return `${Math.floor(diffInSeconds / 604800)}w ago`;
    return date.toLocaleDateString();
};

// Get avatar color based on record id or title
const avatarColors = [
    'bg-red-500',
    'bg-orange-500',
    'bg-amber-500',
    'bg-yellow-500',
    'bg-lime-500',
    'bg-green-500',
    'bg-emerald-500',
    'bg-teal-500',
    'bg-cyan-500',
    'bg-sky-500',
    'bg-blue-500',
    'bg-indigo-500',
    'bg-violet-500',
    'bg-purple-500',
    'bg-fuchsia-500',
    'bg-pink-500',
    'bg-rose-500',
];

const getAvatarColor = (record: any) => {
    const index = (record.id || 0) % avatarColors.length;
    return avatarColors[index];
};

// Get badge color class based on status
const badgeColorClasses: Record<string, string> = {
    active: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20',
    published: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20',
    approved: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20',
    completed: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20',
    draft: 'bg-amber-500/10 text-amber-600 border-amber-500/20',
    pending: 'bg-amber-500/10 text-amber-600 border-amber-500/20',
    processing: 'bg-blue-500/10 text-blue-600 border-blue-500/20',
    in_progress: 'bg-blue-500/10 text-blue-600 border-blue-500/20',
    inactive: 'bg-slate-500/10 text-slate-600 border-slate-500/20',
    archived: 'bg-slate-500/10 text-slate-600 border-slate-500/20',
    cancelled: 'bg-red-500/10 text-red-600 border-red-500/20',
    rejected: 'bg-red-500/10 text-red-600 border-red-500/20',
    failed: 'bg-red-500/10 text-red-600 border-red-500/20',
    out_of_stock: 'bg-red-500/10 text-red-600 border-red-500/20',
};

const getBadgeColorClass = (status: string) => badgeColorClasses[status] || 'bg-primary/10 text-primary border-primary/20';

// Get status icon based on status
const statusIcons: Record<string, LucideIcon> = {
    active: CheckCircle2,
    published: CheckCircle2,
    approved: CheckCircle2,
    completed: CheckCircle2,
    draft: PenLine,
    pending: Clock,
    processing: Loader2,
    in_progress: Play,
    inactive: Circle,
    archived: Archive,
    cancelled: XCircle,
    rejected: XCircle,
    failed: AlertCircle,
    out_of_stock: AlertTriangle,
};

const getStatusIcon = (status: string): LucideIcon => statusIcons[status] || Circle;

const gridColsMap: Record<number, string> = {
    1: 'grid-cols-1',
    2: 'grid-cols-1 sm:grid-cols-2',
    3: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
    4: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4',
    5: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5',
    6: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6',
};

function ProductSkeleton() {
    return (
        <>
            {/* Product Image Skeleton */}
            <Skeleton className="aspect-square w-full" />

            {/* Product Info Skeleton */}
            <div className="p-4 flex-1 flex flex-col">
                {/* Category / SKU */}
                <div className="flex items-center gap-2 mb-2">
                    <Skeleton className="h-3 w-16" />
                    <Skeleton className="h-3 w-20" />
                </div>

                {/* Title */}
                <Skeleton className="h-4 w-full mb-1" />
                <Skeleton className="h-4 w-3/4 mb-2" />

                {/* Description */}
                <Skeleton className="h-3 w-full mb-1" />
                <Skeleton className="h-3 w-2/3 mb-3" />

                {/* Rating */}
                <div className="flex items-center gap-1.5 mb-3">
                    <div className="flex gap-0.5">
                        {FIVE.map((s) => (
                            <Skeleton key={s} className="h-3.5 w-3.5 rounded-sm" />
                        ))}
                    </div>
                    <Skeleton className="h-3 w-8" />
                </div>

                {/* Price & Stock */}
                <div className="mt-auto pt-3 border-t border-border/50">
                    <div className="flex items-end justify-between">
                        <Skeleton className="h-6 w-20" />
                        <div className="flex items-center gap-1.5">
                            <Skeleton className="h-2 w-2 rounded-full" />
                            <Skeleton className="h-3 w-16" />
                        </div>
                    </div>
                </div>
            </div>

            {/* Actions Footer Skeleton */}
            <div className="border-t bg-muted/30 px-4 py-3 flex items-center justify-center gap-2">
                <Skeleton className="h-9 w-20 rounded-md" />
                <Skeleton className="h-9 w-20 rounded-md" />
                <Skeleton className="h-9 w-20 rounded-md" />
            </div>
        </>
    );
}

function SimpleSkeleton() {
    return (
        <>
            {/* Main Card Content Skeleton */}
            <div className="p-5 flex-1">
                {/* Header: Checkbox + ID */}
                <div className="flex items-center gap-2 mb-4 pb-3 border-b border-border/50">
                    <Skeleton className="h-5 w-5 rounded shrink-0" />
                    <Skeleton className="h-4 w-14 rounded" />
                </div>

                {/* Body: Avatar + Info (centered) */}
                <div className="flex flex-col items-center gap-4">
                    {/* Avatar Skeleton */}
                    <Skeleton className="h-16 w-16 rounded-full shrink-0" />

                    {/* Title and Description Skeleton */}
                    <div className="w-full space-y-3 text-center">
                        <div className="flex items-center justify-center gap-2">
                            <Skeleton className="h-4 w-4 rounded shrink-0" />
                            <Skeleton className="h-5 w-3/4 max-w-[200px]" />
                        </div>
                        <div className="flex items-center justify-center gap-2">
                            <Skeleton className="h-4 w-4 rounded shrink-0" />
                            <Skeleton className="h-4 w-full max-w-[250px]" />
                        </div>
                    </div>
                </div>

                {/* Meta Row Skeleton */}
                <div className="flex items-center justify-between gap-3 mt-4 pt-4 border-t border-border/50">
                    {/* Status Badge Skeleton */}
                    <Skeleton className="h-7 w-24 rounded-full" />
                    {/* Timestamp Skeleton */}
                    <Skeleton className="h-7 w-20 rounded-full" />
                </div>
            </div>

            {/* Actions Footer Skeleton */}
            <div className="border-t bg-muted/40 px-5 py-3 flex items-center justify-center gap-3">
                <Skeleton className="h-9 w-20 rounded-md" />
                <Skeleton className="h-9 w-20 rounded-md" />
                <Skeleton className="h-9 w-20 rounded-md" />
            </div>
        </>
    );
}

export default function CardGrid({
    grid,
    records,
    loading = false,
    loadingMore = false,
    bulkActionsAvailable = false,
    resourceSlug,
    modelClass,
    clearSelections = 0,
    onUpdateSelectedRecords,
}: CardGridProps) {
    const [selectedRecords, setSelectedRecords] = useState<Set<number | string>>(() => new Set());
    const [selectAll, setSelectAll] = useState(false);

    // If loading more (infinite scroll), don't show full skeleton - show records + skeleton at bottom
    const isLoading = loadingMore ? false : !!loading;

    const handleSelectAll = (event: ChangeEvent<HTMLInputElement>) => {
        const target = event.target;
        setSelectAll(target.checked);

        const next = target.checked ? new Set<number | string>(records.map((r) => r.id)) : new Set<number | string>();
        setSelectedRecords(next);
        onUpdateSelectedRecords?.(Array.from(next));
    };

    const handleSelectRecord = (recordId: number | string) => {
        const next = new Set(selectedRecords);
        if (next.has(recordId)) {
            next.delete(recordId);
        } else {
            next.add(recordId);
        }
        setSelectedRecords(next);
        setSelectAll(next.size === records.length);
        onUpdateSelectedRecords?.(Array.from(next));
    };

    const isSelected = (recordId: number | string) => selectedRecords.has(recordId);

    // Watch for clear selections signal
    useWatch(clearSelections, () => {
        setSelectedRecords(new Set());
        setSelectAll(false);
    });

    const gridColumns: any[] = (() => {
        // Priority 1: Use card-specific schema if defined
        if (grid.card?.schema && grid.card.schema.length > 0) {
            return grid.card.schema;
        }

        // Priority 2: Use card-specific columns if defined
        if (grid.card?.columns && grid.card.columns.length > 0) {
            return grid.card.columns;
        }

        // Priority 3: If using card builder fields, filter out the fields that are already displayed
        if (grid.card?.imageField || grid.card?.titleField || grid.card?.priceField || grid.card?.descriptionField || grid.card?.badgeField) {
            const usedFields = [
                grid.card?.imageField,
                grid.card?.titleField,
                grid.card?.priceField,
                grid.card?.descriptionField,
                grid.card?.badgeField,
            ].filter(Boolean);

            return grid.columns.filter((column: any) => !usedFields.includes(column.name));
        }

        // Priority 4: Use grid columns as fallback
        return grid.columns;
    })();

    // Card styling
    const cardGap = (() => {
        switch (grid.card?.gap) {
            case 'sm':
                return 'space-y-2';
            case 'md':
                return 'space-y-3';
            case 'lg':
                return 'space-y-4';
            default:
                return 'space-y-3';
        }
    })();

    const getImageUrl = (record: any) => {
        const imageField = grid.card?.imageField;
        if (!imageField) return null;
        return getNestedValue(record, imageField);
    };

    const getTitle = (record: any) => {
        const titleField = grid.card?.titleField;
        if (!titleField) return null;
        return getNestedValue(record, titleField);
    };

    const getSubtitle = (record: any) => {
        const subtitleField = grid.card?.subtitleField;
        if (!subtitleField) return null;
        return getNestedValue(record, subtitleField);
    };

    const getDescription = (record: any) => {
        const descriptionField = grid.card?.descriptionField;
        if (!descriptionField) return null;
        return getNestedValue(record, descriptionField);
    };

    const getPrice = (record: any) => {
        const priceField = grid.card?.priceField;
        if (!priceField) return null;
        return getNestedValue(record, priceField);
    };

    const getBadge = (record: any) => {
        const badgeField = grid.card?.badgeField;
        if (!badgeField) return null;
        return getNestedValue(record, badgeField);
    };

    const getBadgeIcon = (record: any) => {
        const badgeField = grid.card?.badgeField;
        if (!badgeField) return null;
        return record._icons?.[badgeField] || null;
    };

    const getBadgeIconComponent = (record: any): LucideIcon | null => {
        const iconName = getBadgeIcon(record);
        if (!iconName) return null;
        return resolveColumnIcon(iconName);
    };

    // Dynamic grid columns based on cardsPerRow
    const gridColsClass = gridColsMap[grid?.cardsPerRow || 3] || gridColsMap[3];

    // Card style - determines which card template to use
    const cardStyle = grid.card?.style || 'default';

    // Get avatar initials from title
    const getAvatarInitials = (record: any) => {
        // Normalize first: the configured title field may hold a number or object
        const title = toDisplayString(getTitle(record));
        if (!title) return '?';
        const words = title.split(' ');
        if (words.length >= 2) {
            return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
        }
        return title.substring(0, 2).toUpperCase();
    };

    // Handle card click to navigate to record URL
    const handleCardClick = (event: MouseEvent<HTMLElement>, record: any) => {
        // Don't navigate if there's no URL
        if (!record._url) return;

        // Don't navigate if clicking on interactive elements
        const target = event.target as HTMLElement;
        const interactiveElements = ['A', 'BUTTON', 'INPUT', 'SELECT', 'TEXTAREA', 'LABEL'];

        // Check if click is on or inside an interactive element
        let element: HTMLElement | null = target;
        while (element) {
            if (interactiveElements.includes(element.tagName)) return;
            if (element.hasAttribute('data-no-card-click')) return;
            if (element.classList.contains('record-actions')) return;
            element = element.parentElement;
        }

        // Navigate using Inertia
        router.visit(record._url);
    };

    const renderRecordActions = (record: any) => (
        <RecordActions actions={record._actions} record={record} resourceName={resourceSlug} modelClass={modelClass} variant="inline" gap="default" />
    );

    // ================================
    // SIMPLE CARD STYLE
    // User card design with avatar, info, status and timestamp
    // ================================
    const renderSimpleCard = (record: any) => {
        const badge = getBadge(record);
        const imageUrl = getImageUrl(record);
        const title = getTitle(record);
        const description = getDescription(record);
        const relativeTime = getRelativeTime(record);
        const StatusIcon = getStatusIcon(badge);

        return (
            <div
                key={record.id}
                className={cn('group relative overflow-hidden transition-all duration-300 flex flex-col bg-card border rounded-lg', {
                    'hover:border-primary/40': grid.card?.hoverable !== false,
                    'ring-2 ring-primary ring-offset-2 ring-offset-background border-primary/50': isSelected(record.id),
                    'cursor-pointer': record._url,
                })}
                onClick={(event) => handleCardClick(event, record)}
            >
                {/* Main Card Content */}
                <div className="p-5 flex-1">
                    {/* Top Header: Checkbox + ID */}
                    <div className="flex items-center gap-2 mb-4 pb-3 border-b border-border/50">
                        {/* Selection Checkbox */}
                        {bulkActionsAvailable && (
                            <Checkbox
                                checked={isSelected(record.id)}
                                onCheckedChange={() => handleSelectRecord(record.id)}
                                aria-label={`Select record #${toDisplayString(record.id)}`}
                                className="size-5 rounded border-2 border-muted-foreground/40 bg-background cursor-pointer hover:border-primary/60"
                            />
                        )}
                        {/* Record ID */}
                        <span className="text-xs font-mono text-muted-foreground/70 select-none">#{toDisplayString(record.id)}</span>
                    </div>

                    {/* Body: Avatar + Info (centered horizontally) */}
                    <div className="flex flex-col items-center gap-4">
                        {/* Avatar with Status Indicator */}
                        <div className="relative shrink-0">
                            <Avatar className="h-16 w-16 ring-2 ring-border shadow-lg">
                                {imageUrl && <AvatarImage src={imageUrl} alt={title ?? undefined} />}
                                <AvatarFallback className={cn(getAvatarColor(record), 'text-white font-bold text-xl')}>
                                    {getAvatarInitials(record)}
                                </AvatarFallback>
                            </Avatar>
                            {/* Online/Status Indicator Dot */}
                            {badge && (
                                <div
                                    className={cn(
                                        'absolute bottom-0 right-0 h-4 w-4 rounded-full border-2 border-card shadow-sm',
                                        badge === 'active' || badge === 'published'
                                            ? 'bg-emerald-500'
                                            : badge === 'pending' || badge === 'draft'
                                              ? 'bg-amber-500'
                                              : badge === 'inactive' || badge === 'archived'
                                                ? 'bg-slate-400'
                                                : 'bg-red-500',
                                    )}
                                ></div>
                            )}
                        </div>

                        {/* Title and Description (centered) */}
                        <div className="w-full text-center space-y-2">
                            <div className="flex items-center justify-center gap-2">
                                <User className="h-4 w-4 text-primary shrink-0" />
                                {title && <h3 className="text-base font-bold text-foreground truncate">{toDisplayString(title)}</h3>}
                            </div>
                            {description && (
                                <div className="flex items-center justify-center gap-2">
                                    <FileText className="h-4 w-4 text-muted-foreground shrink-0" />
                                    <p className="text-sm text-muted-foreground line-clamp-2">{toDisplayString(description)}</p>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Meta Row: Status Badge + Timestamp */}
                    <div className="flex items-center justify-between gap-3 mt-4 pt-4 border-t border-border/50">
                        {/* Status Badge */}
                        {badge ? (
                            <div
                                className={cn(
                                    'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border shadow-sm',
                                    getBadgeColorClass(badge),
                                )}
                            >
                                <StatusIcon className="h-3.5 w-3.5" />
                                <span>{formatBadgeText(badge)}</span>
                            </div>
                        ) : (
                            <div className="flex-1"></div>
                        )}

                        {/* Timestamp with Calendar Icon */}
                        {relativeTime && (
                            <div className="flex items-center gap-1.5 text-xs text-muted-foreground bg-muted px-3 py-1.5 rounded-full">
                                <Calendar className="h-3.5 w-3.5" />
                                <span className="font-medium">{relativeTime}</span>
                            </div>
                        )}
                    </div>
                </div>

                {/* Actions Footer (CENTERED) */}
                {record._actions && record._actions.length > 0 && (
                    <div className="border-t bg-muted/40 px-5 py-3 flex items-center justify-center gap-3 record-actions" data-no-card-click="">
                        {renderRecordActions(record)}
                    </div>
                )}
            </div>
        );
    };

    // ================================
    // MEDIA CARD STYLE
    // Full background image with gradient overlay
    // ================================
    const renderMediaCard = (record: any) => {
        const badge = getBadge(record);
        const imageUrl = getImageUrl(record);
        const title = getTitle(record);
        const description = getDescription(record);
        const BadgeIcon = getBadgeIconComponent(record);

        return (
            <div
                key={record.id}
                className={cn('group relative overflow-hidden rounded-xl transition-all duration-300 flex flex-col', {
                    'hover:scale-[1.02]': grid.card?.hoverable !== false,
                    'ring-2 ring-primary ring-offset-2 ring-offset-background': isSelected(record.id),
                    'cursor-pointer': record._url,
                })}
                onClick={(event) => handleCardClick(event, record)}
            >
                {/* Background Image */}
                <div className="relative aspect-[16/10] overflow-hidden bg-muted">
                    {imageUrl ? (
                        <img
                            src={imageUrl}
                            alt={title || 'Media image'}
                            className="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                        />
                    ) : (
                        <div className="absolute inset-0 bg-gradient-to-br from-primary/20 to-primary/5 flex items-center justify-center">
                            <span className="text-4xl font-bold text-primary/30">{toDisplayString(title).charAt(0) || '?'}</span>
                        </div>
                    )}

                    {/* Gradient Overlay */}
                    <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent" />

                    {/* Selection Checkbox (top-left) */}
                    {bulkActionsAvailable && (
                        <div className="absolute top-3 left-3 z-10">
                            <Checkbox
                                checked={isSelected(record.id)}
                                onCheckedChange={() => handleSelectRecord(record.id)}
                                aria-label={`Select record #${toDisplayString(record.id)}`}
                                className="border-white/50 data-[state=checked]:bg-primary data-[state=checked]:border-primary"
                            />
                        </div>
                    )}

                    {/* Badge (top-right) */}
                    {badge && (
                        <div className="absolute top-3 right-3 z-10">
                            <Badge variant={getBadgeVariant(badge) as any} className="gap-1 text-xs shadow-lg">
                                {BadgeIcon && <BadgeIcon className="h-3 w-3" />}
                                {formatBadgeText(badge)}
                            </Badge>
                        </div>
                    )}

                    {/* Content Overlay (bottom) */}
                    <div className="absolute bottom-0 left-0 right-0 p-4 text-white">
                        {/* Title */}
                        {title && <h3 className="text-lg font-bold leading-tight line-clamp-2 mb-1 drop-shadow-md">{toDisplayString(title)}</h3>}

                        {/* Description */}
                        {description && <p className="text-sm text-white/80 line-clamp-2 drop-shadow-sm">{toDisplayString(description)}</p>}
                    </div>
                </div>

                {/* Actions Bar */}
                {record._actions && record._actions.length > 0 && (
                    <div
                        className="flex items-center justify-center gap-2 bg-card border border-t-0 rounded-b-xl p-3 record-actions"
                        data-no-card-click=""
                    >
                        {renderRecordActions(record)}
                    </div>
                )}
            </div>
        );
    };

    // ================================
    // PRODUCT CARD STYLE
    // E-commerce style with image, structured info, and price
    // ================================
    const renderProductCard = (record: any) => {
        const badge = getBadge(record);
        const imageUrl = getImageUrl(record);
        const title = getTitle(record);
        const subtitle = getSubtitle(record);
        const description = getDescription(record);
        const price = getPrice(record);
        const StatusIcon = getStatusIcon(badge);

        return (
            <div
                key={record.id}
                className={cn('group relative overflow-hidden transition-all duration-300 flex flex-col bg-card border rounded-xl shadow-sm', {
                    'hover:shadow-lg hover:border-primary/30 hover:-translate-y-1': grid.card?.hoverable !== false,
                    'ring-2 ring-primary ring-offset-2 ring-offset-background border-primary/50': isSelected(record.id),
                    'cursor-pointer': record._url,
                })}
                onClick={(event) => handleCardClick(event, record)}
            >
                {/* Selection Checkbox (floating) */}
                {bulkActionsAvailable && (
                    <div className="absolute top-3 left-3 z-20">
                        <Checkbox
                            checked={isSelected(record.id)}
                            onCheckedChange={() => handleSelectRecord(record.id)}
                            aria-label={`Select record #${toDisplayString(record.id)}`}
                            className="size-5 rounded border-2 border-white/80 bg-white/90 backdrop-blur-sm shadow-sm cursor-pointer hover:border-primary/60"
                        />
                    </div>
                )}

                {/* Badge (floating top-right) */}
                {badge && (
                    <div className="absolute top-3 right-3 z-20">
                        <div
                            className={cn(
                                'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide shadow-md',
                                getBadgeColorClass(badge),
                            )}
                        >
                            <StatusIcon className="h-3 w-3" />
                            <span>{formatBadgeText(badge)}</span>
                        </div>
                    </div>
                )}

                {/* Product Image Container */}
                {grid.card?.showImage !== false && (
                    <div className="relative overflow-hidden bg-gradient-to-br from-muted/50 to-muted aspect-square">
                        {imageUrl ? (
                            <img
                                src={imageUrl}
                                alt={title || 'Product image'}
                                className="w-full h-full object-cover transition-all duration-500 group-hover:scale-110"
                            />
                        ) : (
                            <div className="w-full h-full flex items-center justify-center">
                                <div className="text-center">
                                    <Package className="h-16 w-16 text-muted-foreground/30 mx-auto mb-2" />
                                    <span className="text-sm text-muted-foreground/50">No Image</span>
                                </div>
                            </div>
                        )}
                    </div>
                )}

                {/* Product Info */}
                <div className="flex-1 p-4 flex flex-col">
                    {/* Subtitle (e.g., Category) */}
                    {subtitle && (
                        <div className="flex items-center gap-2 mb-2">
                            <span className="text-[11px] font-medium text-primary uppercase tracking-wide">{toDisplayString(subtitle)}</span>
                        </div>
                    )}

                    {/* Title */}
                    {title && (
                        <h3 className="text-sm font-semibold leading-snug text-foreground line-clamp-2 mb-2 group-hover:text-primary transition-colors">
                            {toDisplayString(title)}
                        </h3>
                    )}

                    {/* Description */}
                    {description && <p className="text-xs text-muted-foreground line-clamp-2 mb-3 flex-1">{toDisplayString(description)}</p>}

                    {/* Rating */}
                    {record.rating ? (
                        <div className="flex items-center gap-1.5 mb-3">
                            <div className="flex items-center">
                                {FIVE.map((i) => (
                                    <Star
                                        key={i}
                                        className={cn(
                                            'h-3.5 w-3.5',
                                            i <= Math.round(record.rating) ? 'text-amber-400 fill-amber-400' : 'text-muted-foreground/30',
                                        )}
                                    />
                                ))}
                            </div>
                            <span className="text-xs text-muted-foreground">({toDisplayString(record.review_count || 0)})</span>
                        </div>
                    ) : null}

                    {/* Price & Stock Section */}
                    <div className="mt-auto pt-3 border-t border-border/50">
                        <div className="flex items-end justify-between gap-2">
                            {/* Price */}
                            {price ? (
                                <div className="flex flex-col">
                                    <span className="text-lg font-bold text-foreground">
                                        {typeof price === 'number' ? `$${price.toFixed(2)}` : toDisplayString(price)}
                                    </span>
                                </div>
                            ) : null}

                            {/* Stock Status */}
                            {record.stock_quantity !== undefined && (
                                <div className="flex items-center gap-1.5">
                                    <div
                                        className={cn(
                                            'h-2 w-2 rounded-full',
                                            record.stock_quantity > 10 ? 'bg-emerald-500' : record.stock_quantity > 0 ? 'bg-amber-500' : 'bg-red-500',
                                        )}
                                    ></div>
                                    <span
                                        className={cn(
                                            'text-xs font-medium',
                                            record.stock_quantity > 10 ? 'text-emerald-600' : record.stock_quantity > 0 ? 'text-amber-600' : 'text-red-600',
                                        )}
                                    >
                                        {record.stock_quantity > 0 ? `${record.stock_quantity} in stock` : 'Out of stock'}
                                    </span>
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* Actions Footer */}
                {record._actions && record._actions.length > 0 && grid.card?.actionsPosition === 'bottom' && (
                    <div className="border-t bg-muted/30 px-4 py-3 flex items-center justify-center gap-2 record-actions" data-no-card-click="">
                        {renderRecordActions(record)}
                    </div>
                )}
            </div>
        );
    };

    // ================================
    // DEFAULT CARD STYLE (Fallback)
    // Used when no specific style or when style is unrecognized
    // ================================
    const renderDefaultCard = (record: any) => {
        const badge = getBadge(record);
        const title = getTitle(record);

        return (
            <Card
                key={record.id}
                className={cn('group relative overflow-hidden transition-all duration-200 flex flex-col', {
                    'hover:border-primary/30': grid.card?.hoverable !== false,
                    'ring-2 ring-primary ring-offset-2 ring-offset-background': isSelected(record.id),
                    'cursor-pointer': record._url,
                })}
                onClick={(event) => handleCardClick(event, record)}
            >
                {/* Card Header with Checkbox */}
                {(bulkActionsAvailable || title) && (
                    <CardHeader className="flex-row items-start gap-3 space-y-0 pb-3">
                        {bulkActionsAvailable && (
                            <Checkbox
                                checked={isSelected(record.id)}
                                onCheckedChange={() => handleSelectRecord(record.id)}
                                aria-label={`Select record #${toDisplayString(record.id)}`}
                                className="mt-1"
                            />
                        )}
                        <div className="flex-1 min-w-0">
                            {title ? (
                                <h3 className="text-sm font-semibold text-foreground line-clamp-2">{toDisplayString(title)}</h3>
                            ) : (
                                <span className="text-sm text-muted-foreground">Record #{toDisplayString(record.id)}</span>
                            )}
                        </div>
                        {badge && (
                            <Badge variant={getBadgeVariant(badge) as any} className="shrink-0">
                                {formatBadgeText(badge)}
                            </Badge>
                        )}
                    </CardHeader>
                )}

                <CardContent className={cn(cardGap, 'flex-1')}>
                    {gridColumns.map((column: any) => {
                        const ColumnComponent = getColumnComponent(column.component);

                        return (
                            // Spread the column config (limit, wrap, badge, imageWidth, editable, name, ...) first,
                            // then override the record-specific values
                            <ColumnComponent
                                key={column.name}
                                {...column}
                                column={column}
                                record={record}
                                recordId={record.id}
                                value={record[column.name]}
                                color={record._colors?.[column.name]}
                                icon={record._icons?.[column.name]}
                                size={record._sizes?.[column.name]}
                                description={record._descriptions?.[column.name]}
                                formattedState={record._formatted?.[column.name]}
                                defaultImageUrl={record._defaultImageUrls?.[column.name] ?? column.defaultImageUrl}
                                resourceSlug={resourceSlug}
                            />
                        );
                    })}
                </CardContent>

                {/* Actions Footer */}
                {record._actions && record._actions.length > 0 && (
                    <CardFooter className="flex items-center justify-center gap-2 border-t pt-4 record-actions" data-no-card-click="">
                        {renderRecordActions(record)}
                    </CardFooter>
                )}
            </Card>
        );
    };

    const renderCard =
        cardStyle === 'simple'
            ? renderSimpleCard
            : cardStyle === 'media'
              ? renderMediaCard
              : cardStyle === 'product'
                ? renderProductCard
                : renderDefaultCard;

    return (
        <div className="p-6">
            {/* Select All (when bulk actions available) */}
            {bulkActionsAvailable && records.length > 0 && (
                <div className="mb-4 flex items-center gap-2">
                    <label className="inline-flex items-center gap-2">
                        <input
                            type="checkbox"
                            checked={selectAll}
                            onChange={handleSelectAll}
                            className="peer size-4 shrink-0 appearance-none rounded-[4px] border border-input bg-background shadow-xs ring-offset-background transition-colors hover:border-input focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 checked:border-primary checked:bg-primary checked:text-primary-foreground cursor-pointer"
                        />
                        <svg
                            className="pointer-events-none absolute size-4 hidden peer-checked:block text-primary-foreground"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="3"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        >
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span className="text-sm font-medium text-foreground">
                            Select {selectedRecords.size} of {records.length} items
                        </span>
                    </label>
                </div>
            )}

            {isLoading && !loadingMore ? (
                // Loading State (show skeleton when loading initial data)
                <div className={cn('grid gap-4', gridColsClass)}>
                    {cardStyle === 'product'
                        ? // Product Card Skeleton
                          SKELETON_INDEXES.map((i) => (
                              <div key={`skeleton-product-${i}`} className="overflow-hidden bg-card border rounded-xl shadow-sm flex flex-col">
                                  <ProductSkeleton />
                              </div>
                          ))
                        : // Simple Card Skeleton (default)
                          SKELETON_INDEXES.map((i) => (
                              <div key={`skeleton-simple-${i}`} className="overflow-hidden bg-card border rounded-lg flex flex-col">
                                  <SimpleSkeleton />
                              </div>
                          ))}
                </div>
            ) : records.length > 0 ? (
                // Grid Content with records (+ loading more skeletons at bottom)
                <div className={cn('grid gap-4', gridColsClass)}>
                    {records.map((record) => renderCard(record))}

                    {/* Loading more skeleton cards (shown at bottom while infinite scrolling) */}
                    {loadingMore && cardStyle === 'product'
                        ? LOADING_MORE_INDEXES.map((i) => (
                              <div key={`loading-more-product-${i}`} className="overflow-hidden bg-card border rounded-xl shadow-sm flex flex-col">
                                  <ProductSkeleton />
                              </div>
                          ))
                        : loadingMore
                          ? LOADING_MORE_INDEXES.map((i) => (
                                <div key={`loading-more-simple-${i}`} className="overflow-hidden bg-card border rounded-lg flex flex-col">
                                    <SimpleSkeleton />
                                </div>
                            ))
                          : null}
                </div>
            ) : (
                // Empty State (only when not loading and no records)
                <div className="flex flex-col items-center justify-center py-16 text-center">
                    <div className="rounded-full bg-muted p-4 mb-4">
                        <Inbox className="h-10 w-10 text-muted-foreground" />
                    </div>
                    <h3 className="text-lg font-semibold text-foreground mb-2">{grid.emptyState?.heading || 'No records found'}</h3>
                    {grid.emptyState?.description ? (
                        <p className="text-sm text-muted-foreground max-w-sm">{grid.emptyState.description}</p>
                    ) : (
                        <p className="text-sm text-muted-foreground max-w-sm">There are no records to display. Try adjusting your filters or search query.</p>
                    )}
                </div>
            )}
        </div>
    );
}
