<?php

return [
    'default_provider' => 'openai',
    'providers' => [
        'openai' => [
            'api_key' => getenv('OPENAI_API_KEY'),
            'model' => getenv('OPENAI_MODEL'),
        ],
        // Add other providers here as needed
    ],
];