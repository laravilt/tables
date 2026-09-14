import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import { useNotification } from '@laravilt/notifications/composables/useNotification';

export interface ColorColumnProps {
    value: any;
    copyable?: boolean;
    copyMessage?: string | null;
    copyMessageDuration?: number | null;
    wrap?: boolean;
    description?: string | null;
    descriptionPosition?: 'above' | 'below';
    maxVisible?: number;
}

export default function ColorColumn({
    value,
    copyable = false,
    copyMessage = null,
    copyMessageDuration = null,
    description = null,
    descriptionPosition = 'below',
    maxVisible = 4,
}: ColorColumnProps) {
    const { notify } = useNotification();

    const colors: any[] = !value ? [] : Array.isArray(value) ? value : [value];
    const visibleColors = colors.slice(0, maxVisible);
    const hiddenColors = colors.slice(maxVisible);
    const hasMoreColors = colors.length > maxVisible;

    const handleCopy = (color: string) => {
        if (copyable && color) {
            navigator.clipboard.writeText(color);
            notify(copyMessage || 'Copied!', `Color ${color} copied to clipboard`, 'success', {
                duration: copyMessageDuration || 1500,
            });
        }
    };

    return (
        <div className="flex flex-col gap-1 max-w-full overflow-hidden">
            {/* Description above */}
            {description && descriptionPosition === 'above' && (
                <div className="text-[11px] text-muted-foreground/70">{description}</div>
            )}

            {colors.length > 1 ? (
                // Main content - stacked circles style for multiple colors
                <div className="flex items-center">
                    <div className="flex -space-x-1.5">
                        {visibleColors.map((color: any, index: number) => (
                            <button
                                key={index}
                                type="button"
                                className={cn(
                                    'h-6 w-6 rounded-full border-2 border-background shrink-0 shadow-sm',
                                    copyable ? 'cursor-pointer hover:ring-2 hover:ring-ring hover:z-10 transition-all' : 'cursor-default',
                                )}
                                style={{ backgroundColor: color, zIndex: visibleColors.length - index }}
                                title={color}
                                onClick={() => {
                                    if (copyable) handleCopy(color);
                                }}
                            />
                        ))}
                        {/* More colors indicator */}
                        {hasMoreColors && (
                            <TooltipProvider>
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <div
                                            className="h-6 w-6 rounded-full border-2 border-background bg-muted flex items-center justify-center text-[10px] font-medium text-muted-foreground shrink-0 shadow-sm"
                                            style={{ zIndex: 0 }}
                                        >
                                            +{hiddenColors.length}
                                        </div>
                                    </TooltipTrigger>
                                    <TooltipContent side="top" className="p-2">
                                        <div className="flex flex-wrap gap-1 max-w-[150px]">
                                            {hiddenColors.map((color: any, index: number) => (
                                                <button
                                                    key={index}
                                                    type="button"
                                                    className={cn(
                                                        'h-5 w-5 rounded border border-border shrink-0',
                                                        copyable ? 'cursor-pointer hover:ring-2 hover:ring-ring transition-all' : 'cursor-default',
                                                    )}
                                                    style={{ backgroundColor: color }}
                                                    title={color}
                                                    onClick={() => {
                                                        if (copyable) handleCopy(color);
                                                    }}
                                                />
                                            ))}
                                        </div>
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        )}
                    </div>
                </div>
            ) : colors.length === 1 ? (
                // Single color - simple square style
                <div className="flex items-center">
                    <button
                        type="button"
                        className={cn(
                            'h-6 w-6 rounded border border-border shrink-0',
                            copyable ? 'cursor-pointer hover:ring-2 hover:ring-ring transition-all' : 'cursor-default',
                        )}
                        style={{ backgroundColor: colors[0] }}
                        title={colors[0]}
                        onClick={() => {
                            if (copyable) handleCopy(colors[0]);
                        }}
                    />
                </div>
            ) : null}

            {/* Description below */}
            {description && descriptionPosition === 'below' && (
                <div className="text-[11px] text-muted-foreground/70">{description}</div>
            )}
        </div>
    );
}
