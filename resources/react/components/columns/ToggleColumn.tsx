import { Switch } from '@/components/ui/switch';
import { cn } from '@/lib/utils';
import { router } from '@inertiajs/react';
import { useNotification } from '@laravilt/notifications/composables/useNotification';
import { useLocalization } from '@laravilt/support/composables/useLocalization';
import { useEffect, useState } from 'react';
import { useColumnUpdate } from '../../composables/useColumnUpdate';
import { useStateRef } from '../../composables/useStateRef';

export interface ToggleColumnProps {
    value: any;
    name: string;
    label?: string | null;
    recordId: number | string;
    resourceSlug?: string;
    /** Authorized, validated column update endpoint (preferred when present) */
    columnUpdateRoute?: string | null;
    /** Legacy panel endpoint, still used by relation manager tables */
    columnExecutionRoute?: string;
    editable?: boolean;
    disabled?: boolean;
    description?: string | null;
    descriptionPosition?: 'above' | 'below';
    // Text labels for i18n support
    successNotificationTitle?: string;
    successNotificationMessage?: string;
    errorNotificationTitle?: string;
    errorNotificationMessage?: string;
}

export default function ToggleColumn({
    value,
    name,
    label = null,
    recordId,
    columnUpdateRoute = null,
    columnExecutionRoute,
    editable = true,
    disabled = false,
    description = null,
    descriptionPosition = 'below',
    successNotificationTitle = 'Updated',
    successNotificationMessage = 'Value updated successfully',
    errorNotificationTitle = 'Error',
    errorNotificationMessage = 'Failed to update value',
}: ToggleColumnProps) {
    const { trans } = useLocalization();
    const { notify } = useNotification();

    // Optimistic save through the column update endpoint (reverts on failure)
    const update = useColumnUpdate<boolean>(Boolean(value), { name, recordId, columnUpdateRoute, editable, disabled });
    const usesUpdateRoute = Boolean(columnUpdateRoute);

    // Compute the execution URL - replace __ID__ placeholder with actual record ID
    const executionUrl = columnExecutionRoute ? columnExecutionRoute.replace('__ID__', String(recordId)) : null;

    // Translated labels - use tables::tables namespace
    const translatedSuccessTitle =
        successNotificationTitle !== 'Updated' ? successNotificationTitle : trans('tables::tables.toggle_column.success_notification_title');
    const translatedSuccessMessage =
        successNotificationMessage !== 'Value updated successfully'
            ? successNotificationMessage
            : trans('tables::tables.toggle_column.success_notification_message');
    const translatedErrorTitle =
        errorNotificationTitle !== 'Error' ? errorNotificationTitle : trans('tables::tables.toggle_column.error_notification_title');
    const translatedErrorMessage =
        errorNotificationMessage !== 'Failed to update value'
            ? errorNotificationMessage
            : trans('tables::tables.toggle_column.error_notification_message');

    // Use the same boolean conversion as the Edit page - simple Boolean() cast
    const [localValue, setLocalValue] = useState<boolean>(() => Boolean(value));
    const [isUpdating, setIsUpdating, isUpdatingRef] = useStateRef<boolean>(false);

    // Watch for prop changes to update local value (but not during updates to avoid conflicts)
    useEffect(() => {
        if (!isUpdatingRef.current) {
            setLocalValue(Boolean(value));
        }
    }, [value, isUpdatingRef]);

    const setChecked = (newValue: boolean) => {
        if (disabled || !editable || isUpdatingRef.current || !executionUrl) return;

        setIsUpdating(true);

        // Send update to backend (convert to 1/0 for database)
        router.patch(
            executionUrl,
            {
                column: name,
                value: newValue ? 1 : 0,
            },
            {
                preserveScroll: true,
                preserveState: true,
                only: ['records'],
                onSuccess: () => {
                    setLocalValue(newValue);
                    setIsUpdating(false);
                    notify(translatedSuccessTitle, translatedSuccessMessage, 'success', {
                        duration: 2000,
                    });
                },
                onError: (errors: Record<string, string>) => {
                    setIsUpdating(false);

                    const errorMessage = errors[name] || translatedErrorMessage;
                    notify(translatedErrorTitle, errorMessage, 'error', {
                        duration: 3000,
                    });
                },
            },
        );
    };

    return (
        <div className="flex flex-col gap-0.5">
            {/* Description above */}
            {description && descriptionPosition === 'above' && (
                <div className="text-[10px] text-muted-foreground/60 leading-tight">{description}</div>
            )}

            {/* Main content */}
            <div className="flex items-center">
                {usesUpdateRoute ? (
                    <Switch
                        checked={Boolean(update.localValue)}
                        disabled={update.isDisabled}
                        aria-label={label || name}
                        aria-busy={update.isSaving}
                        className={cn(update.isSaving && 'opacity-60 cursor-wait')}
                        onCheckedChange={(checked: boolean) => void update.save(checked)}
                    />
                ) : (
                    <Switch
                        checked={localValue}
                        disabled={disabled || !editable || isUpdating || !executionUrl}
                        aria-label={label || name}
                        aria-busy={isUpdating}
                        className={cn(isUpdating && 'opacity-60 cursor-wait', !executionUrl && 'opacity-50 cursor-not-allowed')}
                        onCheckedChange={(checked: boolean) => setChecked(checked)}
                    />
                )}
            </div>

            {/* Description below */}
            {description && descriptionPosition === 'below' && (
                <div className="text-[10px] text-muted-foreground/60 leading-tight">{description}</div>
            )}
        </div>
    );
}
