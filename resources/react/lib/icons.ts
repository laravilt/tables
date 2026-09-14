import { resolveIcon } from '@laravilt/support/lib/icons';
import * as LucideIcons from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

/**
 * Heroicon → Lucide mapping used by every Vue column component.
 */
const heroiconMap: Record<string, string> = {
    'heroicon-o-check-circle': 'CheckCircle',
    'heroicon-o-x-circle': 'XCircle',
    'heroicon-o-exclamation-circle': 'AlertCircle',
    'heroicon-o-information-circle': 'Info',
};

/**
 * Resolve a column icon name exactly like the Vue columns did
 * (`iconMap[name] || name` → PascalCase on `-` → lookup in every lucide export, aliases included),
 * falling back to the shared `resolveIcon()` for the formats it understands.
 *
 * The fallback is needed because `resolveIcon()` only searches lucide's `icons` map,
 * which does not contain aliases such as `CheckCircle`, `XCircle` or `AlertCircle`.
 */
export function resolveColumnIcon(name: unknown): LucideIcon | null {
    if (!name) {
        return null;
    }

    const iconName = String(name);
    const mappedIconName = heroiconMap[iconName] || iconName;

    const pascalCaseName = mappedIconName
        .split('-')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join('');

    const direct = (LucideIcons as Record<string, unknown>)[pascalCaseName];

    if (direct && (typeof direct === 'object' || typeof direct === 'function')) {
        return direct as LucideIcon;
    }

    return resolveIcon(iconName);
}
