<?php

/**
 * Smart Notification Center — Central type mapping (Sprint 19).
 *
 * Every notification belongs to exactly one TYPE. Each type owns:
 *  - icon   (Bootstrap Icons class)
 *  - color  (Bootstrap color name used for strips / dots / badges)
 *  - hex    (exact color used for the bell dot + card strip)
 *  - label  (Arabic human label)
 *
 * The BLACK color is RESERVED for the "student_removed" type only.
 * No other type may use black.
 */
return [
    'types' => [
        'resource' => [
            'label' => 'مصدر',
            'icon'  => 'bi-file-earmark-text-fill',
            'color' => 'purple',
            'hex'   => '#7c3aed',
            'action'=> 'عرض',
        ],
        'exam_published' => [
            'label' => 'امتحان منشور',
            'icon'  => 'bi-file-earmark-check-fill',
            'color' => 'blue',
            'hex'   => '#2563eb',
            'action'=> 'فتح',
        ],
        'exam_closed' => [
            'label' => 'امتحان مغلق',
            'icon'  => 'bi-file-earmark-x-fill',
            'color' => 'orange',
            'hex'   => '#ea580c',
            'action'=> 'عرض',
        ],
        'result_published' => [
            'label' => 'نتيجة امتحان',
            'icon'  => 'bi-bar-chart-fill',
            'color' => 'green',
            'hex'   => '#16a34a',
            'action'=> 'عرض',
        ],
        'exam_created' => [
            'label' => 'امتحان جديد',
            'icon'  => 'bi-file-earmark-plus-fill',
            'color' => 'purple',
            'hex'   => '#7c3aed',
            'action'=> 'فتح',
        ],
        'attendance' => [
            'label' => 'حضور',
            'icon'  => 'bi-calendar2-check-fill',
            'color' => 'green',
            'hex'   => '#16a34a',
            'action'=> 'عرض',
        ],
        'reservation' => [
            'label' => 'حجز',
            'icon'  => 'bi-calendar-check-fill',
            'color' => 'orange',
            'hex'   => '#ea580c',
            'action'=> 'فتح',
        ],
        'term' => [
            'label' => 'ترم',
            'icon'  => 'bi-calendar-range-fill',
            'color' => 'indigo',
            'hex'   => '#4f46e5',
            'action'=> 'عرض',
        ],
        'group' => [
            'label' => 'مجموعة',
            'icon'  => 'bi-people-fill',
            'color' => 'teal',
            'hex'   => '#0d9488',
            'action'=> 'عرض',
        ],
        'assistant' => [
            'label' => 'مساعد',
            'icon'  => 'bi-person-gear',
            'color' => 'brown',
            'hex'   => '#92400e',
            'action'=> 'عرض',
        ],
        'system' => [
            'label' => 'نظام',
            'icon'  => 'bi-gear-fill',
            'color' => 'gray',
            'hex'   => '#6b7280',
            'action'=> 'عرض',
        ],
        'student_removed' => [
            'label' => 'تم إزالة طالب',
            'icon'  => 'bi-person-x-fill',
            'color' => 'black',
            'hex'   => '#111827',
            'action'=> 'عرض',
        ],
        'lesson' => [
            'label' => 'درس',
            'icon'  => 'bi-journal-bookmark-fill',
            'color' => 'blue',
            'hex'   => '#2563eb',
            'action'=> 'عرض',
        ],
        'grade_updated' => [
            'label' => 'تعديل درجة',
            'icon'  => 'bi-bar-chart-fill',
            'color' => 'green',
            'hex'   => '#16a34a',
            'action'=> 'عرض',
        ],
    ],

    /**
     * Multi-category indicator color: when the user has unread notifications
     * belonging to more than one type, the bell shows a RED dot.
     */
    'multi_color' => '#dc2626',
];