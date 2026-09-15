/**
 * Tables plugin for React (twin of resources/js/app.js).
 *
 * Registers all table-related components globally.
 *
 * Note: Filter components are not needed here since we use BaseFilter
 * with Laravilt Form components instead.
 */
export default {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    register(options: Record<string, any> = {}): void {
        // No table-specific components to register yet
        // Filters use BaseFilter + Form components which are already registered
    },
};
