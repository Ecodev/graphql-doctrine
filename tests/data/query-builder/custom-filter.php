<?php

declare(strict_types=1);

// all posts whose title contains "foo"
return [
    'SELECT post1 FROM GraphQLTests\Doctrine\Blog\Model\Post post1 WHERE ((post1.title LIKE :filter1 OR post1.body LIKE :filter1 OR post1.status LIKE :filter1) AND (post1.title LIKE :filter2 OR post1.body LIKE :filter2 OR post1.status LIKE :filter2))',
    \GraphQLTests\Doctrine\Blog\Model\Post::class,
    [
        'groups' => [
            [
                'groupLogic' => 'AND',
                'conditionsLogic' => 'AND',
                'conditions' => [
                    [
                        'custom' => [
                            'search' => [
                                'term' => 'foo bar',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    [],
];
