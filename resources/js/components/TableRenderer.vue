<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import DataTable from './DataTable.vue'
import TableToolbar from './TableToolbar.vue'
import LaraviltComponentRenderer from '@laravilt/forms/components/LaraviltComponentRenderer.vue'
import ActionButton from '@laravilt/actions/components/ActionButton.vue'

interface FilterIndicator {
    label: string
    removeField: string
}

interface TableRendererProps {
    table: any
    records: any[]
    pagination?: any
    recordActions?: any[]
    bulkActions?: any[]
    filterIndicators?: FilterIndicator[]
    resourceSlug: string
    queryRoute: string
    loading?: boolean
}

const props = withDefaults(defineProps<TableRendererProps>(), {
    loading: false,
    pagination: () => ({
        total: 0,
        per_page: 10,
        current_page: 1,
        last_page: 1,
        from: 0,
        to: 0,
    }),
    recordActions: () => [],
    bulkActions: () => [],
    filterIndicators: () => [],
})

const sortColumn = ref<string | null>(props.table.defaultSortColumn || null)
const sortDirection = ref<'asc' | 'desc'>(props.table.defaultSortDirection || 'asc')
const visibleColumns = ref<string[]>([])
const selectedRecords = ref<(number | string)[]>([])
const searchQuery = ref<string>('')
const activeFilters = ref<Record<string, any>>({})
const clearSelectionsKey = ref<number>(0)
const isLoadingData = ref<boolean>(false)
const perPage = ref<number>(props.pagination.per_page || 5)
const allRecords = ref<any[]>(props.records)
const currentPage = ref<number>(props.pagination.current_page || 1)
const isLoadingMore = ref<boolean>(false)

// Watch for records changes and append or replace based on infinite scroll
watch(() => props.records, (newRecords) => {
    if (props.table.infiniteScroll) {
        if (props.pagination.current_page > 1) {
            // Append new records for infinite scroll (loading more)
            allRecords.value = [...allRecords.value, ...newRecords]
        } else {
            // First page or reset - replace records
            allRecords.value = newRecords
        }
    } else {
        // Replace records for regular pagination
        allRecords.value = newRecords
    }
}, { immediate: true })

// Watch for search, filter, sort changes to reset records
watch([searchQuery, activeFilters, sortColumn, sortDirection], () => {
    if (props.table.infiniteScroll) {
        allRecords.value = []
        currentPage.value = 1
    }
})

const handleSort = (column: string, direction: 'asc' | 'desc') => {
    sortColumn.value = column
    sortDirection.value = direction

    reloadData()
}

const handleSearch = (query: string) => {
    searchQuery.value = query
    reloadData()
}

const handleFilterChange = (filters: Record<string, any>) => {
    activeFilters.value = filters
    reloadData()
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
    reloadData()
}

const clearAllFilters = () => {
    activeFilters.value = {}
    reloadData()
}

const removeFilter = (filterName: string) => {
    const { [filterName]: _, ...rest } = activeFilters.value
    activeFilters.value = rest
    reloadData()
}

const handleUpdateSelectedRecords = (records: (number | string)[]) => {
    selectedRecords.value = records
}

const bulkActionData = computed(() => ({
    ids: selectedRecords.value,
}))

const reloadData = (page?: number, resetPage = false) => {
    isLoadingData.value = true

    router.get(props.queryRoute, {
        search: searchQuery.value,
        sort: sortColumn.value,
        direction: sortDirection.value,
        page: resetPage ? 1 : (page || props.pagination.current_page),
        per_page: perPage.value,
        ...activeFilters.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
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

// Infinite scroll load more
const loadMoreRecords = () => {
    if (isLoadingMore.value || isLoadingData.value || !props.pagination) return
    if (props.pagination.current_page >= props.pagination.last_page) return

    isLoadingMore.value = true
    const nextPage = props.pagination.current_page + 1

    router.get(props.queryRoute, {
        search: searchQuery.value,
        sort: sortColumn.value,
        direction: sortDirection.value,
        page: nextPage,
        per_page: perPage.value,
        ...activeFilters.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['table'],
        onFinish: () => {
            isLoadingMore.value = false
        }
    })
}

// Set up intersection observer for infinite scroll
const tableEndRef = ref<HTMLElement | null>(null)
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

// Watch for tableEndRef changes and set up observer (only once)
watch(tableEndRef, (newRef, oldRef) => {
    if (newRef && !oldRef && props.table.infiniteScroll) {
        setupInfiniteScroll()
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

// Listen for bulk action completion to clear selected records
const handleBulkActionCompleted = () => {
    selectedRecords.value = []
    clearSelectionsKey.value++
}

onMounted(() => {
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
    <div class="space-y-4">
        <!-- Table with integrated toolbar -->
        <div class="rounded-lg border border-border shadow-sm bg-card overflow-hidden">
            <!-- Search, Filters, and Column Visibility -->
            <TableToolbar
                v-if="table"
                :searchable="table.searchable"
                :search-placeholder="table.searchPlaceholder"
                :search="searchQuery"
                :filters="table.filters"
                :active-filters="activeFilters"
                :filter-indicators="filterIndicators"
                :columns="table.columns"
                :bulk-actions-available="bulkActions.length > 0"
                :selected-count="selectedRecords.length"
                v-model:visible-columns="visibleColumns"
                @update:search="handleSearch"
                @update:filters="handleFilterChange"
                @remove-filter="removeFilter"
                @clear-filters="clearAllFilters"
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
                        v-for="action in bulkActions"
                        :key="action.name"
                        v-bind="action"
                        :data="bulkActionData"
                    />
                </template>
            </TableToolbar>
            <DataTable
                :columns="table.columns"
                :records="table.infiniteScroll ? allRecords : records"
                :record-actions="recordActions"
                :loading="loading || isLoadingData"
                :sort-column="sortColumn"
                :sort-direction="sortDirection"
                :visible-columns="visibleColumns"
                :bulk-actions-available="bulkActions.length > 0"
                :resource-slug="resourceSlug"
                :clear-selections="clearSelectionsKey"
                :fixed-actions="table.fixedActions"
                @sort="handleSort"
                @update:selected-records="handleUpdateSelectedRecords"
            />

            <!-- Infinite Scroll Loading Indicator & Observer -->
            <div v-if="table.infiniteScroll" class="border-t border-border bg-muted/50">
                <div v-if="isLoadingMore" class="p-8">
                    <div class="flex items-center justify-center gap-2">
                        <div class="animate-spin h-5 w-5 border-2 border-primary border-t-transparent rounded-full"></div>
                        <span class="text-sm text-muted-foreground">Loading more...</span>
                    </div>
                </div>
                <div v-else-if="pagination && pagination.current_page < pagination.last_page" class="p-4">
                    <div ref="tableEndRef" class="h-20 flex items-center justify-center">
                        <span class="text-xs text-muted-foreground">Scroll for more...</span>
                    </div>
                </div>
                <div v-else class="p-4 text-center">
                    <span class="text-sm text-muted-foreground">No more records</span>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="!table.infiniteScroll && pagination && pagination.total > 0" class="p-4 border-t border-border bg-muted/50">
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <!-- Per Page Selector (centered on mobile, left on desktop) -->
                    <div class="flex items-center gap-2 sm:mr-auto">
                        <label for="per-page" class="text-sm text-muted-foreground whitespace-nowrap">
                            Per page:
                        </label>
                        <select
                            id="per-page"
                            :value="perPage"
                            @change="handlePerPageChange"
                            class="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        >
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="15">15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>

                    <!-- Pagination Controls (centered) -->
                    <div class="flex items-center gap-2">
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
                            Previous
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
                            Next
                        </button>
                    </div>

                    <!-- Pagination Info (right side on desktop, centered on mobile) -->
                    <div class="hidden sm:block text-sm text-muted-foreground whitespace-nowrap sm:ml-auto">
                        Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
