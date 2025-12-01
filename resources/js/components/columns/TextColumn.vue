<script setup lang="ts">
import { computed } from 'vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Copy } from 'lucide-vue-next'
import * as LucideIcons from 'lucide-vue-next'
import { useNotification } from '@laravilt/notifications/composables/useNotification'

const { notify } = useNotification()

interface TextColumnProps {
  value: any
  limit?: number
  wrap?: boolean
  copyable?: string | null
  badge?: boolean
  dateTimeFormat?: string | null
  dateFormat?: string | null
  icon?: string | null
  weight?: string | null
  moneyFormat?: { currency: string; divideBy: number } | null
  color?: string | null
  description?: string | null
  descriptionPosition?: 'above' | 'below'
  html?: boolean
}

const props = withDefaults(defineProps<TextColumnProps>(), {
  limit: undefined,
  wrap: false,
  copyable: null,
  badge: false,
  dateTimeFormat: null,
  dateFormat: null,
  icon: null,
  weight: null,
  moneyFormat: null,
  color: null,
  description: null,
  descriptionPosition: 'below',
  html: false,
})

// Check if value is an array (for badge rendering of many-to-many relationships)
const isArray = computed(() => Array.isArray(props.value))

// Check if value is an object or array (for JSON formatting)
const isObjectOrArray = computed(() => {
  return typeof props.value === 'object' && props.value !== null
})

// Map color to badge variant
const badgeVariant = computed(() => {
  if (!props.color) return 'secondary'

  const colorMap: Record<string, string> = {
    'primary': 'default',
    'success': 'success',
    'danger': 'destructive',
    'warning': 'warning',
    'info': 'secondary',
    'gray': 'secondary',
    'secondary': 'secondary',
  }

  return colorMap[props.color] || 'secondary'
})

const formattedValue = computed(() => {
  if (props.value === null || props.value === undefined || props.value === '') {
    return ''
  }

  // If it's an array for badges, don't format it here - we'll render badges individually
  if (isArray.value && props.badge) {
    return ''
  }

  // If it's an object or array (not for badges), format as JSON
  if (isObjectOrArray.value && !props.badge) {
    try {
      return JSON.stringify(props.value, null, 2)
    } catch (e) {
      return String(props.value)
    }
  }

  let result = String(props.value)

  // Format as date/datetime
  if (props.dateTimeFormat) {
    try {
      const date = new Date(props.value)
      // Check if date is valid
      if (!isNaN(date.getTime())) {
        result = date.toLocaleString('en-US', {
          year: 'numeric',
          month: '2-digit',
          day: '2-digit',
          hour: '2-digit',
          minute: '2-digit',
          second: '2-digit',
        })
      }
    } catch (e) {
      result = String(props.value)
    }
  } else if (props.dateFormat) {
    try {
      const date = new Date(props.value)
      // Check if date is valid
      if (!isNaN(date.getTime())) {
        result = date.toLocaleDateString('en-US', {
          year: 'numeric',
          month: '2-digit',
          day: '2-digit',
        })
      }
    } catch (e) {
      result = String(props.value)
    }
  }

  // Format as money
  if (props.moneyFormat) {
    const numValue = Number(props.value) / props.moneyFormat.divideBy
    result = new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: props.moneyFormat.currency,
    }).format(numValue)
  }

  // Apply character limit (not for HTML or JSON)
  if (props.limit && result.length > props.limit && !props.html && !isObjectOrArray.value) {
    result = result.substring(0, props.limit) + '...'
  }

  return result
})

// Icon mapping from Heroicons to Lucide
const iconMap: Record<string, string> = {
  'heroicon-o-check-circle': 'CheckCircle',
  'heroicon-o-x-circle': 'XCircle',
  'heroicon-o-exclamation-circle': 'AlertCircle',
  'heroicon-o-information-circle': 'Info',
}

const lucideIconComponent = computed(() => {
  if (!props.icon) return undefined

  const iconName = iconMap[props.icon] || props.icon

  const pascalCaseName = iconName
    .split('-')
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join('')

  return (LucideIcons as any)[pascalCaseName]
})

const handleCopy = () => {
  if (props.copyable && formattedValue.value) {
    navigator.clipboard.writeText(formattedValue.value)
    notify(props.copyable, 'Copied to clipboard', 'success', {
      duration: 2000
    })
  }
}

const weightClass = computed(() => {
  switch (props.weight) {
    case 'thin':
      return 'font-thin'
    case 'extralight':
      return 'font-extralight'
    case 'light':
      return 'font-light'
    case 'normal':
      return 'font-normal'
    case 'medium':
      return 'font-medium'
    case 'semibold':
      return 'font-semibold'
    case 'bold':
      return 'font-bold'
    case 'extrabold':
      return 'font-extrabold'
    case 'black':
      return 'font-black'
    default:
      return ''
  }
})
</script>

<template>
  <div class="flex flex-col gap-1">
    <!-- Description above -->
    <div v-if="description && descriptionPosition === 'above'" class="text-[11px] text-muted-foreground/70">
      {{ description }}
    </div>

    <!-- Main content -->
    <div class="flex items-start gap-2">
      <!-- Icon outside badge when not using badge -->
      <component
        :is="lucideIconComponent"
        v-if="lucideIconComponent && !badge"
        class="h-4 w-4 shrink-0 mt-0.5"
      />

      <!-- Multiple badges for array values (many-to-many relationships) -->
      <div v-if="badge && isArray" class="flex flex-wrap gap-1.5">
        <Badge
          v-for="(item, index) in value"
          :key="index"
          :variant="badgeVariant"
          class="font-normal flex items-center gap-1.5"
        >
          <component
            :is="lucideIconComponent"
            v-if="lucideIconComponent"
            class="h-3 w-3 shrink-0"
          />
          {{ item }}
        </Badge>
      </div>

      <!-- Single badge -->
      <Badge v-else-if="badge" :variant="badgeVariant" class="font-normal flex items-center gap-1.5">
        <component
          :is="lucideIconComponent"
          v-if="lucideIconComponent"
          class="h-3 w-3 shrink-0"
        />
        {{ formattedValue }}
      </Badge>

      <!-- HTML content -->
      <div
        v-else-if="html"
        :class="[
          weightClass,
          wrap ? 'whitespace-normal' : 'truncate',
        ]"
        v-html="formattedValue"
      />

      <!-- JSON/Object/Array formatting -->
      <pre
        v-else-if="isObjectOrArray && !badge"
        :class="[
          'text-xs bg-muted/50 rounded px-2 py-1 overflow-x-auto',
          weightClass,
        ]"
      ><code>{{ formattedValue }}</code></pre>

      <!-- Regular text -->
      <span
        v-else
        :class="[
          weightClass,
          wrap ? 'whitespace-normal' : 'truncate',
        ]"
      >
        {{ formattedValue }}
      </span>

      <Button
        v-if="copyable"
        variant="ghost"
        size="icon"
        class="h-6 w-6 shrink-0"
        @click="handleCopy"
      >
        <Copy class="h-3 w-3" />
      </Button>
    </div>

    <!-- Description below -->
    <div v-if="description && descriptionPosition === 'below'" class="text-[11px] text-muted-foreground/70">
      {{ description }}
    </div>
  </div>
</template>
