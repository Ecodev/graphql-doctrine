<?php

declare(strict_types=1);

// all posts whose title contains "foo" or "bar"
return [
    'SELECT post1 FROM GraphQLTests\Doctrine\Blog\Model\Post post1 WHERE post1.title LIKE :filter1 OR post1.title LIKE :filter2',
    \GraphQLTests\Doctrine\Blog\Model\Post::class,
    [
        'groups' => [
            [
                'groupLogic' => 'OR',
                'conditionsLogic' => 'AND',
                'conditions' => [
                    [
                        'title' => [
                            'like' => [
                                'value' => '%foo%',
                                'not' => false,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'groupLogic' => 'OR',
                'conditionsLogic' => 'AND',
                'conditions' => [
                    [
                        'title' => [
                            'like' => [
                                'value' => '%bar%',
                                'not' => false,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    [],
];
