<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Search, SlidersHorizontal, X, Columns3, Eye, EyeOff } from 'lucide-vue-next'
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
})

const emit = defineEmits<{
  'update:search': [value: string]
  'update:filters': [filters: Record<string, any>]
  'update:visibleColumns': [columns: string[]]
  'removeFilter': [filterName: string]
  clearFilters: []
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
const hasFilterIndicators = computed(() => props.filterIndicators && props.filterIndicators.length > 0)
</script>

<template>
  <div class="flex flex-col gap-3 bg-card">
    <!-- Bulk Actions Bar (when items are selected) -->
    <div v-if="bulkActionsAvailable && selectedCount > 0" class="flex items-center gap-3 bg-primary/10 dark:bg-primary/20 px-4 py-3 border-b border-primary/30">
      <span class="text-sm font-medium text-foreground">{{ selectedCount }} selected</span>
      <div class="flex items-center gap-2">
        <slot name="bulk-actions" />
      </div>
    </div>

    <!-- Top Row: Search, Filters, Column Toggle -->
    <div class="flex items-center gap-2 flex-nowrap px-4 py-3 border-b border-border">
      <!-- Search -->
      <div v-if="searchable" class="relative w-full max-w-sm shrink-0">
        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
        <Input
          v-model="localSearch"
          type="search"
          :placeholder="searchPlaceholder"
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

      <div class="flex items-center gap-2 ml-auto shrink-0">
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

        <!-- Column Toggle -->
        <DropdownMenu v-if="toggleableColumns.length > 0">
          <DropdownMenuTrigger as-child>
            <Button variant="outline" size="sm" class="gap-2 whitespace-nowrap">
              <Columns3 class="h-4 w-4" />
              Columns
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" class="w-64">
            <DropdownMenuLabel>Toggle columns</DropdownMenuLabel>
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
    <div v-if="hasActiveFilters || hasActiveSearch || hasFilterIndicators" class="flex items-center gap-2 flex-wrap px-4 pb-3">
      <span class="text-sm text-muted-foreground">Active filters:</span>

      <Badge v-if="hasActiveSearch" variant="secondary" class="gap-1">
        Search: "{{ search }}"
        <button @click="clearSearch" class="hover:text-foreground">
          <X class="h-3 w-3" />
        </button>
      </Badge>

      <!-- Custom filter indicators with individual removal -->
      <Badge
        v-for="(indicator, index) in filterIndicators"
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
        v-if="hasActiveFilters || hasActiveSearch || hasFilterIndicators"
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
