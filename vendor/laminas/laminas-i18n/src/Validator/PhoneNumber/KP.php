<?php

return [
    'code' => '850',
    'patterns' => [
        'national' => [
            'general' => '/^(?:1\\d{9}|[28]\\d{7})$/',
            'fixed' => '/^(?:2\\d{7}|85\\d{6})$/',
            'mobile' => '/^19[123]\\d{7}$/',
        ],
        'possible' => [
            'general' => '/^(?:\\d{6,8}|\\d{10})$/',
            'fixed' => '/^\\d{6,8}$/',
            'mobile' => '/^\\d{10}$/',
        ],
    ],
];
