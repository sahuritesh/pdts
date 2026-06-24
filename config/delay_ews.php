<?php

/**
 * Delay severity, EWS alert levels, and escalation definitions.
 * Auto-calculated on delay save; shown in delay register UI for reference.
 * Aligned with tbl_delay_severity_rules, tbl_ews_alert_levels, tbl_ews_escalation_matrix.
 */
return [
    'severity' => [
        'minor' => [
            'label' => 'Minor',
            'description' => '1–7 calendar days of delay.',
            'alert_level' => 'green',
        ],
        'moderate' => [
            'label' => 'Moderate',
            'description' => '8–30 calendar days of delay.',
            'alert_level' => 'amber',
        ],
        'critical' => [
            'label' => 'Critical',
            'description' => 'More than 30 calendar days of delay.',
            'alert_level' => 'red',
        ],
        'showstopper' => [
            'label' => 'Showstopper',
            'description' => 'Impacts licensing, hospital opening, or other go-live milestone (regardless of days).',
            'alert_level' => 'black',
        ],
    ],

    'alert_levels' => [
        'green' => [
            'label' => 'Green — On Track',
            'description' => 'Low-risk delay; monitor at project SPOC level.',
            'badge_class' => 'success',
        ],
        'amber' => [
            'label' => 'Amber — Potential Delay',
            'description' => 'Moderate risk; department leadership should be aware.',
            'badge_class' => 'warning text-dark',
        ],
        'red' => [
            'label' => 'Red — Critical Delay',
            'description' => 'High risk; steering committee visibility required.',
            'badge_class' => 'danger',
        ],
        'black' => [
            'label' => 'Black — Showstopper',
            'description' => 'Blocks licensing or opening; management escalation.',
            'badge_class' => 'dark',
        ],
    ],

    'escalation_levels' => [
        1 => [
            'label' => 'Level 1 — Project SPOC',
            'role' => 'Project SPOC',
            'description' => 'Handled by the project responsible SPOC.',
            'trigger_severity' => 'minor',
            'trigger_alert_level' => 'green',
        ],
        2 => [
            'label' => 'Level 2 — Department Head',
            'role' => 'Department Head',
            'description' => 'Department head or equivalent should review and act.',
            'trigger_severity' => 'moderate',
            'trigger_alert_level' => 'amber',
        ],
        3 => [
            'label' => 'Level 3 — Project Steering Committee',
            'role' => 'Project Steering Committee',
            'description' => 'Steering committee oversight and decisions needed.',
            'trigger_severity' => 'critical',
            'trigger_alert_level' => 'red',
        ],
        4 => [
            'label' => 'Level 4 — Management',
            'role' => 'Management',
            'description' => 'Executive / management escalation for showstopper delays.',
            'trigger_severity' => 'showstopper',
            'trigger_alert_level' => 'black',
        ],
    ],
];
