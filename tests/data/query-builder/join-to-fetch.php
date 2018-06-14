<?php

declare(strict_types=1);

// all posts, but also fetch their author, if any, in a single SQL query
return [
    'SELECT post1, user1 FROM GraphQLTests\Doctrine\Blog\Model\Post post1 LEFT JOIN post1.user user1',
    \GraphQLTests\Doctrine\Blog\Model\Post::class,
    [
        'groups' => [
            [
                'joins' => [
                    'user' => [
                        'type' => 'leftJoin',
                    ],
                ],
            ],
        ],
    ],
    [],
];
