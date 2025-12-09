<?php

return [
    'name' => 'Taskfiles',
    'description' => 'Taskfiles management module',
    'version' => '1.0.0',
    'routes' => [
        'api' => [
            'prefix' => 'api/taskfiles',
            'middleware' => ['api'],
        ],
        'web' => [
            'prefix' => 'taskfiles',
            'middleware' => ['web'],
        ],
    ],
];
