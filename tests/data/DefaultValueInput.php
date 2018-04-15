<?php

declare(strict_types=1);

return [
    'name' => 'DefaultValueInput',
    'description' => null,
    'fields' => [
        [
            'name' => 'nameWithoutDefault',
            'type' => 'String!',
            'description' => null,
        ],
        [
            'name' => 'nameWithDefaultValueOnField',
            'type' => 'String',
            'description' => null,
            'defaultValue' => 'jane',
        ],
        [
            'name' => 'nameWithDefaultValueOnArgument',
            'type' => 'String',
            'description' => null,
            'defaultValue' => 'john',
        ],
        [
            'name' => 'nameWithDefaultValueOnArgumentOverrideField',
            'type' => 'String',
            'description' => null,
            'defaultValue' => 'argument',
        ],
        [
            'name' => 'nameWithDefaultValueOnArgumentNullable',
            'type' => 'String',
            'description' => null,
            'defaultValue' => null,
        ],
        [
            'name' => 'creationDate',
            'type' => 'DateTime!',
            'description' => null,
        ],
    ],
];
