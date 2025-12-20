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

// Get column width style based on column config
const getColumnWidthStyle = (column: Column): Record<string, string> => {
  const style: Record<string, string> = {}

  // Only apply explicit width if developer specified it
  if (column.width) {
    // Support various formats: '200px', '20%', 200 (number)
    const width = typeof column.width === 'number' ? `${column.width}px` : column.width
    style.width = width
    style.minWidth = width
    style.maxWidth = width
    return style
  }

  // If column should grow, let it expand to fill remaining space
  if (column.grow) {
    style.flex = '1'
    return style
  }

  // Default: auto width - let content determine the width
  return style
}

// Get column width class (for additional CSS classes)
const getColumnWidthClass = (column: Column, index: number): string => {
  // Columns should not shrink below their content size
  return ''
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
    <table v-else class="w-full border-collapse">
      <!-- Table Header -->
      <thead class="bg-muted sticky top-0 z-10">
        <tr class="border-b border-border">
          <!-- Drag Handle Header (if reorderable) -->
          <th v-if="reorderable" class="w-[40px] px-2 py-3 bg-muted">
            <span class="sr-only">Reorder</span>
          </th>

          <!-- Checkbox Column (if bulk actions available) -->
          <th v-if="bulkActionsAvailable" class="w-[52px] bg-muted">
            <label class="inline-flex items-center justify-center p-3 cursor-pointer touch-manipulation">
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
          </th>

          <!-- Data Column Headers -->
          <th
            v-for="column in visibleColumnsFiltered"
            :key="`header-${column.name}`"
            :class="[
              'px-3 py-3 text-start text-xs font-semibold tracking-wide bg-muted whitespace-nowrap',
              column.sortable ? 'cursor-pointer select-none hover:bg-accent hover:text-foreground transition-all duration-150' : 'text-muted-foreground',
            ]"
            :style="getColumnWidthStyle(column)"
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
          </th>

          <!-- Actions Header -->
          <th
            v-if="hasRecordActions"
            class="px-3 py-3 text-end text-xs font-semibold tracking-wide bg-muted whitespace-nowrap"
            :class="fixedActions && 'sticky end-0 z-20 border-s border-border'"
          >
            <span class="text-muted-foreground">{{ trans('tables::tables.columns.actions') }}</span>
          </th>
        </tr>
      </thead>

      <!-- Table Body -->
      <tbody class="divide-y divide-border/50">
        <!-- Loading State -->
        <template v-if="loading">
          <tr
            v-for="i in skeletonRows"
            :key="`skeleton-${i}`"
            :class="[striped && i % 2 !== 0 ? 'bg-muted' : 'bg-card']"
          >
            <!-- Checkbox Skeleton -->
            <td v-if="bulkActionsAvailable" class="px-3 py-3.5 w-[52px]">
              <Skeleton class="h-4 w-4 rounded" />
            </td>

            <!-- Column Skeletons -->
            <td
              v-for="column in visibleColumnsFiltered"
              :key="`skeleton-col-${column.name}`"
              class="px-3 py-3.5"
              :style="getColumnWidthStyle(column)"
            >
              <Skeleton class="h-4 w-full max-w-[180px] rounded" />
            </td>

            <!-- Actions Skeleton -->
            <td v-if="hasRecordActions" class="px-3 py-3.5">
              <div class="flex items-center justify-end gap-1.5">
                <Skeleton v-for="n in maxActionsCount" :key="n" class="h-7 w-7 rounded-md" />
              </div>
            </td>
          </tr>
        </template>

        <!-- Data Rows -->
        <template v-else-if="displayRecords.length > 0">
          <tr
            v-for="(record, index) in displayRecords"
            :key="record.id"
            class="group transition-all duration-150"
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
            <!-- Drag Handle -->
            <td v-if="reorderable" class="w-[40px] px-2 py-3.5 cursor-grab active:cursor-grabbing">
              <GripVertical class="h-4 w-4 text-muted-foreground hover:text-foreground transition-colors" />
            </td>

            <!-- Checkbox Column -->
            <td v-if="bulkActionsAvailable" class="w-[52px]" data-no-row-click>
              <label class="inline-flex items-center justify-center p-3 cursor-pointer touch-manipulation">
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
            </td>

            <!-- Data Columns -->
            <td
              v-for="(column, columnIndex) in visibleColumnsFiltered"
              :key="`cell-${column.name}`"
              :class="[
                'px-3 py-3.5 text-sm',
                columnIndex === 0 ? 'font-medium text-foreground' : 'text-foreground/80',
              ]"
              :style="getColumnWidthStyle(column)"
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
            </td>

            <!-- Actions Column -->
            <td
              v-if="record._actions && record._actions.length > 0"
              class="px-3 py-3.5 record-actions"
              :class="fixedActions && 'sticky end-0 z-20 border-s border-border bg-inherit'"
              data-no-row-click
            >
              <div class="flex items-center justify-end gap-1">
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
            </td>
            <td v-else-if="hasRecordActions"></td>
          </tr>
        </template>
      </tbody>
    </table>
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
