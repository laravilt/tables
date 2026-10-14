/**
 * Pure helpers for table filter state.
 *
 * State is an object keyed by filter name. A simple filter (select, ternary,
 * trashed, text...) stores one scalar (or list) value. A filter built with a
 * multi field `->form([...])` stores an object keyed by form field name, which
 * is sent to the server as `filter[field]=value` and arrives in the PHP query
 * callback as the `$data` array.
 */

export type FilterState = Record<string, any>

export interface FilterFieldDefinition {
    name: string
    [key: string]: any
}

export interface FilterDefinition {
    name: string
    label?: string
    default?: any
    hasFormData?: boolean
    formField?: FilterFieldDefinition | null
    formFields?: FilterFieldDefinition[] | null
    [key: string]: any
}

const isPlainObject = (value: unknown): value is Record<string, any> =>
    value !== null && typeof value === 'object' && !Array.isArray(value)

/**
 * Whether a filter value should be treated as "no value".
 * Arrays and objects are empty when nothing meaningful is left in them.
 */
export const isEmptyFilterValue = (value: unknown): boolean => {
    if (value === null || value === undefined || value === '' || value === false) {
        return true
    }

    if (Array.isArray(value)) {
        return value.every(isEmptyFilterValue)
    }

    if (isPlainObject(value)) {
        return Object.values(value).every(isEmptyFilterValue)
    }

    return false
}

/**
 * Drop empty entries from lists and objects. Returns null when nothing is left.
 */
export const cleanFilterValue = (value: any): any => {
    if (Array.isArray(value)) {
        const items = value.filter((item) => !isEmptyFilterValue(item))
        return items.length > 0 ? items : null
    }

    if (isPlainObject(value)) {
        const entries = Object.entries(value).filter(([, item]) => !isEmptyFilterValue(item))
        return entries.length > 0 ? Object.fromEntries(entries) : null
    }

    return isEmptyFilterValue(value) ? null : value
}

/**
 * Remove empty filters from a state object.
 */
export const cleanFilterState = (state: FilterState): FilterState => {
    const result: FilterState = {}

    Object.entries(state || {}).forEach(([name, value]) => {
        const cleaned = cleanFilterValue(value)

        if (cleaned !== null) {
            result[name] = cleaned
        }
    })

    return result
}

/**
 * Flatten request params into [key, value] pairs using bracket notation, which
 * is what PHP/Laravel parses back into arrays:
 *   { status: ['a', 'b'] }          -> status[]=a&status[]=b
 *   { range: { from: '1', to: '2' }} -> range[from]=1&range[to]=2
 */
export const flattenParams = (params: Record<string, any>): Array<[string, string]> => {
    const pairs: Array<[string, string]> = []

    const walk = (key: string, value: any) => {
        if (value === null || value === undefined) {
            return
        }

        if (Array.isArray(value)) {
            value.forEach((item) => walk(`${key}[]`, item))
        } else if (isPlainObject(value)) {
            Object.entries(value).forEach(([subKey, item]) => walk(`${key}[${subKey}]`, item))
        } else {
            pairs.push([key, String(value)])
        }
    }

    Object.entries(params).forEach(([key, value]) => walk(key, value))

    return pairs
}

/**
 * Build a query string from params, expanding lists and objects with brackets.
 */
export const toQueryString = (params: Record<string, any>): string => {
    const search = new URLSearchParams()

    flattenParams(params).forEach(([key, value]) => search.append(key, value))

    return search.toString()
}

/**
 * Read the state of the given filters from a query string, understanding
 * `name=value`, `name[]=a&name[]=b` and `name[field]=value`.
 */
export const parseFilterStateFromSearch = (search: string, filterNames: string[]): FilterState => {
    const params = new URLSearchParams(search)
    const names = new Set(filterNames)
    const state: FilterState = {}

    params.forEach((value, rawKey) => {
        const match = rawKey.match(/^([^[]+)((?:\[[^\]]*\])*)$/)

        if (!match || !names.has(match[1])) {
            return
        }

        const name = match[1]
        const segments = Array.from(match[2].matchAll(/\[([^\]]*)\]/g)).map((m) => m[1])

        if (segments.length === 0) {
            state[name] = value
            return
        }

        // name[] / name[0]: a list. name[field]: an object.
        const [first] = segments
        const isList = first === '' || /^\d+$/.test(first)

        if (isList) {
            const list = Array.isArray(state[name]) ? state[name] : []
            list.push(value)
            state[name] = list
        } else {
            const object = isPlainObject(state[name]) ? state[name] : {}
            object[first] = value
            state[name] = object
        }
    })

    return cleanFilterState(state)
}

/**
 * Initial state for the filter inputs. The URL wins over the server state
 * because it is what the user sees and may have edited; the server state
 * (read from the request by the PHP table) fills anything the URL lacks.
 */
export const buildInitialFilterState = (
    filters: FilterDefinition[] | undefined,
    serverState: FilterState | undefined,
    search: string,
): FilterState => {
    const names = (filters || []).map((filter) => filter.name)

    return cleanFilterState({
        ...(serverState || {}),
        ...parseFilterStateFromSearch(search, names),
    })
}

/**
 * Whether the filter keeps an object of field values rather than one value.
 */
export const usesFormData = (filter: FilterDefinition): boolean => {
    if (typeof filter.hasFormData === 'boolean') {
        return filter.hasFormData
    }

    const fields = filter.formFields || []

    return fields.length > 0 && !(fields.length === 1 && fields[0].name === filter.name)
}

/**
 * The fields to render for a filter: every form field for a multi field form,
 * otherwise the single field (or the filter's own component).
 */
export const getFilterFields = (filter: FilterDefinition): FilterFieldDefinition[] => {
    if (usesFormData(filter)) {
        return filter.formFields || []
    }

    return filter.formField ? [filter.formField] : []
}

/**
 * Current value of one field of a filter, falling back to the filter default.
 */
export const getFilterFieldValue = (
    state: FilterState,
    filter: FilterDefinition,
    fieldName?: string,
): any => {
    const current = state?.[filter.name]

    if (usesFormData(filter) && fieldName !== undefined) {
        const fromState = isPlainObject(current) ? current[fieldName] : undefined
        const fallback = isPlainObject(filter.default) ? filter.default[fieldName] : undefined

        return fromState ?? fallback ?? null
    }

    return current ?? filter.default ?? null
}

/**
 * Return a new state with one field of a filter changed. Empty values remove
 * the field, and a filter with nothing left is removed from the state.
 */
export const setFilterFieldValue = (
    state: FilterState,
    filter: FilterDefinition,
    value: any,
    fieldName?: string,
): FilterState => {
    const next = { ...state }

    if (usesFormData(filter) && fieldName !== undefined) {
        const current = isPlainObject(next[filter.name]) ? next[filter.name] : {}
        next[filter.name] = { ...current, [fieldName]: value }
    } else {
        next[filter.name] = value
    }

    const cleaned = cleanFilterValue(next[filter.name])

    if (cleaned === null) {
        delete next[filter.name]
    } else {
        next[filter.name] = cleaned
    }

    return next
}

/**
 * Human readable summary of a filter value, for indicators.
 */
export const summarizeFilterValue = (value: any): string => {
    if (Array.isArray(value)) {
        return value.map(summarizeFilterValue).join(', ')
    }

    if (isPlainObject(value)) {
        return Object.values(value)
            .filter((item) => !isEmptyFilterValue(item))
            .map(summarizeFilterValue)
            .join(' ')
    }

    return String(value)
}

/**
 * Number of filters that currently hold a value.
 */
export const countActiveFilters = (state: FilterState): number =>
    Object.values(state || {}).filter((value) => !isEmptyFilterValue(value)).length
