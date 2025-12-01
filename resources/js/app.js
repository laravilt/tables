/**
 * Tables Plugin for Vue.js
 *
 * Registers all table-related components globally
 *
 * Note: Filter components are not needed here since we use BaseFilter
 * with Laravilt Form components instead.
 */

export default {
    install(app, options = {}) {
        // No table-specific components to register yet
        // Filters use BaseFilter + Form components which are already registered
    }
};
