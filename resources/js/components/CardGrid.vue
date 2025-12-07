<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Inbox } from 'lucide-vue-next'
import * as LucideIcons from 'lucide-vue-next'
import TextGridColumn from './grid-columns/TextGridColumn.vue'
import ImageGridColumn from './grid-columns/ImageGridColumn.vue'
import ColorGridColumn from './grid-columns/ColorGridColumn.vue'
import IconGridColumn from './grid-columns/IconGridColumn.vue'
import ToggleGridColumn from './grid-columns/ToggleGridColumn.vue'
import RecordActions from '@laravilt/actions/components/RecordActions.vue'

interface CardGridProps {
    grid: any
    records: any[]
    recordActions?: any[]
    loading?: boolean
    loadingMore?: boolean
    bulkActionsAvailable?: boolean
    resourceSlug: string
    clearSelections?: number
}

const props = withDefaults(defineProps<CardGridProps>(), {
    loading: false,
    loadingMore: false,
    recordActions: () => [],
    bulkActionsAvailable: false,
    clearSelections: 0,
})

const emit = defineEmits<{
    'update:selectedRecords': [records: (number | string)[]]
}>()

const selectedRecords = ref<Set<number | string>>(new Set())
const selectAll = ref(false)

// Use computed for loading state - directly reactive to props
const isLoading = computed(() => {
    // If loading more (infinite scroll), don't show full skeleton - show records + skeleton at bottom
    if (props.loadingMore) return false

    // If explicitly loading (filtering, searching, initial load), show skeleton
    if (props.loading) return true

    // Not loading - show records or empty state
    return false
})

const handleSelectAll = (event: Event) => {
    const target = event.target as HTMLInputElement
    selectAll.value = target.checked

    if (target.checked) {
        selectedRecords.value = new Set(props.records.map(r => r.id))
    } else {
        selectedRecords.value.clear()
    }
    emit('update:selectedRecords', Array.from(selectedRecords.value))
}

const handleSelectRecord = (recordId: number | string) => {
    if (selectedRecords.value.has(recordId)) {
        selectedRecords.value.delete(recordId)
    } else {
        selectedRecords.value.add(recordId)
    }
    selectAll.value = selectedRecords.value.size === props.records.length
    emit('update:selectedRecords', Array.from(selectedRecords.value))
}

const isSelected = (recordId: number | string) => {
    return selectedRecords.value.has(recordId)
}

// Watch for clear selections signal
watch(() => props.clearSelections, () => {
    selectedRecords.value.clear()
    selectAll.value = false
})

const getColumnComponent = (columnType: string) => {
    const components: Record<string, any> = {
        'text_grid_column': TextGridColumn,
        'image_grid_column': ImageGridColumn,
        'color_grid_column': ColorGridColumn,
        'icon_grid_column': IconGridColumn,
        'toggle_grid_column': ToggleGridColumn,
    }
    return components[columnType] || TextGridColumn
}

const gridColumns = computed(() => {
    // Priority 1: Use card-specific schema if defined
    if (props.grid.card?.schema && props.grid.card.schema.length > 0) {
        return props.grid.card.schema
    }

    // Priority 2: Use card-specific columns if defined
    if (props.grid.card?.columns && props.grid.card.columns.length > 0) {
        return props.grid.card.columns
    }

    // Priority 3: If using card builder fields, filter out the fields that are already displayed
    if (props.grid.card?.imageField || props.grid.card?.titleField || props.grid.card?.priceField || props.grid.card?.descriptionField || props.grid.card?.badgeField) {
        const usedFields = [
            props.grid.card?.imageField,
            props.grid.card?.titleField,
            props.grid.card?.priceField,
            props.grid.card?.descriptionField,
            props.grid.card?.badgeField
        ].filter(Boolean)

        return props.grid.columns.filter(column => !usedFields.includes(column.name))
    }

    // Priority 4: Use grid columns as fallback
    return props.grid.columns
})

// Card styling computed properties
const cardPadding = computed(() => {
    const padding = props.grid.card?.padding
    switch (padding) {
        case 'sm': return 'p-3'
        case 'md': return 'p-4'
        case 'lg': return 'p-6'
        case 'xl': return 'p-8'
        default: return 'p-6'
    }
})

const cardGap = computed(() => {
    const gap = props.grid.card?.gap
    switch (gap) {
        case 'sm': return 'space-y-2'
        case 'md': return 'space-y-3'
        case 'lg': return 'space-y-4'
        default: return 'space-y-3'
    }
})

const getImageUrl = (record: any) => {
    const imageField = props.grid.card?.imageField
    if (!imageField) return null
    return record[imageField]
}

const getTitle = (record: any) => {
    const titleField = props.grid.card?.titleField
    if (!titleField) return null
    return record[titleField]
}

const getDescription = (record: any) => {
    const descriptionField = props.grid.card?.descriptionField
    if (!descriptionField) return null
    return record[descriptionField]
}

const getPrice = (record: any) => {
    const priceField = props.grid.card?.priceField
    if (!priceField) return null
    return record[priceField]
}

const getBadge = (record: any) => {
    const badgeField = props.grid.card?.badgeField
    if (!badgeField) return null
    return record[badgeField]
}

const getBadgeIcon = (record: any) => {
    const badgeField = props.grid.card?.badgeField
    if (!badgeField) return null
    return record._icons?.[badgeField] || null
}

const formatBadgeText = (text: string) => {
    if (!text) return ''
    // Replace underscores with spaces and convert to title case
    return text
        .replace(/_/g, ' ')
        .split(' ')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
        .join(' ')
}

const getBadgeIconComponent = (record: any) => {
    const iconName = getBadgeIcon(record)
    if (!iconName) return null

    // Icon mapping from Heroicons to Lucide
    const iconMap: Record<string, string> = {
        'heroicon-o-check-circle': 'CheckCircle',
        'heroicon-o-x-circle': 'XCircle',
        'heroicon-o-exclamation-circle': 'AlertCircle',
        'heroicon-o-information-circle': 'Info',
    }

    // Map from Heroicons or other icon sets to Lucide
    const mappedIconName = iconMap[iconName] || iconName

    // Convert to PascalCase for Lucide component lookup
    const pascalCaseName = mappedIconName
        .split('-')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join('')

    return (LucideIcons as any)[pascalCaseName] || null
}

const skeletonCount = 12

// Dynamic grid columns based on cardsPerRow
const gridColsClass = computed(() => {
    const cols = props.grid?.cardsPerRow || 3
    const colsMap: Record<number, string> = {
        1: 'grid-cols-1',
        2: 'grid-cols-1 sm:grid-cols-2',
        3: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
        4: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4',
        5: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5',
        6: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6',
    }
    return colsMap[cols] || colsMap[3]
})
</script>

<template>
    <div class="p-6">
        <!-- Select All (when bulk actions available) -->
        <div v-if="bulkActionsAvailable && records.length > 0" class="mb-4 flex items-center gap-2">
            <label class="inline-flex items-center gap-2">
                <input
                    type="checkbox"
                    :checked="selectAll"
                    @change="handleSelectAll"
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
                <span class="text-sm font-medium text-foreground">
                    Select {{ selectedRecords.size }} of {{ records.length }} items
                </span>
            </label>
        </div>

        <!-- Loading State (show skeleton when loading initial data) -->
        <div v-if="isLoading && !loadingMore" class="grid gap-6" :class="gridColsClass">
            <div
                v-for="i in skeletonCount"
                :key="`skeleton-${i}`"
                class="rounded-xl border border-border bg-card overflow-hidden animate-pulse shadow-sm"
            >
                <div class="aspect-video bg-muted"></div>
                <div class="p-6 space-y-3">
                    <div class="h-4 bg-muted rounded w-3/4"></div>
                    <div class="h-3 bg-muted rounded w-1/2"></div>
                    <div class="h-3 bg-muted rounded w-full"></div>
                </div>
            </div>
        </div>

        <!-- Grid Content with records (+ loading more skeletons at bottom) -->
        <div v-else-if="records.length > 0" class="grid gap-6" :class="gridColsClass">
            <div
                v-for="record in records"
                :key="record.id"
                class="group relative rounded-xl border border-border bg-card overflow-hidden transition-all duration-200 shadow-sm flex flex-col"
                :class="{
                    'hover:shadow-md hover:border-primary/50 hover:-translate-y-1': grid.card?.hoverable !== false,
                    'ring-2 ring-primary ring-offset-2': isSelected(record.id)
                }"
            >
                <!-- Product Card Layout (when using builder fields) -->
                <template v-if="grid.card?.imageField || grid.card?.titleField">
                    <!-- Card Header with Title, Description and Checkbox -->
                    <div v-if="getTitle(record) || getDescription(record)" class="flex items-start gap-3 px-6 pt-6 pb-3 border-b border-border">
                        <!-- Selection Checkbox -->
                        <div v-if="bulkActionsAvailable" class="flex items-center justify-center pt-1">
                            <label class="inline-flex items-center cursor-pointer relative">
                                <input
                                    type="checkbox"
                                    :checked="isSelected(record.id)"
                                    @change="() => handleSelectRecord(record.id)"
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

                        <!-- Title and Description -->
                        <div class="flex-1 min-w-0">
                            <!-- Title -->
                            <h3 v-if="getTitle(record)" class="text-base font-semibold text-foreground line-clamp-2">
                                {{ getTitle(record) }}
                            </h3>

                            <!-- Description -->
                            <p v-if="getDescription(record)" class="text-sm text-muted-foreground line-clamp-2 mt-1">
                                {{ getDescription(record) }}
                            </p>
                        </div>

                        <!-- Badge -->
                        <div v-if="getBadge(record)" class="flex items-center justify-center pt-1">
                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold shadow-sm"
                                :class="{
                                    'bg-green-500 text-white': getBadge(record) === 'active',
                                    'bg-yellow-500 text-white': getBadge(record) === 'draft',
                                    'bg-red-500 text-white': getBadge(record) === 'out_of_stock',
                                    'bg-gray-500 text-white': getBadge(record) === 'archived' || getBadge(record) === 'inactive',
                                    'bg-primary text-primary-foreground': !['active', 'draft', 'out_of_stock', 'archived', 'inactive'].includes(getBadge(record))
                                }"
                            >
                                <component
                                    v-if="getBadgeIconComponent(record)"
                                    :is="getBadgeIconComponent(record)"
                                    class="h-3 w-3"
                                />
                                {{ formatBadgeText(getBadge(record)) }}
                            </span>
                        </div>
                    </div>

                    <!-- Card Image -->
                    <div v-if="grid.card?.showImage && getImageUrl(record)" class="relative overflow-hidden bg-muted">
                        <img
                            :src="getImageUrl(record)"
                            :alt="getTitle(record) || 'Product image'"
                            class="w-full h-56 object-cover transition-transform duration-300 group-hover:scale-105"
                            :class="{
                                'aspect-video': grid.card?.aspectRatio === '16/9',
                                'aspect-square': grid.card?.aspectRatio === '1/1',
                                'aspect-[3/2]': grid.card?.aspectRatio === '3/2',
                                'aspect-[4/3]': grid.card?.aspectRatio === '4/3',
                            }"
                        />
                    </div>

                    <!-- Card Body -->
                    <div :class="[cardPadding, 'flex flex-col flex-1']">

                        <!-- Additional columns -->
                        <div v-if="gridColumns.length > 0" :class="[cardGap, 'mb-3']">
                            <component
                                v-for="column in gridColumns"
                                :key="column.name"
                                :is="getColumnComponent(column.component)"
                                :column="column"
                                :record="record"
                                :value="record[column.name]"
                                :color="record._colors?.[column.name]"
                                :icon="record._icons?.[column.name]"
                                :size="record._sizes?.[column.name]"
                                :description="record._descriptions?.[column.name]"
                                :resource-slug="resourceSlug"
                            />
                        </div>

                        <!-- Price -->
                        <div v-if="getPrice(record)" class="mb-3 mt-auto">
                            <span class="text-xs text-muted-foreground uppercase tracking-wide">Price</span>
                            <div class="text-2xl font-bold text-foreground mt-0.5">
                                {{ typeof getPrice(record) === 'number' ? `$${(getPrice(record) / 100).toFixed(2)}` : getPrice(record) }}
                            </div>
                        </div>
                    </div>

                    <!-- Actions Footer (outside padding) -->
                    <div
                        v-if="record._actions && record._actions.length > 0"
                        class="flex items-center justify-center gap-2 px-6 py-3 border-t border-border"
                    >
                        <RecordActions
                            :actions="record._actions"
                            :record="record"
                            :resource-name="resourceSlug"
                            variant="inline"
                            gap="default"
                        />
                    </div>
                </template>

                <!-- Default Column Layout -->
                <template v-else>
                    <!-- Card Header with Checkbox for default layout -->
                    <div v-if="bulkActionsAvailable" class="flex items-start gap-3 px-6 pt-6 pb-3 border-b border-border">
                        <div class="flex items-center justify-center pt-1">
                            <label class="inline-flex items-center cursor-pointer relative">
                                <input
                                    type="checkbox"
                                    :checked="isSelected(record.id)"
                                    @change="() => handleSelectRecord(record.id)"
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
                        <div class="flex-1 min-w-0">
                            <span class="text-sm text-muted-foreground">Record #{{ record.id }}</span>
                        </div>
                    </div>

                    <div :class="[cardPadding, cardGap, 'flex-1']">
                        <component
                            v-for="column in gridColumns"
                            :key="column.name"
                            :is="getColumnComponent(column.component)"
                            :column="column"
                            :record="record"
                            :value="record[column.name]"
                            :color="record._colors?.[column.name]"
                            :icon="record._icons?.[column.name]"
                            :size="record._sizes?.[column.name]"
                            :description="record._descriptions?.[column.name]"
                            :resource-slug="resourceSlug"
                        />
                    </div>

                    <!-- Actions Footer (outside padding) -->
                    <div
                        v-if="record._actions && record._actions.length > 0"
                        class="flex items-center justify-center gap-2 px-6 py-3 border-t border-border"
                    >
                        <RecordActions
                            :actions="record._actions"
                            :record="record"
                            :resource-name="resourceSlug"
                            variant="inline"
                            gap="default"
                        />
                    </div>
                </template>
            </div>

            <!-- Loading more skeleton cards (shown at bottom while infinite scrolling) -->
            <div
                v-if="loadingMore"
                v-for="i in 12"
                :key="`loading-more-${i}`"
                class="rounded-xl border border-border bg-card overflow-hidden animate-pulse shadow-sm"
            >
                <div class="aspect-video bg-muted"></div>
                <div class="p-6 space-y-3">
                    <div class="h-4 bg-muted rounded w-3/4"></div>
                    <div class="h-3 bg-muted rounded w-1/2"></div>
                    <div class="h-3 bg-muted rounded w-full"></div>
                </div>
            </div>
        </div>

        <!-- Empty State (only when not loading and no records) -->
        <div v-else class="flex flex-col items-center justify-center py-16 text-center">
            <div class="rounded-full bg-muted p-4 mb-4">
                <Inbox class="h-10 w-10 text-muted-foreground" />
            </div>
            <h3 class="text-lg font-semibold text-foreground mb-2">
                {{ grid.emptyState?.heading || 'No records found' }}
            </h3>
            <p v-if="grid.emptyState?.description" class="text-sm text-muted-foreground max-w-sm">
                {{ grid.emptyState.description }}
            </p>
            <p v-else class="text-sm text-muted-foreground max-w-sm">
                There are no records to display. Try adjusting your filters or search query.
            </p>
        </div>
    </div>
</template>
