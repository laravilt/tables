import { cn } from '@/lib/utils';
import {
    Check,
    ChevronDown,
    ChevronRight,
    ChevronsDownUp,
    ChevronsUpDown,
    Code,
    Copy,
    Database,
    Download,
    Edit,
    Eye,
    EyeOff,
    FileJson,
    FolderOpen,
    Key,
    List,
    Loader2,
    Plus,
    PlusCircle,
    Send,
    Trash,
    Trash2,
    Zap,
    type LucideIcon,
} from 'lucide-react';
import { useEffect, useMemo, useState, type CSSProperties } from 'react';
import { useWatch } from '../composables/useWatch';
import './ApiTester.css';

// Scoped `pre` / `select option` rules from ApiTester.vue, applied inline
const PRE_STYLE: CSSProperties = { margin: 0, background: 'transparent' };
const OPTION_STYLE: CSSProperties = { backgroundColor: 'var(--background)', color: 'var(--foreground)' };

interface JsonNodeProps {
    data: any;
    keyName?: string;
    depth?: number;
    isLast?: boolean;
    forceExpand?: boolean | null;
}

// Recursive JSON Tree component for collapsible display
function JsonNode({ data, keyName = '', depth = 0, isLast = true, forceExpand = null }: JsonNodeProps) {
    const [isExpanded, setIsExpanded] = useState<boolean>(() => (forceExpand !== null ? forceExpand : depth < 2));

    const isObject = data !== null && typeof data === 'object' && !Array.isArray(data);
    const isArray = Array.isArray(data);
    const isCollapsible = isObject || isArray;
    const isEmpty = isArray ? data.length === 0 : isObject ? Object.keys(data).length === 0 : false;

    const entries: [any, any][] = isArray ? data.map((v: any, i: number) => [i, v]) : isObject ? Object.entries(data) : [];

    const valueColor = (() => {
        if (data === null) return 'text-gray-500';
        if (typeof data === 'boolean') return 'text-purple-600 dark:text-purple-400';
        if (typeof data === 'number') return 'text-blue-600 dark:text-blue-400';
        if (typeof data === 'string') return 'text-green-600 dark:text-green-400';
        return 'text-foreground';
    })();

    const formatValue = (val: any) => {
        if (val === null) return 'null';
        if (typeof val === 'string') return `"${val}"`;
        return String(val);
    };

    const toggle = () => {
        if (isCollapsible) {
            setIsExpanded(!isExpanded);
        }
    };

    const indent = depth * 16;
    const comma = isLast ? '' : ',';

    if (!isCollapsible) {
        return (
            <div className="flex items-start leading-6" style={{ paddingLeft: `${indent}px` }}>
                {keyName && <span className="text-rose-600 dark:text-rose-400">"{keyName}"</span>}
                {keyName && <span className="text-foreground">: </span>}
                <span className={valueColor}>{formatValue(data)}</span>
                <span className="text-foreground">{comma}</span>
            </div>
        );
    }

    if (isEmpty) {
        const brackets = isArray ? '[]' : '{}';
        return (
            <div className="flex items-start leading-6" style={{ paddingLeft: `${indent}px` }}>
                {keyName && <span className="text-rose-600 dark:text-rose-400">"{keyName}"</span>}
                {keyName && <span className="text-foreground">: </span>}
                <span className="text-foreground">{brackets + comma}</span>
            </div>
        );
    }

    const openBracket = isArray ? '[' : '{';
    const closeBracket = isArray ? ']' : '}';
    const itemCount = entries.length;

    if (!isExpanded) {
        return (
            <div className="flex items-start cursor-pointer hover:bg-muted/50 rounded leading-6" style={{ paddingLeft: `${indent}px` }} onClick={toggle}>
                <ChevronRight className="h-4 w-4 text-muted-foreground flex-shrink-0 mt-1" />
                {keyName && <span className="text-rose-600 dark:text-rose-400 ml-1">"{keyName}"</span>}
                {keyName && <span className="text-foreground">: </span>}
                <span className="text-foreground">{openBracket}</span>
                <span className="text-muted-foreground text-xs mx-1">{`${itemCount} ${isArray ? 'items' : 'keys'}`}</span>
                <span className="text-foreground">{closeBracket + comma}</span>
            </div>
        );
    }

    return (
        <div>
            <div className="flex items-start cursor-pointer hover:bg-muted/50 rounded leading-6" style={{ paddingLeft: `${indent}px` }} onClick={toggle}>
                <ChevronDown className="h-4 w-4 text-muted-foreground flex-shrink-0 mt-1" />
                {keyName && <span className="text-rose-600 dark:text-rose-400 ml-1">"{keyName}"</span>}
                {keyName && <span className="text-foreground">: </span>}
                <span className="text-foreground">{openBracket}</span>
            </div>
            {entries.map(([key, value], index) => (
                <JsonNode
                    key={key}
                    data={value}
                    keyName={isArray ? '' : String(key)}
                    depth={depth + 1}
                    isLast={index === entries.length - 1}
                    forceExpand={forceExpand}
                />
            ))}
            <div className="leading-6" style={{ paddingLeft: `${indent}px` }}>
                <span className="text-foreground ml-5">{closeBracket + comma}</span>
            </div>
        </div>
    );
}

interface ApiColumn {
    name: string;
    label?: string;
    type?: string;
    format?: string;
    nullable?: boolean;
    description?: string;
    example?: any;
    sortable?: boolean;
    filterable?: boolean;
    searchable?: boolean;
    enum?: string[];
}

interface ApiAction {
    name: string;
    slug: string;
    label: string;
    description?: string;
    method: string;
    requiresRecord: boolean;
    bulk: boolean;
    requiresConfirmation: boolean;
    confirmationMessage?: string;
    hidden: boolean;
    requestSchema?: any;
    responseSchema?: any;
    successMessage?: string;
}

export interface ApiResource {
    columns: ApiColumn[];
    endpoint: string;
    baseUrl: string;
    fullUrl: string;
    paginated: boolean;
    perPage: number;
    allowedFilters: string[];
    allowedSorts: string[];
    allowedIncludes?: string[];
    searchableColumns: string[];
    description?: string;
    version?: string;
    authenticated: boolean;
    headers: Record<string, string>;
    sampleRequest?: any;
    sampleResponse?: any;
    fillableFields?: string[];
    actions?: ApiAction[];
    openApiSpec?: any;
}

export interface ApiTesterProps {
    apiResource: ApiResource;
    resourceSlug?: string;
    records?: any[];
    apiToken?: string | null;
}

// HTTP Methods
const httpMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as const;
type HttpMethod = (typeof httpMethods)[number];

// Operation types
type OperationType = 'index' | 'show' | 'store' | 'update' | 'destroy' | 'bulkDestroy' | string;

interface Operation {
    key: OperationType;
    label: string;
    method: HttpMethod;
    icon: LucideIcon;
    description: string;
    path: string;
    needsId?: boolean;
    hasBody?: boolean;
    bulk?: boolean;
    action?: ApiAction;
}

interface KeyValueRow {
    key: string;
    value: string;
    enabled: boolean;
}

// Method colors
const methodColors: Record<HttpMethod, string> = {
    GET: 'bg-green-500',
    POST: 'bg-yellow-500',
    PUT: 'bg-blue-500',
    PATCH: 'bg-purple-500',
    DELETE: 'bg-red-500',
};

const methodTextColors: Record<HttpMethod, string> = {
    GET: 'text-green-500',
    POST: 'text-yellow-500',
    PUT: 'text-blue-500',
    PATCH: 'text-purple-500',
    DELETE: 'text-red-500',
};

const methodBadgeColors: Record<HttpMethod, string> = {
    GET: 'bg-green-500/10 text-green-600 dark:text-green-400',
    POST: 'bg-yellow-500/10 text-yellow-600 dark:text-yellow-400',
    PUT: 'bg-blue-500/10 text-blue-600 dark:text-blue-400',
    PATCH: 'bg-purple-500/10 text-purple-600 dark:text-purple-400',
    DELETE: 'bg-red-500/10 text-red-600 dark:text-red-400',
};

// Status colors
const getStatusColor = (status: number) => {
    if (status >= 200 && status < 300) return 'text-green-500';
    if (status >= 300 && status < 400) return 'text-blue-500';
    if (status >= 400 && status < 500) return 'text-yellow-500';
    if (status >= 500) return 'text-red-500';
    return 'text-gray-500';
};

const getStatusBg = (status: number) => {
    if (status >= 200 && status < 300) return 'bg-green-500/10';
    if (status >= 300 && status < 400) return 'bg-blue-500/10';
    if (status >= 400 && status < 500) return 'bg-yellow-500/10';
    if (status >= 500) return 'bg-red-500/10';
    return 'bg-gray-500/10';
};

// Faker-like random data generators
const faker = {
    firstName: () => ['John', 'Jane', 'Michael', 'Sarah', 'David', 'Emily', 'James', 'Emma'][Math.floor(Math.random() * 8)],
    lastName: () => ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis'][Math.floor(Math.random() * 8)],
    fullName: () => `${faker.firstName()} ${faker.lastName()}`,
    email: () => `${faker.firstName().toLowerCase()}${Math.floor(Math.random() * 999)}@example.com`,
    phone: () =>
        `+1 ${Math.floor(Math.random() * 900) + 100} ${Math.floor(Math.random() * 900) + 100} ${Math.floor(Math.random() * 9000) + 1000}`,
    company: () => ['Acme Inc.', 'Tech Corp', 'Global Solutions', 'Digital Services', 'Innovation Labs'][Math.floor(Math.random() * 5)],
    website: () => `https://${faker.company().toLowerCase().replace(/[^a-z]/g, '')}.com`,
    address: () =>
        `${Math.floor(Math.random() * 9999) + 1} ${['Main', 'Oak', 'Park', 'Cedar', 'Elm'][Math.floor(Math.random() * 5)]} ${['St', 'Ave', 'Blvd', 'Dr'][Math.floor(Math.random() * 4)]}`,
    city: () => ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix', 'San Diego'][Math.floor(Math.random() * 6)],
    state: () => ['NY', 'CA', 'TX', 'FL', 'IL', 'PA', 'OH', 'GA'][Math.floor(Math.random() * 8)],
    country: () => ['USA', 'Canada', 'UK', 'Australia', 'Germany'][Math.floor(Math.random() * 5)],
    postalCode: () => String(Math.floor(Math.random() * 90000) + 10000),
    boolean: () => Math.random() > 0.5,
    number: (min = 0, max = 10000) => Math.floor(Math.random() * (max - min + 1)) + min,
    decimal: (min = 0, max = 10000) => Number((Math.random() * (max - min) + min).toFixed(2)),
    date: () => {
        const d = new Date();
        d.setDate(d.getDate() - Math.floor(Math.random() * 365 * 30));
        return d.toISOString().split('T')[0];
    },
    datetime: () => new Date(Date.now() - Math.floor(Math.random() * 365 * 24 * 60 * 60 * 1000)).toISOString(),
    tags: () => {
        const allTags = ['vip', 'newsletter', 'premium', 'active', 'loyal', 'new'];
        return allTags.slice(0, Math.floor(Math.random() * 3) + 1);
    },
    status: () => ['active', 'inactive', 'pending'][Math.floor(Math.random() * 3)],
    type: () => ['individual', 'business'][Math.floor(Math.random() * 2)],
    text: () => ['Lorem ipsum dolor sit amet', 'Consectetur adipiscing elit', 'Sed do eiusmod tempor'][Math.floor(Math.random() * 3)],
};

// Format bytes
const formatBytes = (bytes: number) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const toYaml = (obj: any, indent = 0): string => {
    const prefix = '  '.repeat(indent);
    let yaml = '';
    for (const [key, value] of Object.entries(obj)) {
        if (value === null || value === undefined) {
            yaml += `${prefix}${key}: null\n`;
        } else if (typeof value === 'boolean') {
            yaml += `${prefix}${key}: ${value}\n`;
        } else if (typeof value === 'number') {
            yaml += `${prefix}${key}: ${value}\n`;
        } else if (typeof value === 'string') {
            if (value.includes('\n') || value.includes(':') || value.includes('#')) {
                yaml += `${prefix}${key}: "${value.replace(/"/g, '\\"')}"\n`;
            } else {
                yaml += `${prefix}${key}: ${value}\n`;
            }
        } else if (Array.isArray(value)) {
            if (value.length === 0) {
                yaml += `${prefix}${key}: []\n`;
            } else {
                yaml += `${prefix}${key}:\n`;
                for (const item of value) {
                    if (typeof item === 'object' && item !== null) {
                        const itemYaml = toYaml(item, indent + 2).trim();
                        yaml += `${prefix}- ${itemYaml.split('\n').join('\n' + prefix + '  ')}\n`;
                    } else {
                        yaml += `${prefix}- ${item}\n`;
                    }
                }
            }
        } else if (typeof value === 'object') {
            yaml += `${prefix}${key}:\n`;
            yaml += toYaml(value, indent + 1);
        }
    }
    return yaml;
};

const INPUT_CLASS =
    'flex-1 px-3 py-1.5 text-sm rounded border border-border bg-background focus:outline-none focus:ring-1 focus:ring-primary';

export default function ApiTester({ apiResource, resourceSlug = '', apiToken = null }: ApiTesterProps) {
    // Selected operation
    const [selectedOperation, setSelectedOperation] = useState<OperationType>('index');
    const [recordId, setRecordId] = useState('');

    // Request state
    const [method, setMethod] = useState<HttpMethod>('GET');
    const [url, setUrl] = useState<string>(apiResource?.fullUrl || '');
    const [isLoading, setIsLoading] = useState(false);
    const [response, setResponse] = useState<any>(null);
    const [responseStatus, setResponseStatus] = useState<number | null>(null);
    const [responseTime, setResponseTime] = useState<number | null>(null);
    const [responseSize, setResponseSize] = useState<number | null>(null);
    const [responseHeaders, setResponseHeaders] = useState<Record<string, string>>({});
    const [error, setError] = useState<string | null>(null);
    const [copiedResponse, setCopiedResponse] = useState(false);

    // Active tabs
    const [requestTab, setRequestTab] = useState<'params' | 'headers' | 'body' | 'auth'>('params');
    const [responseTab, setResponseTab] = useState<'body' | 'headers'>('body');

    // Response view
    const [responseViewMode, setResponseViewMode] = useState<'pretty' | 'raw'>('pretty');
    const [jsonExpandKey, setJsonExpandKey] = useState(0);
    const [forceExpandAll, setForceExpandAll] = useState(false);

    // API Token
    const [apiTokenInput, setApiTokenInput] = useState<string>(apiToken || '');
    const [showToken, setShowToken] = useState(false);

    // Headers
    const [headers, setHeaders] = useState<KeyValueRow[]>([
        { key: 'Accept', value: 'application/json', enabled: true },
        { key: 'Content-Type', value: 'application/json', enabled: true },
    ]);

    // Query parameters
    const [queryParams, setQueryParams] = useState<KeyValueRow[]>([]);

    // Request body
    const [requestBody, setRequestBody] = useState('');

    // Sidebar collapsed
    const [sidebarCollapsed, setSidebarCollapsed] = useState(false);

    // Export
    const [showExportMenu, setShowExportMenu] = useState(false);
    const [copiedOpenApi, setCopiedOpenApi] = useState(false);

    const fullUrl = apiResource?.fullUrl || '';

    // Operations list
    const operations = useMemo<Operation[]>(
        () => [
            {
                key: 'index',
                label: 'List All',
                method: 'GET',
                icon: List,
                description: 'Get paginated list of records',
                path: fullUrl,
            },
            {
                key: 'show',
                label: 'Get One',
                method: 'GET',
                icon: Eye,
                description: 'Get a single record by ID',
                path: `${fullUrl}/{id}`,
                needsId: true,
            },
            {
                key: 'store',
                label: 'Create',
                method: 'POST',
                icon: PlusCircle,
                description: 'Create a new record',
                path: fullUrl,
                hasBody: true,
            },
            {
                key: 'update',
                label: 'Update',
                method: 'PUT',
                icon: Edit,
                description: 'Update an existing record',
                path: `${fullUrl}/{id}`,
                needsId: true,
                hasBody: true,
            },
            {
                key: 'destroy',
                label: 'Delete',
                method: 'DELETE',
                icon: Trash,
                description: 'Delete a single record',
                path: `${fullUrl}/{id}`,
                needsId: true,
            },
            {
                key: 'bulkDestroy',
                label: 'Bulk Delete',
                method: 'DELETE',
                icon: Database,
                description: 'Delete multiple records',
                path: `${fullUrl}/bulk`,
                hasBody: true,
            },
        ],
        [fullUrl],
    );

    // Custom actions from API resource
    const customActions = useMemo<Operation[]>(() => {
        if (!apiResource?.actions) return [];
        return apiResource.actions
            .filter((a) => !a.hidden)
            .map((action) => ({
                key: `action_${action.slug}`,
                label: action.label,
                method: action.method.toUpperCase() as HttpMethod,
                icon: Zap,
                description: action.description || '',
                path: action.requiresRecord ? `${fullUrl}/{id}/actions/${action.slug}` : `${fullUrl}/actions/${action.slug}`,
                needsId: action.requiresRecord,
                hasBody: ['POST', 'PUT', 'PATCH'].includes(action.method.toUpperCase()),
                bulk: action.bulk,
                action,
            }));
    }, [apiResource?.actions, fullUrl]);

    const findOperation = (opKey: OperationType): Operation | undefined => {
        const op = operations.find((o) => o.key === opKey);
        if (op) return op;
        return customActions.find((a) => a.key === opKey);
    };

    // Current operation
    const currentOperation = findOperation(selectedOperation);

    // URL for an operation (Vue: updateUrlForOperation)
    const urlForOperation = (op: Operation, id: string): string => {
        let path = op.path;
        if (op.needsId && id) {
            path = path.replace('{id}', id);
        }
        return path;
    };

    // Reset params for operation
    const resetParamsForOperation = (opKey: OperationType) => {
        if (opKey === 'index') {
            setQueryParams([
                { key: 'page', value: '1', enabled: true },
                { key: 'per_page', value: String(apiResource?.perPage || 12), enabled: true },
            ]);
        } else {
            setQueryParams([]);
        }
    };

    // Generate sample request body
    const generateSampleBody = () => {
        if (!apiResource?.columns) {
            setRequestBody('{}');
            return;
        }

        const sampleData: Record<string, any> = {};
        const fillableFields = apiResource.fillableFields || [];

        apiResource.columns.forEach((col) => {
            if (fillableFields.length > 0 && !fillableFields.includes(col.name)) return;
            if (['id', 'created_at', 'updated_at', 'deleted_at'].includes(col.name)) return;

            const name = col.name.toLowerCase();

            if (name === 'name' || name === 'full_name' || name === 'fullname') {
                sampleData[col.name] = faker.fullName();
            } else if (name === 'first_name' || name === 'firstname') {
                sampleData[col.name] = faker.firstName();
            } else if (name === 'last_name' || name === 'lastname') {
                sampleData[col.name] = faker.lastName();
            } else if (name === 'email' || name.includes('email')) {
                sampleData[col.name] = faker.email();
            } else if (name === 'phone' || name.includes('phone') || name.includes('mobile')) {
                sampleData[col.name] = faker.phone();
            } else if (name === 'company' || name.includes('company')) {
                sampleData[col.name] = faker.company();
            } else if (name === 'website' || name.includes('url') || name.includes('site')) {
                sampleData[col.name] = faker.website();
            } else if (name === 'address' || name.includes('address') || name.includes('street')) {
                sampleData[col.name] = faker.address();
            } else if (name === 'city') {
                sampleData[col.name] = faker.city();
            } else if (name === 'state' || name === 'province') {
                sampleData[col.name] = faker.state();
            } else if (name === 'country') {
                sampleData[col.name] = faker.country();
            } else if (name === 'postal_code' || name === 'zip' || name === 'zipcode') {
                sampleData[col.name] = faker.postalCode();
            } else if (name === 'status') {
                sampleData[col.name] = col.enum ? col.enum[Math.floor(Math.random() * col.enum.length)] : faker.status();
            } else if (name === 'type') {
                sampleData[col.name] = col.enum ? col.enum[Math.floor(Math.random() * col.enum.length)] : faker.type();
            } else if (name.includes('birth') || name.includes('dob')) {
                sampleData[col.name] = faker.date();
            } else if (name.includes('tags') || name.includes('labels')) {
                sampleData[col.name] = faker.tags();
            } else if (
                name.includes('credit') ||
                name.includes('limit') ||
                name.includes('amount') ||
                name.includes('price') ||
                name.includes('spent') ||
                name.includes('total')
            ) {
                sampleData[col.name] = faker.decimal(100, 10000);
            } else if (col.enum && col.enum.length > 0) {
                sampleData[col.name] = col.enum[Math.floor(Math.random() * col.enum.length)];
            } else if (col.type === 'boolean' || name.includes('is_') || name.includes('has_')) {
                sampleData[col.name] = faker.boolean();
            } else if (col.type === 'integer' || col.type === 'number') {
                sampleData[col.name] = faker.number(1, 1000);
            } else if (col.type === 'date') {
                sampleData[col.name] = faker.date();
            } else if (col.type === 'datetime') {
                sampleData[col.name] = faker.datetime();
            } else if (col.type === 'array') {
                sampleData[col.name] = [];
            } else {
                sampleData[col.name] = col.example !== undefined && col.example !== null ? col.example : faker.text();
            }
        });

        setRequestBody(JSON.stringify(sampleData, null, 2));
    };

    // Select an operation
    const selectOperation = (opKey: OperationType) => {
        setSelectedOperation(opKey);
        const op = findOperation(opKey);
        if (op) {
            setMethod(op.method);
            setUrl(urlForOperation(op, recordId));
            resetParamsForOperation(opKey);

            if (op.hasBody) {
                setRequestTab('body');
                if (opKey === 'store' || opKey === 'update') {
                    generateSampleBody();
                } else if (opKey === 'bulkDestroy') {
                    setRequestBody(JSON.stringify({ ids: [] }, null, 2));
                }
            } else {
                setRequestTab('params');
            }
        }
    };

    // Watch recordId changes (Vue watcher → handled where recordId changes)
    const handleRecordIdChange = (id: string) => {
        setRecordId(id);
        if (currentOperation) {
            setUrl(urlForOperation(currentOperation, id));
        }
    };

    // Computed URL with query params
    const computedUrl = (() => {
        const baseUrl = url;
        const enabledParams = queryParams.filter((p) => p.enabled && p.key && p.value);
        if (enabledParams.length === 0) return baseUrl;
        const params = new URLSearchParams();
        enabledParams.forEach((p) => params.append(p.key, p.value));
        return `${baseUrl}?${params.toString()}`;
    })();

    // Formatted response
    const formattedResponse = !response ? '' : typeof response === 'string' ? response : JSON.stringify(response, null, 2);

    // Send request
    const sendRequest = async () => {
        setIsLoading(true);
        setError(null);
        setResponse(null);
        setResponseStatus(null);
        setResponseTime(null);
        setResponseSize(null);
        setResponseHeaders({});

        const startTime = performance.now();

        try {
            const requestHeaders: Record<string, string> = {};
            headers
                .filter((h) => h.enabled && h.key)
                .forEach((h) => {
                    requestHeaders[h.key] = h.value;
                });

            if (apiTokenInput) {
                requestHeaders['Authorization'] = `Bearer ${apiTokenInput}`;
            }

            if (method !== 'GET') {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (csrfToken) {
                    requestHeaders['X-CSRF-TOKEN'] = csrfToken;
                }
            }

            const options: RequestInit = {
                method,
                headers: requestHeaders,
                credentials: 'same-origin',
            };

            if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method) && requestBody) {
                options.body = requestBody;
            }

            const res = await fetch(computedUrl, options);
            const endTime = performance.now();

            setResponseStatus(res.status);
            setResponseTime(Math.round(endTime - startTime));

            const collectedHeaders: Record<string, string> = {};
            res.headers.forEach((value, key) => {
                collectedHeaders[key] = value;
            });
            setResponseHeaders(collectedHeaders);

            const text = await res.text();
            setResponseSize(new Blob([text]).size);

            const contentType = res.headers.get('content-type');
            if (contentType?.includes('application/json')) {
                try {
                    setResponse(JSON.parse(text));
                } catch {
                    setResponse(text);
                }
            } else {
                setResponse(text);
            }
        } catch (err: any) {
            setError(err.message || 'Request failed');
        } finally {
            setIsLoading(false);
        }
    };

    // Add/remove functions
    const addQueryParam = () => {
        setQueryParams([...queryParams, { key: '', value: '', enabled: true }]);
    };

    const removeQueryParam = (index: number) => {
        setQueryParams(queryParams.filter((_, i) => i !== index));
    };

    const updateQueryParam = (index: number, patch: Partial<KeyValueRow>) => {
        setQueryParams(queryParams.map((param, i) => (i === index ? { ...param, ...patch } : param)));
    };

    const addHeader = () => {
        setHeaders([...headers, { key: '', value: '', enabled: true }]);
    };

    const removeHeader = (index: number) => {
        setHeaders(headers.filter((_, i) => i !== index));
    };

    const updateHeader = (index: number, patch: Partial<KeyValueRow>) => {
        setHeaders(headers.map((header, i) => (i === index ? { ...header, ...patch } : header)));
    };

    // Copy response
    const copyResponse = async () => {
        try {
            const text = typeof response === 'string' ? response : JSON.stringify(response, null, 2);
            await navigator.clipboard.writeText(text);
            setCopiedResponse(true);
            setTimeout(() => setCopiedResponse(false), 2000);
        } catch (err) {
            console.error('Failed to copy response:', err);
        }
    };

    // Expand/Collapse all
    const expandAllJson = () => {
        setForceExpandAll(true);
        setJsonExpandKey(jsonExpandKey + 1);
    };

    const collapseAllJson = () => {
        setForceExpandAll(false);
        setJsonExpandKey(jsonExpandKey + 1);
    };

    const downloadBlob = (blob: Blob, filename: string) => {
        const downloadUrl = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = downloadUrl;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(downloadUrl);
    };

    const exportOpenApiJson = () => {
        if (!apiResource?.openApiSpec) return;
        const blob = new Blob([JSON.stringify(apiResource.openApiSpec, null, 2)], { type: 'application/json' });
        downloadBlob(blob, `${resourceSlug || 'api'}-openapi.json`);
        setShowExportMenu(false);
    };

    const exportOpenApiYaml = () => {
        if (!apiResource?.openApiSpec) return;
        const yamlContent = toYaml(apiResource.openApiSpec);
        const blob = new Blob([yamlContent], { type: 'text/yaml' });
        downloadBlob(blob, `${resourceSlug || 'api'}-openapi.yaml`);
        setShowExportMenu(false);
    };

    const copyOpenApiToClipboard = async () => {
        if (!apiResource?.openApiSpec) return;
        try {
            await navigator.clipboard.writeText(JSON.stringify(apiResource.openApiSpec, null, 2));
            setCopiedOpenApi(true);
            setTimeout(() => setCopiedOpenApi(false), 2000);
            setShowExportMenu(false);
        } catch (err) {
            console.error('Failed to copy:', err);
        }
    };

    // Watch for apiResource URL changes (immediate)
    useEffect(() => {
        if (fullUrl) {
            const op = findOperation(selectedOperation);
            if (op) {
                setUrl(urlForOperation(op, recordId));
            }
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [fullUrl]);

    // Watch for apiToken prop changes
    useWatch(apiToken, (newToken) => {
        if (newToken) {
            setApiTokenInput(newToken);
        }
    });

    // Initialize
    useEffect(() => {
        if (apiToken) {
            setApiTokenInput(apiToken);
        }
        selectOperation('index');
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const sendDisabled = !!(isLoading || (currentOperation?.needsId && !recordId));
    const enabledParamsCount = queryParams.filter((p) => p.enabled).length;

    return (
        <div className="flex h-full bg-background">
            {/* Sidebar - Endpoints */}
            <div className={cn('border-r border-border flex flex-col bg-muted/20 transition-all duration-200', sidebarCollapsed ? 'w-12' : 'w-64')}>
                {/* Sidebar Header */}
                <div className="p-3 border-b border-border flex items-center justify-between">
                    {!sidebarCollapsed && (
                        <div className="flex items-center gap-2">
                            <FolderOpen className="h-4 w-4 text-primary" />
                            <span className="font-semibold text-sm">Endpoints</span>
                        </div>
                    )}
                    <button
                        onClick={() => setSidebarCollapsed(!sidebarCollapsed)}
                        className="p-1 rounded hover:bg-muted text-muted-foreground hover:text-foreground transition-colors"
                    >
                        <ChevronRight className={cn('h-4 w-4 transition-transform', sidebarCollapsed ? '' : 'rotate-180')} />
                    </button>
                </div>

                {/* Endpoints List */}
                <div className="flex-1 overflow-y-auto p-2 space-y-1 api-tester-scrollbar">
                    {/* CRUD Operations */}
                    {!sidebarCollapsed && (
                        <p className="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider px-2 pt-2 pb-1">CRUD Operations</p>
                    )}

                    {operations.map((op) => (
                        <div key={op.key} className="relative group">
                            <button
                                onClick={() => selectOperation(op.key)}
                                className={cn(
                                    'w-full flex items-center gap-2 rounded-md text-left transition-all',
                                    sidebarCollapsed ? 'p-2 justify-center' : 'px-3 py-2',
                                    selectedOperation === op.key ? 'bg-primary/10 text-primary' : 'hover:bg-muted text-foreground',
                                )}
                            >
                                {sidebarCollapsed ? (
                                    <span className={cn('text-[10px] font-bold', methodTextColors[op.method])}>{op.method.slice(0, 3)}</span>
                                ) : (
                                    <>
                                        <span className={cn('text-[10px] font-bold w-10', methodTextColors[op.method])}>{op.method}</span>
                                        <span className="text-sm truncate flex-1">{op.label}</span>
                                    </>
                                )}
                            </button>
                            {/* Tooltip for collapsed state */}
                            {sidebarCollapsed && (
                                <div className="sidebar-tooltip">
                                    <span className={cn('font-bold mr-1', methodTextColors[op.method])}>{op.method}</span>
                                    <span>{op.label}</span>
                                </div>
                            )}
                        </div>
                    ))}

                    {/* Custom Actions */}
                    {customActions.length > 0 && (
                        <>
                            {!sidebarCollapsed ? (
                                <>
                                    <div className="border-t border-border my-2"></div>
                                    <p className="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider px-2 pt-2 pb-1">Actions</p>
                                </>
                            ) : (
                                <div className="border-t border-border my-2"></div>
                            )}

                            {customActions.map((action) => (
                                <div key={action.key} className="relative group">
                                    <button
                                        onClick={() => selectOperation(action.key)}
                                        className={cn(
                                            'w-full flex items-center gap-2 rounded-md text-left transition-all',
                                            sidebarCollapsed ? 'p-2 justify-center' : 'px-3 py-2',
                                            selectedOperation === action.key ? 'bg-primary/10 text-primary' : 'hover:bg-muted text-foreground',
                                        )}
                                    >
                                        {sidebarCollapsed ? (
                                            <Zap className="h-4 w-4 text-amber-500" />
                                        ) : (
                                            <>
                                                <span className={cn('text-[10px] font-bold w-10', methodTextColors[action.method])}>{action.method}</span>
                                                <span className="text-sm truncate flex-1">{action.label}</span>
                                            </>
                                        )}
                                    </button>
                                    {/* Tooltip for collapsed state */}
                                    {sidebarCollapsed && (
                                        <div className="sidebar-tooltip">
                                            <span className={cn('font-bold mr-1', methodTextColors[action.method])}>{action.method}</span>
                                            <span>{action.label}</span>
                                        </div>
                                    )}
                                </div>
                            ))}
                        </>
                    )}
                </div>

                {/* API Info Footer */}
                {!sidebarCollapsed && apiResource?.version && (
                    <div className="p-3 border-t border-border">
                        <p className="text-xs text-muted-foreground">API {apiResource.version}</p>
                    </div>
                )}
            </div>

            {/* Main Content */}
            <div className="flex-1 flex flex-col min-w-0">
                {/* Request Section */}
                <div className="border-b border-border">
                    {/* Operation Info */}
                    {currentOperation && (
                        <div className="px-4 pt-3 pb-2">
                            <div className="flex items-center gap-2">
                                <span className={cn('px-2 py-0.5 text-xs font-bold rounded', methodBadgeColors[method])}>{method}</span>
                                <span className="text-sm font-medium">{currentOperation.label}</span>
                            </div>
                            {currentOperation.description && <p className="text-xs text-muted-foreground mt-1">{currentOperation.description}</p>}
                        </div>
                    )}

                    {/* Record ID Input */}
                    {currentOperation?.needsId && (
                        <div className="px-4 pb-2">
                            <div className="flex items-center gap-2">
                                <label className="text-xs text-muted-foreground">Record ID:</label>
                                <input
                                    value={recordId}
                                    onChange={(event) => handleRecordIdChange(event.target.value)}
                                    type="text"
                                    placeholder="Enter ID"
                                    className="w-32 px-2 py-1 text-sm rounded border border-border bg-background focus:outline-none focus:ring-1 focus:ring-primary"
                                />
                            </div>
                        </div>
                    )}

                    {/* URL Bar */}
                    <div className="px-4 pb-3">
                        <div className="flex items-center gap-2 bg-muted/30 rounded-lg p-1">
                            <div className="relative">
                                <select
                                    value={method}
                                    onChange={(event) => setMethod(event.target.value as HttpMethod)}
                                    className={cn(
                                        'appearance-none font-semibold text-sm px-3 py-2 pr-8 rounded-md bg-transparent cursor-pointer focus:outline-none',
                                        methodTextColors[method],
                                    )}
                                >
                                    {httpMethods.map((m) => (
                                        <option key={m} value={m} className="text-foreground bg-background" style={OPTION_STYLE}>
                                            {m}
                                        </option>
                                    ))}
                                </select>
                                <ChevronDown className="absolute right-2 top-1/2 -translate-y-1/2 h-4 w-4 pointer-events-none text-muted-foreground" />
                            </div>

                            <input
                                value={url}
                                onChange={(event) => setUrl(event.target.value)}
                                type="text"
                                placeholder="Enter request URL"
                                className="flex-1 bg-transparent px-3 py-2 text-sm font-mono focus:outline-none"
                            />

                            <button
                                onClick={sendRequest}
                                disabled={sendDisabled}
                                className={cn(
                                    'px-6 py-2 rounded-md font-semibold text-sm text-white flex items-center gap-2 transition-all',
                                    methodColors[method],
                                    sendDisabled ? 'opacity-50 cursor-not-allowed' : 'hover:opacity-90',
                                )}
                            >
                                {isLoading ? <Loader2 className="h-4 w-4 animate-spin" /> : <Send className="h-4 w-4" />}
                                Send
                            </button>
                        </div>
                    </div>

                    {/* Request Tabs */}
                    <div className="flex items-center border-t border-border px-4">
                        {(['params', 'headers', 'body', 'auth'] as const).map((tab) => (
                            <button
                                key={tab}
                                onClick={() => setRequestTab(tab)}
                                className={cn(
                                    'px-4 py-3 text-sm font-medium border-b-2 transition-colors capitalize',
                                    requestTab === tab ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground',
                                )}
                            >
                                {tab === 'params' ? 'Params' : tab === 'auth' ? 'Auth' : tab}
                                {tab === 'params' && enabledParamsCount > 0 && (
                                    <span className="ml-1.5 px-1.5 py-0.5 text-xs rounded-full bg-primary/20 text-primary">{enabledParamsCount}</span>
                                )}
                            </button>
                        ))}

                        <div className="ml-auto flex items-center gap-2">
                            <div className="relative">
                                <button
                                    onClick={() => setShowExportMenu(!showExportMenu)}
                                    className="p-2 rounded hover:bg-muted text-muted-foreground hover:text-foreground transition-colors"
                                    title="Export OpenAPI"
                                >
                                    <Download className="h-4 w-4" />
                                </button>
                                {showExportMenu && (
                                    <div className="absolute right-0 top-full mt-1 w-44 bg-background border border-border rounded-md shadow-lg z-50">
                                        <div className="py-1">
                                            <button
                                                onClick={exportOpenApiJson}
                                                className="w-full flex items-center gap-2 px-3 py-2 text-sm text-left hover:bg-muted transition-colors"
                                            >
                                                <FileJson className="h-4 w-4" />
                                                Export JSON
                                            </button>
                                            <button
                                                onClick={exportOpenApiYaml}
                                                className="w-full flex items-center gap-2 px-3 py-2 text-sm text-left hover:bg-muted transition-colors"
                                            >
                                                <Code className="h-4 w-4" />
                                                Export YAML
                                            </button>
                                            <button
                                                onClick={copyOpenApiToClipboard}
                                                className="w-full flex items-center gap-2 px-3 py-2 text-sm text-left hover:bg-muted transition-colors"
                                            >
                                                {copiedOpenApi ? (
                                                    <>
                                                        <Check className="h-4 w-4 text-green-500" />
                                                        Copied!
                                                    </>
                                                ) : (
                                                    <>
                                                        <Copy className="h-4 w-4" />
                                                        Copy to Clipboard
                                                    </>
                                                )}
                                            </button>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Tab Content */}
                    <div className="p-4 max-h-[250px] overflow-y-auto api-tester-scrollbar">
                        {/* Params Tab */}
                        {requestTab === 'params' && (
                            <div className="space-y-2">
                                {queryParams.length === 0 ? (
                                    <div className="text-center py-6 text-muted-foreground">
                                        <p className="text-sm">No query parameters</p>
                                        <button onClick={addQueryParam} className="mt-2 text-primary text-sm hover:underline">
                                            Add parameter
                                        </button>
                                    </div>
                                ) : (
                                    <>
                                        {queryParams.map((param, index) => (
                                            <div key={index} className="flex items-center gap-2 group">
                                                <input
                                                    type="checkbox"
                                                    checked={param.enabled}
                                                    onChange={(event) => updateQueryParam(index, { enabled: event.target.checked })}
                                                    className="h-4 w-4 rounded border-border accent-primary"
                                                />
                                                <input
                                                    value={param.key}
                                                    onChange={(event) => updateQueryParam(index, { key: event.target.value })}
                                                    type="text"
                                                    placeholder="Key"
                                                    className={INPUT_CLASS}
                                                />
                                                <input
                                                    value={param.value}
                                                    onChange={(event) => updateQueryParam(index, { value: event.target.value })}
                                                    type="text"
                                                    placeholder="Value"
                                                    className={INPUT_CLASS}
                                                />
                                                <button
                                                    onClick={() => removeQueryParam(index)}
                                                    className="p-1.5 text-muted-foreground hover:text-red-500 opacity-0 group-hover:opacity-100 transition-all"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </button>
                                            </div>
                                        ))}
                                        <button
                                            onClick={addQueryParam}
                                            className="flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground transition-colors"
                                        >
                                            <Plus className="h-4 w-4" />
                                            Add parameter
                                        </button>
                                    </>
                                )}
                            </div>
                        )}

                        {/* Headers Tab */}
                        {requestTab === 'headers' && (
                            <div className="space-y-2">
                                {headers.map((header, index) => (
                                    <div key={index} className="flex items-center gap-2 group">
                                        <input
                                            type="checkbox"
                                            checked={header.enabled}
                                            onChange={(event) => updateHeader(index, { enabled: event.target.checked })}
                                            className="h-4 w-4 rounded border-border accent-primary"
                                        />
                                        <input
                                            value={header.key}
                                            onChange={(event) => updateHeader(index, { key: event.target.value })}
                                            type="text"
                                            placeholder="Key"
                                            className={INPUT_CLASS}
                                        />
                                        <input
                                            value={header.value}
                                            onChange={(event) => updateHeader(index, { value: event.target.value })}
                                            type="text"
                                            placeholder="Value"
                                            className={INPUT_CLASS}
                                        />
                                        <button
                                            onClick={() => removeHeader(index)}
                                            className="p-1.5 text-muted-foreground hover:text-red-500 opacity-0 group-hover:opacity-100 transition-all"
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </button>
                                    </div>
                                ))}
                                <button onClick={addHeader} className="flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground transition-colors">
                                    <Plus className="h-4 w-4" />
                                    Add header
                                </button>
                            </div>
                        )}

                        {/* Body Tab */}
                        {requestTab === 'body' && (
                            <div className="space-y-3">
                                <div className="flex items-center justify-between">
                                    <span className="text-sm font-medium text-muted-foreground">Request Body (JSON)</span>
                                    <button
                                        onClick={generateSampleBody}
                                        className="text-xs px-3 py-1 bg-primary/10 text-primary rounded hover:bg-primary/20 transition-colors"
                                    >
                                        Generate Sample
                                    </button>
                                </div>
                                <textarea
                                    value={requestBody}
                                    onChange={(event) => setRequestBody(event.target.value)}
                                    placeholder='{"key": "value"}'
                                    className="w-full h-[150px] px-3 py-2 text-sm font-mono rounded border border-border bg-background resize-none focus:outline-none focus:ring-1 focus:ring-primary"
                                ></textarea>
                            </div>
                        )}

                        {/* Auth Tab */}
                        {requestTab === 'auth' && (
                            <div className="space-y-4">
                                <div className="flex items-center gap-2 p-3 bg-muted/30 rounded-lg">
                                    <Key className="h-5 w-5 text-muted-foreground" />
                                    <span className="text-sm font-medium">Bearer Token</span>
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm text-muted-foreground">Token</label>
                                    <div className="relative">
                                        <input
                                            value={apiTokenInput}
                                            onChange={(event) => setApiTokenInput(event.target.value)}
                                            type={showToken ? 'text' : 'password'}
                                            placeholder="Enter your API token"
                                            className="w-full px-3 py-2 pr-10 text-sm font-mono rounded border border-border bg-background focus:outline-none focus:ring-1 focus:ring-primary"
                                        />
                                        <button
                                            onClick={() => setShowToken(!showToken)}
                                            className="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-muted-foreground hover:text-foreground"
                                        >
                                            {showToken ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                        </button>
                                    </div>
                                    <p className="text-xs text-muted-foreground">Token will be sent as: Authorization: Bearer &lt;token&gt;</p>
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                {/* Response Section */}
                <div className="flex-1 flex flex-col min-h-0">
                    {/* Response Header */}
                    <div className="flex items-center justify-between border-b border-border px-4 py-2">
                        <div className="flex items-center gap-4">
                            <span className="text-sm font-semibold">Response</span>
                            {responseStatus ? (
                                <>
                                    <span className={cn('px-2 py-0.5 rounded text-sm font-medium', getStatusBg(responseStatus), getStatusColor(responseStatus))}>
                                        {responseStatus}
                                    </span>
                                    <span className="text-sm text-muted-foreground">{responseTime}ms</span>
                                    {responseSize ? <span className="text-sm text-muted-foreground">{formatBytes(responseSize)}</span> : null}
                                </>
                            ) : null}
                        </div>

                        <div className="flex items-center gap-2">
                            <div className="flex rounded-md border border-border overflow-hidden mr-2">
                                <button
                                    onClick={() => setResponseTab('body')}
                                    className={cn(
                                        'px-3 py-1 text-xs font-medium transition-colors',
                                        responseTab === 'body' ? 'bg-muted text-foreground' : 'text-muted-foreground hover:text-foreground',
                                    )}
                                >
                                    Body
                                </button>
                                <button
                                    onClick={() => setResponseTab('headers')}
                                    className={cn(
                                        'px-3 py-1 text-xs font-medium border-l border-border transition-colors',
                                        responseTab === 'headers' ? 'bg-muted text-foreground' : 'text-muted-foreground hover:text-foreground',
                                    )}
                                >
                                    Headers
                                </button>
                            </div>

                            {responseTab === 'body' && response && typeof response === 'object' && (
                                <>
                                    <div className="flex rounded-md border border-border overflow-hidden">
                                        <button
                                            onClick={() => setResponseViewMode('pretty')}
                                            className={cn(
                                                'px-2 py-1 text-xs font-medium transition-colors',
                                                responseViewMode === 'pretty' ? 'bg-muted text-foreground' : 'text-muted-foreground hover:text-foreground',
                                            )}
                                        >
                                            Pretty
                                        </button>
                                        <button
                                            onClick={() => setResponseViewMode('raw')}
                                            className={cn(
                                                'px-2 py-1 text-xs font-medium border-l border-border transition-colors',
                                                responseViewMode === 'raw' ? 'bg-muted text-foreground' : 'text-muted-foreground hover:text-foreground',
                                            )}
                                        >
                                            Raw
                                        </button>
                                    </div>

                                    <button onClick={expandAllJson} className="p-1.5 text-muted-foreground hover:text-foreground transition-colors" title="Expand All">
                                        <ChevronsUpDown className="h-4 w-4" />
                                    </button>
                                    <button onClick={collapseAllJson} className="p-1.5 text-muted-foreground hover:text-foreground transition-colors" title="Collapse All">
                                        <ChevronsDownUp className="h-4 w-4" />
                                    </button>
                                </>
                            )}

                            {response && (
                                <button
                                    onClick={copyResponse}
                                    className="p-1.5 text-muted-foreground hover:text-foreground transition-colors"
                                    title={copiedResponse ? 'Copied!' : 'Copy Response'}
                                >
                                    {copiedResponse ? <Check className="h-4 w-4 text-green-500" /> : <Copy className="h-4 w-4" />}
                                </button>
                            )}
                        </div>
                    </div>

                    {/* Response Body */}
                    <div className="flex-1 overflow-auto p-4 bg-muted/5 api-tester-scrollbar">
                        {isLoading ? (
                            <div className="flex items-center justify-center h-full">
                                <div className="flex items-center gap-3 text-muted-foreground">
                                    <Loader2 className="h-5 w-5 animate-spin" />
                                    <span>Sending request...</span>
                                </div>
                            </div>
                        ) : error ? (
                            <div className="flex items-center justify-center h-full">
                                <div className="text-center">
                                    <p className="text-red-500 font-medium">Request Failed</p>
                                    <p className="text-sm text-muted-foreground mt-1">{error}</p>
                                </div>
                            </div>
                        ) : !response ? (
                            <div className="flex items-center justify-center h-full">
                                <div className="text-center text-muted-foreground">
                                    <Send className="h-12 w-12 mx-auto mb-3 opacity-30" />
                                    <p className="font-medium">No Response Yet</p>
                                    <p className="text-sm mt-1">Click Send to make a request</p>
                                </div>
                            </div>
                        ) : responseTab === 'body' ? (
                            responseViewMode === 'pretty' && typeof response === 'object' ? (
                                <div className="text-sm font-mono">
                                    <JsonNode key={jsonExpandKey} data={response} depth={0} forceExpand={forceExpandAll} />
                                </div>
                            ) : (
                                <pre className="text-sm font-mono whitespace-pre-wrap break-words text-foreground" style={PRE_STYLE}>
                                    {formattedResponse}
                                </pre>
                            )
                        ) : responseTab === 'headers' ? (
                            <div className="space-y-1">
                                {Object.entries(responseHeaders).map(([key, value]) => (
                                    <div key={key} className="flex items-start gap-2 py-1 text-sm font-mono">
                                        <span className="text-rose-600 dark:text-rose-400 font-medium">{key}:</span>
                                        <span className="text-foreground break-all">{value}</span>
                                    </div>
                                ))}
                                {Object.keys(responseHeaders).length === 0 && (
                                    <div className="text-center py-8 text-muted-foreground">
                                        <p className="text-sm">No response headers</p>
                                    </div>
                                )}
                            </div>
                        ) : null}
                    </div>
                </div>
            </div>
        </div>
    );
}
