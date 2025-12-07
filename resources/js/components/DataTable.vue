<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import TextColumn from './columns/TextColumn.vue'
import IconColumn from './columns/IconColumn.vue'
import ImageColumn from './columns/ImageColumn.vue'
import ColorColumn from './columns/ColorColumn.vue'
import ToggleColumn from './columns/ToggleColumn.vue'
import RecordActions from '@laravilt/actions/components/RecordActions.vue'
import { Skeleton } from '@/components/ui/skeleton'
import { Checkbox } from '@/components/ui/checkbox'
import { ArrowUp, ArrowDown, ArrowUpDown, Inbox } from 'lucide-vue-next'

interface Column {
  component: string
  name: string
  label: string
  sortable?: boolean
  toggleable?: boolean
  [key: string]: any
}

interface Record {
  id: number | string
  [key: string]: any
}

interface Action {
  name: string
  label?: string
  icon?: string
  color?: string
  url?: string
  requiresConfirmation?: boolean
  [key: string]: any
}

interface DataTableProps {
  columns: Column[]
  records: Record[]
  loading?: boolean
  skeletonRows?: number
  sortColumn?: string | null
  sortDirection?: 'asc' | 'desc'
  visibleColumns?: string[]
  bulkActionsAvailable?: boolean
  resourceSlug?: string
  recordActions?: Action[]
  executionRoute?: string
  clearSelections?: number
  fixedActions?: boolean
  striped?: boolean
  infiniteScroll?: boolean
}

const props = withDefaults(defineProps<DataTableProps>(), {
  columns: () => [],
  records: () => [],
  loading: false,
  skeletonRows: 10,
  sortColumn: null,
  sortDirection: 'asc',
  visibleColumns: () => [],
  bulkActionsAvailable: false,
  resourceSlug: '',
  recordActions: () => [],
  executionRoute: undefined,
  clearSelections: 0,
  fixedActions: false,
  striped: false,
  infiniteScroll: false,
})

const emit = defineEmits<{
  sort: [column: string, direction: 'asc' | 'desc']
  'update:selectedRecords': [records: (number | string)[]]
}>()

const selectedRecords = ref<Set<number | string>>(new Set())
const selectedRecordsArray = computed(() => Array.from(selectedRecords.value))

// Watch for clearSelections prop changes to clear selections
watch(() => props.clearSelections, () => {
  if (props.clearSelections > 0) {
    selectedRecords.value = new Set()
    emit('update:selectedRecords', [])
  }
})

const isColumnVisible = (column: Column): boolean => {
  if (props.visibleColumns.length === 0) return true
  if (column.toggleable === false) return true
  return props.visibleColumns.includes(column.name)
}

const visibleColumnsFiltered = computed(() => {
  return props.columns.filter((col) => isColumnVisible(col))
})

const allSelected = computed(() => {
  if (props.records.length === 0) return false
  return props.records.every((record) => selectedRecords.value.has(record.id))
})

const someSelected = computed(() => {
  if (props.records.length === 0) return false
  return props.records.some((record) => selectedRecords.value.has(record.id)) && !allSelected.value
})

const toggleSelectAll = () => {
  if (allSelected.value) {
    selectedRecords.value = new Set()
  } else {
    selectedRecords.value = new Set(props.records.map((record) => record.id))
  }
  emit('update:selectedRecords', Array.from(selectedRecords.value))
}

const toggleSelectRecord = (recordId: number | string) => {
  const newSet = new Set(selectedRecords.value)
  if (newSet.has(recordId)) {
    newSet.delete(recordId)
  } else {
    newSet.add(recordId)
  }
  selectedRecords.value = newSet
  emit('update:selectedRecords', Array.from(selectedRecords.value))
}

const isRecordSelected = (recordId: number | string) => {
  // Force reactivity by accessing the array length to track changes
  const _ = selectedRecordsArray.value.length
  return selectedRecords.value.has(recordId)
}

const handleSort = (column: Column) => {
  if (!column.sortable) return

  let direction: 'asc' | 'desc' = 'asc'

  if (props.sortColumn === column.name) {
    direction = props.sortDirection === 'asc' ? 'desc' : 'asc'
  }

  emit('sort', column.name, direction)
}

const getSortIcon = (column: Column) => {
  if (!column.sortable) return null

  if (props.sortColumn === column.name) {
    return props.sortDirection === 'asc' ? ArrowUp : ArrowDown
  }

  return ArrowUpDown
}

// Check if any record has actions
const hasRecordActions = computed(() => {
  return props.records.some((record) => record._actions && record._actions.length > 0)
})

// Get component for column type
const getColumnComponent = (columnType: string) => {
  switch (columnType) {
    case 'TextColumn':
      return TextColumn
    case 'IconColumn':
      return IconColumn
    case 'ImageColumn':
      return ImageColumn
    case 'ColorColumn':
      return ColorColumn
    case 'ToggleColumn':
      return ToggleColumn
    default:
      return TextColumn
  }
}
</script>

<template>
  <div class="relative w-full bg-card">
    <!-- Empty State -->
    <div v-if="!loading && !records.length" class="w-full">
      <div class="py-20 px-4">
        <slot name="empty">
          <div class="flex flex-col items-center justify-center text-center">
            <div class="rounded-full bg-muted p-4 mb-4">
              <Inbox class="h-10 w-10 text-muted-foreground" />
            </div>
            <h3 class="text-lg font-semibold text-foreground mb-2">No records found</h3>
            <p class="text-sm text-muted-foreground max-w-sm">
              There are no records to display. Try adjusting your filters or search query.
            </p>
          </div>
        </slot>
      </div>
    </div>

    <!-- Table Content (only show when there are records or loading) -->
    <div v-else>
      <div>
        <!-- Table Header (Sticky at top when scrolling) -->
        <div class="border-b border-border bg-muted/50 sticky top-0 z-10">
          <div class="flex items-center">
            <!-- Checkbox Column (if bulk actions available) -->
            <div
              v-if="bulkActionsAvailable"
              class="flex items-center justify-center px-4 py-3.5 w-[48px] shrink-0"
            >
              <label class="inline-flex items-center">
                <input
                  type="checkbox"
                  :checked="allSelected"
                  :indeterminate.prop="someSelected"
                  @change="toggleSelectAll"
                  class="peer size-4 shrink-0 appearance-none rounded-[4px] border border-input bg-background shadow-xs ring-offset-background transition-colors hover:border-input focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 checked:border-primary checked:bg-primary checked:text-primary-foreground cursor-pointer"
                />
                <svg
                  class="pointer-events-none absolute size-4 hidden peer-checked:block text-primary-foreground"
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="3"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                >
                  <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
              </label>
            </div>

            <!-- Scrollable Header Columns -->
            <div class="flex flex-1 min-w-0">
              <div
                v-for="(column, index) in visibleColumnsFiltered"
                :key="`header-${column.name}`"
                :class="[
                  'px-4 py-3.5 text-left text-xs font-medium uppercase tracking-wider',
                  column.sortable ? 'cursor-pointer select-none hover:text-foreground transition-colors' : 'text-muted-foreground',
                  index === 0 ? 'min-w-[250px] w-[250px]' : 'min-w-[200px] w-[200px]'
                ]"
                @click="handleSort(column)"
              >
                <div class="flex items-center gap-2">
                  <span>{{ column.label }}</span>
                  <component
                    :is="getSortIcon(column)"
                    v-if="column.sortable"
                    class="h-4 w-4"
                    :class="sortColumn === column.name ? 'text-foreground' : 'text-muted-foreground/50'"
                  />
                </div>
              </div>
            </div>

            <!-- Fixed Actions Header -->
            <div v-if="fixedActions && hasRecordActions" class="sticky right-0 z-10 border-l border-border bg-muted flex items-center justify-end px-4 py-3.5 shrink-0 min-w-[180px]">
              <span class="text-xs font-medium uppercase tracking-wider text-muted-foreground whitespace-nowrap">Actions</span>
            </div>
          </div>
        </div>

        <!-- Table Body (no scroll - parent TableRenderer handles scrolling) -->
        <div class="divide-y divide-border">
          <!-- Loading State -->
          <template v-if="loading">
            <div
              v-for="i in skeletonRows"
              :key="`skeleton-${i}`"
              class="group transition-colors hover:bg-muted/30"
              :class="[
                i % 2 === 0 ? 'bg-muted/20' : 'bg-card'
              ]"
            >
              <div class="flex items-center">
                <!-- Checkbox Skeleton (if bulk actions available) -->
                <div
                  v-if="bulkActionsAvailable"
                  class="flex items-center justify-center px-4 py-4 w-[48px] shrink-0"
                >
                  <Skeleton class="h-4 w-4 rounded" />
                </div>

                <!-- Scrollable Skeleton Columns -->
                <div class="flex flex-1 min-w-0">
                  <div
                    v-for="(column, index) in visibleColumnsFiltered"
                    :key="`skeleton-col-${column.name}`"
                    :class="[
                      'px-4 py-4',
                      index === 0 ? 'min-w-[250px] w-[250px]' : 'min-w-[200px] w-[200px]'
                    ]"
                  >
                    <Skeleton class="h-5 w-full max-w-[200px]" />
                  </div>
                </div>

                <!-- Fixed Actions Skeleton -->
                <div
                  v-if="fixedActions && hasRecordActions"
                  class="sticky right-0 z-10 border-l border-border flex items-center justify-end gap-1.5 px-4 py-4 self-stretch shrink-0 min-w-[180px]"
                  :class="[
                    i % 2 === 0 ? 'bg-muted/20' : 'bg-card'
                  ]"
                >
                  <Skeleton class="h-8 w-8 rounded-md" />
                  <Skeleton class="h-8 w-8 rounded-md" />
                  <Skeleton class="h-8 w-8 rounded-md" />
                </div>
              </div>
            </div>
          </template>

          <!-- Data Rows -->
          <TransitionGroup
            v-if="records.length > 0"
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
          >
            <div
              v-for="(record, index) in records"
              :key="record.id"
              class="group transition-colors hover:bg-muted/30"
              :class="[
                striped ? (index % 2 === 0 ? 'bg-card' : 'bg-muted/20') : 'bg-card',
                selectedRecords.has(record.id) && 'bg-primary/5',
              ]"
            >
              <div class="flex items-center">
                <!-- Checkbox Column (if bulk actions available) -->
                <div
                  v-if="bulkActionsAvailable"
                  class="flex items-center justify-center px-4 py-4 w-[48px] shrink-0"
                >
                  <label class="inline-flex items-center">
                    <input
                      type="checkbox"
                      :checked="isRecordSelected(record.id)"
                      @change="() => toggleSelectRecord(record.id)"
                      class="peer size-4 shrink-0 appearance-none rounded-[4px] border border-input bg-background shadow-xs ring-offset-background transition-colors hover:border-input focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 checked:border-primary checked:bg-primary checked:text-primary-foreground cursor-pointer"
                    />
                    <svg
                      class="pointer-events-none absolute size-4 hidden peer-checked:block text-primary-foreground"
                      xmlns="http://www.w3.org/2000/svg"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="3"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    >
                      <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                  </label>
                </div>

                <!-- Scrollable Data Columns -->
                <div class="flex flex-1 min-w-0">
                  <div
                    v-for="(column, columnIndex) in visibleColumnsFiltered"
                    :key="`cell-${column.name}`"
                    :class="[
                      'px-4 py-4 text-sm',
                      columnIndex === 0 ? 'min-w-[250px] w-[250px] font-medium text-foreground' : 'min-w-[200px] w-[200px] text-muted-foreground',
                    ]"
                  >
                    <component
                      :is="getColumnComponent(column.component)"
                      :value="record[column.name]"
                      :color="record._colors?.[column.name]"
                      :icon="record._icons?.[column.name]"
                      :size="record._sizes?.[column.name]"
                      :description="record._descriptions?.[column.name]"
                      :record-id="record.id"
                      :resource-slug="resourceSlug"
                      v-bind="column"
                    />
                  </div>

                  <!-- Inline Actions (when not fixed) -->
                  <div v-if="!fixedActions && record._actions && record._actions.length > 0" class="px-4 py-4 flex items-center justify-end min-w-[180px] ml-auto">
                    <slot name="actions" :record="record">
                      <RecordActions
                        :actions="record._actions"
                        :record="record"
                        :resource-name="resourceSlug"
                        :execution-route="executionRoute"
                        variant="inline"
                      />
                    </slot>
                  </div>
                </div>

                <!-- Fixed Actions Cell -->
                <div
                  v-if="fixedActions && record._actions && record._actions.length > 0"
                  class="sticky right-0 z-10 border-l border-border transition-colors flex items-center justify-end gap-1.5 px-4 self-stretch shrink-0 min-w-[180px]"
                  :class="[
                    index % 2 === 0 ? 'bg-card group-hover:bg-muted' : 'bg-muted group-hover:bg-muted',
                    selectedRecords.has(record.id) && 'bg-primary/10 group-hover:bg-primary/20',
                  ]"
                >
                  <slot name="actions" :record="record">
                    <RecordActions
                      :actions="record._actions"
                      :record="record"
                      :resource-name="resourceSlug"
                      :execution-route="executionRoute"
                      variant="inline"
                    />
                  </slot>
                </div>
              </div>
            </div>
          </TransitionGroup>
        </div>
      </div>
    </div> <!-- end v-else (table content) -->
  </div>
</template>

<style scoped>
/* Custom scrollbar styling */
.custom-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: hsl(var(--muted-foreground) / 0.3) transparent;
  scroll-behavior: smooth;
}

/* Webkit scrollbar styling */
.custom-scrollbar::-webkit-scrollbar {
  height: 8px;
  width: 8px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: hsl(var(--muted-foreground) / 0.3);
  border-radius: 4px;
  transition: background-color 0.2s ease;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background-color: hsl(var(--muted-foreground) / 0.5);
}

/* Dark mode adjustments */
:global(.dark) .custom-scrollbar {
  scrollbar-color: hsl(var(--muted-foreground) / 0.4) transparent;
}

:global(.dark) .custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: hsl(var(--muted-foreground) / 0.4);
}

:global(.dark) .custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background-color: hsl(var(--muted-foreground) / 0.6);
}

/* Smooth transitions for row hover states */
.group {
  transition: background-color 0.15s ease-in-out;
}
</style>
