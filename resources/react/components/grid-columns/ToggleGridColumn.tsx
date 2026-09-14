import { Switch } from '@/components/ui/switch';
import { router, usePage } from '@inertiajs/react';
import { useNotification } from '@laravilt/notifications/composables/useNotification';
import { useLocalization } from '@laravilt/support/composables/useLocalization';
import { useEffect, useState } from 'react';
import { useStateRef } from '../../composables/useStateRef';

export interface ToggleGridColumnProps {
    value: any;
    name: string;
    recordId: number | string;
    resourceSlug: string;
    editable?: boolean;
    disabled?: boolean;
    description?: string | null;
    descriptionPosition?: 'above' | 'below';
}

export default function ToggleGridColumn({
    value,
    name,
    recordId,
    resourceSlug,
    editable = true,
    disabled = false,
    description = null,
    descriptionPosition = 'below',
}: ToggleGridColumnProps) {
    const { trans } = useLocalization();
    const { notify } = useNotification();

    // Get the panel path from Inertia shared data
    const page = usePage();
    const panelPath = (page.props as any).panel?.path || 'dashboard';

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
        if (disabled || !editable || isUpdatingRef.current) return;

        setIsUpdating(true);

        // Send update to backend (convert to 1/0 for database)
        router.patch(
            `/${panelPath}/${resourceSlug}/${recordId}/column`,
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
                    notify(
                        trans('tables::tables.toggle_column.success_notification_title'),
                        trans('tables::tables.toggle_column.success_notification_message'),
                        'success',
                        { duration: 2000 },
                    );
                },
                onError: (errors: Record<string, string>) => {
                    setIsUpdating(false);

                    const errorMessage = errors[name] || trans('tables::tables.toggle_column.error_notification_message');
                    notify(trans('tables::tables.toggle_column.error_notification_title'), errorMessage, 'error', {
                        duration: 3000,
                    });
                },
            },
        );
    };

    return (
        <div className="flex flex-col gap-1">
            {/* Description above */}
            {description && descriptionPosition === 'above' && (
                <div className="text-[11px] text-muted-foreground/70">{description}</div>
            )}

            {/* Main content */}
            <div className="flex items-center">
                <Switch
                    checked={localValue}
                    disabled={disabled || !editable || isUpdating}
                    onCheckedChange={(checked: boolean) => setChecked(checked)}
                />
            </div>

            {/* Description below */}
            {description && descriptionPosition === 'below' && (
                <div className="text-[11px] text-muted-foreground/70">{description}</div>
            )}
        </div>
    );
}
