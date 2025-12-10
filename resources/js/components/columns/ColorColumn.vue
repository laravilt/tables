<script setup lang="ts">
import { computed } from 'vue'
import { useNotification } from '@laravilt/notifications/composables/useNotification'
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip'

interface ColorColumnProps {
  value: any
  copyable?: boolean
  copyMessage?: string | null
  copyMessageDuration?: number | null
  wrap?: boolean
  description?: string | null
  descriptionPosition?: 'above' | 'below'
  maxVisible?: number
}

const props = withDefaults(defineProps<ColorColumnProps>(), {
  copyable: false,
  copyMessage: null,
  copyMessageDuration: null,
  wrap: false,
  description: null,
  descriptionPosition: 'below',
  maxVisible: 4,
})

const { notify } = useNotification()

const colors = computed(() => {
  if (!props.value) return []
  return Array.isArray(props.value) ? props.value : [props.value]
})

const visibleColors = computed(() => {
  return colors.value.slice(0, props.maxVisible)
})

const hiddenColors = computed(() => {
  return colors.value.slice(props.maxVisible)
})

const hasMoreColors = computed(() => {
  return colors.value.length > props.maxVisible
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
  <div class="flex flex-col gap-1 max-w-full overflow-hidden">
    <!-- Description above -->
    <div v-if="description && descriptionPosition === 'above'" class="text-[11px] text-muted-foreground/70">
      {{ description }}
    </div>

    <!-- Main content - stacked circles style for multiple colors -->
    <div v-if="colors.length > 1" class="flex items-center">
      <div class="flex -space-x-1.5">
        <button
          v-for="(color, index) in visibleColors"
          :key="index"
          type="button"
          class="h-6 w-6 rounded-full border-2 border-background shrink-0 shadow-sm"
          :class="copyable ? 'cursor-pointer hover:ring-2 hover:ring-ring hover:z-10 transition-all' : 'cursor-default'"
          :style="{ backgroundColor: color, zIndex: visibleColors.length - index }"
          :title="color"
          @click="copyable && handleCopy(color)"
        />
        <!-- More colors indicator -->
        <TooltipProvider v-if="hasMoreColors">
          <Tooltip>
            <TooltipTrigger as-child>
              <div
                class="h-6 w-6 rounded-full border-2 border-background bg-muted flex items-center justify-center text-[10px] font-medium text-muted-foreground shrink-0 shadow-sm"
                :style="{ zIndex: 0 }"
              >
                +{{ hiddenColors.length }}
              </div>
            </TooltipTrigger>
            <TooltipContent side="top" class="p-2">
              <div class="flex flex-wrap gap-1 max-w-[150px]">
                <button
                  v-for="(color, index) in hiddenColors"
                  :key="index"
                  type="button"
                  class="h-5 w-5 rounded border border-border shrink-0"
                  :class="copyable ? 'cursor-pointer hover:ring-2 hover:ring-ring transition-all' : 'cursor-default'"
                  :style="{ backgroundColor: color }"
                  :title="color"
                  @click="copyable && handleCopy(color)"
                />
              </div>
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>
      </div>
    </div>

    <!-- Single color - simple square style -->
    <div v-else-if="colors.length === 1" class="flex items-center">
      <button
        type="button"
        class="h-6 w-6 rounded border border-border shrink-0"
        :class="copyable ? 'cursor-pointer hover:ring-2 hover:ring-ring transition-all' : 'cursor-default'"
        :style="{ backgroundColor: colors[0] }"
        :title="colors[0]"
        @click="copyable && handleCopy(colors[0])"
      />
    </div>

    <!-- Description below -->
    <div v-if="description && descriptionPosition === 'below'" class="text-[11px] text-muted-foreground/70">
      {{ description }}
    </div>
  </div>
</template>
