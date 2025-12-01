<script setup lang="ts">
import { computed } from 'vue'
import { Label } from '@/components/ui/label'
import { Switch } from '@/components/ui/switch'

interface ToggleFilterProps {
  name: string
  label: string
  modelValue?: boolean | null
  description?: string
}

const props = withDefaults(defineProps<ToggleFilterProps>(), {
  modelValue: null,
  description: undefined,
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean | null]
}>()

const checked = computed({
  get: () => props.modelValue === true,
  set: (value: boolean) => {
    emit('update:modelValue', value)
  },
})
</script>

<template>
  <div class="flex items-center justify-between gap-4 py-2">
    <div class="flex flex-col gap-1">
      <Label :for="name" class="text-sm font-medium cursor-pointer">
        {{ label }}
      </Label>
      <p v-if="description" class="text-xs text-muted-foreground">
        {{ description }}
      </p>
    </div>
    <Switch :id="name" v-model:checked="checked" />
  </div>
</template>
