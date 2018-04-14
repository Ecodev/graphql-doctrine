<?php

declare(strict_types=1);

return [
    'name' => 'PostInput',
    'description' => 'A blog post with title and body',
    'fields' => [
        [
            'name' => 'title',
            'type' => 'String',
            'description' => 'Title',
            'defaultValue' => '',
        ],
        [
            'name' => 'body',
            'type' => 'String',
            'description' => 'The body',
            'defaultValue' => '',
        ],
        [
            'name' => 'status',
            'type' => 'PostStatus',
            'description' => 'Status',
            'defaultValue' => 'public',
        ],
        [
            'name' => 'user',
            'type' => 'UserID!',
            'description' => 'Author of post',
            'defaultValue' => null,
        ],
        [
            'name' => 'publicationDate',
            'type' => 'DateTime!',
            'description' => 'Date of publication',
            'defaultValue' => null,
        ],
        [
            'name' => 'creationDate',
            'type' => 'DateTime!',
            'description' => null,
            'defaultValue' => null,
        ],
    ],
];
