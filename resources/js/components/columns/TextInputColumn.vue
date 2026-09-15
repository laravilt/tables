<script setup lang="ts">
import { ref, watch } from 'vue'
import { Loader2 } from 'lucide-vue-next'
import { Input } from '@/components/ui/input'
import { useColumnUpdate } from '../../composables/useColumnUpdate'

interface TextInputColumnProps {
  value: any
  name: string
  label?: string | null
  placeholder?: string | null
  recordId: number | string
  columnUpdateRoute?: string | null
  editable?: boolean
  disabled?: boolean
  type?: string
  prefix?: string | null
  suffix?: string | null
  description?: string | null
  descriptionPosition?: 'above' | 'below'
}

const props = withDefaults(defineProps<TextInputColumnProps>(), {
  label: null,
  placeholder: null,
  columnUpdateRoute: null,
  editable: true,
  disabled: false,
  type: 'text',
  prefix: null,
  suffix: null,
  description: null,
  descriptionPosition: 'below',
})

const toText = (value: any): string => (value === null || value === undefined ? '' : String(value))

const { localValue, isSaving, isDisabled, save } = useColumnUpdate<any>(
  () => props.value ?? null,
  () => ({
    name: props.name,
    recordId: props.recordId,
    columnUpdateRoute: props.columnUpdateRoute,
    editable: props.editable,
    disabled: props.disabled,
  }),
)

// The draft is what the user is typing; it's saved on Enter/blur and resynced when the stored value changes
const draft = ref(toText(props.value))
watch(localValue, (value) => {
  draft.value = toText(value)
})

const commit = () => {
  if (draft.value === toText(localValue.value)) return
  save(draft.value === '' ? null : props.type === 'number' ? Number(draft.value) : draft.value)
}

const cancel = () => {
  draft.value = toText(localValue.value)
}
</script>

<template>
  <!-- Editing a cell must not trigger the row's click-to-open behaviour -->
  <div class="flex flex-col gap-0.5" @click.stop>
    <div v-if="description && descriptionPosition === 'above'" class="text-[10px] text-muted-foreground/60 leading-tight">
      {{ description }}
    </div>

    <div class="flex items-center gap-1.5">
      <span v-if="prefix" class="text-sm text-muted-foreground">{{ prefix }}</span>
      <Input
        v-model="draft"
        :type="type"
        :placeholder="placeholder ?? undefined"
        :disabled="isDisabled"
        :aria-label="label || name"
        :aria-busy="isSaving"
        :class="isSaving ? 'h-8 min-w-24 opacity-60 cursor-wait' : 'h-8 min-w-24'"
        @blur="commit"
        @keydown.enter.prevent="commit"
        @keydown.esc="cancel"
      />
      <span v-if="suffix" class="text-sm text-muted-foreground">{{ suffix }}</span>
      <Loader2 v-if="isSaving" aria-hidden="true" class="size-3.5 shrink-0 animate-spin text-muted-foreground" />
    </div>

    <div v-if="description && descriptionPosition === 'below'" class="text-[10px] text-muted-foreground/60 leading-tight">
      {{ description }}
    </div>
  </div>
</template>
