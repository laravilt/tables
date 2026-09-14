import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { useLocalization } from '@laravilt/support/composables/useLocalization';
import { ArrowDown, ArrowUp, ArrowUpDown, Check, Columns3, Eye, EyeOff, LayoutList, Search, SlidersHorizontal, X } from 'lucide-react';
import { useState, type KeyboardEvent, type ReactNode } from 'react';
import { useWatch } from '../composables/useWatch';

interface Column {
    name: string;
    label: string;
    toggleable?: boolean;
    [key: string]: any;
}

interface Filter {
    name: string;
    label: string;
    component: string;
    [key: string]: any;
}

interface FilterIndicator {
    label: string;
    removeField: string;
}

interface GroupConfig {
    column: string;
    label: string;
    collapsible: boolean;
}

export interface TableToolbarProps {
    searchable?: boolean;
    searchPlaceholder?: string;
    search?: string;
    filters?: Filter[];
    activeFilters?: Record<string, any>;
    filterIndicators?: FilterIndicator[];
    columns?: Column[];
    visibleColumns?: string[];
    bulkActionsAvailable?: boolean;
    selectedCount?: number;
    showSort?: boolean;
    sortColumn?: string | null;
    sortDirection?: 'asc' | 'desc';
    groups?: GroupConfig[];
    activeGroup?: string | null;
    onUpdateSearch?: (value: string) => void;
    onUpdateFilters?: (filters: Record<string, any>) => void;
    onUpdateVisibleColumns?: (columns: string[]) => void;
    onRemoveFilter?: (filterName: string) => void;
    onClearFilters?: () => void;
    onUpdateSort?: (column: string, direction: 'asc' | 'desc') => void;
    onUpdateActiveGroup?: (group: string | null) => void;
    /** Vue slot `bulk-actions` */
    bulkActions?: ReactNode;
    /** Vue slot `filters` (renamed: `filters` is already the filter-definitions prop) */
    filtersSlot?: ReactNode;
    /** Vue slot `toolbar-actions` */
    toolbarActions?: ReactNode;
    /** Vue slot `active-filters` (renamed: `activeFilters` is already the active-filter-values prop) */
    activeFiltersSlot?: ReactNode;
}

const EMPTY_FILTERS: Filter[] = [];
const EMPTY_ACTIVE_FILTERS: Record<string, any> = {};
const EMPTY_INDICATORS: FilterIndicator[] = [];
const EMPTY_COLUMNS: Column[] = [];
const EMPTY_VISIBLE: string[] = [];
const EMPTY_GROUPS: GroupConfig[] = [];

export default function TableToolbar({
    searchable = true,
    searchPlaceholder = 'Search...',
    search = '',
    filters = EMPTY_FILTERS,
    activeFilters = EMPTY_ACTIVE_FILTERS,
    columns = EMPTY_COLUMNS,
    visibleColumns = EMPTY_VISIBLE,
    bulkActionsAvailable = false,
    selectedCount = 0,
    showSort = false,
    sortColumn = null,
    sortDirection = 'asc',
    groups = EMPTY_GROUPS,
    activeGroup = null,
    onUpdateSearch,
    onUpdateVisibleColumns,
    onRemoveFilter,
    onClearFilters,
    onUpdateSort,
    onUpdateActiveGroup,
    bulkActions,
    filtersSlot,
    toolbarActions,
    activeFiltersSlot,
}: TableToolbarProps) {
    const { trans } = useLocalization();

    const [localSearch, setLocalSearch] = useState<string>(search);

    // Update local search when prop changes (e.g., from clear button)
    useWatch(search, (newValue) => {
        setLocalSearch(newValue);
    });

    const handleSearchSubmit = () => {
        onUpdateSearch?.(localSearch);
    };

    const toggleableColumns = columns.filter((col) => col.toggleable !== false);

    const activeFilterCount = Object.values(activeFilters).filter((value) => value !== null && value !== '' && value !== undefined).length;

    // Compute filter indicators from activeFilters instead of using the prop
    const computedFilterIndicators: Array<{ label: string; removeField: string }> = [];

    Object.entries(activeFilters).forEach(([filterName, value]) => {
        if (value === null || value === '' || value === undefined || value === false) {
            return;
        }

        // Find the filter definition
        const filter = filters.find((f: any) => f.name === filterName);
        if (!filter) return;

        // Get the indicator label from the filter
        let label = `${filter.label || filterName}: ${value}`;

        // If filter has indicateUsing callback, use it
        if (filter.indicateUsing) {
            label = filter.indicateUsing;
        }

        computedFilterIndicators.push({
            label,
            removeField: filterName,
        });
    });

    const isColumnVisible = (columnName: string) => {
        if (visibleColumns.length === 0) return true;
        return visibleColumns.includes(columnName);
    };

    const toggleColumn = (columnName: string) => {
        let newVisibleColumns: string[];

        // If starting from "show all" state (empty array), initialize with all columns
        if (visibleColumns.length === 0) {
            // User is hiding a column, so start with all columns except this one
            newVisibleColumns = columns.map((col) => col.name).filter((name) => name !== columnName);
        } else {
            // Toggle column in existing array
            newVisibleColumns = isColumnVisible(columnName)
                ? visibleColumns.filter((name) => name !== columnName)
                : [...visibleColumns, columnName];
        }

        onUpdateVisibleColumns?.(newVisibleColumns);
    };

    const clearSearch = () => {
        onUpdateSearch?.('');
    };

    const clearFilters = () => {
        onClearFilters?.();
    };

    const removeFilter = (filterName: string) => {
        onRemoveFilter?.(filterName);
    };

    const hasActiveFilters = activeFilterCount > 0;
    const hasActiveSearch = search.length > 0;
    const hasComputedFilterIndicators = computedFilterIndicators.length > 0;

    // Sorting functionality for grid view
    const sortableColumns = columns.filter((col) => col.sortable);

    const currentSortLabel = (() => {
        if (!sortColumn) return trans('tables::tables.toolbar.sort_by') + '...';
        const column = sortableColumns.find((col) => col.name === sortColumn);
        return column ? column.label : trans('tables::tables.toolbar.sort_by') + '...';
    })();

    const SortIcon = !sortColumn ? ArrowUpDown : sortDirection === 'asc' ? ArrowUp : ArrowDown;

    const handleSortChange = (columnName: string) => {
        let direction: 'asc' | 'desc' = 'asc';

        if (sortColumn === columnName) {
            // Toggle direction if same column
            direction = sortDirection === 'asc' ? 'desc' : 'asc';
        }

        onUpdateSort?.(columnName, direction);
    };

    // Grouping functionality
    const hasGroups = groups && groups.length > 0;

    const activeGroupLabel = (() => {
        if (!activeGroup) return trans('tables::tables.toolbar.group_by') || 'Group by';
        const group = groups?.find((g) => g.column === activeGroup);
        return group ? group.label : trans('tables::tables.toolbar.group_by') || 'Group by';
    })();

    const handleGroupChange = (groupColumn: string | null) => {
        onUpdateActiveGroup?.(groupColumn);
    };

    const DirectionIcon = sortDirection === 'asc' ? ArrowUp : ArrowDown;

    return (
        <div className="flex flex-col gap-3 bg-card">
            {/* Bulk Actions Bar (when items are selected) */}
            {bulkActionsAvailable && selectedCount > 0 && (
                <div className="flex items-center gap-3 bg-primary/10 dark:bg-primary/20 px-4 py-3 border-b border-primary/30">
                    <span className="text-sm font-medium text-foreground">
                        {selectedCount} {trans('tables::tables.bulk.selected').replace(':count ', '')}
                    </span>
                    <div className="flex items-center gap-2">{bulkActions}</div>
                </div>
            )}

            {/* Top Row: Search, Filters, Column Toggle */}
            <div className="flex flex-col gap-3 px-4 py-3 border-b border-border sm:flex-row sm:items-center sm:gap-2">
                {/* Search */}
                {searchable && (
                    <div className="relative w-full sm:max-w-sm sm:shrink-0">
                        <Search className="absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={localSearch}
                            onChange={(event) => setLocalSearch(event.target.value)}
                            type="search"
                            placeholder={searchPlaceholder || trans('tables::tables.search.placeholder')}
                            className="ps-9 pe-9"
                            onKeyUp={(event: KeyboardEvent<HTMLInputElement>) => {
                                if (event.key === 'Enter') handleSearchSubmit();
                            }}
                        />
                        {hasActiveSearch && (
                            <button
                                onClick={clearSearch}
                                title={trans('tables::tables.search.clear')}
                                className="absolute end-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                            >
                                <X className="h-4 w-4" />
                            </button>
                        )}
                    </div>
                )}

                <div className="flex items-center gap-2 flex-wrap sm:ms-auto sm:shrink-0 sm:flex-nowrap">
                    {/* Sort Button (for grid view) */}
                    {showSort && sortableColumns.length > 0 && (
                        <Popover>
                            <PopoverTrigger asChild>
                                <Button variant="outline" size="sm" className="gap-2 whitespace-nowrap" title={trans('tables::tables.toolbar.sort_by')}>
                                    <SortIcon className="h-4 w-4" />
                                    {currentSortLabel}
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent align="end" className="w-[280px]">
                                <div className="space-y-2">
                                    <h4 className="text-sm font-semibold mb-3">{trans('tables::tables.toolbar.sort_by')}</h4>
                                    <div className="space-y-1">
                                        {sortableColumns.map((column) => (
                                            <button
                                                key={column.name}
                                                onClick={() => handleSortChange(column.name)}
                                                className={cn(
                                                    'w-full flex items-center justify-between px-3 py-2 text-sm rounded-md transition-colors',
                                                    sortColumn === column.name ? 'bg-primary text-primary-foreground hover:bg-primary/90' : 'hover:bg-muted',
                                                )}
                                            >
                                                <span>{column.label}</span>
                                                {sortColumn === column.name && <DirectionIcon className="h-4 w-4" />}
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            </PopoverContent>
                        </Popover>
                    )}

                    {/* Filters Button */}
                    {filters.length > 0 && (
                        <Popover>
                            <PopoverTrigger asChild>
                                <Button variant="outline" size="sm" className="gap-2 whitespace-nowrap" title={trans('tables::tables.toolbar.filters')}>
                                    <SlidersHorizontal className="h-4 w-4" />
                                    {trans('tables::tables.toolbar.filters')}
                                    {activeFilterCount > 0 && (
                                        <Badge variant="secondary" className="ms-1 px-1.5">
                                            {activeFilterCount}
                                        </Badge>
                                    )}
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent align="end" className="w-[420px]">
                                <div className="space-y-4">
                                    <div className="flex items-center justify-between">
                                        <h4 className="text-sm font-semibold">{trans('tables::tables.toolbar.filters')}</h4>
                                        {hasActiveFilters && (
                                            <Button variant="ghost" size="sm" onClick={clearFilters} className="h-auto py-1 px-2 text-xs">
                                                {trans('tables::tables.toolbar.clear_all')}
                                            </Button>
                                        )}
                                    </div>
                                    <div className="space-y-3">{filtersSlot}</div>
                                </div>
                            </PopoverContent>
                        </Popover>
                    )}

                    {/* Group By Selector */}
                    {hasGroups && (
                        <Popover>
                            <PopoverTrigger asChild>
                                <Button
                                    variant={activeGroup ? 'default' : 'outline'}
                                    size="sm"
                                    className="gap-2 whitespace-nowrap"
                                    title={trans('tables::tables.toolbar.group_by') || 'Group by'}
                                >
                                    <LayoutList className="h-4 w-4" />
                                    {activeGroupLabel}
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent align="end" className="w-[220px]">
                                <div className="space-y-2">
                                    <h4 className="text-sm font-semibold mb-3">{trans('tables::tables.toolbar.group_by') || 'Group by'}</h4>
                                    <div className="space-y-1">
                                        {/* No Grouping Option */}
                                        <button
                                            onClick={() => handleGroupChange(null)}
                                            className={cn(
                                                'w-full flex items-center justify-between px-3 py-2 text-sm rounded-md transition-colors',
                                                !activeGroup ? 'bg-primary text-primary-foreground hover:bg-primary/90' : 'hover:bg-muted',
                                            )}
                                        >
                                            <span>{trans('tables::tables.toolbar.no_grouping') || 'No grouping'}</span>
                                            {!activeGroup && <Check className="h-4 w-4" />}
                                        </button>
                                        {/* Group Options */}
                                        {groups.map((group) => (
                                            <button
                                                key={group.column}
                                                onClick={() => handleGroupChange(group.column)}
                                                className={cn(
                                                    'w-full flex items-center justify-between px-3 py-2 text-sm rounded-md transition-colors',
                                                    activeGroup === group.column ? 'bg-primary text-primary-foreground hover:bg-primary/90' : 'hover:bg-muted',
                                                )}
                                            >
                                                <span>{group.label}</span>
                                                {activeGroup === group.column && <Check className="h-4 w-4" />}
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            </PopoverContent>
                        </Popover>
                    )}

                    {/* Column Toggle */}
                    {toggleableColumns.length > 0 && (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="outline" size="sm" className="gap-2 whitespace-nowrap" title={trans('tables::tables.toolbar.toggle_columns')}>
                                    <Columns3 className="h-4 w-4" />
                                    {trans('tables::tables.toolbar.columns')}
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-64">
                                <DropdownMenuLabel>{trans('tables::tables.toolbar.toggle_columns')}</DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                {toggleableColumns.map((column) => {
                                    const VisibilityIcon = isColumnVisible(column.name) ? Eye : EyeOff;

                                    return (
                                        <DropdownMenuCheckboxItem
                                            key={column.name}
                                            checked={isColumnVisible(column.name)}
                                            onSelect={(event: Event) => {
                                                event.preventDefault();
                                                toggleColumn(column.name);
                                            }}
                                            className="gap-2"
                                        >
                                            <VisibilityIcon
                                                className={cn(
                                                    'h-4 w-4 shrink-0',
                                                    isColumnVisible(column.name) ? 'text-primary' : 'text-muted-foreground',
                                                )}
                                            />
                                            <span className="flex-1">{column.label}</span>
                                        </DropdownMenuCheckboxItem>
                                    );
                                })}
                            </DropdownMenuContent>
                        </DropdownMenu>
                    )}

                    {/* Toolbar Actions Slot */}
                    {toolbarActions}
                </div>
            </div>

            {/* Active Filters Display */}
            {(hasActiveFilters || hasActiveSearch || hasComputedFilterIndicators) && (
                <div className="flex items-center gap-2 flex-wrap px-4 pb-3">
                    <span className="text-sm text-muted-foreground">{trans('tables::tables.toolbar.active_filters')}:</span>

                    {hasActiveSearch && (
                        <Badge variant="secondary" className="gap-1">
                            {trans('tables::tables.toolbar.search')}: "{search}"
                            <button onClick={clearSearch} className="hover:text-foreground">
                                <X className="h-3 w-3" />
                            </button>
                        </Badge>
                    )}

                    {/* Computed filter indicators with individual removal */}
                    {computedFilterIndicators.map((indicator, index) => (
                        <Badge key={index} variant="secondary" className="gap-1">
                            {indicator.label}
                            <button onClick={() => removeFilter(indicator.removeField)} className="hover:text-foreground">
                                <X className="h-3 w-3" />
                            </button>
                        </Badge>
                    ))}

                    {activeFiltersSlot}

                    {(hasActiveFilters || hasActiveSearch || hasComputedFilterIndicators) && (
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => {
                                clearFilters();
                                clearSearch();
                            }}
                            className="h-auto py-1 px-2 text-xs"
                        >
                            {trans('tables::tables.toolbar.clear_all')}
                        </Button>
                    )}
                </div>
            )}
        </div>
    );
}
