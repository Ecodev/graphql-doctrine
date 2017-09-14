<?php

declare(strict_types=1);

return [
    'name' => 'ArrayOfEntity',
    'description' => null,
    'fields' => [
        [
            'name' => 'users',
            'type' => '[User]!',
            'description' => null,
            'args' => [],
        ],
        [
            'name' => 'id',
            'type' => 'ID!',
            'description' => null,
            'args' => [],
        ],
        [
            'name' => 'creationDate',
            'type' => 'DateTime!',
            'description' => null,
            'args' => [],
        ],
    ],
];
