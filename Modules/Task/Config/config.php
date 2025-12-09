<?php

return [
    'name' => 'Task',
    'description' => 'Task management module',
    'version' => '1.0.0',
    'tasks' => [
        'default_per_page' => 15,
        'statuses' => [
            'backlog' => 'Backlog',
            'todo' => 'À faire',
            'doing' => 'En cours',
            'done' => 'Terminé',
        ],
        'priorities' => [
            'low' => 'Basse',
            'medium' => 'Moyenne',
            'high' => 'Haute',
        ],
        'max_file_size' => 10240,
        'allowed_file_types' => [
            'jpg',
            'jpeg',
            'png',
            'gif',
            'pdf',
            'doc',
            'docx',
            'xls',
            'xlsx',
            'ppt',
            'pptx',
            'txt',
            'zip',
            'rar'
        ],
    ],
    'routes' => [
        'api' => [
            'prefix' => 'api/task',
            'middleware' => ['api'],
        ],
        'web' => [
            'prefix' => 'task',
            'middleware' => ['web'],
        ],
    ],
];
