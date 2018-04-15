<?php

declare(strict_types=1);

return [
    'name' => 'IgnoredGetter',
    'description' => null,
    'fields' => [
        [
            'name' => 'public',
            'type' => 'String!',
            'description' => null,
            'args' => [],
        ],
        [
            'name' => 'publicWithArgs',
            'type' => '[String]!',
            'description' => null,
            'args' => [
                [
                    'name' => 'arg1',
                    'type' => 'String!',
                    'description' => null,
                ],
                [
                    'name' => 'arg2',
                    'type' => 'Int!',
                    'description' => null,
                ],
                [
                    'name' => 'arg3',
                    'type' => '[String]',
                    'description' => null,
                    'defaultValue' => ['foo'],
                ],
            ],
        ],
        [
            'name' => 'isValid',
            'type' => 'Boolean!',
            'description' => null,
            'args' => [],
        ],
        [
            'name' => 'hasMoney',
            'type' => 'Boolean!',
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
