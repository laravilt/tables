import { cn } from '@/lib/utils';
import { router } from '@inertiajs/react';
import ActionButton from '@laravilt/actions/components/ActionButton';
import LaraviltComponentRenderer from '@laravilt/forms/components/LaraviltComponentRenderer';
import { useLocalization } from '@laravilt/support/composables';
import { useLatest } from '@laravilt/support/composables/hooks';
import { useCallback, useEffect, useMemo, useRef, useState, type ChangeEvent, type MouseEvent } from 'react';
import { useStateRef } from '../composables/useStateRef';
import { useWatch } from '../composables/useWatch';
import CardGrid from './CardGrid';
import DataTable from './DataTable';
import TableToolbar from './TableToolbar';

export interface FilterIndicator {
    label: string;
    removeField: string;
}

export interface RelationContext {
    baseUrl: string;
    relationship: string;
    canEdit: boolean;
    canDelete: boolean;
    columnExecutionRoute?: string;
}

export interface TableProps {
    table: any;
    records?: any[];
    pagination?: any;
    recordActions?: any[];
    bulkActions?: any[];
    filterIndicators?: FilterIndicator[];
    resourceSlug: string;
    queryRoute: string;
    loading?: boolean;
    currentView?: 'table' | 'grid';
    useAjax?: boolean; // Use fetch API instead of Inertia router (for relation managers)
    onDataLoaded?: (data: { records: any[]; pagination: any }) => void; // Callback when data is loaded via AJAX (Vue: prop + `data-loaded` event)
    relationContext?: RelationContext; // Context for relation manager to build record-specific URLs
    onActionComplete?: (data?: any) => void;
}

const DEFAULT_PAGINATION = {
    total: 0,
    per_page: 12,
    current_page: 1,
    last_page: 1,
    from: 0,
    to: 0,
};

const EMPTY: any[] = [];
const EMPTY_INDICATORS: FilterIndicator[] = [];

// Preserve existing URL params that we don't manage (like 'view')
const PRESERVE_PARAMS = ['view'];

export default function Table({
    table,
    records = EMPTY,
    pagination = DEFAULT_PAGINATION,
    recordActions = EMPTY,
    bulkActions = EMPTY,
    filterIndicators = EMPTY_INDICATORS,
    resourceSlug,
    queryRoute,
    loading = false,
    currentView = 'table',
    useAjax = false,
    onDataLoaded,
    relationContext,
    onActionComplete,
}: TableProps) {
    const { trans } = useLocalization();

    // Latest props for async callbacks (timeouts, observer, window listener, fetch)
    const live = useLatest({ table, pagination, queryRoute, useAjax, onDataLoaded, onActionComplete });

    // Extract bulk actions from toolbarActions (handles BulkActionGroup)
    const extractedBulkActions = useMemo(() => {
        let actions: any[] = [];

        // First, check if bulk actions are directly provided
        if (bulkActions && bulkActions.length > 0) {
            actions = bulkActions;
        }
        // Then check table's bulkActions
        else if (table.bulkActions && table.bulkActions.length > 0) {
            actions = table.bulkActions;
        }
        // Finally, extract from toolbarActions (look for BulkActionGroup)
        else if (table.toolbarActions && table.toolbarActions.length > 0) {
            for (const action of table.toolbarActions) {
                // Check if this is a BulkActionGroup
                if (action.type === 'bulk-action-group' && action.actions) {
                    actions.push(...action.actions);
                }
            }
        }

        // Filter out hidden actions (based on isHidden property from backend)
        // and set preserveState: false for all bulk actions so table refreshes after action
        return actions
            .filter((action) => !action.isHidden)
            .map((action) => ({
                ...action,
                preserveState: action.preserveState ?? false,
                isBulkAction: true,
                deselectRecordsAfterCompletion: action.deselectRecordsAfterCompletion ?? true,
            }));
    }, [bulkActions, table.bulkActions, table.toolbarActions]);

    // Check if table is configured for grid-only mode
    const isGridOnly = table.gridOnly === true && table.card !== null && table.card !== undefined;

    // Determine if we should show grid view (if gridOnly is enabled, always show grid)
    const isGridView = isGridOnly ? true : currentView === 'grid' && table.card !== null && table.card !== undefined;

    // Check if table has card configuration (for view toggle visibility) — not used by the template (same as Vue)
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    const hasGridOption = isGridOnly ? false : table.card !== null && table.card !== undefined;

    const [sortColumn, setSortColumn, sortColumnRef] = useStateRef<string | null>(table.defaultSortColumn || null);
    const [sortDirection, setSortDirection, sortDirectionRef] = useStateRef<'asc' | 'desc'>(table.defaultSortDirection || 'asc');
    const [activeGroup, setActiveGroup, activeGroupRef] = useStateRef<string | null>(table.activeGroup || table.defaultGroup || null);
    const [selectedRecords, setSelectedRecords] = useState<(number | string)[]>([]);
    const [searchQuery, setSearchQuery, searchQueryRef] = useStateRef<string>('');

    // Infinite scroll is disabled when grouping is active
    const isInfiniteScrollActive = !!(table.infiniteScroll && !activeGroup);
    const isInfiniteScrollActiveNow = (): boolean => !!(live.current.table.infiniteScroll && !activeGroupRef.current);

    const [activeFilters, setActiveFilters, activeFiltersRef] = useStateRef<Record<string, any>>({});
    const [clearSelectionsKey, setClearSelectionsKey] = useState<number>(0);
    const [isLoadingData, setIsLoadingData, isLoadingDataRef] = useStateRef<boolean>(false);
    const [perPage, setPerPage, perPageRef] = useStateRef<number>(pagination.per_page || 12);
    const currentPageRef = useRef<number>(pagination.current_page || 1);
    const [isLoadingMore, setIsLoadingMore, isLoadingMoreRef] = useStateRef<boolean>(false);
    const isInitializedRef = useRef<boolean>(false);

    // Track if we're doing a filter/search reload (should replace, not append)
    const isFilterReloadRef = useRef<boolean>(false);

    // Columns may replace an attribute with its display value (formatStateUsing etc.);
    // the server keeps the real value in `_original`, which is what forms must be filled with
    const getRecordFormData = (record: any) =>
        record?._original ? { ...record, ...record._original } : record;

    // Enhance records with actions for relation manager context
    const enhancedRecords = useMemo(() => {
        // If we have relation context, add _actions to each record with proper URLs
        if (relationContext) {
            return records.map((record) => {
                const actions: any[] = [];

                // Add view action (no URL needed, just displays modal with data)
                const viewAction = recordActions.find((a: any) => a.name === 'view');
                if (viewAction) {
                    actions.push({
                        ...viewAction,
                        externalFormData: getRecordFormData(record), // Pass record data to populate form
                        // Actions serialize an empty `modalFormData` when they have no fillForm();
                        // it would win over externalFormData and open an empty form
                        modalFormData: getRecordFormData(record),
                        // No URL or method - view is display only
                    });
                }

                // Add edit action
                if (relationContext.canEdit) {
                    const editAction = recordActions.find((a: any) => a.name === 'edit');
                    if (editAction) {
                        actions.push({
                            ...editAction,
                            url: `${relationContext.baseUrl}/${record.id}`,
                            externalFormData: getRecordFormData(record), // Pass record data to populate form
                            // Actions serialize an empty `modalFormData` when they have no fillForm();
                            // it would win over externalFormData and open an empty form
                            modalFormData: getRecordFormData(record),
                        });
                    }
                }

                // Add delete action
                if (relationContext.canDelete) {
                    const deleteAction = recordActions.find((a: any) => a.name === 'delete');
                    if (deleteAction) {
                        actions.push({
                            ...deleteAction,
                            url: `${relationContext.baseUrl}/${record.id}`,
                        });
                    }
                }

                // Add any other actions that aren't view/edit/delete
                recordActions.forEach((action: any) => {
                    if (action.name !== 'view' && action.name !== 'edit' && action.name !== 'delete') {
                        actions.push({
                            ...action,
                            url: action.url || `${relationContext.baseUrl}/${record.id}`,
                        });
                    }
                });

                return {
                    ...record,
                    _actions: actions,
                };
            });
        }

        // For non-relation context, just use the records as-is with existing _actions
        return records;
    }, [relationContext, records, recordActions]);

    // Column visibility persistence
    // Relation manager tables share the owner resource's slug, so scope their key by
    // relationship to keep them from overwriting the owner's (and each other's) preferences.
    const getColumnStorageKey = () => {
        const key = `laravilt_columns_${resourceSlug || 'default'}`;

        return relationContext?.relationship ? `${key}_${relationContext.relationship}` : key;
    };

    const getSavedColumns = (): string[] | null => {
        if (typeof window === 'undefined') return null;
        try {
            const saved = localStorage.getItem(getColumnStorageKey());
            if (saved) {
                return JSON.parse(saved);
            }
        } catch (e) {
            console.error('Failed to parse saved columns:', e);
        }
        return null;
    };

    const saveColumnPreferences = (columns: string[]) => {
        if (typeof window === 'undefined') return;
        try {
            localStorage.setItem(getColumnStorageKey(), JSON.stringify(columns));
        } catch (e) {
            console.error('Failed to save column preferences:', e);
        }
    };

    // Initialize visible columns - load from localStorage or use defaults
    const [visibleColumns, setVisibleColumnsState] = useState<string[]>(() => {
        // Ignore saved names this table doesn't have (renamed/removed columns, stale keys)
        const saved = getSavedColumns()?.filter((name: string) =>
            table.columns?.some((col: any) => col.name === name),
        );
        if (saved && saved.length > 0) {
            return saved;
        }
        // Default: show all non-hidden columns
        return table.columns?.filter((col: any) => !col.isToggledHiddenByDefault).map((col: any) => col.name) || [];
    });

    // Vue watches visibleColumns and saves to localStorage on every change
    const setVisibleColumns = (columns: string[]) => {
        setVisibleColumnsState(columns);
        saveColumnPreferences(columns);
    };

    // Pagination page size options
    const paginationOptions: number[] =
        table.paginationPageOptions && table.paginationPageOptions.length > 0
            ? table.paginationPageOptions
            : // Default options (12-based for grid layout compatibility)
              [12, 24, 48, 96];

    // Records: append or replace based on infinite scroll.
    // Seed with the initial records; the watcher below handles appends. (Vue's immediate watcher appended the
    // initial page to itself on deep links to page > 1, duplicating every record — not ported.)
    const [allRecords, setAllRecords, allRecordsRef] = useStateRef<any[]>(records);

    useWatch(records, (newRecords) => {
        if (isInfiniteScrollActiveNow()) {
            // If it's a filter reload OR page 1, replace all records
            // Only append if loading more pages (page > 1 and NOT a filter reload)
            if (isFilterReloadRef.current || live.current.pagination.current_page === 1) {
                setAllRecords(newRecords);
                isFilterReloadRef.current = false; // Reset the flag
            } else if (live.current.pagination.current_page > 1) {
                // Only append if we're actually loading more (not a filter change)
                setAllRecords([...allRecordsRef.current, ...newRecords]);
            } else {
                setAllRecords(newRecords);
            }
        } else {
            setAllRecords(newRecords);
        }
    });

    // Watch for search, filter, sort changes to reset records (only after initialization)
    useWatch(JSON.stringify([searchQuery, activeFilters, sortColumn, sortDirection]), () => {
        if (!isInitializedRef.current) return; // Don't trigger on initial mount

        if (isInfiniteScrollActiveNow()) {
            // Don't clear records here - let skeleton show by setting isLoadingData
            // Records will be replaced when new data arrives via the other watcher
            currentPageRef.current = 1;
        }
    });

    // Build query params, only including non-empty values
    const collectParams = (page: number, includeGroup: boolean, urlParams: URLSearchParams): Record<string, any> => {
        const params: Record<string, any> = {
            page,
            per_page: perPageRef.current,
        };

        PRESERVE_PARAMS.forEach((param) => {
            const value = urlParams.get(param);
            if (value !== null) {
                params[param] = value;
            }
        });

        // Only add search if it has a value
        if (searchQueryRef.current) {
            params.search = searchQueryRef.current;
        }

        // Only add sort if it has a value
        if (sortColumnRef.current) {
            params.sort = sortColumnRef.current;
            params.direction = sortDirectionRef.current;
        }

        // Only add filters that have values
        Object.entries(activeFiltersRef.current).forEach(([key, value]) => {
            if (value !== null && value !== undefined && value !== '' && value !== false) {
                params[key] = value;
            }
        });

        // Add group if active
        if (includeGroup && activeGroupRef.current) {
            params.group = activeGroupRef.current;
        }

        return params;
    };

    const reloadData = async (page?: number, resetPage = false) => {
        // Set loading state immediately and keep it true
        setIsLoadingData(true);

        const params = collectParams(
            resetPage ? 1 : page || live.current.pagination.current_page,
            true,
            new URLSearchParams(window.location.search),
        );

        // Use AJAX (fetch) if useAjax is true - this avoids Inertia page reload
        if (live.current.useAjax) {
            try {
                const queryString = new URLSearchParams(params).toString();
                const fetchUrl = `${live.current.queryRoute}?${queryString}`;

                const response = await fetch(fetchUrl, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (response.ok) {
                    const data = await response.json();
                    // Emit data-loaded event for parent to update its state
                    live.current.onDataLoaded?.({
                        records: data.data || [],
                        pagination: data.pagination || live.current.pagination,
                    });
                }
            } catch (error) {
                console.error('Failed to fetch data:', error);
            } finally {
                setIsLoadingData(false);
            }
            return;
        }

        // Update URL manually (only for Inertia mode)
        const url = new URL(window.location.href);
        url.search = new URLSearchParams(params).toString();
        window.history.replaceState({}, '', url.toString());

        router.get(live.current.queryRoute, params, {
            preserveState: true,
            preserveScroll: true,
            onBefore: () => {
                setIsLoadingData(true);
            },
            onSuccess: () => {
                setTimeout(() => {
                    setIsLoadingData(false);
                }, 100);
            },
            onError: () => {
                setIsLoadingData(false);
            },
        });
    };

    // Handle action completion from record actions - reload data and emit to parent
    const handleActionComplete = (data?: any) => {
        // If using AJAX mode, reload data after action
        if (live.current.useAjax) {
            reloadData();
        }

        live.current.onActionComplete?.(data);
    };

    const handleSort = (column: string, direction: 'asc' | 'desc') => {
        setSortColumn(column);
        setSortDirection(direction);

        // For infinite scroll, mark as filter reload to replace records instead of appending
        if (isInfiniteScrollActiveNow()) {
            isFilterReloadRef.current = true;
            currentPageRef.current = 1;
        }

        reloadData(1, true); // Reset to page 1 when sorting
    };

    const handleSearch = (query: string) => {
        setSearchQuery(query);
        isFilterReloadRef.current = true; // Mark this as a search/filter reload
        reloadData(1, true); // Reset to page 1 when searching
    };

    const handleFilterChange = (filters: Record<string, any>) => {
        setActiveFilters(filters);
        isFilterReloadRef.current = true;
        reloadData(1, true);
    };

    const handleFilterUpdate = (filterName: string, value: any) => {
        // Remove filter if value is empty or false (for toggles)
        if (value === null || value === undefined || value === '' || value === false) {
            // eslint-disable-next-line @typescript-eslint/no-unused-vars
            const { [filterName]: _, ...rest } = activeFiltersRef.current;
            setActiveFilters(rest);
        } else {
            setActiveFilters({
                ...activeFiltersRef.current,
                [filterName]: value,
            });
        }
        isFilterReloadRef.current = true; // Mark this as a filter reload
        reloadData(1, true); // Reset to page 1 when filtering
    };

    const clearAllFilters = () => {
        setActiveFilters({});
        setSearchQuery('');
        isFilterReloadRef.current = true; // Mark this as a filter reload
        reloadData(1, true);
    };

    const removeFilter = (filterName: string) => {
        handleFilterUpdate(filterName, null);
    };

    const handleUpdateSelectedRecords = (ids: (number | string)[]) => {
        setSelectedRecords(ids);
    };

    // Track pending load more request
    const loadMorePendingRef = useRef<boolean>(false);
    const loadMoreTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    // Infinite scroll load more with debouncing
    const loadMoreRecords = () => {
        const currentPagination = live.current.pagination;

        // Guard against multiple rapid calls
        if (loadMorePendingRef.current || isLoadingMoreRef.current || isLoadingDataRef.current || !currentPagination) return;
        if (currentPagination.current_page >= currentPagination.last_page) return;

        // Debounce rapid scroll events
        if (loadMoreTimeoutRef.current) {
            clearTimeout(loadMoreTimeoutRef.current);
        }

        loadMorePendingRef.current = true;
        loadMoreTimeoutRef.current = setTimeout(async () => {
            loadMorePendingRef.current = false;

            const latestPagination = live.current.pagination;

            // Re-check conditions after debounce
            if (isLoadingMoreRef.current || isLoadingDataRef.current || !latestPagination) return;
            if (latestPagination.current_page >= latestPagination.last_page) return;

            setIsLoadingMore(true);
            const nextPage = latestPagination.current_page + 1;

            const params = collectParams(nextPage, false, new URLSearchParams(window.location.search));

            // Use AJAX (fetch) if useAjax is true - this avoids Inertia page reload
            if (live.current.useAjax) {
                try {
                    const queryString = new URLSearchParams(params).toString();
                    const fetchUrl = `${live.current.queryRoute}?${queryString}`;

                    const response = await fetch(fetchUrl, {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (response.ok) {
                        const data = await response.json();
                        // Emit data-loaded event for parent to update its state
                        live.current.onDataLoaded?.({
                            records: data.data || [],
                            pagination: data.pagination || live.current.pagination,
                        });
                    }
                } catch (error) {
                    console.error('Failed to fetch data:', error);
                } finally {
                    setIsLoadingMore(false);
                }
                return;
            }

            // Update URL (only for Inertia mode)
            const url = new URL(window.location.href);
            url.search = new URLSearchParams(params).toString();
            window.history.replaceState({}, '', url.toString());

            router.get(live.current.queryRoute, params, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    setIsLoadingMore(false);
                },
                onError: () => {
                    setIsLoadingMore(false);
                },
            });
        }, 150); // 150ms debounce
    };

    // Set up intersection observer for infinite scroll
    const tableEndRef = useRef<HTMLDivElement | null>(null);
    const scrollContainerRef = useRef<HTMLDivElement | null>(null);
    const observerRef = useRef<IntersectionObserver | null>(null);

    const setupInfiniteScroll = () => {
        if (!isInfiniteScrollActiveNow()) return;

        // Clean up existing observer
        if (observerRef.current) {
            observerRef.current.disconnect();
        }

        observerRef.current = new IntersectionObserver(
            (entries) => {
                const entry = entries[0];
                if (entry.isIntersecting && !isLoadingMoreRef.current && !isLoadingDataRef.current) {
                    const currentPagination = live.current.pagination;
                    // Check if there are more pages before loading
                    if (currentPagination && currentPagination.current_page < currentPagination.last_page) {
                        fns.current.loadMoreRecords();
                    }
                }
            },
            {
                threshold: 0.1,
                rootMargin: '50px',
            },
        );

        if (tableEndRef.current) {
            observerRef.current.observe(tableEndRef.current);
        }
    };

    // Handle group change
    const handleGroupChange = (group: string | null) => {
        setActiveGroup(group);

        // Reset infinite scroll state when toggling grouping
        if (table.infiniteScroll) {
            currentPageRef.current = 1;
            isFilterReloadRef.current = true;
            // Disconnect observer when grouping is active
            if (observerRef.current && group) {
                observerRef.current.disconnect();
            }
        }

        // Update URL with group parameter
        const urlParams = new URLSearchParams(window.location.search);
        if (group) {
            urlParams.set('group', group);
        } else {
            urlParams.delete('group');
        }

        // If using AJAX mode, reload data (reloadData() sends the new group from activeGroupRef).
        // Vue calls an undefined `updateUrl()` first, which throws before reloading; AJAX mode keeps state out of the URL.
        if (useAjax) {
            reloadData();
        } else {
            // For Inertia, do a full navigation
            const currentUrl = new URL(window.location.href);
            currentUrl.search = urlParams.toString();
            router.get(
                currentUrl.toString(),
                {},
                {
                    preserveState: true,
                    preserveScroll: true,
                },
            );
        }

        // Re-setup infinite scroll observer when grouping is disabled
        if (table.infiniteScroll && !group) {
            setTimeout(() => {
                fns.current.setupInfiniteScroll();
            }, 100);
        }
    };

    // Handle reorder events from DataTable
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    const handleReorder = async (items: { id: number | string; order: number }[]) => {
        // The DataTable component handles the actual reorder request
        // This handler is for any additional processing needed at Table level
        // For now, we just reload data after reorder to ensure consistency
        if (useAjax) {
            // Wait a moment for the reorder to complete, then reload
            setTimeout(() => {
                fns.current.reloadData();
            }, 500);
        }
    };

    const bulkActionData = useMemo(
        () => ({
            ids: selectedRecords,
            model: table.model,
        }),
        [selectedRecords, table.model],
    );

    const handlePageChange = (page: number, event?: MouseEvent) => {
        if (event) {
            event.preventDefault();
        }
        reloadData(page);
    };

    const handlePerPageChange = (event: ChangeEvent<HTMLSelectElement>) => {
        event.preventDefault();
        setPerPage(parseInt(event.target.value));
        reloadData(undefined, true); // Reset to page 1 when changing per page
    };

    const getPageNumbers = (): (number | string)[] => {
        const current = pagination.current_page;
        const last = pagination.last_page;
        const pages: (number | string)[] = [];

        if (last <= 7) {
            // Show all pages if 7 or fewer
            for (let i = 1; i <= last; i++) {
                pages.push(i);
            }
        } else {
            // Always show first page
            pages.push(1);

            if (current > 3) {
                pages.push('...');
            }

            // Show pages around current
            const start = Math.max(2, current - 1);
            const end = Math.min(last - 1, current + 1);

            for (let i = start; i <= end; i++) {
                pages.push(i);
            }

            if (current < last - 2) {
                pages.push('...');
            }

            // Always show last page
            pages.push(last);
        }

        return pages;
    };

    // Listen for bulk action completion to clear selected records and reload table
    const handleBulkActionCompleted = () => {
        setSelectedRecords([]);
        setClearSelectionsKey((key) => key + 1);

        // If using AJAX mode, reload data
        if (live.current.useAjax) {
            reloadData();
        }

        // Emit action-complete to notify parent
        live.current.onActionComplete?.();
    };

    // Latest function instances for long-lived callbacks
    const fns = useLatest({ setupInfiniteScroll, loadMoreRecords, reloadData, handleBulkActionCompleted, isInfiniteScrollActiveNow });

    // Vue: watch(tableEndRef) — re-setup the observer when the sentinel element changes
    const setTableEndRef = useCallback(
        (element: HTMLDivElement | null) => {
            const changed = element !== tableEndRef.current;
            tableEndRef.current = element;
            if (changed && element && fns.current.isInfiniteScrollActiveNow()) {
                fns.current.setupInfiniteScroll();
            }
        },
        [fns],
    );

    // Watch for view changes - reset scroll and re-setup observer
    useWatch(currentView, (newView, oldView) => {
        if (newView !== oldView) {
            // Reset scroll position when switching views
            if (scrollContainerRef.current) {
                scrollContainerRef.current.scrollTop = 0;
            }

            // Re-setup observer after view change (wait for DOM update)
            if (fns.current.isInfiniteScrollActiveNow()) {
                setTimeout(() => {
                    fns.current.setupInfiniteScroll();
                }, 100);
            }
        }
    });

    useEffect(() => {
        // Initialize filters from URL query parameters
        const urlParams = new URLSearchParams(window.location.search);
        const initialFilters: Record<string, any> = {};

        // Get all filter names from table configuration
        const filterNames: string[] = live.current.table.filters?.map((f: any) => f.name) || [];

        // Read filter values from URL
        filterNames.forEach((filterName: string) => {
            const value = urlParams.get(filterName);
            if (value !== null && value !== '') {
                initialFilters[filterName] = value;
            }
        });

        // Initialize activeFilters with URL values
        setActiveFilters(initialFilters);

        // Initialize search from URL
        const searchParam = urlParams.get('search');
        if (searchParam) {
            setSearchQuery(searchParam);
        }

        // Initialize sort from URL
        const sortParam = urlParams.get('sort');
        const directionParam = urlParams.get('direction');
        if (sortParam) {
            setSortColumn(sortParam);
            // Validate direction - only accept 'asc' or 'desc', default to 'asc'
            setSortDirection(directionParam === 'asc' || directionParam === 'desc' ? directionParam : 'asc');
        }

        // Initialize group from URL
        const groupParam = urlParams.get('group');
        if (groupParam) {
            setActiveGroup(groupParam);
        }

        // Mark as initialized to enable watchers
        isInitializedRef.current = true;

        // Update URL with current state if URL is empty or missing parameters
        const currentUrl = new URL(window.location.href);
        const hasPageParam = urlParams.has('page');
        const hasPerPageParam = urlParams.has('per_page');

        if (!hasPageParam || !hasPerPageParam) {
            // Build query params for current state
            const params = collectParams(live.current.pagination.current_page || 1, true, urlParams);

            // Update URL without reloading
            currentUrl.search = new URLSearchParams(params).toString();
            window.history.replaceState({}, '', currentUrl.toString());
        }

        const listener = () => fns.current.handleBulkActionCompleted();
        window.addEventListener('bulk-action-completed', listener);
        fns.current.setupInfiniteScroll();

        return () => {
            window.removeEventListener('bulk-action-completed', listener);
            if (observerRef.current) {
                observerRef.current.disconnect();
            }
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const displayedRecords = relationContext ? enhancedRecords : isInfiniteScrollActive ? allRecords : records;

    return (
        <div className="flex flex-col h-full min-h-0">
            {/* Table with integrated toolbar */}
            <div className="rounded-lg border border-border shadow-sm bg-card overflow-hidden flex flex-col flex-1 min-h-0">
                {/* Search, Filters, and Column Visibility (Fixed at top) */}
                <div className="flex-shrink-0">
                    {table && (
                        <TableToolbar
                            searchable={table.searchable}
                            searchPlaceholder={table.searchPlaceholder}
                            search={searchQuery}
                            filters={table.filters}
                            activeFilters={activeFilters}
                            filterIndicators={filterIndicators}
                            columns={table.columns}
                            bulkActionsAvailable={extractedBulkActions.length > 0}
                            selectedCount={selectedRecords.length}
                            showSort={isGridView}
                            sortColumn={sortColumn}
                            sortDirection={sortDirection}
                            groups={table.groups}
                            activeGroup={activeGroup}
                            visibleColumns={visibleColumns}
                            onUpdateVisibleColumns={setVisibleColumns}
                            onUpdateSearch={handleSearch}
                            onUpdateFilters={handleFilterChange}
                            onRemoveFilter={removeFilter}
                            onClearFilters={clearAllFilters}
                            onUpdateSort={handleSort}
                            onUpdateActiveGroup={handleGroupChange}
                            toolbarActions={(table.headerActions || []).map((action: any) => (
                                <ActionButton key={action.name} {...action} size="sm" isOutlined={true} onActionComplete={handleActionComplete} />
                            ))}
                            filtersSlot={(table.filters || []).map((filter: any) => (
                                <div key={filter.name} className="filter-field">
                                    {filter.formField ? (
                                        // Render custom form field if available
                                        <LaraviltComponentRenderer
                                            component={filter.formField.component}
                                            props={{
                                                ...filter.formField,
                                                modelValue: activeFilters[filter.name] || filter.default || null,
                                                name: filter.name,
                                                onUpdateModelValue: (value: any) => handleFilterUpdate(filter.name, value),
                                            }}
                                        />
                                    ) : (
                                        // Fallback to default filter component based on type
                                        <LaraviltComponentRenderer
                                            component={filter.component}
                                            props={{
                                                ...filter,
                                                modelValue: activeFilters[filter.name] || filter.default || null,
                                                name: filter.name,
                                                onUpdateModelValue: (value: any) => handleFilterUpdate(filter.name, value),
                                            }}
                                        />
                                    )}
                                </div>
                            ))}
                            bulkActions={extractedBulkActions.map((action: any) => (
                                <ActionButton key={action.name} {...action} data={bulkActionData} />
                            ))}
                        />
                    )}
                </div>

                {/* Scrollable Records Area */}
                <div ref={scrollContainerRef} className="flex-1 min-h-0 overflow-y-auto custom-scrollbar">
                    {!isGridView ? (
                        // Render DataTable for table view
                        <DataTable
                            columns={table.columns}
                            records={displayedRecords}
                            recordActions={recordActions}
                            loading={loading || isLoadingData}
                            sortColumn={sortColumn}
                            sortDirection={sortDirection}
                            visibleColumns={visibleColumns}
                            bulkActionsAvailable={extractedBulkActions.length > 0}
                            resourceSlug={resourceSlug}
                            columnExecutionRoute={relationContext?.columnExecutionRoute || table.columnExecutionRoute}
                            columnUpdateRoute={relationContext ? null : table.columnUpdateRoute}
                            modelClass={table.model}
                            clearSelections={clearSelectionsKey}
                            fixedActions={table.fixedActions}
                            striped={table.striped}
                            infiniteScroll={isInfiniteScrollActive}
                            useAjax={useAjax}
                            reorderable={table.reorderable}
                            reorderableColumn={table.reorderableColumn}
                            reorderRoute={table.reorderRoute}
                            groups={table.groups}
                            activeGroup={activeGroup}
                            onSort={handleSort}
                            onUpdateSelectedRecords={handleUpdateSelectedRecords}
                            onActionComplete={handleActionComplete}
                            onReorder={handleReorder}
                        />
                    ) : (
                        // Render CardGrid for grid view
                        <CardGrid
                            grid={{ ...table, card: table.card }}
                            records={displayedRecords}
                            recordActions={recordActions}
                            loading={loading || isLoadingData}
                            loadingMore={isLoadingMore}
                            bulkActionsAvailable={extractedBulkActions.length > 0}
                            resourceSlug={resourceSlug}
                            modelClass={table.model}
                            clearSelections={clearSelectionsKey}
                            onUpdateSelectedRecords={handleUpdateSelectedRecords}
                        />
                    )}

                    {/* Infinite Scroll Loading Indicator & Observer (inside scrollable area) */}
                    {isInfiniteScrollActive && (
                        <div className="border-t border-border bg-muted/50">
                            {isLoadingMore ? (
                                <div className="p-8">
                                    <div className="flex items-center justify-center gap-2">
                                        <div className="animate-spin h-5 w-5 border-2 border-primary border-t-transparent rounded-full"></div>
                                        <span className="text-sm text-muted-foreground">{trans('tables::tables.infinite_scroll.loading_more')}</span>
                                    </div>
                                </div>
                            ) : pagination && pagination.current_page < pagination.last_page ? (
                                <div className="p-4">
                                    <div ref={setTableEndRef} className="h-20 flex items-center justify-center">
                                        <span className="text-xs text-muted-foreground">{trans('tables::tables.infinite_scroll.scroll_for_more')}</span>
                                    </div>
                                </div>
                            ) : (
                                <div className="p-4 text-center">
                                    <span className="text-sm text-muted-foreground">{trans('tables::tables.infinite_scroll.no_more_records')}</span>
                                </div>
                            )}
                        </div>
                    )}
                </div>
                {/* End Scrollable Records Area */}

                {/* Pagination (Fixed at bottom) */}
                {!isInfiniteScrollActive && pagination && pagination.total > 0 && (
                    <div className="flex-shrink-0 p-4 border-t border-border bg-muted/50">
                        <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
                            {/* Pagination Info (left side) */}
                            <div className="hidden sm:block text-sm text-muted-foreground whitespace-nowrap order-1">
                                {trans('tables::tables.pagination.showing')} {pagination.from} {trans('tables::tables.pagination.to')} {pagination.to}{' '}
                                {trans('tables::tables.pagination.of')} {pagination.total}
                            </div>

                            {/* Pagination Controls (centered) */}
                            <div className="flex items-center gap-2 order-2">
                                {/* Previous Button */}
                                <button
                                    onClick={(event) => handlePageChange(pagination.current_page - 1, event)}
                                    disabled={pagination.current_page <= 1}
                                    className={cn(
                                        'px-3 py-1.5 text-sm font-medium rounded-md border border-border transition-colors disabled:opacity-50 disabled:cursor-not-allowed hover:bg-muted',
                                        {
                                            'cursor-not-allowed': pagination.current_page <= 1,
                                            'hover:bg-muted': pagination.current_page > 1,
                                        },
                                    )}
                                >
                                    {trans('tables::tables.pagination.previous')}
                                </button>

                                {/* Page Numbers */}
                                <div className="flex items-center gap-1">
                                    {getPageNumbers().map((page, index) =>
                                        page === '...' ? (
                                            <span key={`ellipsis-${index}`} className="px-2 text-sm text-muted-foreground">
                                                ...
                                            </span>
                                        ) : (
                                            <button
                                                key={page}
                                                onClick={(event) => handlePageChange(page as number, event)}
                                                className={cn('min-w-[32px] px-2 py-1.5 text-sm font-medium rounded-md border transition-colors', {
                                                    'bg-primary text-primary-foreground border-primary': page === pagination.current_page,
                                                    'border-border hover:bg-muted': page !== pagination.current_page,
                                                })}
                                            >
                                                {page}
                                            </button>
                                        ),
                                    )}
                                </div>

                                {/* Next Button */}
                                <button
                                    onClick={(event) => handlePageChange(pagination.current_page + 1, event)}
                                    disabled={pagination.current_page >= pagination.last_page}
                                    className={cn(
                                        'px-3 py-1.5 text-sm font-medium rounded-md border border-border transition-colors disabled:opacity-50 disabled:cursor-not-allowed',
                                        {
                                            'cursor-not-allowed': pagination.current_page >= pagination.last_page,
                                            'hover:bg-muted': pagination.current_page < pagination.last_page,
                                        },
                                    )}
                                >
                                    {trans('tables::tables.pagination.next')}
                                </button>
                            </div>

                            {/* Per Page Selector (right side) */}
                            <div className="flex items-center gap-2 order-3">
                                <label htmlFor="per-page" className="text-sm text-muted-foreground whitespace-nowrap">
                                    {trans('tables::tables.pagination.per_page')}
                                </label>
                                <select
                                    id="per-page"
                                    value={perPage}
                                    onChange={handlePerPageChange}
                                    className="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                >
                                    {paginationOptions.map((option) => (
                                        <option key={option} value={option}>
                                            {option}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
