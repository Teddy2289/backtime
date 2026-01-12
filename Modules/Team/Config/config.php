<?php

return [
    'name' => 'Team',
    'description' => 'Team management module',
    'version' => '1.0.0',
    'permissions' => [
        'view_teams' => 'View teams',
        'create_teams' => 'Create teams',
        'edit_teams' => 'Edit teams',
        'delete_teams' => 'Delete teams',
        'manage_team_members' => 'Manage team members',
    ],
    
    // Default settings
    'defaults' => [
        'per_page' => 15,
        'max_members' => 50,
    ],
    
    // Routes configuration
    'routes' => [
        'prefix' => 'api/teams',
        'middleware' => ['api', 'auth:sanctum'],
    ],
    
    // Routes configuration
    'routes' => [
        'api' => [
            'prefix' => 'api/team',
            'middleware' => ['api'],
        ],
        'web' => [
            'prefix' => 'team',
            'middleware' => ['web'],
        ],
    ],
];
