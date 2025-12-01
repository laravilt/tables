<script setup lang="ts">
import { ref, watch } from 'vue'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'

interface TextFilterProps {
  name: string
  label: string
  modelValue?: string | null
  placeholder?: string
  type?: 'text' | 'email' | 'url' | 'number'
}

const props = withDefaults(defineProps<TextFilterProps>(), {
  modelValue: null,
  placeholder: 'Enter text...',
  type: 'text',
})

const emit = defineEmits<{
  'update:modelValue': [value: string | null]
}>()

const localValue = ref(props.modelValue || '')

watch(() => props.modelValue, (newValue) => {
  localValue.value = newValue || ''
})

const handleInput = () => {
  const value = localValue.value.trim()
  emit('update:modelValue', value || null)
}
</script>

<template>
  <div class="flex flex-col gap-2">
    <Label :for="name" class="text-sm font-medium">
      {{ label }}
    </Label>
    <Input
      :id="name"
      v-model="localValue"
      :type="type"
      :placeholder="placeholder"
      @blur="handleInput"
      @keyup.enter="handleInput"
    />
  </div>
</template>
