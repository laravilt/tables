<?php

return [
    // Table Actions
    'actions' => [
        'create' => 'إنشاء',
        'edit' => 'تعديل',
        'view' => 'عرض',
        'delete' => 'حذف',
        'restore' => 'استعادة',
        'force_delete' => 'حذف نهائي',
        'bulk_delete' => 'حذف المحدد',
        'bulk_restore' => 'استعادة المحدد',
        'export' => 'تصدير',
        'import' => 'استيراد',
    ],

    // Table Columns
    'columns' => [
        'select_all' => 'تحديد الكل',
        'actions' => 'الإجراءات',
        'no_data' => 'لا توجد سجلات',
        'loading' => 'جاري التحميل...',
    ],

    // Toolbar
    'toolbar' => [
        'columns' => 'الأعمدة',
        'toggle_columns' => 'تبديل الأعمدة',
        'filters' => 'التصفية',
        'clear_all' => 'مسح الكل',
        'active_filters' => 'التصفيات النشطة',
        'sort_by' => 'ترتيب حسب',
        'search' => 'بحث',
        'group_by' => 'تجميع حسب',
        'no_grouping' => 'بدون تجميع',
    ],

    // Search & Filter
    'search' => [
        'placeholder' => 'بحث...',
        'no_results' => 'لا توجد نتائج',
        'clear' => 'مسح البحث',
    ],

    'filters' => [
        'title' => 'التصفية',
        'apply' => 'تطبيق التصفية',
        'reset' => 'إعادة تعيين التصفية',
        'clear' => 'مسح',
        'indicator' => ':count نشط',
        'trashed' => [
            'label' => 'السجلات المحذوفة',
            'without' => 'بدون المحذوف',
            'with' => 'مع المحذوف',
            'only' => 'المحذوف فقط',
        ],
    ],

    // Pagination
    'pagination' => [
        'showing' => 'عرض',
        'to' => 'إلى',
        'of' => 'من',
        'results' => 'نتيجة',
        'per_page' => 'لكل صفحة',
        'previous' => 'السابق',
        'next' => 'التالي',
        'first' => 'الأول',
        'last' => 'الأخير',
    ],

    // Sorting
    'sorting' => [
        'asc' => 'تصاعدي',
        'desc' => 'تنازلي',
        'clear' => 'مسح الترتيب',
    ],

    // Bulk Actions
    'bulk' => [
        'selected' => ':count محدد',
        'select_all' => 'تحديد جميع :count العناصر',
        'deselect_all' => 'إلغاء تحديد الكل',
        'no_selection_title' => 'لم يتم تحديد سجلات',
        'no_selection_body' => 'يرجى تحديد سجل واحد على الأقل للحذف.',
    ],

    // Confirmation
    'confirm' => [
        'delete' => 'هل أنت متأكد أنك تريد حذف هذا السجل؟',
        'bulk_delete' => 'هل أنت متأكد أنك تريد حذف السجلات المحددة؟',
        'restore' => 'هل أنت متأكد أنك تريد استعادة هذا السجل؟',
        'bulk_restore' => 'هل أنت متأكد أنك تريد استعادة السجلات المحددة؟',
    ],

    // Messages
    'messages' => [
        'deleted' => 'تم حذف السجل بنجاح',
        'restored' => 'تم استعادة السجل بنجاح',
        'bulk_deleted' => 'تم حذف :count سجل بنجاح',
        'bulk_restored' => 'تم استعادة :count سجل بنجاح',
    ],

    // Toggle Column
    'toggle_column' => [
        'success_notification_title' => 'تم التحديث',
        'success_notification_message' => 'تم تحديث القيمة بنجاح',
        'error_notification_title' => 'خطأ',
        'error_notification_message' => 'فشل تحديث القيمة',
    ],

    // Infinite Scroll
    'infinite_scroll' => [
        'loading_more' => 'جاري تحميل المزيد...',
        'scroll_for_more' => 'مرر للمزيد',
        'no_more_records' => 'لا توجد سجلات أخرى',
    ],
];
