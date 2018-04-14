<?php

declare(strict_types=1);

return [
    'name' => 'DefaultValuePartialInput',
    'description' => null,
    'fields' => [
        [
            'name' => 'nameWithoutDefault',
            'type' => 'String',
            'description' => null,
            'defaultValue' => null,
        ],
        [
            'name' => 'nameWithDefaultValueOnField',
            'type' => 'String',
            'description' => null,
            'defaultValue' => null,
        ],
        [
            'name' => 'nameWithDefaultValueOnArgument',
            'type' => 'String',
            'description' => null,
            'defaultValue' => null,
        ],
        [
            'name' => 'nameWithDefaultValueOnArgumentOverrideField',
            'type' => 'String',
            'description' => null,
            'defaultValue' => null,
        ],
        [
            'name' => 'nameWithDefaultValueOnArgumentNullable',
            'type' => 'String',
            'description' => null,
            'defaultValue' => null,
        ],
        [
            'name' => 'creationDate',
            'type' => 'DateTime',
            'description' => null,
            'defaultValue' => null,
        ],
    ],
];
