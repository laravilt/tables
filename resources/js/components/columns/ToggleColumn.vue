<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { Switch } from '@/components/ui/switch'
import { useNotification } from '@laravilt/notifications/composables/useNotification'
import { useLocalization } from '@/composables/useLocalization'
import { useColumnUpdate } from '../../composables/useColumnUpdate'

// Initialize localization
const { trans } = useLocalization()

interface ToggleColumnProps {
  value: any
  name: string
  label?: string | null
  recordId: number | string
  resourceSlug?: string
  /** Authorized, validated column update endpoint (preferred when present) */
  columnUpdateRoute?: string | null
  /** Legacy panel endpoint, still used by relation manager tables */
  columnExecutionRoute?: string
  editable?: boolean
  disabled?: boolean
  description?: string | null
  descriptionPosition?: 'above' | 'below'
  // Text labels for i18n support
  successNotificationTitle?: string
  successNotificationMessage?: string
  errorNotificationTitle?: string
  errorNotificationMessage?: string
}

const props = withDefaults(defineProps<ToggleColumnProps>(), {
  editable: true,
  disabled: false,
  description: null,
  descriptionPosition: 'below',
  label: null,
  resourceSlug: '',
  columnUpdateRoute: null,
  columnExecutionRoute: undefined,
  successNotificationTitle: 'Updated',
  successNotificationMessage: 'Value updated successfully',
  errorNotificationTitle: 'Error',
  errorNotificationMessage: 'Failed to update value',
})

const { notify } = useNotification()

// Optimistic save through the column update endpoint (reverts on failure)
const update = useColumnUpdate<boolean>(
  () => Boolean(props.value),
  () => ({
    name: props.name,
    recordId: props.recordId,
    columnUpdateRoute: props.columnUpdateRoute,
    editable: props.editable,
    disabled: props.disabled,
  }),
)
const { localValue: updateValue, isSaving: updateIsSaving, isDisabled: updateIsDisabled } = update
const usesUpdateRoute = computed(() => Boolean(props.columnUpdateRoute))

// Compute the execution URL - replace __ID__ placeholder with actual record ID
const executionUrl = computed(() => {
  if (props.columnExecutionRoute) {
    return props.columnExecutionRoute.replace('__ID__', String(props.recordId))
  }
  return null
})

// Computed translated labels - use tables::tables namespace
const translatedSuccessTitle = computed(() => props.successNotificationTitle !== 'Updated' ? props.successNotificationTitle : trans('tables::tables.toggle_column.success_notification_title'))
const translatedSuccessMessage = computed(() => props.successNotificationMessage !== 'Value updated successfully' ? props.successNotificationMessage : trans('tables::tables.toggle_column.success_notification_message'))
const translatedErrorTitle = computed(() => props.errorNotificationTitle !== 'Error' ? props.errorNotificationTitle : trans('tables::tables.toggle_column.error_notification_title'))
const translatedErrorMessage = computed(() => props.errorNotificationMessage !== 'Failed to update value' ? props.errorNotificationMessage : trans('tables::tables.toggle_column.error_notification_message'))

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
    if (props.disabled || !props.editable || isUpdating.value || !executionUrl.value) return

    isUpdating.value = true

    // Send update to backend (convert to 1/0 for database)
    router.patch(
      executionUrl.value,
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
          notify(translatedSuccessTitle.value, translatedSuccessMessage.value, 'success', {
            duration: 2000,
          })
        },
        onError: (errors) => {
          isUpdating.value = false

          const errorMessage = errors[props.name] || translatedErrorMessage.value
          notify(translatedErrorTitle.value, errorMessage, 'error', {
            duration: 3000,
          })
        },
      }
    )
  },
})
</script>

<template>
  <div class="flex flex-col gap-0.5">
    <!-- Description above -->
    <div v-if="description && descriptionPosition === 'above'" class="text-[10px] text-muted-foreground/60 leading-tight">
      {{ description }}
    </div>

    <!-- Main content -->
    <div class="flex items-center">
      <Switch
        v-if="usesUpdateRoute"
        :model-value="Boolean(updateValue)"
        :disabled="updateIsDisabled"
        :aria-label="label || name"
        :aria-busy="updateIsSaving"
        :class="updateIsSaving ? 'opacity-60 cursor-wait' : undefined"
        @update:model-value="(checked: boolean) => update.save(checked)"
      />
      <Switch
        v-else
        :aria-label="label || name"
        :aria-busy="isUpdating"
        :model-value="isChecked"
        :disabled="disabled || !editable || isUpdating || !executionUrl"
        :class="[
          isUpdating && 'opacity-60 cursor-wait',
          !executionUrl && 'opacity-50 cursor-not-allowed',
        ]"
        @update:model-value="isChecked = $event"
      />
    </div>

    <!-- Description below -->
    <div v-if="description && descriptionPosition === 'below'" class="text-[10px] text-muted-foreground/60 leading-tight">
      {{ description }}
    </div>
  </div>
</template>
