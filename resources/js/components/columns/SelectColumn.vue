<script setup lang="ts">
import { computed } from 'vue'
import { Loader2 } from 'lucide-vue-next'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { useColumnUpdate } from '../../composables/useColumnUpdate'

interface SelectColumnProps {
  value: any
  name: string
  label?: string | null
  placeholder?: string | null
  recordId: number | string
  columnUpdateRoute?: string | null
  editable?: boolean
  disabled?: boolean
  options?: Record<string, string> | string[]
  selectablePlaceholder?: boolean
  description?: string | null
  descriptionPosition?: 'above' | 'below'
}

const props = withDefaults(defineProps<SelectColumnProps>(), {
  label: null,
  placeholder: null,
  columnUpdateRoute: null,
  editable: true,
  disabled: false,
  options: () => ({}),
  selectablePlaceholder: true,
  description: null,
  descriptionPosition: 'below',
})

// Radix/Reka select items can't use an empty value, so "no value" gets a sentinel item
const NULL_VALUE = '__laravilt_null__'

const toKey = (value: any): string | null => (value === null || value === undefined || value === '' ? null : String(value))

const { localValue, isSaving, isDisabled, save } = useColumnUpdate<string | null>(
  () => toKey(props.value),
  () => ({
    name: props.name,
    recordId: props.recordId,
    columnUpdateRoute: props.columnUpdateRoute,
    editable: props.editable,
    disabled: props.disabled,
  }),
)

const entries = computed(() =>
  Object.entries(props.options ?? {}).map(([key, optionLabel]) => ({ key, label: String(optionLabel) })),
)

const onValueChange = (next: any) => {
  const newValue = next === NULL_VALUE || next === null || next === undefined ? null : String(next)
  if (newValue !== localValue.value) save(newValue)
}
</script>

<template>
  <!-- Editing a cell must not trigger the row's click-to-open behaviour -->
  <div class="flex flex-col gap-0.5" @click.stop>
    <div v-if="description && descriptionPosition === 'above'" class="text-[10px] text-muted-foreground/60 leading-tight">
      {{ description }}
    </div>

    <div class="flex items-center gap-1.5">
      <Select :model-value="localValue ?? ''" :disabled="isDisabled" @update:model-value="onValueChange">
        <SelectTrigger
          :aria-label="label || name"
          :aria-busy="isSaving"
          :class="isSaving ? 'h-8 min-w-32 opacity-60 cursor-wait' : 'h-8 min-w-32'"
        >
          <SelectValue :placeholder="placeholder || '—'" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem v-if="selectablePlaceholder" :value="NULL_VALUE">{{ placeholder || '—' }}</SelectItem>
          <SelectItem v-for="option in entries" :key="option.key" :value="option.key">
            {{ option.label }}
          </SelectItem>
        </SelectContent>
      </Select>
      <Loader2 v-if="isSaving" aria-hidden="true" class="size-3.5 shrink-0 animate-spin text-muted-foreground" />
    </div>

    <div v-if="description && descriptionPosition === 'below'" class="text-[10px] text-muted-foreground/60 leading-tight">
      {{ description }}
    </div>
  </div>
</template>
