<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import TextColumn from './columns/TextColumn.vue'
import IconColumn from './columns/IconColumn.vue'
import ImageColumn from './columns/ImageColumn.vue'
import ColorColumn from './columns/ColorColumn.vue'
import ToggleColumn from './columns/ToggleColumn.vue'
import RecordActions from '@laravilt/actions/components/RecordActions.vue'
import { Skeleton } from '@/components/ui/skeleton'
import { Checkbox } from '@/components/ui/checkbox'
import { ArrowUp, ArrowDown, ArrowUpDown, Inbox, GripVertical, ChevronDown, ChevronRight } from 'lucide-vue-next'
import { useLocalization } from '@/composables/useLocalization'

// Initialize localization
const { trans } = useLocalization()

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
  _url?: string
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

interface GroupConfig {
  column: string
  label: string
  collapsible: boolean
}

interface RecordGroup {
  value: string | number | null
  title: string
  description?: string | null
  records: Record[]
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
  columnExecutionRoute?: string
  modelClass?: string
  recordActions?: Action[]
  executionRoute?: string
  clearSelections?: number
  fixedActions?: boolean
  striped?: boolean
  infiniteScroll?: boolean
  useAjax?: boolean
  reorderable?: boolean
  reorderableColumn?: string
  reorderRoute?: string
  activeGroup?: string | null
  groups?: GroupConfig[]
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
  columnExecutionRoute: undefined,
  modelClass: undefined,
  recordActions: () => [],
  executionRoute: undefined,
  clearSelections: 0,
  fixedActions: false,
  striped: false,
  infiniteScroll: false,
  useAjax: false,
  reorderable: false,
  reorderableColumn: 'sort_order',
  reorderRoute: undefined,
  activeGroup: null,
  groups: () => [],
})

const emit = defineEmits<{
  sort: [column: string, direction: 'asc' | 'desc']
  'update:selectedRecords': [records: (number | string)[]]
  'action-complete': [data?: any]
  'reorder': [items: { id: number | string, order: number }[]]
}>()

// Drag and drop state for reorderable
const draggedIndex = ref<number | null>(null)
const dragOverIndex = ref<number | null>(null)
const localRecords = ref<Record[]>([])
const isReordering = ref(false)

// Keep local records in sync with props
watch(() => props.records, (newRecords) => {
  localRecords.value = [...newRecords]
}, { immediate: true, deep: true })

// Drag and drop handlers
const handleDragStart = (event: DragEvent, index: number) => {
  if (!props.reorderable) return
  draggedIndex.value = index
  if (event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'move'
    event.dataTransfer.setData('text/plain', String(index))
  }
}

const handleDragOver = (event: DragEvent, index: number) => {
  if (!props.reorderable || draggedIndex.value === null) return
  event.preventDefault()
  if (event.dataTransfer) {
    event.dataTransfer.dropEffect = 'move'
  }
  dragOverIndex.value = index
}

const handleDragLeave = () => {
  dragOverIndex.value = null
}

const handleDrop = async (event: DragEvent, targetIndex: number) => {
  if (!props.reorderable || draggedIndex.value === null) return
  event.preventDefault()

  const sourceIndex = draggedIndex.value
  if (sourceIndex === targetIndex) {
    draggedIndex.value = null
    dragOverIndex.value = null
    return
  }

  // Reorder local records
  const newRecords = [...localRecords.value]
  const [movedItem] = newRecords.splice(sourceIndex, 1)
  newRecords.splice(targetIndex, 0, movedItem)
  localRecords.value = newRecords

  // Reset drag state
  draggedIndex.value = null
  dragOverIndex.value = null

  // Build new order data
  const reorderData = newRecords.map((record, index) => ({
    id: record.id,
    order: index + 1,
  }))

  // Emit reorder event
  emit('reorder', reorderData)

  // Send to server
  await saveReorder(reorderData)
}

const handleDragEnd = () => {
  draggedIndex.value = null
  dragOverIndex.value = null
}

const saveReorder = async (items: { id: number | string, order: number }[]) => {
  if (!props.reorderRoute && !props.resourceSlug) return

  isReordering.value = true
  try {
    const url = props.reorderRoute || `/admin/${props.resourceSlug}/reorder`
    await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({
        items,
        column: props.reorderableColumn,
      }),
    })
  } catch (error) {
    console.error('Failed to save reorder:', error)
    // Revert to original order on error
    localRecords.value = [...props.records]
  } finally {
    isReordering.value = false
  }
}

// Use local records for rendering when reorderable
const displayRecords = computed(() => {
  return props.reorderable ? localRecords.value : props.records
})

// Track collapsed groups
const collapsedGroups = ref<Set<string | number | null>>(new Set())

// Check if grouping is active
const isGrouped = computed(() => props.activeGroup !== null && props.activeGroup !== undefined)

// Get active group config
const activeGroupConfig = computed(() => {
  if (!props.activeGroup) return null
  return props.groups?.find(g => g.column === props.activeGroup) || null
})

// Group records by active group column
const groupedRecords = computed<RecordGroup[]>(() => {
  if (!isGrouped.value || !props.activeGroup) {
    return []
  }

  const records = displayRecords.value
  const groupMap = new Map<string | number | null, RecordGroup>()

  for (const record of records) {
    const groupInfo = record._group
    const groupValue = groupInfo?.value ?? null
    const groupKey = String(groupValue)

    if (!groupMap.has(groupKey)) {
      groupMap.set(groupKey, {
        value: groupValue,
        title: groupInfo?.title || String(groupValue),
        description: groupInfo?.description || null,
        records: [],
      })
    }

    groupMap.get(groupKey)!.records.push(record)
  }

  return Array.from(groupMap.values())
})

// Toggle group collapse state
const toggleGroupCollapse = (groupValue: string | number | null) => {
  const groupKey = String(groupValue)
  if (collapsedGroups.value.has(groupKey)) {
    collapsedGroups.value.delete(groupKey)
  } else {
    collapsedGroups.value.add(groupKey)
  }
}

// Check if a group is collapsed
const isGroupCollapsed = (groupValue: string | number | null) => {
  return collapsedGroups.value.has(String(groupValue))
}

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

// Get max actions count across all records for consistent column width
const maxActionsCount = computed(() => {
  if (!props.records.length) return 3 // Default for skeleton
  return Math.max(...props.records.map((record) =>
    record._actions?.filter((a: Action) => !a.isHidden)?.length || 0
  ), 1)
})

// Calculate actions column width based on max actions
// Each button is 32px (h-8 w-8) + 4px gap, plus 32px padding (px-4 = 16px each side)
const actionsColumnWidth = computed(() => {
  const buttonSize = 32
  const gap = 4
  const padding = 32
  const count = maxActionsCount.value
  return `${(buttonSize * count) + (gap * (count - 1)) + padding}px`
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

// Handle row click to navigate to record URL
const handleRowClick = (event: MouseEvent, record: Record) => {
  // Don't navigate if there's no URL
  if (!record._url) return

  // Don't navigate if clicking on interactive elements
  const target = event.target as HTMLElement
  const interactiveElements = ['A', 'BUTTON', 'INPUT', 'SELECT', 'TEXTAREA', 'LABEL']

  // Check if click is on or inside an interactive element
  let element: HTMLElement | null = target
  while (element) {
    if (interactiveElements.includes(element.tagName)) return
    if (element.hasAttribute('data-no-row-click')) return
    if (element.classList.contains('record-actions')) return
    element = element.parentElement
  }

  // Navigate using Inertia
  router.visit(record._url)
}

// Check if any record has a URL (for cursor styling)
const hasRecordUrls = computed(() => {
  return displayRecords.value.some((record) => record._url)
})

// Get column width class based on column type and name
const getColumnWidthClass = (column: Column, index: number): string => {
  // Check for explicit width from column config
  if (column.width) {
    return `w-[${column.width}]`
  }

  // Small width for ID columns
  if (column.name === 'id' || column.name.endsWith('_id')) {
    return 'w-[80px] min-w-[80px]'
  }

  // Small width for boolean/icon/toggle columns
  if (column.component === 'IconColumn' || column.component === 'ToggleColumn') {
    return 'w-[100px] min-w-[100px]'
  }

  // Small width for color columns
  if (column.component === 'ColorColumn') {
    return 'w-[100px] min-w-[100px]'
  }

  // Medium width for image columns
  if (column.component === 'ImageColumn') {
    return 'w-[120px] min-w-[120px]'
  }

  // Flexible width for name/title as first meaningful column (usually index 0 or 1)
  if (index === 0 || column.name === 'name' || column.name === 'title') {
    return 'min-w-[180px] flex-1'
  }

  // Default medium width for other text columns
  return 'min-w-[140px] w-[180px]'
}
</script>

<template>
  <div class="relative w-full border-x border-border bg-card overflow-x-auto custom-scrollbar">
    <!-- Empty State -->
    <div v-if="!loading && !records.length" class="w-full">
      <div class="py-16 px-6">
        <slot name="empty">
          <div class="flex flex-col items-center justify-center text-center">
            <div class="rounded-full bg-muted/80 p-4 mb-4 ring-1 ring-border/50">
              <Inbox class="h-8 w-8 text-muted-foreground/70" />
            </div>
            <h3 class="text-base font-semibold text-foreground mb-1.5">No records found</h3>
            <p class="text-sm text-muted-foreground max-w-sm leading-relaxed">
              There are no records to display. Try adjusting your filters or search query.
            </p>
          </div>
        </slot>
      </div>
    </div>

    <!-- Table Content (only show when there are records or loading) -->
    <div v-else>
      <div class="min-w-max">
        <!-- Table Header (Sticky at top when scrolling) -->
        <div class="border-b border-border bg-muted sticky top-0 z-10">
          <div class="flex items-center w-full">
            <!-- Drag Handle Header (if reorderable) -->
            <div
              v-if="reorderable"
              class="flex items-center justify-center px-2 py-3 w-[40px] shrink-0 bg-muted"
            >
              <span class="sr-only">Reorder</span>
            </div>

            <!-- Checkbox Column (if bulk actions available) -->
            <div
              v-if="bulkActionsAvailable"
              class="flex items-center justify-center px-3 py-3 w-[52px] shrink-0 bg-muted"
            >
              <label class="inline-flex items-center">
                <input
                  type="checkbox"
                  :checked="allSelected"
                  :indeterminate.prop="someSelected"
                  @change="toggleSelectAll"
                  class="peer size-4 shrink-0 appearance-none rounded border border-input bg-background shadow-sm ring-offset-background transition-all duration-150 hover:border-primary/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50 checked:border-primary checked:bg-primary checked:text-primary-foreground cursor-pointer"
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
            <div class="flex flex-1 min-w-0 bg-muted">
              <div
                v-for="(column, index) in visibleColumnsFiltered"
                :key="`header-${column.name}`"
                :class="[
                  'px-3 py-3 text-start text-xs font-semibold tracking-wide bg-muted',
                  column.sortable ? 'cursor-pointer select-none hover:bg-accent hover:text-foreground transition-all duration-150' : 'text-muted-foreground',
                  getColumnWidthClass(column, index),
                  'shrink-0'
                ]"
                @click="handleSort(column)"
              >
                <div class="flex items-center gap-1.5">
                  <span class="text-muted-foreground">{{ column.label }}</span>
                  <component
                    :is="getSortIcon(column)"
                    v-if="column.sortable"
                    class="h-3.5 w-3.5 transition-colors"
                    :class="sortColumn === column.name ? 'text-foreground' : 'text-muted-foreground/40'"
                  />
                </div>
              </div>

              <!-- Inline Actions Header (when not fixed) -->
              <div
                v-if="!fixedActions && hasRecordActions"
                class="px-3 py-3 text-end text-xs font-semibold tracking-wide bg-muted min-w-[160px] ml-auto shrink-0"
              >
                <span class="text-muted-foreground">{{ trans('tables::tables.columns.actions') }}</span>
              </div>
            </div>

            <!-- Fixed Actions Header -->
            <div
              v-if="fixedActions && hasRecordActions"
              class="sticky end-0 z-20 border-s border-border bg-muted flex items-center justify-center px-3 py-3 shrink-0"
              :style="{ width: actionsColumnWidth, minWidth: actionsColumnWidth }"
            >
              <span class="text-xs font-semibold tracking-wide text-muted-foreground whitespace-nowrap">{{ trans('tables::tables.columns.actions') }}</span>
            </div>
          </div>
        </div>

        <!-- Table Body (no scroll - parent Table handles scrolling) -->
        <div class="divide-y divide-border/50">
          <!-- Loading State -->
          <template v-if="loading">
            <div
              v-for="i in skeletonRows"
              :key="`skeleton-${i}`"
              class="group transition-colors flex items-center min-w-full"
              :class="[
                striped && i % 2 !== 0 ? 'bg-muted' : 'bg-card'
              ]"
            >
              <!-- Checkbox Skeleton (if bulk actions available) -->
              <div
                v-if="bulkActionsAvailable"
                class="flex items-center justify-center px-3 py-3.5 w-[52px] shrink-0"
              >
                <Skeleton class="h-4 w-4 rounded" />
              </div>

              <!-- Scrollable Skeleton Columns -->
              <div class="flex flex-1 min-w-0">
                <div
                  v-for="(column, index) in visibleColumnsFiltered"
                  :key="`skeleton-col-${column.name}`"
                  :class="[
                    'px-3 py-3.5 shrink-0',
                    getColumnWidthClass(column, index)
                  ]"
                >
                  <Skeleton class="h-4 w-full max-w-[180px] rounded" />
                </div>

                <!-- Inline Actions Skeleton (when not fixed) -->
                <div v-if="!fixedActions && hasRecordActions" class="px-3 py-3.5 flex items-center justify-end gap-1.5 min-w-[160px] ml-auto shrink-0">
                  <Skeleton v-for="n in maxActionsCount" :key="n" class="h-7 w-7 rounded-md" />
                </div>
              </div>

              <!-- Fixed Actions Skeleton -->
              <div
                v-if="fixedActions && hasRecordActions"
                class="sticky end-0 z-20 border-s border-border flex items-center justify-center gap-1.5 px-3 py-3.5 self-stretch shrink-0"
                :class="[
                  striped && i % 2 !== 0 ? 'bg-muted' : 'bg-card'
                ]"
                :style="{ width: actionsColumnWidth, minWidth: actionsColumnWidth }"
              >
                <Skeleton v-for="n in maxActionsCount" :key="n" class="h-7 w-7 rounded-md" />
              </div>
            </div>
          </template>

          <!-- Grouped Data Rows -->
          <template v-if="isGrouped && groupedRecords.length > 0">
            <div
              v-for="group in groupedRecords"
              :key="`group-${group.value}`"
              class="border-b border-border last:border-b-0"
            >
              <!-- Group Header -->
              <div
                class="flex items-center gap-3 px-4 py-3 bg-muted/70 border-b border-border cursor-pointer hover:bg-muted transition-colors"
                :class="[activeGroupConfig?.collapsible && 'select-none']"
                @click="activeGroupConfig?.collapsible && toggleGroupCollapse(group.value)"
              >
                <component
                  :is="isGroupCollapsed(group.value) ? ChevronRight : ChevronDown"
                  v-if="activeGroupConfig?.collapsible"
                  class="h-4 w-4 text-muted-foreground transition-transform"
                />
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2">
                    <span class="font-semibold text-foreground">{{ group.title }}</span>
                    <span class="text-xs text-muted-foreground px-1.5 py-0.5 bg-muted rounded-full">
                      {{ group.records.length }}
                    </span>
                  </div>
                  <p v-if="group.description" class="text-xs text-muted-foreground mt-0.5">
                    {{ group.description }}
                  </p>
                </div>
              </div>

              <!-- Group Records -->
              <TransitionGroup
                v-if="!isGroupCollapsed(group.value)"
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0 translate-y-1"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
                tag="div"
              >
                <div
                  v-for="(record, index) in group.records"
                  :key="record.id"
                  class="group transition-all duration-150 flex items-center min-w-full"
                  :class="[
                    striped ? (index % 2 === 0 ? 'bg-card hover:bg-accent' : 'bg-muted/30 hover:bg-accent') : 'bg-card hover:bg-accent',
                    selectedRecords.has(record.id) && 'bg-primary/10 hover:bg-primary/15 ring-1 ring-inset ring-primary/30',
                    record._url && 'cursor-pointer',
                  ]"
                  @click="(e) => handleRowClick(e, record)"
                >
                  <!-- Checkbox Column (if bulk actions available) -->
                  <div
                    v-if="bulkActionsAvailable"
                    class="flex items-center justify-center px-3 py-3.5 w-[52px] shrink-0"
                  >
                    <label class="inline-flex items-center">
                      <input
                        type="checkbox"
                        :checked="isRecordSelected(record.id)"
                        @change="() => toggleSelectRecord(record.id)"
                        class="peer size-4 shrink-0 appearance-none rounded border border-input bg-background shadow-sm ring-offset-background transition-all duration-150 hover:border-primary/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50 checked:border-primary checked:bg-primary checked:text-primary-foreground cursor-pointer"
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
                        'px-3 py-3.5 text-sm shrink-0',
                        getColumnWidthClass(column, columnIndex),
                        columnIndex === 0 ? 'font-medium text-foreground' : 'text-foreground/80',
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
                        :column-execution-route="columnExecutionRoute"
                        v-bind="column"
                      />
                    </div>

                    <!-- Inline Actions (when not fixed) -->
                    <div v-if="!fixedActions && record._actions && record._actions.length > 0" class="px-3 py-3.5 flex items-center justify-end min-w-[160px] ml-auto shrink-0 record-actions" data-no-row-click>
                      <slot name="actions" :record="record">
                        <RecordActions
                          :actions="record._actions"
                          :record="record"
                          :resource-name="resourceSlug"
                          :model-class="modelClass"
                          :execution-route="executionRoute"
                          variant="inline"
                          @action-complete="(data) => emit('action-complete', data)"
                        />
                      </slot>
                    </div>
                  </div>

                  <!-- Fixed Actions Cell -->
                  <div
                    v-if="fixedActions && record._actions && record._actions.length > 0"
                    class="sticky end-0 z-20 border-s border-border transition-all duration-150 flex items-center justify-center gap-1.5 px-3 self-stretch shrink-0 record-actions"
                    :class="[
                      striped ? (index % 2 === 0 ? 'bg-card group-hover:bg-accent' : 'bg-muted/30 group-hover:bg-accent') : 'bg-card group-hover:bg-accent',
                      selectedRecords.has(record.id) && 'bg-primary/10 group-hover:bg-primary/15',
                    ]"
                    :style="{ width: actionsColumnWidth, minWidth: actionsColumnWidth }"
                    data-no-row-click
                  >
                    <slot name="actions" :record="record">
                      <RecordActions
                        :actions="record._actions"
                        :record="record"
                        :resource-name="resourceSlug"
                        :model-class="modelClass"
                        :execution-route="executionRoute"
                        variant="inline"
                        @action-complete="(data) => emit('action-complete', data)"
                      />
                    </slot>
                  </div>
                </div>
              </TransitionGroup>
            </div>
          </template>

          <!-- Ungrouped Data Rows -->
          <TransitionGroup
            v-else-if="displayRecords.length > 0"
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
            tag="div"
          >
            <div
              v-for="(record, index) in displayRecords"
              :key="record.id"
              class="group transition-all duration-150 flex items-center min-w-full"
              :class="[
                striped ? (index % 2 === 0 ? 'bg-card hover:bg-accent' : 'bg-muted hover:bg-accent') : 'bg-card hover:bg-accent',
                selectedRecords.has(record.id) && 'bg-primary/10 hover:bg-primary/15 ring-1 ring-inset ring-primary/30',
                draggedIndex === index && 'opacity-50',
                dragOverIndex === index && draggedIndex !== index && 'border-t-2 border-primary',
                record._url && 'cursor-pointer',
              ]"
              :draggable="reorderable"
              @dragstart="(e) => handleDragStart(e, index)"
              @dragover="(e) => handleDragOver(e, index)"
              @dragleave="handleDragLeave"
              @drop="(e) => handleDrop(e, index)"
              @dragend="handleDragEnd"
              @click="(e) => handleRowClick(e, record)"
            >
              <!-- Drag Handle (if reorderable) -->
              <div
                v-if="reorderable"
                class="flex items-center justify-center px-2 py-3.5 w-[40px] shrink-0 cursor-grab active:cursor-grabbing"
              >
                <GripVertical class="h-4 w-4 text-muted-foreground hover:text-foreground transition-colors" />
              </div>

              <!-- Checkbox Column (if bulk actions available) -->
              <div
                v-if="bulkActionsAvailable"
                class="flex items-center justify-center px-3 py-3.5 w-[52px] shrink-0"
              >
                <label class="inline-flex items-center">
                  <input
                    type="checkbox"
                    :checked="isRecordSelected(record.id)"
                    @change="() => toggleSelectRecord(record.id)"
                    class="peer size-4 shrink-0 appearance-none rounded border border-input bg-background shadow-sm ring-offset-background transition-all duration-150 hover:border-primary/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50 checked:border-primary checked:bg-primary checked:text-primary-foreground cursor-pointer"
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
                    'px-3 py-3.5 text-sm shrink-0',
                    getColumnWidthClass(column, columnIndex),
                    columnIndex === 0 ? 'font-medium text-foreground' : 'text-foreground/80',
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
                    :column-execution-route="columnExecutionRoute"
                    v-bind="column"
                  />
                </div>

                <!-- Inline Actions (when not fixed) -->
                <div v-if="!fixedActions && record._actions && record._actions.length > 0" class="px-3 py-3.5 flex items-center justify-end min-w-[160px] ml-auto shrink-0 record-actions" data-no-row-click>
                  <slot name="actions" :record="record">
                    <RecordActions
                      :actions="record._actions"
                      :record="record"
                      :resource-name="resourceSlug"
                      :model-class="modelClass"
                      :execution-route="executionRoute"
                      variant="inline"
                      @action-complete="(data) => emit('action-complete', data)"
                    />
                  </slot>
                </div>
              </div>

              <!-- Fixed Actions Cell -->
              <div
                v-if="fixedActions && record._actions && record._actions.length > 0"
                class="sticky end-0 z-20 border-s border-border transition-all duration-150 flex items-center justify-center gap-1.5 px-3 self-stretch shrink-0 record-actions"
                :class="[
                  striped ? (index % 2 === 0 ? 'bg-card group-hover:bg-accent' : 'bg-muted group-hover:bg-accent') : 'bg-card group-hover:bg-accent',
                  selectedRecords.has(record.id) && 'bg-primary/10 group-hover:bg-primary/15',
                ]"
                :style="{ width: actionsColumnWidth, minWidth: actionsColumnWidth }"
                data-no-row-click
              >
                <slot name="actions" :record="record">
                  <RecordActions
                    :actions="record._actions"
                    :record="record"
                    :resource-name="resourceSlug"
                    :model-class="modelClass"
                    :execution-route="executionRoute"
                    variant="inline"
                    @action-complete="(data) => emit('action-complete', data)"
                  />
                </slot>
              </div>
            </div>
          </TransitionGroup>
        </div>
      </div>
    </div> <!-- end v-else (table content) -->
  </div>
</template>

<style scoped>
/* Smooth transitions for row hover states */
.group {
  transition: background-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}
</style>

<!-- Global scrollbar styles (not scoped for proper pseudo-element support) -->
<style>
/* Custom scrollbar for DataTable - thin and elegant */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
    margin: 4px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: hsl(var(--muted-foreground) / 0.3);
    border-radius: 9999px;
    transition: background 0.15s ease;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: hsl(var(--muted-foreground) / 0.5);
}

/* Dark mode scrollbar */
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: hsl(var(--muted-foreground) / 0.25);
}

.dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: hsl(var(--muted-foreground) / 0.4);
}

/* Firefox scrollbar */
.custom-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: hsl(var(--muted-foreground) / 0.3) transparent;
}

.dark .custom-scrollbar {
    scrollbar-color: hsl(var(--muted-foreground) / 0.25) transparent;
}

/* Scrollbar corner */
.custom-scrollbar::-webkit-scrollbar-corner {
    background: transparent;
}
</style>
