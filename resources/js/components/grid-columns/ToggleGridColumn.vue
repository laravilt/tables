<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { Switch } from '@/components/ui/switch'
import { useNotification } from '@laravilt/notifications/composables/useNotification'
import { useLocalization } from '@/composables/useLocalization'

// Initialize localization
const { trans } = useLocalization()

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

// Get the panel path from Inertia shared data
const page = usePage()
const panelPath = computed(() => (page.props as any).panel?.path || 'dashboard')

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
      `/${panelPath.value}/${props.resourceSlug}/${props.recordId}/column`,
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
          notify(
            trans('tables::tables.toggle_column.success_notification_title'),
            trans('tables::tables.toggle_column.success_notification_message'),
            'success',
            { duration: 2000 }
          )
        },
        onError: (errors) => {
          isUpdating.value = false

          const errorMessage = errors[props.name] || trans('tables::tables.toggle_column.error_notification_message')
          notify(trans('tables::tables.toggle_column.error_notification_title'), errorMessage, 'error', {
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
