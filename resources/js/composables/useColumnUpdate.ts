import { computed, ref, watch, type Ref } from 'vue'
import { useNotification } from '@laravilt/notifications/composables/useNotification'
import { useLocalization } from '@/composables/useLocalization'

export interface ColumnUpdateOptions {
  name: string
  recordId: number | string
  columnUpdateRoute?: string | null
  editable?: boolean
  disabled?: boolean
}

/**
 * Shared inline-edit state for editable table columns (Select/TextInput/Checkbox/Toggle).
 *
 * Optimistic: the local value changes immediately, is sent to the column update endpoint, and is
 * reverted (with an error notification) if the server rejects it. Keep in sync with the React hook.
 */
export function useColumnUpdate<T>(value: () => T, options: () => ColumnUpdateOptions) {
  const { notify } = useNotification()
  const { trans } = useLocalization()

  const localValue = ref(value()) as Ref<T>
  const isSaving = ref(false)

  // Follow server-side changes, but never clobber an in-flight optimistic value
  watch(value, (newValue) => {
    if (!isSaving.value) localValue.value = newValue
  })

  const url = computed(() => {
    const { columnUpdateRoute, recordId } = options()
    return columnUpdateRoute ? columnUpdateRoute.replace('__ID__', encodeURIComponent(String(recordId))) : null
  })

  const isDisabled = computed(() => {
    const { editable = true, disabled = false } = options()
    return disabled || !editable || !url.value || isSaving.value
  })

  const save = async (newValue: T): Promise<boolean> => {
    if (isDisabled.value || !url.value) return false

    const previous = localValue.value
    localValue.value = newValue
    isSaving.value = true

    try {
      const response = await fetch(url.value, {
        method: 'PATCH',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ column: options().name, value: newValue }),
      })

      const data = await response.json().catch(() => ({}))

      if (!response.ok) {
        throw new Error(data?.errors?.value?.[0] || data?.message || trans('tables::tables.toggle_column.error_notification_message'))
      }

      if (data && 'state' in data) localValue.value = data.state as T

      notify(
        trans('tables::tables.toggle_column.success_notification_title'),
        trans('tables::tables.toggle_column.success_notification_message'),
        'success',
        { duration: 2000 },
      )

      return true
    } catch (error) {
      localValue.value = previous
      notify(
        trans('tables::tables.toggle_column.error_notification_title'),
        error instanceof Error && error.message ? error.message : trans('tables::tables.toggle_column.error_notification_message'),
        'error',
        { duration: 3000 },
      )

      return false
    } finally {
      isSaving.value = false
    }
  }

  return { localValue, isSaving, isDisabled, url, save }
}
