<?php

declare(strict_types=1);

return [
    'sources' => [
        'guardian' => [
            'api_key' => env('GUARDIAN_API_KEY'),
            'base_url' => env('GUARDIAN_BASE_URL', 'https://content.guardianapis.com'),
            'max_requests_per_minute' => env('GUARDIAN_RATE_LIMIT', 12),
        ],
        'newsapi' => [
            'api_key' => env('NEWS_API_KEY'),
            'base_url' => env('NEWS_API_BASE_URL', 'https://newsapi.org/v2'),
            'max_requests_per_minute' => env('NEWS_API_RATE_LIMIT', 100),
        ],
        'nyt' => [
            'api_key' => env('NYT_API_KEY'),
            'base_url' => env('NYT_BASE_URL', 'https://api.nytimes.com/svc'),
            'max_requests_per_minute' => env('NYT_RATE_LIMIT', 5),
        ],
    ],
];
