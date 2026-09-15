import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';
import { router } from '@inertiajs/react';
import RecordActions from '@laravilt/actions/components/RecordActions';
import { useLatest } from '@laravilt/support/composables/hooks';
import { useLocalization } from '@laravilt/support/composables/useLocalization';
import { ArrowDown, ArrowUp, ArrowUpDown, GripVertical, Inbox } from 'lucide-react';
import { useMemo, useState, type ComponentType, type CSSProperties, type DragEvent, type MouseEvent, type ReactNode } from 'react';
import { useWatch } from '../composables/useWatch';
import ColorColumn from './columns/ColorColumn';
import IconColumn from './columns/IconColumn';
import ImageColumn from './columns/ImageColumn';
import TextColumn from './columns/TextColumn';
import CheckboxColumn from './columns/CheckboxColumn';
import SelectColumn from './columns/SelectColumn';
import TextInputColumn from './columns/TextInputColumn';
import ToggleColumn from './columns/ToggleColumn';
import './DataTable.css';

export interface Column {
    component: string;
    name: string;
    label: string;
    sortable?: boolean;
    toggleable?: boolean;
    [key: string]: any;
}

export interface TableRecord {
    id: number | string;
    _url?: string;
    [key: string]: any;
}

export interface Action {
    name: string;
    label?: string;
    icon?: string;
    color?: string;
    url?: string;
    requiresConfirmation?: boolean;
    [key: string]: any;
}

export interface GroupConfig {
    column: string;
    label: string;
    collapsible: boolean;
}

export interface RecordGroup {
    value: string | number | null;
    title: string;
    description?: string | null;
    records: TableRecord[];
}

export interface DataTableProps {
    columns?: Column[];
    records?: TableRecord[];
    loading?: boolean;
    skeletonRows?: number;
    sortColumn?: string | null;
    sortDirection?: 'asc' | 'desc';
    visibleColumns?: string[];
    bulkActionsAvailable?: boolean;
    resourceSlug?: string;
    columnExecutionRoute?: string;
    columnUpdateRoute?: string | null;
    modelClass?: string;
    recordActions?: Action[];
    executionRoute?: string;
    clearSelections?: number;
    fixedActions?: boolean;
    striped?: boolean;
    infiniteScroll?: boolean;
    useAjax?: boolean;
    reorderable?: boolean;
    reorderableColumn?: string;
    reorderRoute?: string;
    activeGroup?: string | null;
    groups?: GroupConfig[];
    onSort?: (column: string, direction: 'asc' | 'desc') => void;
    onUpdateSelectedRecords?: (records: (number | string)[]) => void;
    onActionComplete?: (data?: any) => void;
    onReorder?: (items: { id: number | string; order: number }[]) => void;
    /** Vue slot `empty` */
    empty?: ReactNode;
    /** Vue scoped slot `actions` */
    actions?: (scope: { record: TableRecord }) => ReactNode;
}

const EMPTY_COLUMNS: Column[] = [];
const EMPTY_RECORDS: TableRecord[] = [];
const EMPTY_STRINGS: string[] = [];
const EMPTY_ACTIONS: Action[] = [];
const EMPTY_GROUPS: GroupConfig[] = [];

// Scoped `.group` rule from DataTable.vue (smooth transitions for row hover states)
const ROW_TRANSITION_STYLE: CSSProperties = {
    transition: 'background-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out',
};

const CHECKBOX_CLASS =
    'peer size-4 shrink-0 appearance-none rounded border border-input bg-background shadow-sm ring-offset-background transition-all duration-150 hover:border-primary/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50 checked:border-primary checked:bg-primary checked:text-primary-foreground cursor-pointer';

function CheckIcon() {
    return (
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
    );
}

export default function DataTable({
    columns = EMPTY_COLUMNS,
    records = EMPTY_RECORDS,
    loading = false,
    skeletonRows = 10,
    sortColumn = null,
    sortDirection = 'asc',
    visibleColumns = EMPTY_STRINGS,
    bulkActionsAvailable = false,
    resourceSlug = '',
    columnExecutionRoute,
    columnUpdateRoute = null,
    modelClass,
    executionRoute,
    clearSelections = 0,
    fixedActions = false,
    striped = false,
    reorderable = false,
    reorderableColumn = 'sort_order',
    reorderRoute,
    activeGroup = null,
    groups = EMPTY_GROUPS,
    onSort,
    onUpdateSelectedRecords,
    onActionComplete,
    onReorder,
    empty,
    actions,
}: DataTableProps) {
    const { trans } = useLocalization();
    const recordsRef = useLatest(records);

    // Drag and drop state for reorderable
    const [draggedIndex, setDraggedIndex] = useState<number | null>(null);
    const [dragOverIndex, setDragOverIndex] = useState<number | null>(null);
    const [localRecords, setLocalRecords] = useState<TableRecord[]>(() => [...records]);
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    const [isReordering, setIsReordering] = useState(false);

    // Keep local records in sync with props (Vue: immediate deep watcher)
    const [syncedRecords, setSyncedRecords] = useState<TableRecord[]>(records);
    if (syncedRecords !== records) {
        setSyncedRecords(records);
        setLocalRecords([...records]);
    }

    // Drag and drop handlers
    const handleDragStart = (event: DragEvent<HTMLTableRowElement>, index: number) => {
        if (!reorderable) return;
        setDraggedIndex(index);
        if (event.dataTransfer) {
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', String(index));
        }
    };

    const handleDragOver = (event: DragEvent<HTMLTableRowElement>, index: number) => {
        if (!reorderable || draggedIndex === null) return;
        event.preventDefault();
        if (event.dataTransfer) {
            event.dataTransfer.dropEffect = 'move';
        }
        setDragOverIndex(index);
    };

    const handleDragLeave = () => {
        setDragOverIndex(null);
    };

    const saveReorder = async (items: { id: number | string; order: number }[]) => {
        if (!reorderRoute && !resourceSlug) return;

        setIsReordering(true);
        try {
            const url = reorderRoute || `/admin/${resourceSlug}/reorder`;
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    items,
                    column: reorderableColumn,
                }),
            });

            // fetch only rejects on network failure; treat 4xx/5xx as a failed save too
            if (!response.ok) {
                throw new Error(`Reorder request failed with status ${response.status}`);
            }
        } catch (error) {
            console.error('Failed to save reorder:', error);
            // Revert to original order on error
            setLocalRecords([...recordsRef.current]);
        } finally {
            setIsReordering(false);
        }
    };

    const handleDrop = async (event: DragEvent<HTMLTableRowElement>, targetIndex: number) => {
        if (!reorderable || draggedIndex === null) return;
        event.preventDefault();

        const sourceIndex = draggedIndex;
        if (sourceIndex === targetIndex) {
            setDraggedIndex(null);
            setDragOverIndex(null);
            return;
        }

        // Reorder local records
        const newRecords = [...localRecords];
        const [movedItem] = newRecords.splice(sourceIndex, 1);
        newRecords.splice(targetIndex, 0, movedItem);
        setLocalRecords(newRecords);

        // Reset drag state
        setDraggedIndex(null);
        setDragOverIndex(null);

        // Build new order data
        const reorderData = newRecords.map((record, index) => ({
            id: record.id,
            order: index + 1,
        }));

        // Emit reorder event
        onReorder?.(reorderData);

        // Send to server
        await saveReorder(reorderData);
    };

    const handleDragEnd = () => {
        setDraggedIndex(null);
        setDragOverIndex(null);
    };

    // Use local records for rendering when reorderable
    const displayRecords = reorderable ? localRecords : records;

    // Track collapsed groups (not used by the template — same as Vue)
    const [collapsedGroups, setCollapsedGroups] = useState<Set<string | number | null>>(() => new Set());

    // Check if grouping is active
    const isGrouped = activeGroup !== null && activeGroup !== undefined;

    // Get active group config (not used by the template — same as Vue)
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    const activeGroupConfig = !activeGroup ? null : groups?.find((g) => g.column === activeGroup) || null;

    // Group records by active group column (not used by the template — same as Vue)
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    const groupedRecords = useMemo<RecordGroup[]>(() => {
        if (!isGrouped || !activeGroup) {
            return [];
        }

        const groupMap = new Map<string | number | null, RecordGroup>();

        for (const record of displayRecords) {
            const groupInfo = record._group;
            const groupValue = groupInfo?.value ?? null;
            const groupKey = String(groupValue);

            if (!groupMap.has(groupKey)) {
                groupMap.set(groupKey, {
                    value: groupValue,
                    title: groupInfo?.title || String(groupValue),
                    description: groupInfo?.description || null,
                    records: [],
                });
            }

            groupMap.get(groupKey)!.records.push(record);
        }

        return Array.from(groupMap.values());
    }, [isGrouped, activeGroup, displayRecords]);

    // Toggle group collapse state (not used by the template — same as Vue)
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    const toggleGroupCollapse = (groupValue: string | number | null) => {
        const groupKey = String(groupValue);
        const next = new Set(collapsedGroups);
        if (next.has(groupKey)) {
            next.delete(groupKey);
        } else {
            next.add(groupKey);
        }
        setCollapsedGroups(next);
    };

    // Check if a group is collapsed (not used by the template — same as Vue)
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    const isGroupCollapsed = (groupValue: string | number | null) => collapsedGroups.has(String(groupValue));

    const [selectedRecords, setSelectedRecords] = useState<Set<number | string>>(() => new Set());

    // Watch for clearSelections prop changes to clear selections
    useWatch(clearSelections, (value) => {
        if (value > 0) {
            setSelectedRecords(new Set());
            onUpdateSelectedRecords?.([]);
        }
    });

    const isColumnVisible = (column: Column): boolean => {
        if (visibleColumns.length === 0) return true;
        if (column.toggleable === false) return true;
        return visibleColumns.includes(column.name);
    };

    const visibleColumnsFiltered = columns.filter((col) => isColumnVisible(col));

    const allSelected = records.length === 0 ? false : records.every((record) => selectedRecords.has(record.id));

    const someSelected = records.length === 0 ? false : records.some((record) => selectedRecords.has(record.id)) && !allSelected;

    const toggleSelectAll = () => {
        const next = allSelected ? new Set<number | string>() : new Set(records.map((record) => record.id));
        setSelectedRecords(next);
        onUpdateSelectedRecords?.(Array.from(next));
    };

    const toggleSelectRecord = (recordId: number | string) => {
        const newSet = new Set(selectedRecords);
        if (newSet.has(recordId)) {
            newSet.delete(recordId);
        } else {
            newSet.add(recordId);
        }
        setSelectedRecords(newSet);
        onUpdateSelectedRecords?.(Array.from(newSet));
    };

    const isRecordSelected = (recordId: number | string) => selectedRecords.has(recordId);

    const handleSort = (column: Column) => {
        if (!column.sortable) return;

        let direction: 'asc' | 'desc' = 'asc';

        if (sortColumn === column.name) {
            direction = sortDirection === 'asc' ? 'desc' : 'asc';
        }

        onSort?.(column.name, direction);
    };

    const getSortIcon = (column: Column) => {
        if (!column.sortable) return null;

        if (sortColumn === column.name) {
            return sortDirection === 'asc' ? ArrowUp : ArrowDown;
        }

        return ArrowUpDown;
    };

    // Check if any record has actions
    const hasRecordActions = records.some((record) => record._actions && record._actions.length > 0);

    // Get max actions count across all records for consistent column width
    const maxActionsCount = !records.length
        ? 3 // Default for skeleton
        : Math.max(...records.map((record) => record._actions?.filter((a: Action) => !a.isHidden)?.length || 0), 1);

    // Calculate actions column width based on max actions (not used by the template — same as Vue)
    // Each button is 32px (h-8 w-8) + 4px gap, plus 32px padding (px-4 = 16px each side)
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    const actionsColumnWidth = `${32 * maxActionsCount + 4 * (maxActionsCount - 1) + 32}px`;

    // Get component for column type
    const getColumnComponent = (columnType: string): ComponentType<any> => {
        switch (columnType) {
            case 'TextColumn':
                return TextColumn;
            case 'IconColumn':
                return IconColumn;
            case 'ImageColumn':
                return ImageColumn;
            case 'ColorColumn':
                return ColorColumn;
            case 'ToggleColumn':
                return ToggleColumn;
            case 'SelectColumn':
                return SelectColumn;
            case 'TextInputColumn':
                return TextInputColumn;
            case 'CheckboxColumn':
                return CheckboxColumn;
            default:
                return TextColumn;
        }
    };

    // Handle row click to navigate to record URL
    const handleRowClick = (event: MouseEvent<HTMLTableRowElement>, record: TableRecord) => {
        // Don't navigate if there's no URL
        if (!record._url) return;

        // Don't navigate if clicking on interactive elements
        const target = event.target as HTMLElement;
        const interactiveElements = ['A', 'BUTTON', 'INPUT', 'SELECT', 'TEXTAREA', 'LABEL'];

        // Check if click is on or inside an interactive element
        let element: HTMLElement | null = target;
        while (element) {
            if (interactiveElements.includes(element.tagName)) return;
            if (element.hasAttribute('data-no-row-click')) return;
            if (element.classList.contains('record-actions')) return;
            element = element.parentElement;
        }

        // Navigate using Inertia
        router.visit(record._url);
    };

    // Get column width style based on column config
    const getColumnWidthStyle = (column: Column): CSSProperties => {
        const style: CSSProperties = {};

        // Only apply explicit width if developer specified it
        if (column.width) {
            // Support various formats: '200px', '20%', 200 (number)
            const width = typeof column.width === 'number' ? `${column.width}px` : column.width;
            style.width = width;
            style.minWidth = width;
            style.maxWidth = width;
            return style;
        }

        // If column should grow, let it expand to fill remaining space
        if (column.grow) {
            style.flex = '1';
            return style;
        }

        // Default: auto width - let content determine the width
        return style;
    };

    const skeletonIndexes = Array.from({ length: skeletonRows }, (_, index) => index + 1);
    const actionSkeletonIndexes = Array.from({ length: maxActionsCount }, (_, index) => index + 1);

    return (
        <div className="relative w-full border-x border-border bg-card overflow-x-auto custom-scrollbar">
            {!loading && !records.length ? (
                // Empty State
                <div className="w-full">
                    <div className="py-16 px-6">
                        {empty != null ? (
                            empty
                        ) : (
                            <div className="flex flex-col items-center justify-center text-center">
                                <div className="rounded-full bg-muted/80 p-4 mb-4 ring-1 ring-border/50">
                                    <Inbox className="h-8 w-8 text-muted-foreground/70" />
                                </div>
                                <h3 className="text-base font-semibold text-foreground mb-1.5">No records found</h3>
                                <p className="text-sm text-muted-foreground max-w-sm leading-relaxed">
                                    There are no records to display. Try adjusting your filters or search query.
                                </p>
                            </div>
                        )}
                    </div>
                </div>
            ) : (
                // Table Content (only show when there are records or loading)
                <table className="w-full border-collapse">
                    {/* Table Header */}
                    <thead className="bg-muted sticky top-0 z-10">
                        <tr className="border-b border-border">
                            {/* Drag Handle Header (if reorderable) */}
                            {reorderable && (
                                <th className="w-[40px] px-2 py-3 bg-muted">
                                    <span className="sr-only">Reorder</span>
                                </th>
                            )}

                            {/* Checkbox Column (if bulk actions available) */}
                            {bulkActionsAvailable && (
                                <th className="w-[52px] bg-muted">
                                    <label className="inline-flex items-center justify-center p-3 cursor-pointer touch-manipulation">
                                        <input
                                            type="checkbox"
                                            checked={allSelected}
                                            ref={(element) => {
                                                if (element) element.indeterminate = someSelected;
                                            }}
                                            onChange={toggleSelectAll}
                                            className={CHECKBOX_CLASS}
                                        />
                                        <CheckIcon />
                                    </label>
                                </th>
                            )}

                            {/* Data Column Headers */}
                            {visibleColumnsFiltered.map((column) => {
                                const SortIcon = getSortIcon(column);

                                return (
                                    <th
                                        key={`header-${column.name}`}
                                        className={cn(
                                            'px-3 py-3 text-start text-xs font-semibold tracking-wide bg-muted whitespace-nowrap',
                                            column.sortable
                                                ? 'cursor-pointer select-none hover:bg-accent hover:text-foreground transition-all duration-150'
                                                : 'text-muted-foreground',
                                        )}
                                        style={getColumnWidthStyle(column)}
                                        onClick={() => handleSort(column)}
                                    >
                                        <div className="flex items-center gap-1.5">
                                            <span className="text-muted-foreground">{column.label}</span>
                                            {column.sortable && SortIcon && (
                                                <SortIcon
                                                    className={cn(
                                                        'h-3.5 w-3.5 transition-colors',
                                                        sortColumn === column.name ? 'text-foreground' : 'text-muted-foreground/40',
                                                    )}
                                                />
                                            )}
                                        </div>
                                    </th>
                                );
                            })}

                            {/* Actions Header */}
                            {hasRecordActions && (
                                <th
                                    className={cn(
                                        'px-3 py-3 text-end text-xs font-semibold tracking-wide bg-muted whitespace-nowrap',
                                        fixedActions && 'sticky end-0 z-20 border-s border-border',
                                    )}
                                >
                                    <span className="text-muted-foreground">{trans('tables::tables.columns.actions')}</span>
                                </th>
                            )}
                        </tr>
                    </thead>

                    {/* Table Body */}
                    <tbody className="divide-y divide-border/50">
                        {loading
                            ? // Loading State
                              skeletonIndexes.map((i) => (
                                  <tr key={`skeleton-${i}`} className={cn(striped && i % 2 !== 0 ? 'bg-muted' : 'bg-card')}>
                                      {/* Drag Handle Skeleton (keeps cells aligned with the reorder header) */}
                                      {reorderable && <td className="w-[40px] px-2 py-3.5" />}

                                      {/* Checkbox Skeleton */}
                                      {bulkActionsAvailable && (
                                          <td className="px-3 py-3.5 w-[52px]">
                                              <Skeleton className="h-4 w-4 rounded" />
                                          </td>
                                      )}

                                      {/* Column Skeletons */}
                                      {visibleColumnsFiltered.map((column) => (
                                          <td key={`skeleton-col-${column.name}`} className="px-3 py-3.5" style={getColumnWidthStyle(column)}>
                                              <Skeleton className="h-4 w-full max-w-[180px] rounded" />
                                          </td>
                                      ))}

                                      {/* Actions Skeleton */}
                                      {hasRecordActions && (
                                          <td className="px-3 py-3.5">
                                              <div className="flex items-center justify-end gap-1.5">
                                                  {actionSkeletonIndexes.map((n) => (
                                                      <Skeleton key={n} className="h-7 w-7 rounded-md" />
                                                  ))}
                                              </div>
                                          </td>
                                      )}
                                  </tr>
                              ))
                            : displayRecords.length > 0
                              ? // Data Rows
                                displayRecords.map((record, index) => (
                                    <tr
                                        key={record.id}
                                        className={cn(
                                            'group transition-all duration-150',
                                            striped
                                                ? index % 2 === 0
                                                    ? 'bg-card hover:bg-accent'
                                                    : 'bg-muted hover:bg-accent'
                                                : 'bg-card hover:bg-accent',
                                            selectedRecords.has(record.id) && 'bg-primary/10 hover:bg-primary/15 ring-1 ring-inset ring-primary/30',
                                            draggedIndex === index && 'opacity-50',
                                            dragOverIndex === index && draggedIndex !== index && 'border-t-2 border-primary',
                                            record._url && 'cursor-pointer',
                                        )}
                                        style={ROW_TRANSITION_STYLE}
                                        draggable={reorderable}
                                        onDragStart={(event) => handleDragStart(event, index)}
                                        onDragOver={(event) => handleDragOver(event, index)}
                                        onDragLeave={handleDragLeave}
                                        onDrop={(event) => handleDrop(event, index)}
                                        onDragEnd={handleDragEnd}
                                        onClick={(event) => handleRowClick(event, record)}
                                    >
                                        {/* Drag Handle */}
                                        {reorderable && (
                                            <td className="w-[40px] px-2 py-3.5 cursor-grab active:cursor-grabbing">
                                                <GripVertical className="h-4 w-4 text-muted-foreground hover:text-foreground transition-colors" />
                                            </td>
                                        )}

                                        {/* Checkbox Column */}
                                        {bulkActionsAvailable && (
                                            <td className="w-[52px]" data-no-row-click="">
                                                <label className="inline-flex items-center justify-center p-3 cursor-pointer touch-manipulation">
                                                    <input
                                                        type="checkbox"
                                                        checked={isRecordSelected(record.id)}
                                                        onChange={() => toggleSelectRecord(record.id)}
                                                        className={CHECKBOX_CLASS}
                                                    />
                                                    <CheckIcon />
                                                </label>
                                            </td>
                                        )}

                                        {/* Data Columns */}
                                        {visibleColumnsFiltered.map((column, columnIndex) => {
                                            const ColumnComponent = getColumnComponent(column.component);

                                            return (
                                                <td
                                                    key={`cell-${column.name}`}
                                                    className={cn(
                                                        'px-3 py-3.5 text-sm',
                                                        columnIndex === 0 ? 'font-medium text-foreground' : 'text-foreground/80',
                                                    )}
                                                    style={getColumnWidthStyle(column)}
                                                >
                                                    {/* Same binding order as Vue: column config overrides the record-level values */}
                                                    <ColumnComponent
                                                        value={record[column.name]}
                                                        color={record._colors?.[column.name]}
                                                        icon={record._icons?.[column.name]}
                                                        size={record._sizes?.[column.name]}
                                                        description={record._descriptions?.[column.name]}
                                                        recordId={record.id}
                                                        resourceSlug={resourceSlug}
                                                        columnExecutionRoute={columnExecutionRoute}
                                                        columnUpdateRoute={columnUpdateRoute}
                                                        {...column}
                                                        defaultImageUrl={record._defaultImageUrls?.[column.name] ?? column.defaultImageUrl}
                                                    />
                                                </td>
                                            );
                                        })}

                                        {/* Actions Column */}
                                        {record._actions && record._actions.length > 0 ? (
                                            <td
                                                className={cn(
                                                    'px-3 py-3.5 record-actions',
                                                    fixedActions && 'sticky end-0 z-20 border-s border-border bg-inherit',
                                                )}
                                                data-no-row-click=""
                                            >
                                                <div className="flex items-center justify-end gap-1">
                                                    {actions ? (
                                                        actions({ record })
                                                    ) : (
                                                        <RecordActions
                                                            actions={record._actions}
                                                            record={record}
                                                            resourceName={resourceSlug}
                                                            modelClass={modelClass}
                                                            executionRoute={executionRoute}
                                                            variant="inline"
                                                            onActionComplete={(data?: any) => onActionComplete?.(data)}
                                                        />
                                                    )}
                                                </div>
                                            </td>
                                        ) : hasRecordActions ? (
                                            <td></td>
                                        ) : null}
                                    </tr>
                                ))
                              : null}
                    </tbody>
                </table>
            )}
        </div>
    );
}
