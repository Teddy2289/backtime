<?php

return [
    'name' => 'Taskcomment',
    'description' => 'Taskcomment management module',
    'version' => '1.0.0',
    'routes' => [
        'api' => [
            'prefix' => 'api/taskcomment',
            'middleware' => ['api'],
        ],
        'web' => [
            'prefix' => 'taskcomment',
            'middleware' => ['web'],
        ],
    ],
];
