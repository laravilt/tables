<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { Inbox, Clock, User, FileText, Calendar, Eye, Pencil, Package, Star } from 'lucide-vue-next'
import * as LucideIcons from 'lucide-vue-next'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import TextGridColumn from './grid-columns/TextGridColumn.vue'
import ImageGridColumn from './grid-columns/ImageGridColumn.vue'
import ColorGridColumn from './grid-columns/ColorGridColumn.vue'
import IconGridColumn from './grid-columns/IconGridColumn.vue'
import ToggleGridColumn from './grid-columns/ToggleGridColumn.vue'
import RecordActions from '@laravilt/actions/components/RecordActions.vue'
import { Card, CardContent, CardFooter, CardHeader } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'
import { Checkbox } from '@/components/ui/checkbox'

interface CardGridProps {
    grid: any
    records: any[]
    recordActions?: any[]
    loading?: boolean
    loadingMore?: boolean
    bulkActionsAvailable?: boolean
    resourceSlug: string
    modelClass?: string
    clearSelections?: number
}

const props = withDefaults(defineProps<CardGridProps>(), {
    loading: false,
    loadingMore: false,
    recordActions: () => [],
    bulkActionsAvailable: false,
    modelClass: undefined,
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

// Helper to get nested value using dot notation (e.g., 'category.name')
const getNestedValue = (record: any, field: string) => {
    if (!field) return null

    // First check if the flattened key exists (from backend processing)
    if (record[field] !== undefined) {
        return record[field]
    }

    // Otherwise, traverse nested objects
    if (field.includes('.')) {
        const parts = field.split('.')
        let value = record
        for (const part of parts) {
            if (value === null || value === undefined) return null
            value = value[part]
        }
        return value
    }

    return record[field]
}

const getImageUrl = (record: any) => {
    const imageField = props.grid.card?.imageField
    if (!imageField) return null
    return getNestedValue(record, imageField)
}

const getTitle = (record: any) => {
    const titleField = props.grid.card?.titleField
    if (!titleField) return null
    return getNestedValue(record, titleField)
}

const getSubtitle = (record: any) => {
    const subtitleField = props.grid.card?.subtitleField
    if (!subtitleField) return null
    return getNestedValue(record, subtitleField)
}

const getDescription = (record: any) => {
    const descriptionField = props.grid.card?.descriptionField
    if (!descriptionField) return null
    return getNestedValue(record, descriptionField)
}

const getPrice = (record: any) => {
    const priceField = props.grid.card?.priceField
    if (!priceField) return null
    return getNestedValue(record, priceField)
}

const getBadge = (record: any) => {
    const badgeField = props.grid.card?.badgeField
    if (!badgeField) return null
    return getNestedValue(record, badgeField)
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

// Get badge variant based on status value
const getBadgeVariant = (status: string) => {
    const variantMap: Record<string, 'default' | 'secondary' | 'destructive' | 'outline' | 'success' | 'warning'> = {
        'active': 'success',
        'draft': 'warning',
        'pending': 'warning',
        'out_of_stock': 'destructive',
        'archived': 'secondary',
        'inactive': 'secondary',
    }
    return variantMap[status] || 'default'
}

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

// Card style - determines which card template to use
const cardStyle = computed(() => {
    return props.grid.card?.style || 'default'
})

// Get relative time from created_at or updated_at
const getRelativeTime = (record: any) => {
    const dateField = record.created_at || record.updated_at
    if (!dateField) return null

    const date = new Date(dateField)
    const now = new Date()
    const diffInSeconds = Math.floor((now.getTime() - date.getTime()) / 1000)

    if (diffInSeconds < 60) return 'Just now'
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`
    if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)}d ago`
    if (diffInSeconds < 2592000) return `${Math.floor(diffInSeconds / 604800)}w ago`
    return date.toLocaleDateString()
}

// Get avatar initials from title
const getAvatarInitials = (record: any) => {
    const title = getTitle(record)
    if (!title) return '?'
    const words = title.split(' ')
    if (words.length >= 2) {
        return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase()
    }
    return title.substring(0, 2).toUpperCase()
}

// Get avatar color based on record id or title
const getAvatarColor = (record: any) => {
    const colors = [
        'bg-red-500',
        'bg-orange-500',
        'bg-amber-500',
        'bg-yellow-500',
        'bg-lime-500',
        'bg-green-500',
        'bg-emerald-500',
        'bg-teal-500',
        'bg-cyan-500',
        'bg-sky-500',
        'bg-blue-500',
        'bg-indigo-500',
        'bg-violet-500',
        'bg-purple-500',
        'bg-fuchsia-500',
        'bg-pink-500',
        'bg-rose-500',
    ]
    const index = (record.id || 0) % colors.length
    return colors[index]
}

// Get badge color class based on status
const getBadgeColorClass = (status: string) => {
    const colorMap: Record<string, string> = {
        'active': 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20',
        'published': 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20',
        'approved': 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20',
        'completed': 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20',
        'draft': 'bg-amber-500/10 text-amber-600 border-amber-500/20',
        'pending': 'bg-amber-500/10 text-amber-600 border-amber-500/20',
        'processing': 'bg-blue-500/10 text-blue-600 border-blue-500/20',
        'in_progress': 'bg-blue-500/10 text-blue-600 border-blue-500/20',
        'inactive': 'bg-slate-500/10 text-slate-600 border-slate-500/20',
        'archived': 'bg-slate-500/10 text-slate-600 border-slate-500/20',
        'cancelled': 'bg-red-500/10 text-red-600 border-red-500/20',
        'rejected': 'bg-red-500/10 text-red-600 border-red-500/20',
        'failed': 'bg-red-500/10 text-red-600 border-red-500/20',
        'out_of_stock': 'bg-red-500/10 text-red-600 border-red-500/20',
    }
    return colorMap[status] || 'bg-primary/10 text-primary border-primary/20'
}

// Get status icon based on status
const getStatusIcon = (status: string) => {
    const iconMap: Record<string, any> = {
        'active': LucideIcons.CheckCircle2,
        'published': LucideIcons.CheckCircle2,
        'approved': LucideIcons.CheckCircle2,
        'completed': LucideIcons.CheckCircle2,
        'draft': LucideIcons.PenLine,
        'pending': LucideIcons.Clock,
        'processing': LucideIcons.Loader2,
        'in_progress': LucideIcons.Play,
        'inactive': LucideIcons.Circle,
        'archived': LucideIcons.Archive,
        'cancelled': LucideIcons.XCircle,
        'rejected': LucideIcons.XCircle,
        'failed': LucideIcons.AlertCircle,
        'out_of_stock': LucideIcons.AlertTriangle,
    }
    return iconMap[status] || LucideIcons.Circle
}

// Handle card click to navigate to record URL
const handleCardClick = (event: MouseEvent, record: any) => {
    // Don't navigate if there's no URL
    if (!record._url) return

    // Don't navigate if clicking on interactive elements
    const target = event.target as HTMLElement
    const interactiveElements = ['A', 'BUTTON', 'INPUT', 'SELECT', 'TEXTAREA', 'LABEL']

    // Check if click is on or inside an interactive element
    let element: HTMLElement | null = target
    while (element) {
        if (interactiveElements.includes(element.tagName)) return
        if (element.hasAttribute('data-no-card-click')) return
        if (element.classList.contains('record-actions')) return
        element = element.parentElement
    }

    // Navigate using Inertia
    router.visit(record._url)
}
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
        <div v-if="isLoading && !loadingMore" class="grid gap-4" :class="gridColsClass">
            <!-- Product Card Skeleton -->
            <template v-if="cardStyle === 'product'">
                <div
                    v-for="i in skeletonCount"
                    :key="`skeleton-product-${i}`"
                    class="overflow-hidden bg-card border rounded-xl shadow-sm flex flex-col"
                >
                    <!-- Product Image Skeleton -->
                    <Skeleton class="aspect-square w-full" />

                    <!-- Product Info Skeleton -->
                    <div class="p-4 flex-1 flex flex-col">
                        <!-- Category / SKU -->
                        <div class="flex items-center gap-2 mb-2">
                            <Skeleton class="h-3 w-16" />
                            <Skeleton class="h-3 w-20" />
                        </div>

                        <!-- Title -->
                        <Skeleton class="h-4 w-full mb-1" />
                        <Skeleton class="h-4 w-3/4 mb-2" />

                        <!-- Description -->
                        <Skeleton class="h-3 w-full mb-1" />
                        <Skeleton class="h-3 w-2/3 mb-3" />

                        <!-- Rating -->
                        <div class="flex items-center gap-1.5 mb-3">
                            <div class="flex gap-0.5">
                                <Skeleton v-for="s in 5" :key="s" class="h-3.5 w-3.5 rounded-sm" />
                            </div>
                            <Skeleton class="h-3 w-8" />
                        </div>

                        <!-- Price & Stock -->
                        <div class="mt-auto pt-3 border-t border-border/50">
                            <div class="flex items-end justify-between">
                                <Skeleton class="h-6 w-20" />
                                <div class="flex items-center gap-1.5">
                                    <Skeleton class="h-2 w-2 rounded-full" />
                                    <Skeleton class="h-3 w-16" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions Footer Skeleton -->
                    <div class="border-t bg-muted/30 px-4 py-3 flex items-center justify-center gap-2">
                        <Skeleton class="h-9 w-20 rounded-md" />
                        <Skeleton class="h-9 w-20 rounded-md" />
                        <Skeleton class="h-9 w-20 rounded-md" />
                    </div>
                </div>
            </template>

            <!-- Simple Card Skeleton (default) -->
            <template v-else>
                <div
                    v-for="i in skeletonCount"
                    :key="`skeleton-simple-${i}`"
                    class="overflow-hidden bg-card border rounded-lg flex flex-col"
                >
                    <!-- Main Card Content Skeleton -->
                    <div class="p-5 flex-1">
                        <!-- Header: Checkbox + ID -->
                        <div class="flex items-center gap-2 mb-4 pb-3 border-b border-border/50">
                            <Skeleton class="h-5 w-5 rounded shrink-0" />
                            <Skeleton class="h-4 w-14 rounded" />
                        </div>

                        <!-- Body: Avatar + Info (centered) -->
                        <div class="flex flex-col items-center gap-4">
                            <!-- Avatar Skeleton -->
                            <Skeleton class="h-16 w-16 rounded-full shrink-0" />

                            <!-- Title and Description Skeleton -->
                            <div class="w-full space-y-3 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <Skeleton class="h-4 w-4 rounded shrink-0" />
                                    <Skeleton class="h-5 w-3/4 max-w-[200px]" />
                                </div>
                                <div class="flex items-center justify-center gap-2">
                                    <Skeleton class="h-4 w-4 rounded shrink-0" />
                                    <Skeleton class="h-4 w-full max-w-[250px]" />
                                </div>
                            </div>
                        </div>

                        <!-- Meta Row Skeleton -->
                        <div class="flex items-center justify-between gap-3 mt-4 pt-4 border-t border-border/50">
                            <!-- Status Badge Skeleton -->
                            <Skeleton class="h-7 w-24 rounded-full" />
                            <!-- Timestamp Skeleton -->
                            <Skeleton class="h-7 w-20 rounded-full" />
                        </div>
                    </div>

                    <!-- Actions Footer Skeleton -->
                    <div class="border-t bg-muted/40 px-5 py-3 flex items-center justify-center gap-3">
                        <Skeleton class="h-9 w-20 rounded-md" />
                        <Skeleton class="h-9 w-20 rounded-md" />
                        <Skeleton class="h-9 w-20 rounded-md" />
                    </div>
                </div>
            </template>
        </div>

        <!-- Grid Content with records (+ loading more skeletons at bottom) -->
        <div v-else-if="records.length > 0" class="grid gap-4" :class="gridColsClass">
            <!-- ================================ -->
            <!-- SIMPLE CARD STYLE -->
            <!-- User card design with avatar, info, status and timestamp -->
            <!-- ================================ -->
            <div
                v-for="record in records"
                v-if="cardStyle === 'simple'"
                :key="record.id"
                class="group relative overflow-hidden transition-all duration-300 flex flex-col bg-card border rounded-lg"
                :class="{
                    'hover:border-primary/40': grid.card?.hoverable !== false,
                    'ring-2 ring-primary ring-offset-2 ring-offset-background border-primary/50': isSelected(record.id),
                    'cursor-pointer': record._url
                }"
                @click="(e) => handleCardClick(e, record)"
            >
                <!-- Main Card Content -->
                <div class="p-5 flex-1">
                    <!-- Top Header: Checkbox + ID -->
                    <div class="flex items-center gap-2 mb-4 pb-3 border-b border-border/50">
                        <!-- Selection Checkbox -->
                        <div
                            v-if="bulkActionsAvailable"
                            @click.stop="handleSelectRecord(record.id)"
                        >
                            <div
                                :class="[
                                    'h-5 w-5 rounded border-2 flex items-center justify-center cursor-pointer transition-all duration-200',
                                    isSelected(record.id)
                                        ? 'bg-primary border-primary'
                                        : 'border-muted-foreground/40 hover:border-primary/60 bg-background'
                                ]"
                            >
                                <svg
                                    v-if="isSelected(record.id)"
                                    class="h-3.5 w-3.5 text-primary-foreground"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="3"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </div>
                        <!-- Record ID -->
                        <span class="text-xs font-mono text-muted-foreground/70 select-none">#{{ record.id }}</span>
                    </div>

                    <!-- Body: Avatar + Info (centered horizontally) -->
                    <div class="flex flex-col items-center gap-4">
                        <!-- Avatar with Status Indicator -->
                        <div class="relative shrink-0">
                            <Avatar class="h-16 w-16 ring-2 ring-border shadow-lg">
                                <AvatarImage v-if="getImageUrl(record)" :src="getImageUrl(record)" :alt="getTitle(record)" />
                                <AvatarFallback :class="[getAvatarColor(record), 'text-white font-bold text-xl']">
                                    {{ getAvatarInitials(record) }}
                                </AvatarFallback>
                            </Avatar>
                            <!-- Online/Status Indicator Dot -->
                            <div
                                v-if="getBadge(record)"
                                :class="[
                                    'absolute bottom-0 right-0 h-4 w-4 rounded-full border-2 border-card shadow-sm',
                                    getBadge(record) === 'active' || getBadge(record) === 'published' ? 'bg-emerald-500' :
                                    getBadge(record) === 'pending' || getBadge(record) === 'draft' ? 'bg-amber-500' :
                                    getBadge(record) === 'inactive' || getBadge(record) === 'archived' ? 'bg-slate-400' :
                                    'bg-red-500'
                                ]"
                            ></div>
                        </div>

                        <!-- Title and Description (centered) -->
                        <div class="w-full text-center space-y-2">
                            <div class="flex items-center justify-center gap-2">
                                <User class="h-4 w-4 text-primary shrink-0" />
                                <h3 v-if="getTitle(record)" class="text-base font-bold text-foreground truncate">
                                    {{ getTitle(record) }}
                                </h3>
                            </div>
                            <div v-if="getDescription(record)" class="flex items-center justify-center gap-2">
                                <FileText class="h-4 w-4 text-muted-foreground shrink-0" />
                                <p class="text-sm text-muted-foreground line-clamp-2">
                                    {{ getDescription(record) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Meta Row: Status Badge + Timestamp -->
                    <div class="flex items-center justify-between gap-3 mt-4 pt-4 border-t border-border/50">
                        <!-- Status Badge -->
                        <div
                            v-if="getBadge(record)"
                            :class="[
                                'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border shadow-sm',
                                getBadgeColorClass(getBadge(record))
                            ]"
                        >
                            <component
                                :is="getStatusIcon(getBadge(record))"
                                class="h-3.5 w-3.5"
                            />
                            <span>{{ formatBadgeText(getBadge(record)) }}</span>
                        </div>
                        <div v-else class="flex-1"></div>

                        <!-- Timestamp with Calendar Icon -->
                        <div v-if="getRelativeTime(record)" class="flex items-center gap-1.5 text-xs text-muted-foreground bg-muted px-3 py-1.5 rounded-full">
                            <Calendar class="h-3.5 w-3.5" />
                            <span class="font-medium">{{ getRelativeTime(record) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Actions Footer (CENTERED) -->
                <div
                    v-if="record._actions && record._actions.length > 0"
                    class="border-t bg-muted/40 px-5 py-3 flex items-center justify-center gap-3 record-actions"
                    data-no-card-click
                >
                    <RecordActions
                        :actions="record._actions"
                        :record="record"
                        :resource-name="resourceSlug"
                        :model-class="modelClass"
                        variant="inline"
                        gap="default"
                    />
                </div>
            </div>

            <!-- ================================ -->
            <!-- MEDIA CARD STYLE -->
            <!-- Full background image with gradient overlay -->
            <!-- ================================ -->
            <div
                v-for="record in records"
                v-else-if="cardStyle === 'media'"
                :key="record.id"
                class="group relative overflow-hidden rounded-xl transition-all duration-300 flex flex-col"
                :class="{
                    'hover:scale-[1.02]': grid.card?.hoverable !== false,
                    'ring-2 ring-primary ring-offset-2 ring-offset-background': isSelected(record.id),
                    'cursor-pointer': record._url
                }"
                @click="(e) => handleCardClick(e, record)"
            >
                <!-- Background Image -->
                <div class="relative aspect-[16/10] overflow-hidden bg-muted">
                    <img
                        v-if="getImageUrl(record)"
                        :src="getImageUrl(record)"
                        :alt="getTitle(record) || 'Media image'"
                        class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                    />
                    <div v-else class="absolute inset-0 bg-gradient-to-br from-primary/20 to-primary/5 flex items-center justify-center">
                        <span class="text-4xl font-bold text-primary/30">{{ getTitle(record)?.charAt(0) || '?' }}</span>
                    </div>

                    <!-- Gradient Overlay -->
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent" />

                    <!-- Selection Checkbox (top-left) -->
                    <div v-if="bulkActionsAvailable" class="absolute top-3 left-3 z-10">
                        <Checkbox
                            :checked="isSelected(record.id)"
                            @update:checked="() => handleSelectRecord(record.id)"
                            class="border-white/50 data-[state=checked]:bg-primary data-[state=checked]:border-primary"
                        />
                    </div>

                    <!-- Badge (top-right) -->
                    <div v-if="getBadge(record)" class="absolute top-3 right-3 z-10">
                        <Badge :variant="getBadgeVariant(getBadge(record))" class="gap-1 text-xs shadow-lg">
                            <component
                                v-if="getBadgeIconComponent(record)"
                                :is="getBadgeIconComponent(record)"
                                class="h-3 w-3"
                            />
                            {{ formatBadgeText(getBadge(record)) }}
                        </Badge>
                    </div>

                    <!-- Content Overlay (bottom) -->
                    <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
                        <!-- Title -->
                        <h3 v-if="getTitle(record)" class="text-lg font-bold leading-tight line-clamp-2 mb-1 drop-shadow-md">
                            {{ getTitle(record) }}
                        </h3>

                        <!-- Description -->
                        <p v-if="getDescription(record)" class="text-sm text-white/80 line-clamp-2 drop-shadow-sm">
                            {{ getDescription(record) }}
                        </p>
                    </div>
                </div>

                <!-- Actions Bar -->
                <div
                    v-if="record._actions && record._actions.length > 0"
                    class="flex items-center justify-center gap-2 bg-card border border-t-0 rounded-b-xl p-3 record-actions"
                    data-no-card-click
                >
                    <RecordActions
                        :actions="record._actions"
                        :record="record"
                        :resource-name="resourceSlug"
                        :model-class="modelClass"
                        variant="inline"
                        gap="default"
                    />
                </div>
            </div>

            <!-- ================================ -->
            <!-- PRODUCT CARD STYLE -->
            <!-- E-commerce style with image, structured info, and price -->
            <!-- ================================ -->
            <div
                v-for="record in records"
                v-else-if="cardStyle === 'product'"
                :key="record.id"
                class="group relative overflow-hidden transition-all duration-300 flex flex-col bg-card border rounded-xl shadow-sm"
                :class="{
                    'hover:shadow-lg hover:border-primary/30 hover:-translate-y-1': grid.card?.hoverable !== false,
                    'ring-2 ring-primary ring-offset-2 ring-offset-background border-primary/50': isSelected(record.id),
                    'cursor-pointer': record._url
                }"
                @click="(e) => handleCardClick(e, record)"
            >
                <!-- Selection Checkbox (floating) -->
                <div v-if="bulkActionsAvailable" class="absolute top-3 left-3 z-20">
                    <div
                        @click.stop="handleSelectRecord(record.id)"
                        :class="[
                            'h-5 w-5 rounded border-2 flex items-center justify-center cursor-pointer transition-all duration-200 shadow-sm',
                            isSelected(record.id)
                                ? 'bg-primary border-primary'
                                : 'border-white/80 hover:border-primary/60 bg-white/90 backdrop-blur-sm'
                        ]"
                    >
                        <svg
                            v-if="isSelected(record.id)"
                            class="h-3.5 w-3.5 text-primary-foreground"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="3"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </div>

                <!-- Badge (floating top-right) -->
                <div v-if="getBadge(record)" class="absolute top-3 right-3 z-20">
                    <div
                        :class="[
                            'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide shadow-md',
                            getBadgeColorClass(getBadge(record))
                        ]"
                    >
                        <component
                            :is="getStatusIcon(getBadge(record))"
                            class="h-3 w-3"
                        />
                        <span>{{ formatBadgeText(getBadge(record)) }}</span>
                    </div>
                </div>

                <!-- Product Image Container -->
                <div v-if="grid.card?.showImage !== false" class="relative overflow-hidden bg-gradient-to-br from-muted/50 to-muted aspect-square">
                    <img
                        v-if="getImageUrl(record)"
                        :src="getImageUrl(record)"
                        :alt="getTitle(record) || 'Product image'"
                        class="w-full h-full object-cover transition-all duration-500 group-hover:scale-110"
                    />
                    <div v-else class="w-full h-full flex items-center justify-center">
                        <div class="text-center">
                            <Package class="h-16 w-16 text-muted-foreground/30 mx-auto mb-2" />
                            <span class="text-sm text-muted-foreground/50">No Image</span>
                        </div>
                    </div>

                </div>

                <!-- Product Info -->
                <div class="flex-1 p-4 flex flex-col">
                    <!-- Subtitle (e.g., Category) -->
                    <div v-if="getSubtitle(record)" class="flex items-center gap-2 mb-2">
                        <span class="text-[11px] font-medium text-primary uppercase tracking-wide">
                            {{ getSubtitle(record) }}
                        </span>
                    </div>

                    <!-- Title -->
                    <h3 v-if="getTitle(record)" class="text-sm font-semibold leading-snug text-foreground line-clamp-2 mb-2 group-hover:text-primary transition-colors">
                        {{ getTitle(record) }}
                    </h3>

                    <!-- Description -->
                    <p v-if="getDescription(record)" class="text-xs text-muted-foreground line-clamp-2 mb-3 flex-1">
                        {{ getDescription(record) }}
                    </p>

                    <!-- Rating -->
                    <div v-if="record.rating" class="flex items-center gap-1.5 mb-3">
                        <div class="flex items-center">
                            <Star
                                v-for="i in 5"
                                :key="i"
                                :class="[
                                    'h-3.5 w-3.5',
                                    i <= Math.round(record.rating) ? 'text-amber-400 fill-amber-400' : 'text-muted-foreground/30'
                                ]"
                            />
                        </div>
                        <span class="text-xs text-muted-foreground">({{ record.review_count || 0 }})</span>
                    </div>

                    <!-- Price & Stock Section -->
                    <div class="mt-auto pt-3 border-t border-border/50">
                        <div class="flex items-end justify-between gap-2">
                            <!-- Price -->
                            <div v-if="getPrice(record)" class="flex flex-col">
                                <span class="text-lg font-bold text-foreground">
                                    {{ typeof getPrice(record) === 'number' ? `$${getPrice(record).toFixed(2)}` : getPrice(record) }}
                                </span>
                            </div>

                            <!-- Stock Status -->
                            <div v-if="record.stock_quantity !== undefined" class="flex items-center gap-1.5">
                                <div
                                    :class="[
                                        'h-2 w-2 rounded-full',
                                        record.stock_quantity > 10 ? 'bg-emerald-500' :
                                        record.stock_quantity > 0 ? 'bg-amber-500' : 'bg-red-500'
                                    ]"
                                ></div>
                                <span
                                    :class="[
                                        'text-xs font-medium',
                                        record.stock_quantity > 10 ? 'text-emerald-600' :
                                        record.stock_quantity > 0 ? 'text-amber-600' : 'text-red-600'
                                    ]"
                                >
                                    {{ record.stock_quantity > 0 ? `${record.stock_quantity} in stock` : 'Out of stock' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions Footer -->
                <div
                    v-if="record._actions && record._actions.length > 0 && grid.card?.actionsPosition === 'bottom'"
                    class="border-t bg-muted/30 px-4 py-3 flex items-center justify-center gap-2 record-actions"
                    data-no-card-click
                >
                    <RecordActions
                        :actions="record._actions"
                        :record="record"
                        :resource-name="resourceSlug"
                        :model-class="modelClass"
                        variant="inline"
                        gap="default"
                    />
                </div>
            </div>

            <!-- ================================ -->
            <!-- DEFAULT CARD STYLE (Fallback) -->
            <!-- Used when no specific style or when style is unrecognized -->
            <!-- ================================ -->
            <Card
                v-for="record in records"
                v-else
                :key="record.id"
                class="group relative overflow-hidden transition-all duration-200 flex flex-col"
                :class="{
                    'hover:border-primary/30': grid.card?.hoverable !== false,
                    'ring-2 ring-primary ring-offset-2 ring-offset-background': isSelected(record.id),
                    'cursor-pointer': record._url
                }"
                @click="(e) => handleCardClick(e, record)"
            >
                <!-- Card Header with Checkbox -->
                <CardHeader v-if="bulkActionsAvailable || getTitle(record)" class="flex-row items-start gap-3 space-y-0 pb-3">
                    <Checkbox
                        v-if="bulkActionsAvailable"
                        :checked="isSelected(record.id)"
                        @update:checked="() => handleSelectRecord(record.id)"
                        class="mt-1"
                    />
                    <div class="flex-1 min-w-0">
                        <h3 v-if="getTitle(record)" class="text-sm font-semibold text-foreground line-clamp-2">
                            {{ getTitle(record) }}
                        </h3>
                        <span v-else class="text-sm text-muted-foreground">Record #{{ record.id }}</span>
                    </div>
                    <Badge v-if="getBadge(record)" :variant="getBadgeVariant(getBadge(record))" class="shrink-0">
                        {{ formatBadgeText(getBadge(record)) }}
                    </Badge>
                </CardHeader>

                <CardContent :class="[cardGap, 'flex-1']">
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
                        :default-image-url="record._defaultImageUrls?.[column.name] ?? column.defaultImageUrl"
                        :resource-slug="resourceSlug"
                    />
                </CardContent>

                <!-- Actions Footer -->
                <CardFooter
                    v-if="record._actions && record._actions.length > 0"
                    class="flex items-center justify-center gap-2 border-t pt-4 record-actions"
                    data-no-card-click
                >
                    <RecordActions
                        :actions="record._actions"
                        :record="record"
                        :resource-name="resourceSlug"
                        :model-class="modelClass"
                        variant="inline"
                        gap="default"
                    />
                </CardFooter>
            </Card>

            <!-- Loading more skeleton cards (shown at bottom while infinite scrolling) -->
            <!-- Product Card Skeleton for loadingMore -->
            <template v-if="loadingMore && cardStyle === 'product'">
                <div
                    v-for="i in 12"
                    :key="`loading-more-product-${i}`"
                    class="overflow-hidden bg-card border rounded-xl shadow-sm flex flex-col"
                >
                    <Skeleton class="aspect-square w-full" />
                    <div class="p-4 flex-1 flex flex-col">
                        <div class="flex items-center gap-2 mb-2">
                            <Skeleton class="h-3 w-16" />
                            <Skeleton class="h-3 w-20" />
                        </div>
                        <Skeleton class="h-4 w-full mb-1" />
                        <Skeleton class="h-4 w-3/4 mb-2" />
                        <Skeleton class="h-3 w-full mb-1" />
                        <Skeleton class="h-3 w-2/3 mb-3" />
                        <div class="flex items-center gap-1.5 mb-3">
                            <div class="flex gap-0.5">
                                <Skeleton v-for="s in 5" :key="s" class="h-3.5 w-3.5 rounded-sm" />
                            </div>
                            <Skeleton class="h-3 w-8" />
                        </div>
                        <div class="mt-auto pt-3 border-t border-border/50">
                            <div class="flex items-end justify-between">
                                <Skeleton class="h-6 w-20" />
                                <div class="flex items-center gap-1.5">
                                    <Skeleton class="h-2 w-2 rounded-full" />
                                    <Skeleton class="h-3 w-16" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="border-t bg-muted/30 px-4 py-3 flex items-center justify-center gap-2">
                        <Skeleton class="h-9 w-20 rounded-md" />
                        <Skeleton class="h-9 w-20 rounded-md" />
                        <Skeleton class="h-9 w-20 rounded-md" />
                    </div>
                </div>
            </template>

            <!-- Simple Card Skeleton for loadingMore -->
            <template v-else-if="loadingMore">
                <div
                    v-for="i in 12"
                    :key="`loading-more-simple-${i}`"
                    class="overflow-hidden bg-card border rounded-lg flex flex-col"
                >
                    <div class="p-5 flex-1">
                        <div class="flex items-center gap-2 mb-4 pb-3 border-b border-border/50">
                            <Skeleton class="h-5 w-5 rounded shrink-0" />
                            <Skeleton class="h-4 w-14 rounded" />
                        </div>
                        <div class="flex flex-col items-center gap-4">
                            <Skeleton class="h-16 w-16 rounded-full shrink-0" />
                            <div class="w-full space-y-3 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <Skeleton class="h-4 w-4 rounded shrink-0" />
                                    <Skeleton class="h-5 w-3/4 max-w-[200px]" />
                                </div>
                                <div class="flex items-center justify-center gap-2">
                                    <Skeleton class="h-4 w-4 rounded shrink-0" />
                                    <Skeleton class="h-4 w-full max-w-[250px]" />
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-3 mt-4 pt-4 border-t border-border/50">
                            <Skeleton class="h-7 w-24 rounded-full" />
                            <Skeleton class="h-7 w-20 rounded-full" />
                        </div>
                    </div>
                    <div class="border-t bg-muted/40 px-5 py-3 flex items-center justify-center gap-3">
                        <Skeleton class="h-9 w-20 rounded-md" />
                        <Skeleton class="h-9 w-20 rounded-md" />
                        <Skeleton class="h-9 w-20 rounded-md" />
                    </div>
                </div>
            </template>
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
