<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Search, SlidersHorizontal, X, Columns3, Eye, EyeOff, ArrowUpDown, ArrowUp, ArrowDown } from 'lucide-vue-next'
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'
import { Badge } from '@/components/ui/badge'
import { useLocalization } from '@/composables/useLocalization'

// Initialize localization
const { trans } = useLocalization()

interface Column {
  name: string
  label: string
  toggleable?: boolean
  [key: string]: any
}

interface Filter {
  name: string
  label: string
  component: string
  [key: string]: any
}

interface FilterIndicator {
  label: string
  removeField: string
}

interface TableToolbarProps {
  searchable?: boolean
  searchPlaceholder?: string
  search?: string
  filters?: Filter[]
  activeFilters?: Record<string, any>
  filterIndicators?: FilterIndicator[]
  columns?: Column[]
  visibleColumns?: string[]
  bulkActionsAvailable?: boolean
  selectedCount?: number
  showSort?: boolean
  sortColumn?: string | null
  sortDirection?: 'asc' | 'desc'
}

const props = withDefaults(defineProps<TableToolbarProps>(), {
  searchable: true,
  searchPlaceholder: 'Search...',
  search: '',
  filters: () => [],
  activeFilters: () => ({}),
  filterIndicators: () => [],
  columns: () => [],
  visibleColumns: () => [],
  bulkActionsAvailable: false,
  selectedCount: 0,
  showSort: false,
  sortColumn: null,
  sortDirection: 'asc',
})

const emit = defineEmits<{
  'update:search': [value: string]
  'update:filters': [filters: Record<string, any>]
  'update:visibleColumns': [columns: string[]]
  'removeFilter': [filterName: string]
  clearFilters: []
  'update:sort': [column: string, direction: 'asc' | 'desc']
}>()

const localSearch = ref(props.search)

// Update local search when prop changes (e.g., from clear button)
watch(() => props.search, (newValue) => {
  localSearch.value = newValue
})

const handleSearchSubmit = () => {
  emit('update:search', localSearch.value)
}

const toggleableColumns = computed(() =>
  props.columns.filter((col) => col.toggleable !== false)
)

const activeFilterCount = computed(() => {
  return Object.values(props.activeFilters).filter(
    (value) => value !== null && value !== '' && value !== undefined
  ).length
})

// Compute filter indicators from activeFilters instead of using the prop
const computedFilterIndicators = computed(() => {
  const indicators: Array<{ label: string; removeField: string }> = []

  Object.entries(props.activeFilters).forEach(([filterName, value]) => {
    if (value === null || value === '' || value === undefined || value === false) {
      return
    }

    // Find the filter definition
    const filter = props.filters.find((f: any) => f.name === filterName)
    if (!filter) return

    // Get the indicator label from the filter
    let label = `${filter.label || filterName}: ${value}`

    // If filter has indicateUsing callback, use it
    if (filter.indicateUsing) {
      label = filter.indicateUsing
    }

    indicators.push({
      label,
      removeField: filterName
    })
  })

  return indicators
})

const isColumnVisible = (columnName: string) => {
  if (props.visibleColumns.length === 0) return true
  return props.visibleColumns.includes(columnName)
}

const toggleColumn = (columnName: string) => {
  let newVisibleColumns: string[]

  // If starting from "show all" state (empty array), initialize with all columns
  if (props.visibleColumns.length === 0) {
    // User is hiding a column, so start with all columns except this one
    newVisibleColumns = props.columns
      .map((col) => col.name)
      .filter((name) => name !== columnName)
  } else {
    // Toggle column in existing array
    newVisibleColumns = isColumnVisible(columnName)
      ? props.visibleColumns.filter((name) => name !== columnName)
      : [...props.visibleColumns, columnName]
  }

  emit('update:visibleColumns', newVisibleColumns)
}

const clearSearch = () => {
  emit('update:search', '')
}

const clearFilters = () => {
  emit('clearFilters')
}

const removeFilter = (filterName: string) => {
  emit('removeFilter', filterName)
}

const hasActiveFilters = computed(() => activeFilterCount.value > 0)
const hasActiveSearch = computed(() => props.search.length > 0)
const hasComputedFilterIndicators = computed(() => computedFilterIndicators.value.length > 0)

// Sorting functionality for grid view
const sortableColumns = computed(() => {
  return props.columns.filter(col => col.sortable)
})

const currentSortLabel = computed(() => {
  if (!props.sortColumn) return trans('tables::tables.toolbar.sort_by') + '...'
  const column = sortableColumns.value.find(col => col.name === props.sortColumn)
  return column ? column.label : trans('tables::tables.toolbar.sort_by') + '...'
})

const sortIcon = computed(() => {
  if (!props.sortColumn) return ArrowUpDown
  return props.sortDirection === 'asc' ? ArrowUp : ArrowDown
})

const handleSortChange = (columnName: string) => {
  let direction: 'asc' | 'desc' = 'asc'

  if (props.sortColumn === columnName) {
    // Toggle direction if same column
    direction = props.sortDirection === 'asc' ? 'desc' : 'asc'
  }

  emit('update:sort', columnName, direction)
}
</script>

<template>
  <div class="flex flex-col gap-3 bg-card">
    <!-- Bulk Actions Bar (when items are selected) -->
    <div v-if="bulkActionsAvailable && selectedCount > 0" class="flex items-center gap-3 bg-primary/10 dark:bg-primary/20 px-4 py-3 border-b border-primary/30">
      <span class="text-sm font-medium text-foreground">{{ selectedCount }} {{ trans('tables::tables.bulk.selected').replace(':count ', '') }}</span>
      <div class="flex items-center gap-2">
        <slot name="bulk-actions" />
      </div>
    </div>

    <!-- Top Row: Search, Filters, Column Toggle -->
    <div class="flex items-center gap-2 flex-nowrap px-4 py-3 border-b border-border">
      <!-- Search -->
      <div v-if="searchable" class="relative w-full max-w-sm shrink-0">
        <Search class="absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
        <Input
          v-model="localSearch"
          type="search"
          :placeholder="searchPlaceholder || trans('tables::tables.search.placeholder')"
          class="ps-9 pe-9"
          @keyup.enter="handleSearchSubmit"
        />
        <button
          v-if="hasActiveSearch"
          @click="clearSearch"
          :title="trans('tables::tables.search.clear')"
          class="absolute end-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
        >
          <X class="h-4 w-4" />
        </button>
      </div>

      <div class="flex items-center gap-2 ms-auto shrink-0">
        <!-- Sort Button (for grid view) -->
        <Popover v-if="showSort && sortableColumns.length > 0">
          <PopoverTrigger as-child>
            <Button variant="outline" size="sm" class="gap-2 whitespace-nowrap" :title="trans('tables::tables.toolbar.sort_by')">
              <component :is="sortIcon" class="h-4 w-4" />
              {{ currentSortLabel }}
            </Button>
          </PopoverTrigger>
          <PopoverContent align="end" class="w-[280px]">
            <div class="space-y-2">
              <h4 class="text-sm font-semibold mb-3">{{ trans('tables::tables.toolbar.sort_by') }}</h4>
              <div class="space-y-1">
                <button
                  v-for="column in sortableColumns"
                  :key="column.name"
                  @click="handleSortChange(column.name)"
                  class="w-full flex items-center justify-between px-3 py-2 text-sm rounded-md transition-colors"
                  :class="sortColumn === column.name
                    ? 'bg-primary text-primary-foreground hover:bg-primary/90'
                    : 'hover:bg-muted'
                  "
                >
                  <span>{{ column.label }}</span>
                  <component
                    v-if="sortColumn === column.name"
                    :is="sortDirection === 'asc' ? ArrowUp : ArrowDown"
                    class="h-4 w-4"
                  />
                </button>
              </div>
            </div>
          </PopoverContent>
        </Popover>

        <!-- Filters Button -->
        <Popover v-if="filters.length > 0">
          <PopoverTrigger as-child>
            <Button variant="outline" size="sm" class="gap-2 whitespace-nowrap" :title="trans('tables::tables.toolbar.filters')">
              <SlidersHorizontal class="h-4 w-4" />
              {{ trans('tables::tables.toolbar.filters') }}
              <Badge v-if="activeFilterCount > 0" variant="secondary" class="ms-1 px-1.5">
                {{ activeFilterCount }}
              </Badge>
            </Button>
          </PopoverTrigger>
          <PopoverContent align="end" class="w-[420px]">
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <h4 class="text-sm font-semibold">{{ trans('tables::tables.toolbar.filters') }}</h4>
                <Button
                  v-if="hasActiveFilters"
                  variant="ghost"
                  size="sm"
                  @click="clearFilters"
                  class="h-auto py-1 px-2 text-xs"
                >
                  {{ trans('tables::tables.toolbar.clear_all') }}
                </Button>
              </div>
              <div class="space-y-3">
                <slot name="filters" />
              </div>
            </div>
          </PopoverContent>
        </Popover>

        <!-- Column Toggle -->
        <DropdownMenu v-if="toggleableColumns.length > 0">
          <DropdownMenuTrigger as-child>
            <Button variant="outline" size="sm" class="gap-2 whitespace-nowrap" :title="trans('tables::tables.toolbar.toggle_columns')">
              <Columns3 class="h-4 w-4" />
              {{ trans('tables::tables.toolbar.columns') }}
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" class="w-64">
            <DropdownMenuLabel>{{ trans('tables::tables.toolbar.toggle_columns') }}</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuCheckboxItem
              v-for="column in toggleableColumns"
              :key="column.name"
              :checked="isColumnVisible(column.name)"
              @click.prevent="toggleColumn(column.name)"
              class="gap-2"
            >
              <component
                :is="isColumnVisible(column.name) ? Eye : EyeOff"
                class="h-4 w-4 shrink-0"
                :class="isColumnVisible(column.name) ? 'text-primary' : 'text-muted-foreground'"
              />
              <span class="flex-1">{{ column.label }}</span>
            </DropdownMenuCheckboxItem>
          </DropdownMenuContent>
        </DropdownMenu>

        <!-- Toolbar Actions Slot -->
        <slot name="toolbar-actions" />
      </div>
    </div>

    <!-- Active Filters Display -->
    <div v-if="hasActiveFilters || hasActiveSearch || hasComputedFilterIndicators" class="flex items-center gap-2 flex-wrap px-4 pb-3">
      <span class="text-sm text-muted-foreground">{{ trans('tables::tables.toolbar.active_filters') }}:</span>

      <Badge v-if="hasActiveSearch" variant="secondary" class="gap-1">
        {{ trans('tables::tables.toolbar.search') }}: "{{ search }}"
        <button @click="clearSearch" class="hover:text-foreground">
          <X class="h-3 w-3" />
        </button>
      </Badge>

      <!-- Computed filter indicators with individual removal -->
      <Badge
        v-for="(indicator, index) in computedFilterIndicators"
        :key="index"
        variant="secondary"
        class="gap-1"
      >
        {{ indicator.label }}
        <button @click="removeFilter(indicator.removeField)" class="hover:text-foreground">
          <X class="h-3 w-3" />
        </button>
      </Badge>

      <slot name="active-filters" />

      <Button
        v-if="hasActiveFilters || hasActiveSearch || hasComputedFilterIndicators"
        variant="ghost"
        size="sm"
        @click="clearFilters(); clearSearch()"
        class="h-auto py-1 px-2 text-xs"
      >
        {{ trans('tables::tables.toolbar.clear_all') }}
      </Button>
    </div>
  </div>
</template>
