import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { useLocalization } from '@laravilt/support/composables';
import { ArrowDown, ArrowUp, ArrowUpDown, Search, SlidersHorizontal, X } from 'lucide-react';
import { useState, type KeyboardEvent, type ReactNode } from 'react';
import { useWatch } from '../composables/useWatch';

interface FilterIndicator {
    label: string;
    removeField: string;
}

export interface GridToolbarProps {
    searchable?: boolean;
    searchPlaceholder?: string;
    search?: string;
    filters?: any[];
    activeFilters?: Record<string, any>;
    filterIndicators?: FilterIndicator[];
    bulkActionsAvailable?: boolean;
    selectedCount?: number;
    columns?: any[];
    sortColumn?: string | null;
    sortDirection?: 'asc' | 'desc';
    onUpdateSearch?: (value: string) => void;
    onUpdateFilters?: (filters: Record<string, any>) => void;
    onRemoveFilter?: (filterName: string) => void;
    onClearFilters?: () => void;
    onUpdateSort?: (column: string, direction: 'asc' | 'desc') => void;
    /** Vue slot `bulk-actions` */
    bulkActions?: ReactNode;
    /** Vue slot `filters` (renamed: `filters` is already the filter-definitions prop) */
    filtersSlot?: ReactNode;
    /** Vue slot `toolbar-actions` */
    toolbarActions?: ReactNode;
    /** Vue slot `active-filters` (renamed: `activeFilters` is already the active-filter-values prop) */
    activeFiltersSlot?: ReactNode;
}

const EMPTY: any[] = [];
const EMPTY_ACTIVE_FILTERS: Record<string, any> = {};

export default function GridToolbar({
    searchable = true,
    searchPlaceholder = 'Search...',
    search = '',
    filters = EMPTY,
    activeFilters = EMPTY_ACTIVE_FILTERS,
    bulkActionsAvailable = false,
    selectedCount = 0,
    columns = EMPTY,
    sortColumn = null,
    sortDirection = 'asc',
    onUpdateSearch,
    onRemoveFilter,
    onClearFilters,
    onUpdateSort,
    bulkActions,
    filtersSlot,
    toolbarActions,
    activeFiltersSlot,
}: GridToolbarProps) {
    const { trans } = useLocalization();

    const [localSearch, setLocalSearch] = useState<string>(search);

    useWatch(search, (newValue) => {
        setLocalSearch(newValue);
    });

    const handleSearchSubmit = () => {
        onUpdateSearch?.(localSearch);
    };

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

    const sortableColumns = columns.filter((col: any) => col.sortable);

    const currentSortLabel = (() => {
        if (!sortColumn) return 'Sort by...';
        const column = sortableColumns.find((col: any) => col.name === sortColumn);
        return column ? column.label : 'Sort by...';
    })();

    const SortIcon = !sortColumn ? ArrowUpDown : sortDirection === 'asc' ? ArrowUp : ArrowDown;
    const DirectionIcon = sortDirection === 'asc' ? ArrowUp : ArrowDown;

    const handleSortChange = (columnName: string) => {
        let direction: 'asc' | 'desc' = 'asc';

        if (sortColumn === columnName) {
            // Toggle direction if same column
            direction = sortDirection === 'asc' ? 'desc' : 'asc';
        }

        onUpdateSort?.(columnName, direction);
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

    return (
        <div className="flex flex-col gap-3 bg-card">
            {/* Bulk Actions Bar */}
            {bulkActionsAvailable && selectedCount > 0 && (
                <div className="flex items-center gap-3 bg-primary/10 dark:bg-primary/20 px-4 py-3 border-b border-primary/30">
                    <span className="text-sm font-medium text-foreground">{selectedCount} selected</span>
                    <div className="flex items-center gap-2">{bulkActions}</div>
                </div>
            )}

            {/* Top Row: Search and Filters */}
            <div className="flex flex-col gap-3 px-4 py-3 border-b border-border sm:flex-row sm:items-center sm:gap-2">
                {/* Search */}
                {searchable && (
                    <div className="relative w-full sm:max-w-sm sm:shrink-0">
                        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={localSearch}
                            onChange={(event) => setLocalSearch(event.target.value)}
                            type="search"
                            placeholder={searchPlaceholder || trans('tables::tables.search.placeholder')}
                            className="pl-9 pr-9"
                            onKeyUp={(event: KeyboardEvent<HTMLInputElement>) => {
                                if (event.key === 'Enter') handleSearchSubmit();
                            }}
                        />
                        {hasActiveSearch && (
                            <button
                                onClick={clearSearch}
                                className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                            >
                                <X className="h-4 w-4" />
                            </button>
                        )}
                    </div>
                )}

                <div className="flex items-center gap-2 flex-wrap sm:ml-auto sm:shrink-0 sm:flex-nowrap">
                    {/* Sort Button */}
                    {sortableColumns.length > 0 && (
                        <Popover>
                            <PopoverTrigger asChild>
                                <Button variant="outline" size="sm" className="gap-2 whitespace-nowrap">
                                    <SortIcon className="h-4 w-4" />
                                    {currentSortLabel}
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent align="end" className="w-[280px]">
                                <div className="space-y-2">
                                    <h4 className="text-sm font-semibold mb-3">Sort by</h4>
                                    <div className="space-y-1">
                                        {sortableColumns.map((column: any) => (
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
                                <Button variant="outline" size="sm" className="gap-2 whitespace-nowrap">
                                    <SlidersHorizontal className="h-4 w-4" />
                                    Filters
                                    {activeFilterCount > 0 && (
                                        <Badge variant="secondary" className="ml-1 px-1.5">
                                            {activeFilterCount}
                                        </Badge>
                                    )}
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent align="end" className="w-[420px]">
                                <div className="space-y-4">
                                    <div className="flex items-center justify-between">
                                        <h4 className="text-sm font-semibold">Filters</h4>
                                        {hasActiveFilters && (
                                            <Button variant="ghost" size="sm" onClick={clearFilters} className="h-auto py-1 px-2 text-xs">
                                                Clear all
                                            </Button>
                                        )}
                                    </div>
                                    <div className="space-y-3">{filtersSlot}</div>
                                </div>
                            </PopoverContent>
                        </Popover>
                    )}

                    {/* Toolbar Actions Slot */}
                    {toolbarActions}
                </div>
            </div>

            {/* Active Filters Display */}
            {(hasActiveFilters || hasActiveSearch || hasComputedFilterIndicators) && (
                <div className="flex items-center gap-2 flex-wrap px-4 pb-3">
                    <span className="text-sm text-muted-foreground">Active filters:</span>

                    {hasActiveSearch && (
                        <Badge variant="secondary" className="gap-1">
                            Search: "{search}"
                            <button onClick={clearSearch} className="hover:text-foreground">
                                <X className="h-3 w-3" />
                            </button>
                        </Badge>
                    )}

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
                            Clear all
                        </Button>
                    )}
                </div>
            )}
        </div>
    );
}
