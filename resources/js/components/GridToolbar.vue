<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Search, SlidersHorizontal, X, ArrowUpDown, ArrowUp, ArrowDown } from 'lucide-vue-next'
import { useLocalization } from '@laravilt/support/composables'

const { trans } = useLocalization()
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Badge } from '@/components/ui/badge'

interface FilterIndicator {
  label: string
  removeField: string
}

interface GridToolbarProps {
  searchable?: boolean
  searchPlaceholder?: string
  search?: string
  filters?: any[]
  activeFilters?: Record<string, any>
  filterIndicators?: FilterIndicator[]
  bulkActionsAvailable?: boolean
  selectedCount?: number
  columns?: any[]
  sortColumn?: string | null
  sortDirection?: 'asc' | 'desc'
}

const props = withDefaults(defineProps<GridToolbarProps>(), {
  searchable: true,
  searchPlaceholder: 'Search...',
  search: '',
  filters: () => [],
  activeFilters: () => ({}),
  filterIndicators: () => [],
  bulkActionsAvailable: false,
  selectedCount: 0,
  columns: () => [],
  sortColumn: null,
  sortDirection: 'asc',
})

const emit = defineEmits<{
  'update:search': [value: string]
  'update:filters': [filters: Record<string, any>]
  'removeFilter': [filterName: string]
  clearFilters: []
  'update:sort': [column: string, direction: 'asc' | 'desc']
}>()

const localSearch = ref(props.search)

watch(() => props.search, (newValue) => {
  localSearch.value = newValue
})

const handleSearchSubmit = () => {
  emit('update:search', localSearch.value)
}

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

const sortableColumns = computed(() => {
  return props.columns.filter(col => col.sortable)
})

const currentSortLabel = computed(() => {
  if (!props.sortColumn) return 'Sort by...'
  const column = sortableColumns.value.find(col => col.name === props.sortColumn)
  return column ? column.label : 'Sort by...'
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
</script>

<template>
  <div class="flex flex-col gap-3 bg-card">
    <!-- Bulk Actions Bar -->
    <div v-if="bulkActionsAvailable && selectedCount > 0" class="flex items-center gap-3 bg-primary/10 dark:bg-primary/20 px-4 py-3 border-b border-primary/30">
      <span class="text-sm font-medium text-foreground">{{ selectedCount }} selected</span>
      <div class="flex items-center gap-2">
        <slot name="bulk-actions" />
      </div>
    </div>

    <!-- Top Row: Search and Filters -->
    <div class="flex flex-col gap-3 px-4 py-3 border-b border-border sm:flex-row sm:items-center sm:gap-2">
      <!-- Search -->
      <div v-if="searchable" class="relative w-full sm:max-w-sm sm:shrink-0">
        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
        <Input
          v-model="localSearch"
          type="search"
          :placeholder="searchPlaceholder || trans('tables::tables.search.placeholder')"
          class="pl-9 pr-9"
          @keyup.enter="handleSearchSubmit"
        />
        <button
          v-if="hasActiveSearch"
          @click="clearSearch"
          class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
        >
          <X class="h-4 w-4" />
        </button>
      </div>

      <div class="flex items-center gap-2 flex-wrap sm:ml-auto sm:shrink-0 sm:flex-nowrap">
        <!-- Sort Button -->
        <Popover v-if="sortableColumns.length > 0">
          <PopoverTrigger as-child>
            <Button variant="outline" size="sm" class="gap-2 whitespace-nowrap">
              <component :is="sortIcon" class="h-4 w-4" />
              {{ currentSortLabel }}
            </Button>
          </PopoverTrigger>
          <PopoverContent align="end" class="w-[280px]">
            <div class="space-y-2">
              <h4 class="text-sm font-semibold mb-3">Sort by</h4>
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
            <Button variant="outline" size="sm" class="gap-2 whitespace-nowrap">
              <SlidersHorizontal class="h-4 w-4" />
              Filters
              <Badge v-if="activeFilterCount > 0" variant="secondary" class="ml-1 px-1.5">
                {{ activeFilterCount }}
              </Badge>
            </Button>
          </PopoverTrigger>
          <PopoverContent align="end" class="w-[420px]">
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <h4 class="text-sm font-semibold">Filters</h4>
                <Button
                  v-if="hasActiveFilters"
                  variant="ghost"
                  size="sm"
                  @click="clearFilters"
                  class="h-auto py-1 px-2 text-xs"
                >
                  Clear all
                </Button>
              </div>
              <div class="space-y-3">
                <slot name="filters" />
              </div>
            </div>
          </PopoverContent>
        </Popover>

        <!-- Toolbar Actions Slot -->
        <slot name="toolbar-actions" />
      </div>
    </div>

    <!-- Active Filters Display -->
    <div v-if="hasActiveFilters || hasActiveSearch || hasComputedFilterIndicators" class="flex items-center gap-2 flex-wrap px-4 pb-3">
      <span class="text-sm text-muted-foreground">Active filters:</span>

      <Badge v-if="hasActiveSearch" variant="secondary" class="gap-1">
        Search: "{{ search }}"
        <button @click="clearSearch" class="hover:text-foreground">
          <X class="h-3 w-3" />
        </button>
      </Badge>

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
        Clear all
      </Button>
    </div>
  </div>
</template>
