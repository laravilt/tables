import { describe, expect, it } from 'vitest'
import {
    buildInitialFilterState,
    cleanFilterState,
    countActiveFilters,
    getFilterFieldValue,
    getFilterFields,
    isEmptyFilterValue,
    parseFilterStateFromSearch,
    setFilterFieldValue,
    summarizeFilterValue,
    toQueryString,
    usesFormData,
} from '../../resources/js/composables/filterState'

const status = { name: 'status', formField: { name: 'status' }, default: null }
const trashed = { name: 'trashed', formField: { name: 'trashed' }, default: 'without' }
const startDatetime = {
    name: 'start_datetime',
    hasFormData: true,
    formField: { name: 'start_date' },
    formFields: [{ name: 'start_date' }, { name: 'start_time' }],
}

describe('isEmptyFilterValue', () => {
    it('treats null, empty string, false and empty containers as empty', () => {
        expect(isEmptyFilterValue(null)).toBe(true)
        expect(isEmptyFilterValue(undefined)).toBe(true)
        expect(isEmptyFilterValue('')).toBe(true)
        expect(isEmptyFilterValue(false)).toBe(true)
        expect(isEmptyFilterValue([])).toBe(true)
        expect(isEmptyFilterValue({ a: '', b: null })).toBe(true)
    })

    it('keeps zero and non-empty values', () => {
        expect(isEmptyFilterValue(0)).toBe(false)
        expect(isEmptyFilterValue('0')).toBe(false)
        expect(isEmptyFilterValue({ a: '', b: 'x' })).toBe(false)
    })
})

describe('parseFilterStateFromSearch', () => {
    it('reads scalar filters from the URL', () => {
        const state = parseFilterStateFromSearch('?page=1&status=live&trashed=only&other=1', ['status', 'trashed'])

        expect(state).toEqual({ status: 'live', trashed: 'only' })
    })

    it('reads list filters', () => {
        expect(parseFilterStateFromSearch('status[]=a&status[]=b', ['status'])).toEqual({ status: ['a', 'b'] })
        expect(parseFilterStateFromSearch('status[0]=a&status[1]=b', ['status'])).toEqual({ status: ['a', 'b'] })
    })

    it('reads multi field form filters', () => {
        const search = 'start_datetime[start_date]=2026-02-01&start_datetime[start_time]=10%3A30'

        expect(parseFilterStateFromSearch(search, ['start_datetime'])).toEqual({
            start_datetime: { start_date: '2026-02-01', start_time: '10:30' },
        })
    })

    it('drops empty values', () => {
        expect(parseFilterStateFromSearch('status=&start_datetime[start_date]=', ['status', 'start_datetime'])).toEqual({})
    })
})

describe('buildInitialFilterState', () => {
    it('hydrates from the server state when the URL has nothing', () => {
        expect(buildInitialFilterState([status], { status: 'live' }, '')).toEqual({ status: 'live' })
    })

    it('lets the URL override the server state', () => {
        expect(buildInitialFilterState([status], { status: 'draft' }, '?status=live')).toEqual({ status: 'live' })
    })

    it('hydrates every filter type at once', () => {
        const state = buildInitialFilterState(
            [status, trashed, startDatetime],
            { trashed: 'only' },
            '?status=live&start_datetime[start_date]=2026-02-01',
        )

        expect(state).toEqual({
            status: 'live',
            trashed: 'only',
            start_datetime: { start_date: '2026-02-01' },
        })
    })
})

describe('toQueryString', () => {
    it('round trips lists and form data through parseFilterStateFromSearch', () => {
        const state = {
            status: ['a', 'b'],
            trashed: 'only',
            start_datetime: { start_date: '2026-02-01', start_time: '10:30' },
        }

        const query = toQueryString({ page: 1, ...state })

        expect(decodeURIComponent(query)).toContain('status[]=a&status[]=b')
        expect(decodeURIComponent(query)).toContain('start_datetime[start_date]=2026-02-01')
        expect(parseFilterStateFromSearch(query, ['status', 'trashed', 'start_datetime'])).toEqual(state)
    })

    it('skips null values', () => {
        expect(toQueryString({ a: null, b: undefined, c: 'x' })).toBe('c=x')
    })
})

describe('form filters', () => {
    it('detects multi field forms', () => {
        expect(usesFormData(startDatetime)).toBe(true)
        expect(usesFormData(status)).toBe(false)
        expect(usesFormData({ name: 'title', formFields: [{ name: 'title' }] })).toBe(false)
        expect(usesFormData({ name: 'range', formFields: [{ name: 'from' }, { name: 'to' }] })).toBe(true)
    })

    it('returns every field of a multi field form', () => {
        expect(getFilterFields(startDatetime).map((field) => field.name)).toEqual(['start_date', 'start_time'])
        expect(getFilterFields(status).map((field) => field.name)).toEqual(['status'])
    })

    it('keeps state per field and removes the filter when all fields are cleared', () => {
        let state = setFilterFieldValue({}, startDatetime, '2026-02-01', 'start_date')
        state = setFilterFieldValue(state, startDatetime, '10:30', 'start_time')

        expect(state).toEqual({ start_datetime: { start_date: '2026-02-01', start_time: '10:30' } })
        expect(getFilterFieldValue(state, startDatetime, 'start_time')).toBe('10:30')

        state = setFilterFieldValue(state, startDatetime, null, 'start_date')
        expect(state).toEqual({ start_datetime: { start_time: '10:30' } })

        state = setFilterFieldValue(state, startDatetime, '', 'start_time')
        expect(state).toEqual({})
    })

    it('does not mutate the previous state', () => {
        const previous = { start_datetime: { start_date: '2026-02-01' } }

        setFilterFieldValue(previous, startDatetime, '10:30', 'start_time')

        expect(previous).toEqual({ start_datetime: { start_date: '2026-02-01' } })
    })
})

describe('scalar filters', () => {
    it('reads the state value before the default', () => {
        expect(getFilterFieldValue({ trashed: 'only' }, trashed)).toBe('only')
        expect(getFilterFieldValue({}, trashed)).toBe('without')
        expect(getFilterFieldValue({}, status)).toBeNull()
    })

    it('sets and clears a value', () => {
        expect(setFilterFieldValue({}, status, 'live')).toEqual({ status: 'live' })
        expect(setFilterFieldValue({ status: 'live' }, status, '')).toEqual({})
        expect(setFilterFieldValue({ status: 'live' }, status, null)).toEqual({})
    })
})

describe('summaries and counts', () => {
    it('summarizes values for indicators', () => {
        expect(summarizeFilterValue('live')).toBe('live')
        expect(summarizeFilterValue(['a', 'b'])).toBe('a, b')
        expect(summarizeFilterValue({ start_date: '2026-02-01', start_time: '' })).toBe('2026-02-01')
        expect(summarizeFilterValue({ start_date: '2026-02-01', start_time: '10:30' })).toBe('2026-02-01 10:30')
    })

    it('counts active filters', () => {
        expect(countActiveFilters({ status: 'live', start_datetime: { a: '' }, other: [] })).toBe(1)
        expect(countActiveFilters(cleanFilterState({ status: 'live' }))).toBe(1)
    })
})
