<script setup lang="ts">
import { computed } from 'vue'
import { useNotification } from '@laravilt/notifications/composables/useNotification'

interface ColorGridColumnProps {
  value: any
  copyable?: boolean
  copyMessage?: string | null
  copyMessageDuration?: number | null
  wrap?: boolean
  description?: string | null
  descriptionPosition?: 'above' | 'below'
}

const props = withDefaults(defineProps<ColorGridColumnProps>(), {
  copyable: false,
  copyMessage: null,
  copyMessageDuration: null,
  wrap: false,
  description: null,
  descriptionPosition: 'below',
})

const { notify } = useNotification()

const colors = computed(() => {
  if (!props.value) return []
  return Array.isArray(props.value) ? props.value : [props.value]
})

const handleCopy = (color: string) => {
  if (props.copyable && color) {
    navigator.clipboard.writeText(color)
    notify(
      props.copyMessage || 'Copied!',
      `Color ${color} copied to clipboard`,
      'success',
      {
        duration: props.copyMessageDuration || 1500,
      }
    )
  }
}
</script>

<template>
  <div class="flex flex-col gap-1">
    <!-- Description above -->
    <div v-if="description && descriptionPosition === 'above'" class="text-[11px] text-muted-foreground/70">
      {{ description }}
    </div>

    <!-- Main content -->
    <div :class="wrap ? 'flex flex-wrap gap-1.5' : 'flex items-center gap-1.5'">
      <button
        v-for="(color, index) in colors"
        :key="index"
        type="button"
        :class="[
          'h-6 w-6 rounded border border-border shrink-0',
          copyable ? 'cursor-pointer hover:ring-2 hover:ring-ring transition-all' : 'cursor-default',
        ]"
        :style="{ backgroundColor: color }"
        :title="color"
        @click="copyable && handleCopy(color)"
      />
    </div>

    <!-- Description below -->
    <div v-if="description && descriptionPosition === 'below'" class="text-[11px] text-muted-foreground/70">
      {{ description }}
    </div>
  </div>
</template>
