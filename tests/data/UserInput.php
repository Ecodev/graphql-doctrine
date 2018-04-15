<?php

declare(strict_types=1);

return [
    'name' => 'UserInput',
    'description' => 'A blog author or visitor',
    'fields' => [
        [
            'name' => 'name',
            'type' => 'String',
            'description' => 'Name',
            'defaultValue' => '',
        ],
        [
            'name' => 'email',
            'type' => 'String',
            'description' => 'A valid email or null',
        ],
        [
            'name' => 'password',
            'type' => 'String!',
            'description' => 'Encrypt and change the user password',
        ],
        [
            'name' => 'manager',
            'type' => 'UserID',
            'description' => null,
        ],
        [
            'name' => 'creationDate',
            'type' => 'DateTime!',
            'description' => null,
        ],
    ],
];
