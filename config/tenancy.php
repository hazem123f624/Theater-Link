<?php

return [
    /*
    | The workspace reservation hold window. A pending hall request keeps the
    | slot held for this many minutes before the expiry job releases it.
    */
    'reservation_hold_minutes' => (int) env('THEATERLINK_RESERVATION_HOLD_MINUTES', 2880),

    /*
    | How far ahead hall slots are materialized from weekly schedules.
    */
    'slot_horizon_days' => (int) env('THEATERLINK_SLOT_HORIZON_DAYS', 90),

    /*
    | Minimum lead time before a slot can be requested or booked.
    */
    'booking_lead_minutes' => (int) env('THEATERLINK_BOOKING_LEAD_MINUTES', 60),

    'default_currency' => env('THEATERLINK_CURRENCY', 'SAR'),

    'workspace' => [
        'base_url' => env('THEATERLINK_WORKSPACE_BASE_URL', env('APP_URL', 'http://theaterlink.test')),
    ],

    'support' => [
        'email' => env('THEATERLINK_SUPPORT_EMAIL', 'support@theaterlink.test'),
        'phone' => env('THEATERLINK_SUPPORT_PHONE', '+966500000000'),
    ],
];
