<script setup lang="ts">
import LaraviltComponentRenderer from '@laravilt/forms/components/LaraviltComponentRenderer.vue'
import {
    getFilterFields,
    getFilterFieldValue,
    usesFormData,
    type FilterDefinition,
    type FilterState,
} from '../composables/filterState'

/**
 * Renders the inputs of every table filter. Used by all filter layouts
 * (sidebar, dropdown and above_table) so they behave identically.
 *
 * A filter with a multi field `->form([...])` renders every field, grouped
 * under the filter. Values come from `state`, which Table.vue hydrates from
 * the request/URL.
 */
interface Props {
    filters?: FilterDefinition[]
    state?: FilterState
    layout?: 'stacked' | 'inline'
}

const props = withDefaults(defineProps<Props>(), {
    filters: () => [],
    state: () => ({}),
    layout: 'stacked',
})

const emit = defineEmits<{
    update: [filter: FilterDefinition, value: any, fieldName?: string]
}>()

// Field components are not consistent about reading `value` or `modelValue`
// (TextInput reads `value`, DatePicker reads `modelValue || value`, Select reads
// `value ?? modelValue`), and the serialized field carries its own `value`, so
// always bind both to the live filter state.
const fieldProps = (filter: FilterDefinition, field: Record<string, any>, grouped: boolean) => {
    const value = getFilterFieldValue(props.state, filter, grouped ? field.name : undefined)

    return {
        ...field,
        name: grouped ? field.name : filter.name,
        value,
        modelValue: value,
    }
}

const componentFor = (filter: FilterDefinition, field?: Record<string, any>) =>
    field?.component ?? filter.component
</script>

<template>
    <div :class="layout === 'inline' ? 'flex flex-wrap items-end gap-4' : 'space-y-3'">
        <div
            v-for="filter in filters"
            :key="filter.name"
            class="filter-field"
            :class="layout === 'inline' ? 'min-w-[200px] flex-1' : ''"
            :data-filter="filter.name"
        >
            <!-- Multi field form: every field, grouped under the filter -->
            <fieldset
                v-if="usesFormData(filter)"
                class="space-y-3 rounded-md border border-border p-3"
            >
                <legend v-if="filter.label" class="px-1 text-sm font-medium">
                    {{ filter.label }}
                </legend>
                <div
                    v-for="field in getFilterFields(filter)"
                    :key="field.name"
                    class="filter-form-field"
                    :data-filter-field="field.name"
                >
                    <LaraviltComponentRenderer
                        :component="componentFor(filter, field)"
                        :props="fieldProps(filter, field, true)"
                        @update:modelValue="(value: any) => emit('update', filter, value, field.name)"
                    />
                </div>
            </fieldset>

            <!-- Single field filters (select, ternary, trashed, custom schema) -->
            <LaraviltComponentRenderer
                v-else-if="filter.formField"
                :component="filter.formField.component"
                :props="fieldProps(filter, filter.formField, false)"
                @update:modelValue="(value: any) => emit('update', filter, value)"
            />

            <!-- Fallback to the default filter component based on type -->
            <LaraviltComponentRenderer
                v-else
                :component="filter.component"
                :props="{
                    ...filter,
                    name: filter.name,
                    value: getFilterFieldValue(state, filter),
                    modelValue: getFilterFieldValue(state, filter),
                }"
                @update:modelValue="(value: any) => emit('update', filter, value)"
            />
        </div>
    </div>
</template>
