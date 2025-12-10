<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import DataTable from './DataTable.vue'
import CardGrid from './CardGrid.vue'
import TableToolbar from './TableToolbar.vue'
import GridToolbar from './GridToolbar.vue'
import LaraviltComponentRenderer from '@laravilt/forms/components/LaraviltComponentRenderer.vue'
import ActionButton from '@laravilt/actions/components/ActionButton.vue'
import { useLocalization } from '@laravilt/support/composables'

const { trans } = useLocalization()

interface FilterIndicator {
    label: string
    removeField: string
}

interface RelationContext {
    baseUrl: string
    relationship: string
    canEdit: boolean
    canDelete: boolean
}

interface TableProps {
    table: any
    records: any[]
    pagination?: any
    recordActions?: any[]
    bulkActions?: any[]
    filterIndicators?: FilterIndicator[]
    resourceSlug: string
    queryRoute: string
    loading?: boolean
    currentView?: 'table' | 'grid'
    useAjax?: boolean // Use fetch API instead of Inertia router (for relation managers)
    onDataLoaded?: (data: { records: any[], pagination: any }) => void // Callback when data is loaded via AJAX
    relationContext?: RelationContext // Context for relation manager to build record-specific URLs
}

const props = withDefaults(defineProps<TableProps>(), {
    records: () => [],
    loading: false,
    pagination: () => ({
        total: 0,
        per_page: 12,
        current_page: 1,
        last_page: 1,
        from: 0,
        to: 0,
    }),
    recordActions: () => [],
    bulkActions: () => [],
    filterIndicators: () => [],
    currentView: 'table',
    useAjax: false,
})

const emit = defineEmits<{
    'data-loaded': [data: { records: any[], pagination: any }]
    'action-complete': [data?: any]
}>()

// Extract bulk actions from toolbarActions (handles BulkActionGroup)
const extractedBulkActions = computed(() => {
    let actions: any[] = []

    // First, check if bulk actions are directly provided
    if (props.bulkActions && props.bulkActions.length > 0) {
        actions = props.bulkActions
    }
    // Then check table's bulkActions
    else if (props.table.bulkActions && props.table.bulkActions.length > 0) {
        actions = props.table.bulkActions
    }
    // Finally, extract from toolbarActions (look for BulkActionGroup)
    else if (props.table.toolbarActions && props.table.toolbarActions.length > 0) {
        for (const action of props.table.toolbarActions) {
            // Check if this is a BulkActionGroup
            if (action.type === 'bulk-action-group' && action.actions) {
                actions.push(...action.actions)
            }
        }
    }

    // Set preserveState: false for all bulk actions so table refreshes after action
    return actions.map(action => ({
        ...action,
        preserveState: action.preserveState ?? false,
        isBulkAction: true,
        deselectRecordsAfterCompletion: action.deselectRecordsAfterCompletion ?? true,
    }))
})

// Computed to determine if we should show grid view
const isGridView = computed(() => {
    return props.currentView === 'grid' && props.table.card !== null && props.table.card !== undefined
})

// Check if table has card configuration (for view toggle visibility)
const hasGridOption = computed(() => {
    return props.table.card !== null && props.table.card !== undefined
})

const sortColumn = ref<string | null>(props.table.defaultSortColumn || null)
const sortDirection = ref<'asc' | 'desc'>(props.table.defaultSortDirection || 'asc')
const selectedRecords = ref<(number | string)[]>([])
const searchQuery = ref<string>('')
const activeFilters = ref<Record<string, any>>({})
const clearSelectionsKey = ref<number>(0)
const isLoadingData = ref<boolean>(false)
const perPage = ref<number>(props.pagination.per_page || 12)
const allRecords = ref<any[]>(props.records)
const currentPage = ref<number>(props.pagination.current_page || 1)
const isLoadingMore = ref<boolean>(false)
const isInitialized = ref<boolean>(false)

// Enhance records with actions for relation manager context
const enhancedRecords = computed(() => {
    // If we have relation context, add _actions to each record with proper URLs
    if (props.relationContext) {
        return props.records.map(record => {
            const actions: any[] = []

            // Add view action (no URL needed, just displays modal with data)
            const viewAction = props.recordActions.find((a: any) => a.name === 'view')
            if (viewAction) {
                actions.push({
                    ...viewAction,
                    externalFormData: record, // Pass record data to populate form
                    // No URL or method - view is display only
                })
            }

            // Add edit action
            if (props.relationContext!.canEdit) {
                const editAction = props.recordActions.find((a: any) => a.name === 'edit')
                if (editAction) {
                    actions.push({
                        ...editAction,
                        url: `${props.relationContext!.baseUrl}/${record.id}`,
                        externalFormData: record, // Pass record data to populate form
                    })
                }
            }

            // Add delete action
            if (props.relationContext!.canDelete) {
                const deleteAction = props.recordActions.find((a: any) => a.name === 'delete')
                if (deleteAction) {
                    actions.push({
                        ...deleteAction,
                        url: `${props.relationContext!.baseUrl}/${record.id}`,
                    })
                }
            }

            // Add any other actions that aren't view/edit/delete
            props.recordActions.forEach((action: any) => {
                if (action.name !== 'view' && action.name !== 'edit' && action.name !== 'delete') {
                    actions.push({
                        ...action,
                        url: action.url || `${props.relationContext!.baseUrl}/${record.id}`,
                    })
                }
            })

            return {
                ...record,
                _actions: actions,
            }
        })
    }

    // For non-relation context, just use the records as-is with existing _actions
    return props.records
})

// Handle action completion from record actions - reload data and emit to parent
const handleActionComplete = (data?: any) => {
    // If using AJAX mode, reload data after action
    if (props.useAjax) {
        reloadData()
    }

    emit('action-complete', data)
}

// Column visibility persistence
const getColumnStorageKey = () => {
    return `laravilt_columns_${props.resourceSlug || 'default'}`
}

const getSavedColumns = (): string[] | null => {
    if (typeof window === 'undefined') return null
    try {
        const saved = localStorage.getItem(getColumnStorageKey())
        if (saved) {
            return JSON.parse(saved)
        }
    } catch (e) {
        console.error('Failed to parse saved columns:', e)
    }
    return null
}

const saveColumnPreferences = (columns: string[]) => {
    if (typeof window === 'undefined') return
    try {
        localStorage.setItem(getColumnStorageKey(), JSON.stringify(columns))
    } catch (e) {
        console.error('Failed to save column preferences:', e)
    }
}

// Initialize visible columns - load from localStorage or use defaults
const initVisibleColumns = (): string[] => {
    const saved = getSavedColumns()
    if (saved && saved.length > 0) {
        return saved
    }
    // Default: show all non-hidden columns
    return props.table.columns
        ?.filter((col: any) => !col.isToggledHiddenByDefault)
        .map((col: any) => col.name) || []
}

const visibleColumns = ref<string[]>(initVisibleColumns())

// Watch for changes to visible columns and save to localStorage
watch(visibleColumns, (newColumns) => {
    saveColumnPreferences(newColumns)
}, { deep: true })

// Pagination page size options
const paginationOptions = computed(() => {
    if (props.table.paginationPageOptions && props.table.paginationPageOptions.length > 0) {
        return props.table.paginationPageOptions
    }
    // Default options (12-based for grid layout compatibility)
    return [12, 24, 48, 96]
})

// Track if we're doing a filter/search reload (should replace, not append)
const isFilterReload = ref<boolean>(false)

// Watch for records changes and append or replace based on infinite scroll
watch(() => props.records, (newRecords, oldRecords) => {
    if (props.table.infiniteScroll) {
        // If it's a filter reload OR page 1, replace all records
        // Only append if loading more pages (page > 1 and NOT a filter reload)
        if (isFilterReload.value || props.pagination.current_page === 1) {
            allRecords.value = newRecords
            isFilterReload.value = false // Reset the flag
        } else if (props.pagination.current_page > 1) {
            // Only append if we're actually loading more (not a filter change)
            allRecords.value = [...allRecords.value, ...newRecords]
        } else {
            allRecords.value = newRecords
        }
    } else {
        allRecords.value = newRecords
    }
}, { immediate: true, deep: true })

// Watch for search, filter, sort changes to reset records (only after initialization)
watch([searchQuery, activeFilters, sortColumn, sortDirection], (newValues, oldValues) => {
    if (!isInitialized.value) return // Don't trigger on initial mount

    // Only reset if values actually changed (not just initializing)
    const hasActualChange = oldValues && oldValues.some((oldVal, index) => {
        const newVal = newValues[index]
        return JSON.stringify(oldVal) !== JSON.stringify(newVal)
    })

    if (!hasActualChange) return

    if (props.table.infiniteScroll) {
        // Don't clear records here - let skeleton show by setting isLoadingData
        // Records will be replaced when new data arrives via the other watcher
        currentPage.value = 1
    }
})

const handleSort = (column: string, direction: 'asc' | 'desc') => {
    sortColumn.value = column
    sortDirection.value = direction

    // For infinite scroll, mark as filter reload to replace records instead of appending
    if (props.table.infiniteScroll) {
        isFilterReload.value = true
        currentPage.value = 1
    }

    reloadData(1, true) // Reset to page 1 when sorting
}

const handleSearch = (query: string) => {
    searchQuery.value = query
    isFilterReload.value = true // Mark this as a search/filter reload
    reloadData(1, true) // Reset to page 1 when searching
}

const handleFilterChange = (filters: Record<string, any>) => {
    activeFilters.value = filters
    isFilterReload.value = true
    reloadData(1, true)
}

const handleFilterUpdate = (filterName: string, value: any) => {
    // Remove filter if value is empty or false (for toggles)
    if (value === null || value === undefined || value === '' || value === false) {
        const { [filterName]: _, ...rest } = activeFilters.value
        activeFilters.value = rest
    } else {
        activeFilters.value = {
            ...activeFilters.value,
            [filterName]: value
        }
    }
    isFilterReload.value = true // Mark this as a filter reload
    reloadData(1, true) // Reset to page 1 when filtering
}

const clearAllFilters = () => {
    activeFilters.value = {}
    searchQuery.value = ''
    isFilterReload.value = true // Mark this as a filter reload
    reloadData(1, true)
}

const removeFilter = (filterName: string) => {
    handleFilterUpdate(filterName, null)
}

const handleUpdateSelectedRecords = (records: (number | string)[]) => {
    selectedRecords.value = records
}

const bulkActionData = computed(() => ({
    ids: selectedRecords.value,
    model: props.table.model,
}))

const reloadData = async (page?: number, resetPage = false) => {
    // Set loading state immediately and keep it true
    isLoadingData.value = true

    // Build query params, only including non-empty values
    const params: Record<string, any> = {
        page: resetPage ? 1 : (page || props.pagination.current_page),
        per_page: perPage.value,
    }

    // Preserve existing URL params that we don't manage (like 'view')
    const urlParams = new URLSearchParams(window.location.search)
    const preserveParams = ['view']
    preserveParams.forEach(param => {
        const value = urlParams.get(param)
        if (value !== null) {
            params[param] = value
        }
    })

    // Only add search if it has a value
    if (searchQuery.value) {
        params.search = searchQuery.value
    }

    // Only add sort if it has a value
    if (sortColumn.value) {
        params.sort = sortColumn.value
        params.direction = sortDirection.value
    }

    // Only add filters that have values
    Object.entries(activeFilters.value).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== '' && value !== false) {
            params[key] = value
        }
    })

    // Use AJAX (fetch) if useAjax is true - this avoids Inertia page reload
    if (props.useAjax) {
        try {
            const queryString = new URLSearchParams(params).toString()
            const fetchUrl = `${props.queryRoute}?${queryString}`

            const response = await fetch(fetchUrl, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })

            if (response.ok) {
                const data = await response.json()
                // Emit data-loaded event for parent to update its state
                emit('data-loaded', {
                    records: data.data || [],
                    pagination: data.pagination || props.pagination,
                })
            }
        } catch (error) {
            console.error('Failed to fetch data:', error)
        } finally {
            isLoadingData.value = false
        }
        return
    }

    // Update URL manually (only for Inertia mode)
    const url = new URL(window.location.href)
    url.search = new URLSearchParams(params).toString()
    window.history.replaceState({}, '', url.toString())

    router.get(props.queryRoute, params, {
        preserveState: true,
        preserveScroll: true,
        onBefore: () => {
            isLoadingData.value = true
        },
        onSuccess: () => {
            setTimeout(() => {
                isLoadingData.value = false
            }, 100)
        },
        onError: () => {
            isLoadingData.value = false
        }
    })
}

const handlePageChange = (page: number, event?: Event) => {
    if (event) {
        event.preventDefault()
    }
    reloadData(page)
}

const handlePerPageChange = (event: Event) => {
    event.preventDefault()
    const target = event.target as HTMLSelectElement
    perPage.value = parseInt(target.value)
    reloadData(undefined, true) // Reset to page 1 when changing per page
}

// Track pending load more request
let loadMorePending = false
let loadMoreTimeout: ReturnType<typeof setTimeout> | null = null

// Infinite scroll load more with debouncing
const loadMoreRecords = () => {
    // Guard against multiple rapid calls
    if (loadMorePending || isLoadingMore.value || isLoadingData.value || !props.pagination) return
    if (props.pagination.current_page >= props.pagination.last_page) return

    // Debounce rapid scroll events
    if (loadMoreTimeout) {
        clearTimeout(loadMoreTimeout)
    }

    loadMorePending = true
    loadMoreTimeout = setTimeout(async () => {
        loadMorePending = false

        // Re-check conditions after debounce
        if (isLoadingMore.value || isLoadingData.value || !props.pagination) return
        if (props.pagination.current_page >= props.pagination.last_page) return

        isLoadingMore.value = true
        const nextPage = props.pagination.current_page + 1

        // Build query params, only including non-empty values
        const params: Record<string, any> = {
            page: nextPage,
            per_page: perPage.value,
        }

        // Preserve existing URL params that we don't manage (like 'view')
        const urlParams = new URLSearchParams(window.location.search)
        const preserveParams = ['view']
        preserveParams.forEach(param => {
            const value = urlParams.get(param)
            if (value !== null) {
                params[param] = value
            }
        })

        // Only add search if it has a value
        if (searchQuery.value) {
            params.search = searchQuery.value
        }

        // Only add sort if it has a value
        if (sortColumn.value) {
            params.sort = sortColumn.value
            params.direction = sortDirection.value
        }

        // Only add filters that have values
        Object.entries(activeFilters.value).forEach(([key, value]) => {
            if (value !== null && value !== undefined && value !== '' && value !== false) {
                params[key] = value
            }
        })

        // Use AJAX (fetch) if useAjax is true - this avoids Inertia page reload
        if (props.useAjax) {
            try {
                const queryString = new URLSearchParams(params).toString()
                const fetchUrl = `${props.queryRoute}?${queryString}`

                const response = await fetch(fetchUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })

                if (response.ok) {
                    const data = await response.json()
                    // Emit data-loaded event for parent to update its state
                    emit('data-loaded', {
                        records: data.data || [],
                        pagination: data.pagination || props.pagination,
                    })
                }
            } catch (error) {
                console.error('Failed to fetch data:', error)
            } finally {
                isLoadingMore.value = false
            }
            return
        }

        // Update URL (only for Inertia mode)
        const url = new URL(window.location.href)
        url.search = new URLSearchParams(params).toString()
        window.history.replaceState({}, '', url.toString())

        router.get(props.queryRoute, params, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                isLoadingMore.value = false
            },
            onError: () => {
                isLoadingMore.value = false
            }
        })
    }, 150) // 150ms debounce
}

// Set up intersection observer for infinite scroll
const tableEndRef = ref<HTMLElement | null>(null)
const scrollContainerRef = ref<HTMLElement | null>(null)
let observer: IntersectionObserver | null = null

const setupInfiniteScroll = () => {
    if (!props.table.infiniteScroll) return

    // Clean up existing observer
    if (observer) {
        observer.disconnect()
    }

    observer = new IntersectionObserver((entries) => {
        const entry = entries[0]
        if (entry.isIntersecting && !isLoadingMore.value && !isLoadingData.value) {
            // Check if there are more pages before loading
            if (props.pagination && props.pagination.current_page < props.pagination.last_page) {
                loadMoreRecords()
            }
        }
    }, {
        threshold: 0.1,
        rootMargin: '50px'
    })

    if (tableEndRef.value) {
        observer.observe(tableEndRef.value)
    }
}

// Watch for tableEndRef changes and set up observer
watch(tableEndRef, (newRef) => {
    if (newRef && props.table.infiniteScroll) {
        setupInfiniteScroll()
    }
})

// Watch for view changes - reset scroll and re-setup observer
watch(() => props.currentView, (newView, oldView) => {
    if (newView !== oldView) {
        // Reset scroll position when switching views
        if (scrollContainerRef.value) {
            scrollContainerRef.value.scrollTop = 0
        }

        // Re-setup observer after view change (nextTick to wait for DOM update)
        if (props.table.infiniteScroll) {
            setTimeout(() => {
                setupInfiniteScroll()
            }, 100)
        }
    }
})

const getPageNumbers = (): (number | string)[] => {
    const current = props.pagination.current_page
    const last = props.pagination.last_page
    const pages: (number | string)[] = []

    if (last <= 7) {
        // Show all pages if 7 or fewer
        for (let i = 1; i <= last; i++) {
            pages.push(i)
        }
    } else {
        // Always show first page
        pages.push(1)

        if (current > 3) {
            pages.push('...')
        }

        // Show pages around current
        const start = Math.max(2, current - 1)
        const end = Math.min(last - 1, current + 1)

        for (let i = start; i <= end; i++) {
            pages.push(i)
        }

        if (current < last - 2) {
            pages.push('...')
        }

        // Always show last page
        pages.push(last)
    }

    return pages
}

// Listen for bulk action completion to clear selected records and reload table
const handleBulkActionCompleted = () => {
    selectedRecords.value = []
    clearSelectionsKey.value++

    // If using AJAX mode, reload data
    if (props.useAjax) {
        reloadData()
    }

    // Emit action-complete to notify parent
    emit('action-complete')
}

onMounted(() => {
    // Initialize filters from URL query parameters
    const urlParams = new URLSearchParams(window.location.search)
    const initialFilters: Record<string, any> = {}

    // Get all filter names from table configuration
    const filterNames = props.table.filters?.map((f: any) => f.name) || []

    // Read filter values from URL
    filterNames.forEach((filterName: string) => {
        const value = urlParams.get(filterName)
        if (value !== null && value !== '') {
            initialFilters[filterName] = value
        }
    })

    // Initialize activeFilters with URL values
    activeFilters.value = initialFilters

    // Initialize search from URL
    const searchParam = urlParams.get('search')
    if (searchParam) {
        searchQuery.value = searchParam
    }

    // Initialize sort from URL
    const sortParam = urlParams.get('sort')
    const directionParam = urlParams.get('direction')
    if (sortParam) {
        sortColumn.value = sortParam
        // Validate direction - only accept 'asc' or 'desc', default to 'asc'
        sortDirection.value = (directionParam === 'asc' || directionParam === 'desc') ? directionParam : 'asc'
    }

    // Mark as initialized to enable watchers
    isInitialized.value = true

    // Update URL with current state if URL is empty or missing parameters
    const currentUrl = new URL(window.location.href)
    const hasPageParam = urlParams.has('page')
    const hasPerPageParam = urlParams.has('per_page')

    if (!hasPageParam || !hasPerPageParam) {
        // Build query params for current state
        const params: Record<string, any> = {
            page: props.pagination.current_page || 1,
            per_page: perPage.value,
        }

        // Preserve existing URL params that we don't manage (like 'view')
        const preserveParams = ['view']
        preserveParams.forEach(param => {
            const value = urlParams.get(param)
            if (value !== null) {
                params[param] = value
            }
        })

        // Add search if present
        if (searchQuery.value) {
            params.search = searchQuery.value
        }

        // Add sort if present
        if (sortColumn.value) {
            params.sort = sortColumn.value
            params.direction = sortDirection.value
        }

        // Add filters if present
        Object.entries(activeFilters.value).forEach(([key, value]) => {
            if (value !== null && value !== undefined && value !== '' && value !== false) {
                params[key] = value
            }
        })

        // Update URL without reloading
        currentUrl.search = new URLSearchParams(params).toString()
        window.history.replaceState({}, '', currentUrl.toString())
    }

    window.addEventListener('bulk-action-completed', handleBulkActionCompleted)
    setupInfiniteScroll()
})

onUnmounted(() => {
    window.removeEventListener('bulk-action-completed', handleBulkActionCompleted)
    if (observer) {
        observer.disconnect()
    }
})
</script>

<template>
    <div class="flex flex-col h-full min-h-0">
        <!-- Table with integrated toolbar -->
        <div class="rounded-lg border border-border shadow-sm bg-card overflow-hidden flex flex-col flex-1 min-h-0">
            <!-- Search, Filters, and Column Visibility (Fixed at top) -->
            <div class="flex-shrink-0">
                <TableToolbar
                v-if="table"
                :searchable="table.searchable"
                :search-placeholder="table.searchPlaceholder"
                :search="searchQuery"
                :filters="table.filters"
                :active-filters="activeFilters"
                :filter-indicators="filterIndicators"
                :columns="table.columns"
                :bulk-actions-available="extractedBulkActions.length > 0"
                :selected-count="selectedRecords.length"
                :show-sort="isGridView"
                :sort-column="sortColumn"
                :sort-direction="sortDirection"
                v-model:visible-columns="visibleColumns"
                @update:search="handleSearch"
                @update:filters="handleFilterChange"
                @remove-filter="removeFilter"
                @clear-filters="clearAllFilters"
                @update:sort="handleSort"
            >
                <template #filters>
                    <div
                        v-for="filter in table.filters"
                        :key="filter.name"
                        class="filter-field"
                    >
                        <!-- Render custom form field if available -->
                        <LaraviltComponentRenderer
                            v-if="filter.formField"
                            :component="filter.formField.component"
                            :props="{
                                ...filter.formField,
                                modelValue: activeFilters[filter.name] || filter.default || null,
                                name: filter.name,
                            }"
                            @update:modelValue="(value) => handleFilterUpdate(filter.name, value)"
                        />
                        <!-- Fallback to default filter component based on type -->
                        <LaraviltComponentRenderer
                            v-else
                            :component="filter.component"
                            :props="{
                                ...filter,
                                modelValue: activeFilters[filter.name] || filter.default || null,
                                name: filter.name,
                            }"
                            @update:modelValue="(value) => handleFilterUpdate(filter.name, value)"
                        />
                    </div>
                </template>
                <template #bulk-actions>
                    <ActionButton
                        v-for="action in extractedBulkActions"
                        :key="action.name"
                        v-bind="action"
                        :data="bulkActionData"
                    />
                </template>
            </TableToolbar>
            </div>

            <!-- Scrollable Records Area -->
            <div ref="scrollContainerRef" class="flex-1 min-h-0 overflow-y-auto custom-scrollbar">
                <!-- Render DataTable for table view -->
            <DataTable
                v-if="!isGridView"
                :columns="table.columns"
                :records="relationContext ? enhancedRecords : (table.infiniteScroll ? allRecords : records)"
                :record-actions="recordActions"
                :loading="loading || isLoadingData"
                :sort-column="sortColumn"
                :sort-direction="sortDirection"
                :visible-columns="visibleColumns"
                :bulk-actions-available="extractedBulkActions.length > 0"
                :resource-slug="resourceSlug"
                :column-execution-route="relationContext?.columnExecutionRoute || table.columnExecutionRoute"
                :model-class="table.model"
                :clear-selections="clearSelectionsKey"
                :fixed-actions="table.fixedActions"
                :striped="table.striped"
                :infinite-scroll="table.infiniteScroll"
                :use-ajax="useAjax"
                @sort="handleSort"
                @update:selected-records="handleUpdateSelectedRecords"
                @action-complete="handleActionComplete"
            />

            <!-- Render CardGrid for grid view -->
            <CardGrid
                v-else
                :grid="{ ...table, card: table.card }"
                :records="relationContext ? enhancedRecords : (table.infiniteScroll ? allRecords : records)"
                :record-actions="recordActions"
                :loading="loading || isLoadingData"
                :loading-more="isLoadingMore"
                :bulk-actions-available="extractedBulkActions.length > 0"
                :resource-slug="resourceSlug"
                :model-class="table.model"
                :clear-selections="clearSelectionsKey"
                :use-ajax="useAjax"
                @update:selected-records="handleUpdateSelectedRecords"
                @action-complete="handleActionComplete"
            />

            <!-- Infinite Scroll Loading Indicator & Observer (inside scrollable area) -->
            <div v-if="table.infiniteScroll" class="border-t border-border bg-muted/50">
                <div v-if="isLoadingMore" class="p-8">
                    <div class="flex items-center justify-center gap-2">
                        <div class="animate-spin h-5 w-5 border-2 border-primary border-t-transparent rounded-full"></div>
                        <span class="text-sm text-muted-foreground">{{ trans('tables::tables.infinite_scroll.loading_more') }}</span>
                    </div>
                </div>
                <div v-else-if="pagination && pagination.current_page < pagination.last_page" class="p-4">
                    <div ref="tableEndRef" class="h-20 flex items-center justify-center">
                        <span class="text-xs text-muted-foreground">{{ trans('tables::tables.infinite_scroll.scroll_for_more') }}</span>
                    </div>
                </div>
                <div v-else class="p-4 text-center">
                    <span class="text-sm text-muted-foreground">{{ trans('tables::tables.infinite_scroll.no_more_records') }}</span>
                </div>
            </div>
            </div> <!-- End Scrollable Records Area -->

            <!-- Pagination (Fixed at bottom) -->
            <div v-if="!table.infiniteScroll && pagination && pagination.total > 0" class="flex-shrink-0 p-4 border-t border-border bg-muted/50">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <!-- Pagination Info (left side) -->
                    <div class="hidden sm:block text-sm text-muted-foreground whitespace-nowrap order-1">
                        {{ trans('tables::tables.pagination.showing') }} {{ pagination.from }} {{ trans('tables::tables.pagination.to') }} {{ pagination.to }} {{ trans('tables::tables.pagination.of') }} {{ pagination.total }}
                    </div>

                    <!-- Pagination Controls (centered) -->
                    <div class="flex items-center gap-2 order-2">
                        <!-- Previous Button -->
                        <button
                            @click="(e) => handlePageChange(pagination.current_page - 1, e)"
                            :disabled="pagination.current_page <= 1"
                            class="px-3 py-1.5 text-sm font-medium rounded-md border border-border transition-colors disabled:opacity-50 disabled:cursor-not-allowed hover:bg-muted"
                            :class="{
                                'cursor-not-allowed': pagination.current_page <= 1,
                                'hover:bg-muted': pagination.current_page > 1
                            }"
                        >
                            {{ trans('tables::tables.pagination.previous') }}
                        </button>

                        <!-- Page Numbers -->
                        <div class="flex items-center gap-1">
                            <template v-for="page in getPageNumbers()" :key="page">
                                <span v-if="page === '...'" class="px-2 text-sm text-muted-foreground">
                                    ...
                                </span>
                                <button
                                    v-else
                                    @click="(e) => handlePageChange(page as number, e)"
                                    class="min-w-[32px] px-2 py-1.5 text-sm font-medium rounded-md border transition-colors"
                                    :class="{
                                        'bg-primary text-primary-foreground border-primary': page === pagination.current_page,
                                        'border-border hover:bg-muted': page !== pagination.current_page
                                    }"
                                >
                                    {{ page }}
                                </button>
                            </template>
                        </div>

                        <!-- Next Button -->
                        <button
                            @click="(e) => handlePageChange(pagination.current_page + 1, e)"
                            :disabled="pagination.current_page >= pagination.last_page"
                            class="px-3 py-1.5 text-sm font-medium rounded-md border border-border transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            :class="{
                                'cursor-not-allowed': pagination.current_page >= pagination.last_page,
                                'hover:bg-muted': pagination.current_page < pagination.last_page
                            }"
                        >
                            {{ trans('tables::tables.pagination.next') }}
                        </button>
                    </div>

                    <!-- Per Page Selector (right side) -->
                    <div class="flex items-center gap-2 order-3">
                        <label for="per-page" class="text-sm text-muted-foreground whitespace-nowrap">
                            {{ trans('tables::tables.pagination.per_page') }}
                        </label>
                        <select
                            id="per-page"
                            :value="perPage"
                            @change="handlePerPageChange"
                            class="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        >
                            <option v-for="option in paginationOptions" :key="option" :value="option">{{ option }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
