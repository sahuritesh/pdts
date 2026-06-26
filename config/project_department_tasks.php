<?php

return [
    'kinds' => [
        'standard' => 'Task',
        'linked_department' => 'Link department',
    ],

    'statuses' => [
        'not_started' => 'Not Started',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'on_hold' => 'On Hold',
    ],

    'status_badges' => [
        'not_started' => ['Not Started', 'badge-soft-secondary'],
        'in_progress' => ['In Progress', 'badge-soft-primary'],
        'completed' => ['Completed', 'badge-soft-success'],
        'on_hold' => ['On Hold', 'badge-soft-warning'],
    ],
];
