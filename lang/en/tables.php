<?php

return [
    // Table Actions
    'actions' => [
        'create' => 'Create',
        'edit' => 'Edit',
        'view' => 'View',
        'delete' => 'Delete',
        'restore' => 'Restore',
        'force_delete' => 'Force Delete',
        'bulk_delete' => 'Delete Selected',
        'bulk_restore' => 'Restore Selected',
        'export' => 'Export',
        'import' => 'Import',
    ],

    // Table Columns
    'columns' => [
        'select_all' => 'Select All',
        'actions' => 'Actions',
        'no_data' => 'No records found',
        'loading' => 'Loading...',
    ],

    // Toolbar
    'toolbar' => [
        'columns' => 'Columns',
        'toggle_columns' => 'Toggle columns',
        'filters' => 'Filters',
        'clear_all' => 'Clear all',
        'active_filters' => 'Active filters',
        'sort_by' => 'Sort by',
        'search' => 'Search',
    ],

    // Search & Filter
    'search' => [
        'placeholder' => 'Search...',
        'no_results' => 'No results found',
        'clear' => 'Clear search',
    ],

    'filters' => [
        'title' => 'Filters',
        'apply' => 'Apply Filters',
        'reset' => 'Reset Filters',
        'clear' => 'Clear',
        'indicator' => ':count active',
        'trashed' => [
            'label' => 'Deleted Records',
            'without' => 'Without trashed',
            'with' => 'With trashed',
            'only' => 'Only trashed',
        ],
    ],

    // Pagination
    'pagination' => [
        'showing' => 'Showing',
        'to' => 'to',
        'of' => 'of',
        'results' => 'results',
        'per_page' => 'Per page',
        'previous' => 'Previous',
        'next' => 'Next',
        'first' => 'First',
        'last' => 'Last',
    ],

    // Sorting
    'sorting' => [
        'asc' => 'Ascending',
        'desc' => 'Descending',
        'clear' => 'Clear sorting',
    ],

    // Bulk Actions
    'bulk' => [
        'selected' => ':count selected',
        'select_all' => 'Select all :count items',
        'deselect_all' => 'Deselect all',
        'no_selection_title' => 'No records selected',
        'no_selection_body' => 'Please select at least one record to delete.',
    ],

    // Confirmation
    'confirm' => [
        'delete' => 'Are you sure you want to delete this record?',
        'bulk_delete' => 'Are you sure you want to delete the selected records?',
        'restore' => 'Are you sure you want to restore this record?',
        'bulk_restore' => 'Are you sure you want to restore the selected records?',
    ],

    // Messages
    'messages' => [
        'deleted' => 'Record deleted successfully',
        'restored' => 'Record restored successfully',
        'bulk_deleted' => ':count records deleted successfully',
        'bulk_restored' => ':count records restored successfully',
    ],

    // Toggle Column
    'toggle_column' => [
        'success_notification_title' => 'Updated',
        'success_notification_message' => 'Value updated successfully',
        'error_notification_title' => 'Error',
        'error_notification_message' => 'Failed to update value',
    ],

    // Infinite Scroll
    'infinite_scroll' => [
        'loading_more' => 'Loading more...',
        'scroll_for_more' => 'Scroll for more',
        'no_more_records' => 'No more records',
    ],
];
