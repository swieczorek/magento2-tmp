<?php

return [
    'code' => '236',
    'patterns' => [
        'national' => [
            'general' => '/^[278]\\d{7}$/',
            'fixed' => '/^2[12]\\d{6}$/',
            'mobile' => '/^7[0257]\\d{6}$/',
            'premium' => '/^8776\\d{4}$/',
        ],
        'possible' => [
            'general' => '/^\\d{8}$/',
        ],
    ],
];
