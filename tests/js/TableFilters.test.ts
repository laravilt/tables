import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import TableFilters from '../../resources/js/components/TableFilters.vue'

const select = (name: string, extra: Record<string, any> = {}) => ({
    name,
    label: name,
    component: 'SelectFilter',
    default: null,
    hasFormData: false,
    formField: { component: 'Select', name, value: null, ...extra },
})

const startDatetime = {
    name: 'start_datetime',
    label: 'Start datetime',
    component: 'Filter',
    default: null,
    hasFormData: true,
    formField: { component: 'DatePicker', name: 'start_date', value: null },
    formFields: [
        { component: 'DatePicker', name: 'start_date', label: 'Start date', value: null },
        { component: 'TimePicker', name: 'start_time', label: 'Start time', value: null },
    ],
}

const rendered = (wrapper: ReturnType<typeof mount>) =>
    wrapper.findAll('.renderer-stub').map((node) => ({
        component: node.attributes('data-component'),
        props: JSON.parse(node.attributes('data-props') || '{}'),
    }))

describe('TableFilters', () => {
    it('renders every field of a multi field filter form, grouped under the filter', () => {
        const wrapper = mount(TableFilters, { props: { filters: [startDatetime as any], state: {} } })

        expect(wrapper.find('fieldset legend').text()).toBe('Start datetime')
        expect(rendered(wrapper).map((r) => [r.component, r.props.name])).toEqual([
            ['DatePicker', 'start_date'],
            ['TimePicker', 'start_time'],
        ])
    })

    it('binds each form field to its own value from the hydrated state', () => {
        const wrapper = mount(TableFilters, {
            props: {
                filters: [startDatetime as any],
                state: { start_datetime: { start_date: '2026-02-01', start_time: '10:30' } },
            },
        })

        const [date, time] = rendered(wrapper)

        expect(date.props.modelValue).toBe('2026-02-01')
        expect(date.props.value).toBe('2026-02-01')
        expect(time.props.modelValue).toBe('10:30')
        expect(time.props.value).toBe('10:30')
    })

    it('shows the state value instead of the serialized field value or default', () => {
        const trashed = { ...select('trashed', { value: 'without' }), default: 'without' }
        const wrapper = mount(TableFilters, { props: { filters: [trashed as any], state: { trashed: 'only' } } })

        const [field] = rendered(wrapper)

        expect(field.props.modelValue).toBe('only')
        expect(field.props.value).toBe('only')
        expect(field.props.name).toBe('trashed')
    })

    it('falls back to the filter default when the state is empty', () => {
        const trashed = { ...select('trashed', { value: null }), default: 'without' }
        const wrapper = mount(TableFilters, { props: { filters: [trashed as any], state: {} } })

        expect(rendered(wrapper)[0].props.modelValue).toBe('without')
    })

    it('hydrates several filter types at once', () => {
        const wrapper = mount(TableFilters, {
            props: {
                filters: [select('status') as any, select('featured') as any, startDatetime as any],
                state: { status: 'live', featured: 'true', start_datetime: { start_date: '2026-02-01' } },
            },
        })

        expect(rendered(wrapper).map((r) => r.props.modelValue)).toEqual(['live', 'true', '2026-02-01', null])
    })

    it('emits updates keyed by filter and field name', async () => {
        const wrapper = mount(TableFilters, { props: { filters: [startDatetime as any, select('status') as any], state: {} } })

        const renderers = wrapper.findAllComponents({ name: 'LaraviltComponentRendererStub' })
        const stubs = wrapper.findAll('.renderer-stub')
        expect(stubs).toHaveLength(3)

        // The stub is a plain component, so drive the listeners the way the real renderer would.
        const instances = wrapper.findAllComponents('.renderer-stub' as any)
        instances[1].vm.$emit('update:modelValue', '10:30')
        instances[2].vm.$emit('update:modelValue', 'live')

        expect(wrapper.emitted('update')).toEqual([
            [expect.objectContaining({ name: 'start_datetime' }), '10:30', 'start_time'],
            [expect.objectContaining({ name: 'status' }), 'live'],
        ])
        expect(renderers).toBeDefined()
    })

    it('uses the inline layout for the above_table bar', () => {
        const wrapper = mount(TableFilters, { props: { filters: [select('status') as any], state: {}, layout: 'inline' } })

        expect(wrapper.classes()).toContain('flex-wrap')
    })
})
