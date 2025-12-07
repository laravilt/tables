<script setup lang="ts">
import { computed } from 'vue'
import * as LucideIcons from 'lucide-vue-next'

interface IconGridColumnProps {
  value: any
  boolean?: boolean
  wrap?: boolean
  icon?: string | null
  color?: string | null
  size?: string | null
  description?: string | null
  descriptionPosition?: 'above' | 'below'
}

const props = withDefaults(defineProps<IconGridColumnProps>(), {
  boolean: false,
  wrap: false,
  icon: null,
  color: null,
  size: null,
  description: null,
  descriptionPosition: 'below',
})

// Icon mapping from Heroicons to Lucide
const iconMap: Record<string, string> = {
  'heroicon-o-check-circle': 'CheckCircle',
  'heroicon-o-x-circle': 'XCircle',
  'heroicon-o-exclamation-circle': 'AlertCircle',
  'heroicon-o-information-circle': 'Info',
}

const lucideIconComponent = computed(() => {
  // Use evaluated icon from backend, or fallback to value (column data)
  const iconName = props.icon || props.value

  if (!iconName) return undefined

  // Map from Heroicons or other icon sets to Lucide
  const mappedIconName = iconMap[iconName] || iconName

  // Convert to PascalCase for Lucide component lookup
  const pascalCaseName = mappedIconName
    .split('-')
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join('')

  return (LucideIcons as any)[pascalCaseName]
})

// Map color to Tailwind classes
const colorClass = computed(() => {
  if (!props.color) return 'text-muted-foreground'

  const colorMap: Record<string, string> = {
    'primary': 'text-primary',
    'success': 'text-green-500',
    'danger': 'text-destructive',
    'warning': 'text-yellow-500',
    'info': 'text-blue-500',
    'gray': 'text-muted-foreground',
    'secondary': 'text-muted-foreground',
  }

  return colorMap[props.color] || 'text-muted-foreground'
})

// Map size to icon classes
const sizeClass = computed(() => {
  const sizeMap: Record<string, string> = {
    'xs': 'h-3 w-3',
    'sm': 'h-4 w-4',
    'md': 'h-5 w-5',
    'lg': 'h-6 w-6',
    'xl': 'h-8 w-8',
    '2xl': 'h-10 w-10',
    'extra-small': 'h-3 w-3',
    'small': 'h-4 w-4',
    'medium': 'h-5 w-5',
    'large': 'h-6 w-6',
    'extra-large': 'h-8 w-8',
    'two-extra-large': 'h-10 w-10',
  }

  return sizeMap[props.size || 'large'] || 'h-6 w-6'
})
</script>

<template>
  <div class="flex flex-col gap-1">
    <!-- Description above -->
    <div v-if="description && descriptionPosition === 'above'" class="text-[11px] text-muted-foreground/70">
      {{ description }}
    </div>

    <!-- Main content -->
    <div :class="wrap ? 'flex flex-wrap gap-1' : 'flex items-center'">
      <component
        :is="lucideIconComponent"
        v-if="lucideIconComponent"
        :class="[sizeClass, colorClass, 'shrink-0']"
      />
    </div>

    <!-- Description below -->
    <div v-if="description && descriptionPosition === 'below'" class="text-[11px] text-muted-foreground/70">
      {{ description }}
    </div>
  </div>
</template>
