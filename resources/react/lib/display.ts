/**
 * Equivalent of Vue's `{{ value }}` interpolation (`toDisplayString`):
 * null/undefined → '', arrays and plain objects → pretty JSON, everything else → String().
 * React cannot render objects or booleans as children, Vue can.
 */
export function toDisplayString(value: unknown): string {
    if (typeof value === 'string') {
        return value;
    }

    if (value === null || value === undefined) {
        return '';
    }

    if (
        Array.isArray(value) ||
        (typeof value === 'object' &&
            ((value as object).toString === Object.prototype.toString || typeof (value as any).toString !== 'function'))
    ) {
        return JSON.stringify(value, null, 2);
    }

    return String(value);
}
