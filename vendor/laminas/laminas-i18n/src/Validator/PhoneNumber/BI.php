<?php

return [
    'code' => '257',
    'patterns' => [
        'national' => [
            'general' => '/^[27]\\d{7}$/',
            'fixed' => '/^22(?:2[0-7]|[3-5]0)\\d{4}$/',
            'mobile' => '/^(?:29\\d|7(?:1[1-3]|[4-9]\\d))\\d{5}$/',
            'emergency' => '/^11[78]$/',
        ],
        'possible' => [
            'general' => '/^\\d{8}$/',
            'emergency' => '/^\\d{3}$/',
        ],
    ],
];
