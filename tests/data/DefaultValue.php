<?php

declare(strict_types=1);

return [
    'name' => 'DefaultValue',
    'description' => null,
    'fields' => [
        [
            'name' => 'nameWithoutDefault',
            'type' => 'String!',
            'description' => null,
            'args' => [
                [
                    'name' => 'name',
                    'type' => 'String!',
                    'description' => null,
                ],
            ],

        ],
        [
            'name' => 'nameWithDefaultValueOnArgument',
            'type' => 'String!',
            'description' => null,
            'args' => [
                [
                    'name' => 'name',
                    'type' => 'String',
                    'description' => null,
                    'defaultValue' => 'john',
                ],
            ],
        ],
        [
            'name' => 'nameWithDefaultValueOnArgumentNullable',
            'type' => 'String!',
            'description' => null,
            'args' => [

                [
                    'name' => 'name',
                    'type' => 'String',
                    'description' => null,
                    'defaultValue' => null,
                ],

            ],
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
