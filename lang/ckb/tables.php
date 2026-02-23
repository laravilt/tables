<?php

return [
    // Table Actions
    'actions' => [
        'create' => 'زیادکردن',
        'edit' => 'دەستکاریکردن',
        'view' => 'بینین',
        'delete' => 'سڕینەوە',
        'restore' => 'گەڕاندنەوە',
        'force_delete' => 'سڕینەوەی بەزۆر',
        'bulk_delete' => 'سڕینەوەی دەستنیشانکراوەکان',
        'bulk_restore' => 'گەڕاندنەوەی دەستنیشانکراوەکان',
        'export' => 'هەناردەکردن',
        'import' => 'هاوردەکردن',
    ],

    // Table Columns
    'columns' => [
        'select_all' => 'هەمووی دیاریبکە',
        'actions' => 'کردارەکان',
        'no_data' => 'هیچ تۆمارێک نەدۆزرایەوە',
        'loading' => 'بارکردن...',
    ],

    // Toolbar
    'toolbar' => [
        'columns' => 'ستوونەکان',
        'toggle_columns' => 'گۆڕینی ستوونەکان',
        'filters' => 'پاڵاوتنەکان',
        'clear_all' => 'سڕینەوەی هەموو',
        'active_filters' => 'پاڵاوتنە چالاکەکان',
        'sort_by' => 'ڕیزکردن بەپێی',
        'search' => 'گەڕان',
        'group_by' => 'کۆکردنەوە بەپێی',
        'no_grouping' => 'بێ کۆکردنەوە',
    ],

    // Search & Filter
    'search' => [
        'placeholder' => 'گەڕان...',
        'no_results' => 'هیچ ئەنجامێک نەدۆزرایەوە',
        'clear' => 'سڕینەوەی گەڕان',
    ],

    'filters' => [
        'title' => 'پاڵاوتنەکان',
        'apply' => 'جێبەجێکردنی پاڵاوتنەکان',
        'reset' => 'ڕێکخستنەوەی پاڵاوتنەکان',
        'clear' => 'سڕینەوە',
        'indicator' => ':count چالاک',
        'trashed' => [
            'label' => 'تۆمارە سڕاوەکان',
            'without' => 'بەبێ سڕاوەکان',
            'with' => 'لەگەڵ سڕاوەکان',
            'only' => 'تەنها سڕاوەکان',
        ],
    ],

    // Pagination
    'pagination' => [
        'showing' => 'پیشاندانی',
        'to' => 'بۆ',
        'of' => 'لە',
        'results' => 'ئەنجامەکان',
        'per_page' => 'بۆ هەر پەڕەیەک',
        'previous' => 'پێشتر',
        'next' => 'دواتر',
        'first' => 'یەکەم',
        'last' => 'کۆتا',
    ],

    // Sorting
    'sorting' => [
        'asc' => 'بەرەوژوور',
        'desc' => 'بەرەوخوار',
        'clear' => 'لابردنی ڕیزکردن',
    ],

    // Bulk Actions
    'bulk' => [
        'selected' => ':count دەستنیشانکراو',
        'select_all' => 'هەموو :count دیاریبکە',
        'deselect_all' => 'هەڵوەشاندنەوەی هەموو',
        'no_selection_title' => 'هیچ تۆمارێک دیاری نەکراوە',
        'no_selection_body' => 'تکایە لانیکەم یەک تۆمار بۆ سڕینەوە دیاری بکە.',
    ],

    // Confirmation
    'confirm' => [
        'delete' => 'دڵنیایت دەتەوێت ئەم تۆمارە بسڕیتەوە؟',
        'bulk_delete' => 'دڵنیایت دەتەوێت تۆمارە دیاریکراوەکان بسڕیتەوە؟',
        'restore' => 'دڵنیایت دەتەوێت ئەم تۆمارە بگەڕێنیتەوە؟',
        'bulk_restore' => 'دڵنیایت دەتەوێت تۆمارە دیاریکراوەکان بگەڕێنیتەوە؟',
    ],

    // Messages
    'messages' => [
        'deleted' => 'تۆمار بە سەرکەوتوویی سڕایەوە',
        'restored' => 'تۆمار بە سەرکەوتوویی گەڕێندرایەوە',
        'bulk_deleted' => ':count تۆمار بە سەرکەوتوویی سڕانەوە',
        'bulk_restored' => ':count تۆمار بە سەرکەوتوویی گەڕێندرانەوە',
    ],

    // Toggle Column
    'toggle_column' => [
        'success_notification_title' => 'نوێکرایەوە',
        'success_notification_message' => 'بەهاکە بە سەرکەوتوویی نوێکرایەوە',
        'error_notification_title' => 'هەڵە',
        'error_notification_message' => 'نوێکردنەوەی بەهاکە سەرکەوتوو نەبوو',
    ],

    // Infinite Scroll
    'infinite_scroll' => [
        'loading_more' => 'بارکردنی زیاتر...',
        'scroll_for_more' => 'بۆ زیاتر سکڕۆڵ بکە',
        'no_more_records' => 'هیچ تۆمارێکی تر نییە',
    ],
];
