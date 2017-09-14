<?php

declare(strict_types=1);

return [
    'name' => 'Post',
    'description' => 'A blog post with title and body',
    'fields' => [
        [
            'name' => 'title',
            'type' => 'String!',
            'description' => 'Title',
            'args' => [],
        ],
        [
            'name' => 'content',
            'type' => 'String!',
            'description' => 'The post content',
            'args' => [],
        ],
        [
            'name' => 'status',
            'type' => 'PostStatus!',
            'description' => 'Status',
            'args' => [],
        ],
        [
            'name' => 'user',
            'type' => 'User!',
            'description' => 'Author of post',
            'args' => [],
        ],
        [
            'name' => 'publicationDate',
            'type' => 'DateTime!',
            'description' => 'Date of publication',
            'args' => [],
        ],
        [
            'name' => 'words',
            'type' => '[String]!',
            'description' => null,
            'args' => [],
        ],
        [
            'name' => 'hasWords',
            'type' => 'Boolean!',
            'description' => null,
            'args' => [
                [
                    'name' => 'words',
                    'type' => '[String]!',
                    'description' => null,
                    'defaultValue' => null,
                ],
            ],
        ],
        [
            'name' => 'isLong',
            'type' => 'Boolean!',
            'description' => null,
            'args' => [
                [
                    'name' => 'wordLimit',
                    'type' => 'Int',
                    'description' => null,
                    'defaultValue' => 50,
                ],
            ],
        ],
        [
            'name' => 'isAllowedEditing',
            'type' => 'Boolean!',
            'description' => null,
            'args' => [
                [
                    'name' => 'user',
                    'type' => 'UserID!',
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
