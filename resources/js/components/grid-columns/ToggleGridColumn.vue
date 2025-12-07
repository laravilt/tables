<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { Switch } from '@/components/ui/switch'
import { useNotification } from '@laravilt/notifications/composables/useNotification'

interface ToggleGridColumnProps {
  value: any
  name: string
  recordId: number | string
  resourceSlug: string
  editable?: boolean
  disabled?: boolean
  description?: string | null
  descriptionPosition?: 'above' | 'below'
}

const props = withDefaults(defineProps<ToggleGridColumnProps>(), {
  editable: true,
  disabled: false,
  description: null,
  descriptionPosition: 'below',
})

const { notify } = useNotification()

// Use the same boolean conversion as the Edit page - simple Boolean() cast
const localValue = ref<boolean>(Boolean(props.value))
const isUpdating = ref(false)

// Watch for prop changes to update local value (but not during updates to avoid conflicts)
watch(() => props.value, (newValue) => {
  if (!isUpdating.value) {
    localValue.value = Boolean(newValue)
  }
}, { immediate: true })

const isChecked = computed({
  get: () => localValue.value,
  set: (newValue: boolean) => {
    if (props.disabled || !props.editable || isUpdating.value) return

    isUpdating.value = true

    // Send update to backend (convert to 1/0 for database)
    router.patch(
      `/dashboard/${props.resourceSlug}/${props.recordId}/column`,
      {
        column: props.name,
        value: newValue ? 1 : 0,
      },
      {
        preserveScroll: true,
        preserveState: true,
        only: ['records'],
        onSuccess: () => {
          localValue.value = newValue
          isUpdating.value = false
          notify('Updated', 'Value updated successfully', 'success', {
            duration: 2000,
          })
        },
        onError: (errors) => {
          isUpdating.value = false

          const errorMessage = errors[props.name] || 'Failed to update value'
          notify('Error', errorMessage, 'error', {
            duration: 3000,
          })
        },
      }
    )
  },
})
</script>

<template>
  <div class="flex flex-col gap-1">
    <!-- Description above -->
    <div v-if="description && descriptionPosition === 'above'" class="text-[11px] text-muted-foreground/70">
      {{ description }}
    </div>

    <!-- Main content -->
    <div class="flex items-center">
      <Switch
        :model-value="isChecked"
        :disabled="disabled || !editable || isUpdating"
        @update:model-value="isChecked = $event"
      />
    </div>

    <!-- Description below -->
    <div v-if="description && descriptionPosition === 'below'" class="text-[11px] text-muted-foreground/70">
      {{ description }}
    </div>
  </div>
</template>
