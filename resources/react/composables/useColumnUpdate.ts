import { useNotification } from '@laravilt/notifications/composables/useNotification';
import { useLocalization } from '@laravilt/support/composables/useLocalization';
import { useCallback, useEffect } from 'react';
import { useStateRef } from './useStateRef';

export interface ColumnUpdateOptions {
    name: string;
    recordId: number | string;
    columnUpdateRoute?: string | null;
    editable?: boolean;
    disabled?: boolean;
}

/**
 * Shared inline-edit state for editable table columns (Select/TextInput/Checkbox/Toggle).
 *
 * Optimistic: the local value changes immediately, is sent to the column update endpoint, and is
 * reverted (with an error notification) if the server rejects it. Keep in sync with the Vue composable.
 */
export function useColumnUpdate<T>(value: T, { name, recordId, columnUpdateRoute, editable = true, disabled = false }: ColumnUpdateOptions) {
    const { notify } = useNotification();
    const { trans } = useLocalization();

    const [localValue, setLocalValue, localValueRef] = useStateRef<T>(value);
    const [isSaving, setIsSaving, isSavingRef] = useStateRef<boolean>(false);

    // Follow server-side changes, but never clobber an in-flight optimistic value
    useEffect(() => {
        if (!isSavingRef.current) {
            setLocalValue(value);
        }
    }, [value, isSavingRef, setLocalValue]);

    const url = columnUpdateRoute ? columnUpdateRoute.replace('__ID__', encodeURIComponent(String(recordId))) : null;
    const isDisabled = disabled || !editable || !url || isSaving;

    const save = useCallback(
        async (newValue: T): Promise<boolean> => {
            if (disabled || !editable || !url || isSavingRef.current) return false;

            const previous = localValueRef.current;
            setLocalValue(newValue);
            setIsSaving(true);

            try {
                const response = await fetch(url, {
                    method: 'PATCH',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ column: name, value: newValue }),
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(data?.errors?.value?.[0] || data?.message || trans('tables::tables.toggle_column.error_notification_message'));
                }

                if (data && 'state' in data) setLocalValue(data.state as T);

                notify(
                    trans('tables::tables.toggle_column.success_notification_title'),
                    trans('tables::tables.toggle_column.success_notification_message'),
                    'success',
                    { duration: 2000 },
                );

                return true;
            } catch (error) {
                setLocalValue(previous);
                notify(
                    trans('tables::tables.toggle_column.error_notification_title'),
                    error instanceof Error && error.message ? error.message : trans('tables::tables.toggle_column.error_notification_message'),
                    'error',
                    { duration: 3000 },
                );

                return false;
            } finally {
                setIsSaving(false);
            }
        },
        [disabled, editable, url, name, isSavingRef, localValueRef, setLocalValue, setIsSaving, notify, trans],
    );

    return { localValue, isSaving, isDisabled, url, save };
}
