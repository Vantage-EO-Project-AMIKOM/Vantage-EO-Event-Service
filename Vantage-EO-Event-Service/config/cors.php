<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',
        'https://vantage-amikom.netlify.app',
        'https://vantage-eo.vercel.app',
        'https://vantage-eo-vantage18.vercel.app',
        'https://vantage-eo-git-main-vantage18.vercel.app',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
