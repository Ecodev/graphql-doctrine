<?php

declare(strict_types=1);

// this is nonsensical, but shows that we can build a query with the same table appearing more than once
return [
    'SELECT user1, posts1, user2 FROM GraphQLTests\Doctrine\Blog\Model\User user1 INNER JOIN user1.posts posts1 INNER JOIN posts1.user user2',
    \GraphQLTests\Doctrine\Blog\Model\User::class,
    [
        'groups' => [
            [
                'groupLogic' => 'OR',
                'conditionsLogic' => 'AND',
                'joins' => [
                    'posts' => [
                        'type' => 'innerJoin',
                        'joins' => [
                            'user' => [
                                'type' => 'innerJoin',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    [],
];
