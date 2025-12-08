<script setup lang="ts">
import { ref, computed, watch, onMounted, h, defineComponent } from 'vue'
import { ChevronDown, ChevronRight, Send, Copy, Check, Loader2, Plus, Trash2, Code, FileJson, Download, ChevronsDownUp, ChevronsUpDown, Key, Eye, EyeOff, List, PlusCircle, Edit, Trash, Database, Zap, FolderOpen } from 'lucide-vue-next'

// Recursive JSON Tree component for collapsible display
const JsonNode = defineComponent({
    name: 'JsonNode',
    props: {
        data: { type: [Object, Array, String, Number, Boolean, null] as any, required: true },
        keyName: { type: String, default: '' },
        depth: { type: Number, default: 0 },
        isLast: { type: Boolean, default: true },
        forceExpand: { type: Boolean, default: null }
    },
    setup(props) {
        const isExpanded = ref(props.forceExpand !== null ? props.forceExpand : props.depth < 2)

        const isObject = computed(() => props.data !== null && typeof props.data === 'object' && !Array.isArray(props.data))
        const isArray = computed(() => Array.isArray(props.data))
        const isCollapsible = computed(() => isObject.value || isArray.value)
        const isEmpty = computed(() => {
            if (isArray.value) return props.data.length === 0
            if (isObject.value) return Object.keys(props.data).length === 0
            return false
        })

        const entries = computed(() => {
            if (isArray.value) return props.data.map((v: any, i: number) => [i, v])
            if (isObject.value) return Object.entries(props.data)
            return []
        })

        const valueColor = computed(() => {
            if (props.data === null) return 'text-gray-500'
            if (typeof props.data === 'boolean') return 'text-purple-600 dark:text-purple-400'
            if (typeof props.data === 'number') return 'text-blue-600 dark:text-blue-400'
            if (typeof props.data === 'string') return 'text-green-600 dark:text-green-400'
            return 'text-foreground'
        })

        const formatValue = (val: any) => {
            if (val === null) return 'null'
            if (typeof val === 'string') return `"${val}"`
            return String(val)
        }

        const toggle = () => {
            if (isCollapsible.value) {
                isExpanded.value = !isExpanded.value
            }
        }

        return () => {
            const indent = props.depth * 16
            const comma = props.isLast ? '' : ','

            if (!isCollapsible.value) {
                return h('div', { class: 'flex items-start leading-6', style: { paddingLeft: `${indent}px` } }, [
                    props.keyName && h('span', { class: 'text-rose-600 dark:text-rose-400' }, `"${props.keyName}"`),
                    props.keyName && h('span', { class: 'text-foreground' }, ': '),
                    h('span', { class: valueColor.value }, formatValue(props.data)),
                    h('span', { class: 'text-foreground' }, comma)
                ])
            }

            if (isEmpty.value) {
                const brackets = isArray.value ? '[]' : '{}'
                return h('div', { class: 'flex items-start leading-6', style: { paddingLeft: `${indent}px` } }, [
                    props.keyName && h('span', { class: 'text-rose-600 dark:text-rose-400' }, `"${props.keyName}"`),
                    props.keyName && h('span', { class: 'text-foreground' }, ': '),
                    h('span', { class: 'text-foreground' }, brackets + comma)
                ])
            }

            const openBracket = isArray.value ? '[' : '{'
            const closeBracket = isArray.value ? ']' : '}'
            const itemCount = entries.value.length

            if (!isExpanded.value) {
                return h('div', { class: 'flex items-start cursor-pointer hover:bg-muted/50 rounded leading-6', style: { paddingLeft: `${indent}px` }, onClick: toggle }, [
                    h(ChevronRight, { class: 'h-4 w-4 text-muted-foreground flex-shrink-0 mt-1' }),
                    props.keyName && h('span', { class: 'text-rose-600 dark:text-rose-400 ml-1' }, `"${props.keyName}"`),
                    props.keyName && h('span', { class: 'text-foreground' }, ': '),
                    h('span', { class: 'text-foreground' }, openBracket),
                    h('span', { class: 'text-muted-foreground text-xs mx-1' }, `${itemCount} ${isArray.value ? 'items' : 'keys'}`),
                    h('span', { class: 'text-foreground' }, closeBracket + comma)
                ])
            }

            return h('div', {}, [
                h('div', { class: 'flex items-start cursor-pointer hover:bg-muted/50 rounded leading-6', style: { paddingLeft: `${indent}px` }, onClick: toggle }, [
                    h(ChevronDown, { class: 'h-4 w-4 text-muted-foreground flex-shrink-0 mt-1' }),
                    props.keyName && h('span', { class: 'text-rose-600 dark:text-rose-400 ml-1' }, `"${props.keyName}"`),
                    props.keyName && h('span', { class: 'text-foreground' }, ': '),
                    h('span', { class: 'text-foreground' }, openBracket)
                ]),
                ...entries.value.map(([key, value]: [any, any], index: number) =>
                    h(JsonNode, {
                        key: key,
                        data: value,
                        keyName: isArray.value ? '' : String(key),
                        depth: props.depth + 1,
                        isLast: index === entries.value.length - 1,
                        forceExpand: props.forceExpand
                    })
                ),
                h('div', { class: 'leading-6', style: { paddingLeft: `${indent}px` } }, [
                    h('span', { class: 'text-foreground ml-5' }, closeBracket + comma)
                ])
            ])
        }
    }
})

interface ApiColumn {
    name: string
    label?: string
    type?: string
    format?: string
    nullable?: boolean
    description?: string
    example?: any
    sortable?: boolean
    filterable?: boolean
    searchable?: boolean
    enum?: string[]
}

interface ApiAction {
    name: string
    slug: string
    label: string
    description?: string
    method: string
    requiresRecord: boolean
    bulk: boolean
    requiresConfirmation: boolean
    confirmationMessage?: string
    hidden: boolean
    requestSchema?: any
    responseSchema?: any
    successMessage?: string
}

interface ApiResource {
    columns: ApiColumn[]
    endpoint: string
    baseUrl: string
    fullUrl: string
    paginated: boolean
    perPage: number
    allowedFilters: string[]
    allowedSorts: string[]
    allowedIncludes?: string[]
    searchableColumns: string[]
    description?: string
    version?: string
    authenticated: boolean
    headers: Record<string, string>
    sampleRequest?: any
    sampleResponse?: any
    fillableFields?: string[]
    actions?: ApiAction[]
    openApiSpec?: any
}

interface Props {
    apiResource: ApiResource
    resourceSlug?: string
    records?: any[]
    apiToken?: string | null
}

const props = withDefaults(defineProps<Props>(), {
    resourceSlug: '',
    records: () => [],
    apiToken: null
})

// HTTP Methods
const httpMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as const
type HttpMethod = typeof httpMethods[number]

// Operation types
type OperationType = 'index' | 'show' | 'store' | 'update' | 'destroy' | 'bulkDestroy' | string

// Selected operation
const selectedOperation = ref<OperationType>('index')
const recordId = ref('')

// Request state
const method = ref<HttpMethod>('GET')
const url = ref(props.apiResource?.fullUrl || '')
const isLoading = ref(false)
const response = ref<any>(null)
const responseStatus = ref<number | null>(null)
const responseTime = ref<number | null>(null)
const responseSize = ref<number | null>(null)
const responseHeaders = ref<Record<string, string>>({})
const error = ref<string | null>(null)
const copiedResponse = ref(false)

// Active tabs
const requestTab = ref<'params' | 'headers' | 'body' | 'auth'>('params')
const responseTab = ref<'body' | 'headers'>('body')

// Response view
const responseViewMode = ref<'pretty' | 'raw'>('pretty')
const jsonExpandKey = ref(0)
const forceExpandAll = ref(false)

// API Token
const apiTokenInput = ref(props.apiToken || '')
const showToken = ref(false)

// Headers
const headers = ref<Array<{ key: string; value: string; enabled: boolean }>>([
    { key: 'Accept', value: 'application/json', enabled: true },
    { key: 'Content-Type', value: 'application/json', enabled: true },
])

// Query parameters
const queryParams = ref<Array<{ key: string; value: string; enabled: boolean }>>([])

// Request body
const requestBody = ref('')

// Sidebar collapsed
const sidebarCollapsed = ref(false)

// Operations list
const operations = computed(() => [
    {
        key: 'index' as OperationType,
        label: 'List All',
        method: 'GET' as HttpMethod,
        icon: List,
        description: 'Get paginated list of records',
        path: props.apiResource?.fullUrl || '',
    },
    {
        key: 'show' as OperationType,
        label: 'Get One',
        method: 'GET' as HttpMethod,
        icon: Eye,
        description: 'Get a single record by ID',
        path: `${props.apiResource?.fullUrl || ''}/{id}`,
        needsId: true,
    },
    {
        key: 'store' as OperationType,
        label: 'Create',
        method: 'POST' as HttpMethod,
        icon: PlusCircle,
        description: 'Create a new record',
        path: props.apiResource?.fullUrl || '',
        hasBody: true,
    },
    {
        key: 'update' as OperationType,
        label: 'Update',
        method: 'PUT' as HttpMethod,
        icon: Edit,
        description: 'Update an existing record',
        path: `${props.apiResource?.fullUrl || ''}/{id}`,
        needsId: true,
        hasBody: true,
    },
    {
        key: 'destroy' as OperationType,
        label: 'Delete',
        method: 'DELETE' as HttpMethod,
        icon: Trash,
        description: 'Delete a single record',
        path: `${props.apiResource?.fullUrl || ''}/{id}`,
        needsId: true,
    },
    {
        key: 'bulkDestroy' as OperationType,
        label: 'Bulk Delete',
        method: 'DELETE' as HttpMethod,
        icon: Database,
        description: 'Delete multiple records',
        path: `${props.apiResource?.fullUrl || ''}/bulk`,
        hasBody: true,
    },
])

// Custom actions from API resource
const customActions = computed(() => {
    if (!props.apiResource?.actions) return []
    return props.apiResource.actions.filter(a => !a.hidden).map(action => ({
        key: `action_${action.slug}` as OperationType,
        label: action.label,
        method: action.method.toUpperCase() as HttpMethod,
        icon: Zap,
        description: action.description || '',
        path: action.requiresRecord
            ? `${props.apiResource?.fullUrl || ''}/{id}/actions/${action.slug}`
            : `${props.apiResource?.fullUrl || ''}/actions/${action.slug}`,
        needsId: action.requiresRecord,
        hasBody: ['POST', 'PUT', 'PATCH'].includes(action.method.toUpperCase()),
        bulk: action.bulk,
        action,
    }))
})

// Current operation
const currentOperation = computed(() => {
    const op = operations.value.find(o => o.key === selectedOperation.value)
    if (op) return op
    return customActions.value.find(a => a.key === selectedOperation.value)
})

// Method colors
const methodColors: Record<HttpMethod, string> = {
    GET: 'bg-green-500',
    POST: 'bg-yellow-500',
    PUT: 'bg-blue-500',
    PATCH: 'bg-purple-500',
    DELETE: 'bg-red-500',
}

const methodTextColors: Record<HttpMethod, string> = {
    GET: 'text-green-500',
    POST: 'text-yellow-500',
    PUT: 'text-blue-500',
    PATCH: 'text-purple-500',
    DELETE: 'text-red-500',
}

const methodBadgeColors: Record<HttpMethod, string> = {
    GET: 'bg-green-500/10 text-green-600 dark:text-green-400',
    POST: 'bg-yellow-500/10 text-yellow-600 dark:text-yellow-400',
    PUT: 'bg-blue-500/10 text-blue-600 dark:text-blue-400',
    PATCH: 'bg-purple-500/10 text-purple-600 dark:text-purple-400',
    DELETE: 'bg-red-500/10 text-red-600 dark:text-red-400',
}

// Select an operation
const selectOperation = (opKey: OperationType) => {
    selectedOperation.value = opKey
    const op = currentOperation.value
    if (op) {
        method.value = op.method
        updateUrlForOperation()
        resetParamsForOperation()

        if (op.hasBody) {
            requestTab.value = 'body'
            if (opKey === 'store' || opKey === 'update') {
                generateSampleBody()
            } else if (opKey === 'bulkDestroy') {
                requestBody.value = JSON.stringify({ ids: [] }, null, 2)
            }
        } else {
            requestTab.value = 'params'
        }
    }
}

// Update URL based on operation
const updateUrlForOperation = () => {
    const op = currentOperation.value
    if (!op) return

    let path = op.path
    if (op.needsId && recordId.value) {
        path = path.replace('{id}', recordId.value)
    }
    url.value = path
}

// Reset params for operation
const resetParamsForOperation = () => {
    if (selectedOperation.value === 'index') {
        queryParams.value = [
            { key: 'page', value: '1', enabled: true },
            { key: 'per_page', value: String(props.apiResource?.perPage || 15), enabled: true },
        ]
    } else {
        queryParams.value = []
    }
}

// Watch recordId changes
watch(recordId, () => {
    updateUrlForOperation()
})

// Status colors
const getStatusColor = (status: number) => {
    if (status >= 200 && status < 300) return 'text-green-500'
    if (status >= 300 && status < 400) return 'text-blue-500'
    if (status >= 400 && status < 500) return 'text-yellow-500'
    if (status >= 500) return 'text-red-500'
    return 'text-gray-500'
}

const getStatusBg = (status: number) => {
    if (status >= 200 && status < 300) return 'bg-green-500/10'
    if (status >= 300 && status < 400) return 'bg-blue-500/10'
    if (status >= 400 && status < 500) return 'bg-yellow-500/10'
    if (status >= 500) return 'bg-red-500/10'
    return 'bg-gray-500/10'
}

// Computed URL with query params
const computedUrl = computed(() => {
    const baseUrl = url.value
    const enabledParams = queryParams.value.filter(p => p.enabled && p.key && p.value)
    if (enabledParams.length === 0) return baseUrl
    const params = new URLSearchParams()
    enabledParams.forEach(p => params.append(p.key, p.value))
    return `${baseUrl}?${params.toString()}`
})

// Formatted response
const formattedResponse = computed(() => {
    if (!response.value) return ''
    if (typeof response.value === 'string') return response.value
    return JSON.stringify(response.value, null, 2)
})

// Faker-like random data generators
const faker = {
    firstName: () => ['John', 'Jane', 'Michael', 'Sarah', 'David', 'Emily', 'James', 'Emma'][Math.floor(Math.random() * 8)],
    lastName: () => ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis'][Math.floor(Math.random() * 8)],
    fullName: () => `${faker.firstName()} ${faker.lastName()}`,
    email: () => `${faker.firstName().toLowerCase()}${Math.floor(Math.random() * 999)}@example.com`,
    phone: () => `+1 ${Math.floor(Math.random() * 900) + 100} ${Math.floor(Math.random() * 900) + 100} ${Math.floor(Math.random() * 9000) + 1000}`,
    company: () => ['Acme Inc.', 'Tech Corp', 'Global Solutions', 'Digital Services', 'Innovation Labs'][Math.floor(Math.random() * 5)],
    website: () => `https://${faker.company().toLowerCase().replace(/[^a-z]/g, '')}.com`,
    address: () => `${Math.floor(Math.random() * 9999) + 1} ${['Main', 'Oak', 'Park', 'Cedar', 'Elm'][Math.floor(Math.random() * 5)]} ${['St', 'Ave', 'Blvd', 'Dr'][Math.floor(Math.random() * 4)]}`,
    city: () => ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix', 'San Diego'][Math.floor(Math.random() * 6)],
    state: () => ['NY', 'CA', 'TX', 'FL', 'IL', 'PA', 'OH', 'GA'][Math.floor(Math.random() * 8)],
    country: () => ['USA', 'Canada', 'UK', 'Australia', 'Germany'][Math.floor(Math.random() * 5)],
    postalCode: () => String(Math.floor(Math.random() * 90000) + 10000),
    boolean: () => Math.random() > 0.5,
    number: (min = 0, max = 10000) => Math.floor(Math.random() * (max - min + 1)) + min,
    decimal: (min = 0, max = 10000) => Number((Math.random() * (max - min) + min).toFixed(2)),
    date: () => {
        const d = new Date()
        d.setDate(d.getDate() - Math.floor(Math.random() * 365 * 30))
        return d.toISOString().split('T')[0]
    },
    datetime: () => new Date(Date.now() - Math.floor(Math.random() * 365 * 24 * 60 * 60 * 1000)).toISOString(),
    tags: () => {
        const allTags = ['vip', 'newsletter', 'premium', 'active', 'loyal', 'new']
        return allTags.slice(0, Math.floor(Math.random() * 3) + 1)
    },
    status: () => ['active', 'inactive', 'pending'][Math.floor(Math.random() * 3)],
    type: () => ['individual', 'business'][Math.floor(Math.random() * 2)],
    text: () => ['Lorem ipsum dolor sit amet', 'Consectetur adipiscing elit', 'Sed do eiusmod tempor'][Math.floor(Math.random() * 3)],
}

// Generate sample request body
const generateSampleBody = () => {
    if (!props.apiResource?.columns) {
        requestBody.value = '{}'
        return
    }

    const sampleData: Record<string, any> = {}
    const fillableFields = props.apiResource.fillableFields || []

    props.apiResource.columns.forEach(col => {
        if (fillableFields.length > 0 && !fillableFields.includes(col.name)) return
        if (['id', 'created_at', 'updated_at', 'deleted_at'].includes(col.name)) return

        const name = col.name.toLowerCase()

        if (name === 'name' || name === 'full_name' || name === 'fullname') {
            sampleData[col.name] = faker.fullName()
        } else if (name === 'first_name' || name === 'firstname') {
            sampleData[col.name] = faker.firstName()
        } else if (name === 'last_name' || name === 'lastname') {
            sampleData[col.name] = faker.lastName()
        } else if (name === 'email' || name.includes('email')) {
            sampleData[col.name] = faker.email()
        } else if (name === 'phone' || name.includes('phone') || name.includes('mobile')) {
            sampleData[col.name] = faker.phone()
        } else if (name === 'company' || name.includes('company')) {
            sampleData[col.name] = faker.company()
        } else if (name === 'website' || name.includes('url') || name.includes('site')) {
            sampleData[col.name] = faker.website()
        } else if (name === 'address' || name.includes('address') || name.includes('street')) {
            sampleData[col.name] = faker.address()
        } else if (name === 'city') {
            sampleData[col.name] = faker.city()
        } else if (name === 'state' || name === 'province') {
            sampleData[col.name] = faker.state()
        } else if (name === 'country') {
            sampleData[col.name] = faker.country()
        } else if (name === 'postal_code' || name === 'zip' || name === 'zipcode') {
            sampleData[col.name] = faker.postalCode()
        } else if (name === 'status') {
            sampleData[col.name] = col.enum ? col.enum[Math.floor(Math.random() * col.enum.length)] : faker.status()
        } else if (name === 'type') {
            sampleData[col.name] = col.enum ? col.enum[Math.floor(Math.random() * col.enum.length)] : faker.type()
        } else if (name.includes('birth') || name.includes('dob')) {
            sampleData[col.name] = faker.date()
        } else if (name.includes('tags') || name.includes('labels')) {
            sampleData[col.name] = faker.tags()
        } else if (name.includes('credit') || name.includes('limit') || name.includes('amount') || name.includes('price') || name.includes('spent') || name.includes('total')) {
            sampleData[col.name] = faker.decimal(100, 10000)
        } else if (col.enum && col.enum.length > 0) {
            sampleData[col.name] = col.enum[Math.floor(Math.random() * col.enum.length)]
        } else if (col.type === 'boolean' || name.includes('is_') || name.includes('has_')) {
            sampleData[col.name] = faker.boolean()
        } else if (col.type === 'integer' || col.type === 'number') {
            sampleData[col.name] = faker.number(1, 1000)
        } else if (col.type === 'date') {
            sampleData[col.name] = faker.date()
        } else if (col.type === 'datetime') {
            sampleData[col.name] = faker.datetime()
        } else if (col.type === 'array') {
            sampleData[col.name] = []
        } else {
            sampleData[col.name] = col.example !== undefined && col.example !== null ? col.example : faker.text()
        }
    })

    requestBody.value = JSON.stringify(sampleData, null, 2)
}

// Send request
const sendRequest = async () => {
    isLoading.value = true
    error.value = null
    response.value = null
    responseStatus.value = null
    responseTime.value = null
    responseSize.value = null
    responseHeaders.value = {}

    const startTime = performance.now()

    try {
        const requestHeaders: Record<string, string> = {}
        headers.value
            .filter(h => h.enabled && h.key)
            .forEach(h => {
                requestHeaders[h.key] = h.value
            })

        if (apiTokenInput.value) {
            requestHeaders['Authorization'] = `Bearer ${apiTokenInput.value}`
        }

        if (method.value !== 'GET') {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            if (csrfToken) {
                requestHeaders['X-CSRF-TOKEN'] = csrfToken
            }
        }

        const options: RequestInit = {
            method: method.value,
            headers: requestHeaders,
            credentials: 'same-origin',
        }

        if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method.value) && requestBody.value) {
            options.body = requestBody.value
        }

        const res = await fetch(computedUrl.value, options)
        const endTime = performance.now()

        responseStatus.value = res.status
        responseTime.value = Math.round(endTime - startTime)

        res.headers.forEach((value, key) => {
            responseHeaders.value[key] = value
        })

        const text = await res.text()
        responseSize.value = new Blob([text]).size

        const contentType = res.headers.get('content-type')
        if (contentType?.includes('application/json')) {
            try {
                response.value = JSON.parse(text)
            } catch {
                response.value = text
            }
        } else {
            response.value = text
        }
    } catch (err: any) {
        error.value = err.message || 'Request failed'
    } finally {
        isLoading.value = false
    }
}

// Add/remove functions
const addQueryParam = () => {
    queryParams.value.push({ key: '', value: '', enabled: true })
}

const removeQueryParam = (index: number) => {
    queryParams.value.splice(index, 1)
}

const addHeader = () => {
    headers.value.push({ key: '', value: '', enabled: true })
}

const removeHeader = (index: number) => {
    headers.value.splice(index, 1)
}

// Copy response
const copyResponse = async () => {
    try {
        const text = typeof response.value === 'string'
            ? response.value
            : JSON.stringify(response.value, null, 2)
        await navigator.clipboard.writeText(text)
        copiedResponse.value = true
        setTimeout(() => copiedResponse.value = false, 2000)
    } catch (err) {
        console.error('Failed to copy response:', err)
    }
}

// Expand/Collapse all
const expandAllJson = () => {
    forceExpandAll.value = true
    jsonExpandKey.value++
}

const collapseAllJson = () => {
    forceExpandAll.value = false
    jsonExpandKey.value++
}

// Format bytes
const formatBytes = (bytes: number) => {
    if (bytes === 0) return '0 B'
    const k = 1024
    const sizes = ['B', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

// Export functions
const showExportMenu = ref(false)
const copiedOpenApi = ref(false)

const exportOpenApiJson = () => {
    if (!props.apiResource?.openApiSpec) return
    const blob = new Blob([JSON.stringify(props.apiResource.openApiSpec, null, 2)], { type: 'application/json' })
    const downloadUrl = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = downloadUrl
    a.download = `${props.resourceSlug || 'api'}-openapi.json`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(downloadUrl)
    showExportMenu.value = false
}

const toYaml = (obj: any, indent = 0): string => {
    const prefix = '  '.repeat(indent)
    let yaml = ''
    for (const [key, value] of Object.entries(obj)) {
        if (value === null || value === undefined) {
            yaml += `${prefix}${key}: null\n`
        } else if (typeof value === 'boolean') {
            yaml += `${prefix}${key}: ${value}\n`
        } else if (typeof value === 'number') {
            yaml += `${prefix}${key}: ${value}\n`
        } else if (typeof value === 'string') {
            if (value.includes('\n') || value.includes(':') || value.includes('#')) {
                yaml += `${prefix}${key}: "${value.replace(/"/g, '\\"')}"\n`
            } else {
                yaml += `${prefix}${key}: ${value}\n`
            }
        } else if (Array.isArray(value)) {
            if (value.length === 0) {
                yaml += `${prefix}${key}: []\n`
            } else {
                yaml += `${prefix}${key}:\n`
                for (const item of value) {
                    if (typeof item === 'object' && item !== null) {
                        const itemYaml = toYaml(item, indent + 2).trim()
                        yaml += `${prefix}- ${itemYaml.split('\n').join('\n' + prefix + '  ')}\n`
                    } else {
                        yaml += `${prefix}- ${item}\n`
                    }
                }
            }
        } else if (typeof value === 'object') {
            yaml += `${prefix}${key}:\n`
            yaml += toYaml(value, indent + 1)
        }
    }
    return yaml
}

const exportOpenApiYaml = () => {
    if (!props.apiResource?.openApiSpec) return
    const yamlContent = toYaml(props.apiResource.openApiSpec)
    const blob = new Blob([yamlContent], { type: 'text/yaml' })
    const downloadUrl = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = downloadUrl
    a.download = `${props.resourceSlug || 'api'}-openapi.yaml`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(downloadUrl)
    showExportMenu.value = false
}

const copyOpenApiToClipboard = async () => {
    if (!props.apiResource?.openApiSpec) return
    try {
        await navigator.clipboard.writeText(JSON.stringify(props.apiResource.openApiSpec, null, 2))
        copiedOpenApi.value = true
        setTimeout(() => copiedOpenApi.value = false, 2000)
        showExportMenu.value = false
    } catch (err) {
        console.error('Failed to copy:', err)
    }
}

// Watch for apiResource URL changes
watch(() => props.apiResource?.fullUrl, (newUrl) => {
    if (newUrl) {
        updateUrlForOperation()
    }
}, { immediate: true })

// Watch for apiToken prop changes
watch(() => props.apiToken, (newToken) => {
    if (newToken) {
        apiTokenInput.value = newToken
    }
})

// Initialize
onMounted(() => {
    if (props.apiToken) {
        apiTokenInput.value = props.apiToken
    }
    selectOperation('index')
})
</script>

<template>
    <div class="flex h-full bg-background">
        <!-- Sidebar - Endpoints -->
        <div
            :class="[
                'border-r border-border flex flex-col bg-muted/20 transition-all duration-200',
                sidebarCollapsed ? 'w-12' : 'w-64'
            ]"
        >
            <!-- Sidebar Header -->
            <div class="p-3 border-b border-border flex items-center justify-between">
                <div v-if="!sidebarCollapsed" class="flex items-center gap-2">
                    <FolderOpen class="h-4 w-4 text-primary" />
                    <span class="font-semibold text-sm">Endpoints</span>
                </div>
                <button
                    @click="sidebarCollapsed = !sidebarCollapsed"
                    class="p-1 rounded hover:bg-muted text-muted-foreground hover:text-foreground transition-colors"
                >
                    <ChevronRight :class="['h-4 w-4 transition-transform', sidebarCollapsed ? '' : 'rotate-180']" />
                </button>
            </div>

            <!-- Endpoints List -->
            <div class="flex-1 overflow-y-auto p-2 space-y-1 api-tester-scrollbar">
                <!-- CRUD Operations -->
                <template v-if="!sidebarCollapsed">
                    <p class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider px-2 pt-2 pb-1">
                        CRUD Operations
                    </p>
                </template>

                <div
                    v-for="op in operations"
                    :key="op.key"
                    class="relative group"
                >
                    <button
                        @click="selectOperation(op.key)"
                        :class="[
                            'w-full flex items-center gap-2 rounded-md text-left transition-all',
                            sidebarCollapsed ? 'p-2 justify-center' : 'px-3 py-2',
                            selectedOperation === op.key
                                ? 'bg-primary/10 text-primary'
                                : 'hover:bg-muted text-foreground'
                        ]"
                    >
                        <span
                            v-if="sidebarCollapsed"
                            :class="['text-[10px] font-bold', methodTextColors[op.method]]"
                        >
                            {{ op.method.slice(0, 3) }}
                        </span>
                        <template v-else>
                            <span :class="['text-[10px] font-bold w-10', methodTextColors[op.method]]">
                                {{ op.method }}
                            </span>
                            <span class="text-sm truncate flex-1">{{ op.label }}</span>
                        </template>
                    </button>
                    <!-- Tooltip for collapsed state -->
                    <div
                        v-if="sidebarCollapsed"
                        class="sidebar-tooltip"
                    >
                        <span :class="['font-bold mr-1', methodTextColors[op.method]]">{{ op.method }}</span>
                        <span>{{ op.label }}</span>
                    </div>
                </div>

                <!-- Custom Actions -->
                <template v-if="customActions.length > 0">
                    <template v-if="!sidebarCollapsed">
                        <div class="border-t border-border my-2"></div>
                        <p class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider px-2 pt-2 pb-1">
                            Actions
                        </p>
                    </template>
                    <div v-else class="border-t border-border my-2"></div>

                    <div
                        v-for="action in customActions"
                        :key="action.key"
                        class="relative group"
                    >
                        <button
                            @click="selectOperation(action.key)"
                            :class="[
                                'w-full flex items-center gap-2 rounded-md text-left transition-all',
                                sidebarCollapsed ? 'p-2 justify-center' : 'px-3 py-2',
                                selectedOperation === action.key
                                    ? 'bg-primary/10 text-primary'
                                    : 'hover:bg-muted text-foreground'
                            ]"
                        >
                            <Zap v-if="sidebarCollapsed" class="h-4 w-4 text-amber-500" />
                            <template v-else>
                                <span :class="['text-[10px] font-bold w-10', methodTextColors[action.method]]">
                                    {{ action.method }}
                                </span>
                                <span class="text-sm truncate flex-1">{{ action.label }}</span>
                            </template>
                        </button>
                        <!-- Tooltip for collapsed state -->
                        <div
                            v-if="sidebarCollapsed"
                            class="sidebar-tooltip"
                        >
                            <span :class="['font-bold mr-1', methodTextColors[action.method]]">{{ action.method }}</span>
                            <span>{{ action.label }}</span>
                        </div>
                    </div>
                </template>
            </div>

            <!-- API Info Footer -->
            <div v-if="!sidebarCollapsed && apiResource?.version" class="p-3 border-t border-border">
                <p class="text-xs text-muted-foreground">
                    API {{ apiResource.version }}
                </p>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Request Section -->
            <div class="border-b border-border">
                <!-- Operation Info -->
                <div v-if="currentOperation" class="px-4 pt-3 pb-2">
                    <div class="flex items-center gap-2">
                        <span :class="['px-2 py-0.5 text-xs font-bold rounded', methodBadgeColors[method]]">
                            {{ method }}
                        </span>
                        <span class="text-sm font-medium">{{ currentOperation.label }}</span>
                    </div>
                    <p v-if="currentOperation.description" class="text-xs text-muted-foreground mt-1">
                        {{ currentOperation.description }}
                    </p>
                </div>

                <!-- Record ID Input -->
                <div v-if="currentOperation?.needsId" class="px-4 pb-2">
                    <div class="flex items-center gap-2">
                        <label class="text-xs text-muted-foreground">Record ID:</label>
                        <input
                            v-model="recordId"
                            type="text"
                            placeholder="Enter ID"
                            class="w-32 px-2 py-1 text-sm rounded border border-border bg-background focus:outline-none focus:ring-1 focus:ring-primary"
                        />
                    </div>
                </div>

                <!-- URL Bar -->
                <div class="px-4 pb-3">
                    <div class="flex items-center gap-2 bg-muted/30 rounded-lg p-1">
                        <div class="relative">
                            <select
                                v-model="method"
                                :class="[
                                    'appearance-none font-semibold text-sm px-3 py-2 pr-8 rounded-md bg-transparent cursor-pointer focus:outline-none',
                                    methodTextColors[method]
                                ]"
                            >
                                <option v-for="m in httpMethods" :key="m" :value="m" class="text-foreground bg-background">
                                    {{ m }}
                                </option>
                            </select>
                            <ChevronDown class="absolute right-2 top-1/2 -translate-y-1/2 h-4 w-4 pointer-events-none text-muted-foreground" />
                        </div>

                        <input
                            v-model="url"
                            type="text"
                            placeholder="Enter request URL"
                            class="flex-1 bg-transparent px-3 py-2 text-sm font-mono focus:outline-none"
                        />

                        <button
                            @click="sendRequest"
                            :disabled="isLoading || (currentOperation?.needsId && !recordId)"
                            :class="[
                                'px-6 py-2 rounded-md font-semibold text-sm text-white flex items-center gap-2 transition-all',
                                methodColors[method],
                                (isLoading || (currentOperation?.needsId && !recordId)) ? 'opacity-50 cursor-not-allowed' : 'hover:opacity-90'
                            ]"
                        >
                            <Loader2 v-if="isLoading" class="h-4 w-4 animate-spin" />
                            <Send v-else class="h-4 w-4" />
                            Send
                        </button>
                    </div>
                </div>

                <!-- Request Tabs -->
                <div class="flex items-center border-t border-border px-4">
                    <button
                        v-for="tab in (['params', 'headers', 'body', 'auth'] as const)"
                        :key="tab"
                        @click="requestTab = tab"
                        :class="[
                            'px-4 py-3 text-sm font-medium border-b-2 transition-colors capitalize',
                            requestTab === tab
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted-foreground hover:text-foreground'
                        ]"
                    >
                        {{ tab === 'params' ? 'Params' : tab === 'auth' ? 'Auth' : tab }}
                        <span v-if="tab === 'params' && queryParams.filter(p => p.enabled).length" class="ml-1.5 px-1.5 py-0.5 text-xs rounded-full bg-primary/20 text-primary">
                            {{ queryParams.filter(p => p.enabled).length }}
                        </span>
                    </button>

                    <div class="ml-auto flex items-center gap-2">
                        <div class="relative">
                            <button
                                @click="showExportMenu = !showExportMenu"
                                class="p-2 rounded hover:bg-muted text-muted-foreground hover:text-foreground transition-colors"
                                title="Export OpenAPI"
                            >
                                <Download class="h-4 w-4" />
                            </button>
                            <div
                                v-if="showExportMenu"
                                class="absolute right-0 top-full mt-1 w-44 bg-background border border-border rounded-md shadow-lg z-50"
                            >
                                <div class="py-1">
                                    <button
                                        @click="exportOpenApiJson"
                                        class="w-full flex items-center gap-2 px-3 py-2 text-sm text-left hover:bg-muted transition-colors"
                                    >
                                        <FileJson class="h-4 w-4" />
                                        Export JSON
                                    </button>
                                    <button
                                        @click="exportOpenApiYaml"
                                        class="w-full flex items-center gap-2 px-3 py-2 text-sm text-left hover:bg-muted transition-colors"
                                    >
                                        <Code class="h-4 w-4" />
                                        Export YAML
                                    </button>
                                    <button
                                        @click="copyOpenApiToClipboard"
                                        class="w-full flex items-center gap-2 px-3 py-2 text-sm text-left hover:bg-muted transition-colors"
                                    >
                                        <template v-if="copiedOpenApi">
                                            <Check class="h-4 w-4 text-green-500" />
                                            Copied!
                                        </template>
                                        <template v-else>
                                            <Copy class="h-4 w-4" />
                                            Copy to Clipboard
                                        </template>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Content -->
                <div class="p-4 max-h-[250px] overflow-y-auto api-tester-scrollbar">
                    <!-- Params Tab -->
                    <div v-if="requestTab === 'params'" class="space-y-2">
                        <div v-if="queryParams.length === 0" class="text-center py-6 text-muted-foreground">
                            <p class="text-sm">No query parameters</p>
                            <button @click="addQueryParam" class="mt-2 text-primary text-sm hover:underline">
                                Add parameter
                            </button>
                        </div>
                        <template v-else>
                            <div
                                v-for="(param, index) in queryParams"
                                :key="index"
                                class="flex items-center gap-2 group"
                            >
                                <input type="checkbox" v-model="param.enabled" class="h-4 w-4 rounded border-border accent-primary" />
                                <input v-model="param.key" type="text" placeholder="Key" class="flex-1 px-3 py-1.5 text-sm rounded border border-border bg-background focus:outline-none focus:ring-1 focus:ring-primary" />
                                <input v-model="param.value" type="text" placeholder="Value" class="flex-1 px-3 py-1.5 text-sm rounded border border-border bg-background focus:outline-none focus:ring-1 focus:ring-primary" />
                                <button @click="removeQueryParam(index)" class="p-1.5 text-muted-foreground hover:text-red-500 opacity-0 group-hover:opacity-100 transition-all">
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                            <button @click="addQueryParam" class="flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground transition-colors">
                                <Plus class="h-4 w-4" />
                                Add parameter
                            </button>
                        </template>
                    </div>

                    <!-- Headers Tab -->
                    <div v-if="requestTab === 'headers'" class="space-y-2">
                        <div
                            v-for="(header, index) in headers"
                            :key="index"
                            class="flex items-center gap-2 group"
                        >
                            <input type="checkbox" v-model="header.enabled" class="h-4 w-4 rounded border-border accent-primary" />
                            <input v-model="header.key" type="text" placeholder="Key" class="flex-1 px-3 py-1.5 text-sm rounded border border-border bg-background focus:outline-none focus:ring-1 focus:ring-primary" />
                            <input v-model="header.value" type="text" placeholder="Value" class="flex-1 px-3 py-1.5 text-sm rounded border border-border bg-background focus:outline-none focus:ring-1 focus:ring-primary" />
                            <button @click="removeHeader(index)" class="p-1.5 text-muted-foreground hover:text-red-500 opacity-0 group-hover:opacity-100 transition-all">
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                        <button @click="addHeader" class="flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground transition-colors">
                            <Plus class="h-4 w-4" />
                            Add header
                        </button>
                    </div>

                    <!-- Body Tab -->
                    <div v-if="requestTab === 'body'" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-muted-foreground">Request Body (JSON)</span>
                            <button
                                @click="generateSampleBody"
                                class="text-xs px-3 py-1 bg-primary/10 text-primary rounded hover:bg-primary/20 transition-colors"
                            >
                                Generate Sample
                            </button>
                        </div>
                        <textarea
                            v-model="requestBody"
                            placeholder='{"key": "value"}'
                            class="w-full h-[150px] px-3 py-2 text-sm font-mono rounded border border-border bg-background resize-none focus:outline-none focus:ring-1 focus:ring-primary"
                        ></textarea>
                    </div>

                    <!-- Auth Tab -->
                    <div v-if="requestTab === 'auth'" class="space-y-4">
                        <div class="flex items-center gap-2 p-3 bg-muted/30 rounded-lg">
                            <Key class="h-5 w-5 text-muted-foreground" />
                            <span class="text-sm font-medium">Bearer Token</span>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm text-muted-foreground">Token</label>
                            <div class="relative">
                                <input
                                    v-model="apiTokenInput"
                                    :type="showToken ? 'text' : 'password'"
                                    placeholder="Enter your API token"
                                    class="w-full px-3 py-2 pr-10 text-sm font-mono rounded border border-border bg-background focus:outline-none focus:ring-1 focus:ring-primary"
                                />
                                <button
                                    @click="showToken = !showToken"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-muted-foreground hover:text-foreground"
                                >
                                    <EyeOff v-if="showToken" class="h-4 w-4" />
                                    <Eye v-else class="h-4 w-4" />
                                </button>
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Token will be sent as: Authorization: Bearer &lt;token&gt;
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Response Section -->
            <div class="flex-1 flex flex-col min-h-0">
                <!-- Response Header -->
                <div class="flex items-center justify-between border-b border-border px-4 py-2">
                    <div class="flex items-center gap-4">
                        <span class="text-sm font-semibold">Response</span>
                        <template v-if="responseStatus">
                            <span :class="['px-2 py-0.5 rounded text-sm font-medium', getStatusBg(responseStatus), getStatusColor(responseStatus)]">
                                {{ responseStatus }}
                            </span>
                            <span class="text-sm text-muted-foreground">{{ responseTime }}ms</span>
                            <span v-if="responseSize" class="text-sm text-muted-foreground">{{ formatBytes(responseSize) }}</span>
                        </template>
                    </div>

                    <div class="flex items-center gap-2">
                        <div class="flex rounded-md border border-border overflow-hidden mr-2">
                            <button
                                @click="responseTab = 'body'"
                                :class="['px-3 py-1 text-xs font-medium transition-colors', responseTab === 'body' ? 'bg-muted text-foreground' : 'text-muted-foreground hover:text-foreground']"
                            >
                                Body
                            </button>
                            <button
                                @click="responseTab = 'headers'"
                                :class="['px-3 py-1 text-xs font-medium border-l border-border transition-colors', responseTab === 'headers' ? 'bg-muted text-foreground' : 'text-muted-foreground hover:text-foreground']"
                            >
                                Headers
                            </button>
                        </div>

                        <template v-if="responseTab === 'body' && response && typeof response === 'object'">
                            <div class="flex rounded-md border border-border overflow-hidden">
                                <button
                                    @click="responseViewMode = 'pretty'"
                                    :class="['px-2 py-1 text-xs font-medium transition-colors', responseViewMode === 'pretty' ? 'bg-muted text-foreground' : 'text-muted-foreground hover:text-foreground']"
                                >
                                    Pretty
                                </button>
                                <button
                                    @click="responseViewMode = 'raw'"
                                    :class="['px-2 py-1 text-xs font-medium border-l border-border transition-colors', responseViewMode === 'raw' ? 'bg-muted text-foreground' : 'text-muted-foreground hover:text-foreground']"
                                >
                                    Raw
                                </button>
                            </div>

                            <button @click="expandAllJson" class="p-1.5 text-muted-foreground hover:text-foreground transition-colors" title="Expand All">
                                <ChevronsUpDown class="h-4 w-4" />
                            </button>
                            <button @click="collapseAllJson" class="p-1.5 text-muted-foreground hover:text-foreground transition-colors" title="Collapse All">
                                <ChevronsDownUp class="h-4 w-4" />
                            </button>
                        </template>

                        <button
                            v-if="response"
                            @click="copyResponse"
                            class="p-1.5 text-muted-foreground hover:text-foreground transition-colors"
                            :title="copiedResponse ? 'Copied!' : 'Copy Response'"
                        >
                            <Check v-if="copiedResponse" class="h-4 w-4 text-green-500" />
                            <Copy v-else class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <!-- Response Body -->
                <div class="flex-1 overflow-auto p-4 bg-muted/5 api-tester-scrollbar">
                    <div v-if="isLoading" class="flex items-center justify-center h-full">
                        <div class="flex items-center gap-3 text-muted-foreground">
                            <Loader2 class="h-5 w-5 animate-spin" />
                            <span>Sending request...</span>
                        </div>
                    </div>

                    <div v-else-if="error" class="flex items-center justify-center h-full">
                        <div class="text-center">
                            <p class="text-red-500 font-medium">Request Failed</p>
                            <p class="text-sm text-muted-foreground mt-1">{{ error }}</p>
                        </div>
                    </div>

                    <div v-else-if="!response" class="flex items-center justify-center h-full">
                        <div class="text-center text-muted-foreground">
                            <Send class="h-12 w-12 mx-auto mb-3 opacity-30" />
                            <p class="font-medium">No Response Yet</p>
                            <p class="text-sm mt-1">Click Send to make a request</p>
                        </div>
                    </div>

                    <template v-else-if="responseTab === 'body'">
                        <div v-if="responseViewMode === 'pretty' && typeof response === 'object'" class="text-sm font-mono">
                            <JsonNode :key="jsonExpandKey" :data="response" :depth="0" :forceExpand="forceExpandAll" />
                        </div>
                        <pre v-else class="text-sm font-mono whitespace-pre-wrap break-words text-foreground">{{ formattedResponse }}</pre>
                    </template>

                    <div v-else-if="responseTab === 'headers'" class="space-y-1">
                        <div
                            v-for="(value, key) in responseHeaders"
                            :key="key"
                            class="flex items-start gap-2 py-1 text-sm font-mono"
                        >
                            <span class="text-rose-600 dark:text-rose-400 font-medium">{{ key }}:</span>
                            <span class="text-foreground break-all">{{ value }}</span>
                        </div>
                        <div v-if="Object.keys(responseHeaders).length === 0" class="text-center py-8 text-muted-foreground">
                            <p class="text-sm">No response headers</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
pre {
    margin: 0;
    background: transparent;
}

select option {
    background-color: var(--background);
    color: var(--foreground);
}

/* Sidebar tooltip styles */
.sidebar-tooltip {
    position: absolute;
    left: 100%;
    top: 50%;
    transform: translateY(-50%);
    margin-left: 8px;
    padding: 6px 10px;
    background: hsl(var(--popover));
    color: hsl(var(--popover-foreground));
    border: 1px solid hsl(var(--border));
    border-radius: 6px;
    font-size: 12px;
    white-space: nowrap;
    z-index: 50;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.15s ease, visibility 0.15s ease;
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    pointer-events: none;
}

/* Tooltip arrow */
.sidebar-tooltip::before {
    content: '';
    position: absolute;
    left: -5px;
    top: 50%;
    transform: translateY(-50%) rotate(45deg);
    width: 8px;
    height: 8px;
    background: hsl(var(--popover));
    border-left: 1px solid hsl(var(--border));
    border-bottom: 1px solid hsl(var(--border));
}

/* Show tooltip on hover */
.group:hover .sidebar-tooltip {
    opacity: 1;
    visibility: visible;
}
</style>

<!-- Global scrollbar styles (not scoped) -->
<style>
/* Custom scrollbar for API Tester */
.api-tester-scrollbar::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.api-tester-scrollbar::-webkit-scrollbar-track {
    background: #e5e5e5;
    border-radius: 4px;
}

.api-tester-scrollbar::-webkit-scrollbar-thumb {
    background: #a3a3a3;
    border-radius: 4px;
}

.api-tester-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #737373;
}

/* Dark mode scrollbar */
.dark .api-tester-scrollbar::-webkit-scrollbar-track {
    background: #262626;
}

.dark .api-tester-scrollbar::-webkit-scrollbar-thumb {
    background: #525252;
}

.dark .api-tester-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #737373;
}

/* Firefox scrollbar */
.api-tester-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: #a3a3a3 #e5e5e5;
}

.dark .api-tester-scrollbar {
    scrollbar-color: #525252 #262626;
}

/* Scrollbar corner */
.api-tester-scrollbar::-webkit-scrollbar-corner {
    background: transparent;
}
</style>
