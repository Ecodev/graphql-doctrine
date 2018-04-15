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
        ],
        [
            'name' => 'nameWithDefaultValueOnField',
            'type' => 'String',
            'description' => null,
        ],
        [
            'name' => 'nameWithDefaultValueOnArgument',
            'type' => 'String',
            'description' => null,
        ],
        [
            'name' => 'nameWithDefaultValueOnArgumentOverrideField',
            'type' => 'String',
            'description' => null,
        ],
        [
            'name' => 'nameWithDefaultValueOnArgumentNullable',
            'type' => 'String',
            'description' => null,
        ],
        [
            'name' => 'creationDate',
            'type' => 'DateTime',
            'description' => null,
        ],
    ],
];
