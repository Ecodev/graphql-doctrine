<?php

declare(strict_types=1);

return [
    'name' => 'User',
    'description' => 'A blog author or visitor',
    'fields' => [
        [
            'name' => 'name',
            'type' => 'String!',
            'description' => 'The user real name',
            'args' => [],
        ],
        [
            'name' => 'email',
            'type' => 'String',
            'description' => 'The validated email or null',
            'args' => [],
        ],
        [
            'name' => 'isAdministrator',
            'type' => 'Boolean!',
            'description' => 'Whether the user is an administrator',
            'args' => [],
        ],
        [
            'name' => 'posts',
            'type' => '[Post]!',
            'description' => 'All posts of the specified status',
            'args' => [
                [
                    'name' => 'status',
                    'type' => 'PostStatus',
                    'description' => 'The status of posts as defined in \GraphQLTests\Doctrine\Blog\Model\Post',
                    'defaultValue' => 'public',
                ],
            ],
        ],
        [
            'name' => 'postsWithIds',
            'type' => '[Post]!',
            'description' => null,
            'args' => [
                [
                    'name' => 'ids',
                    'type' => '[ID]!',
                    'description' => null,
                    'defaultValue' => null,
                ],
            ],
        ],
        [
            'name' => 'manager',
            'type' => 'User',
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
