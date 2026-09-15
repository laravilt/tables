<script setup lang="ts">
import { Checkbox } from '@/components/ui/checkbox'
import { useColumnUpdate } from '../../composables/useColumnUpdate'

interface CheckboxColumnProps {
  value: any
  name: string
  label?: string | null
  recordId: number | string
  columnUpdateRoute?: string | null
  editable?: boolean
  disabled?: boolean
  description?: string | null
  descriptionPosition?: 'above' | 'below'
}

const props = withDefaults(defineProps<CheckboxColumnProps>(), {
  label: null,
  columnUpdateRoute: null,
  editable: true,
  disabled: false,
  description: null,
  descriptionPosition: 'below',
})

const { localValue, isSaving, isDisabled, save } = useColumnUpdate<boolean>(
  () => Boolean(props.value),
  () => ({
    name: props.name,
    recordId: props.recordId,
    columnUpdateRoute: props.columnUpdateRoute,
    editable: props.editable,
    disabled: props.disabled,
  }),
)
</script>

<template>
  <!-- Editing a cell must not trigger the row's click-to-open behaviour -->
  <div class="flex flex-col gap-0.5" @click.stop>
    <div v-if="description && descriptionPosition === 'above'" class="text-[10px] text-muted-foreground/60 leading-tight">
      {{ description }}
    </div>

    <div class="flex items-center">
      <Checkbox
        :model-value="Boolean(localValue)"
        :disabled="isDisabled"
        :aria-label="label || name"
        :aria-busy="isSaving"
        :class="isSaving ? 'opacity-60 cursor-wait' : undefined"
        @update:model-value="(checked: boolean | 'indeterminate') => save(checked === true)"
      />
    </div>

    <div v-if="description && descriptionPosition === 'below'" class="text-[10px] text-muted-foreground/60 leading-tight">
      {{ description }}
    </div>
  </div>
</template>
