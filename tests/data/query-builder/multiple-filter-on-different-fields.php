<?php

declare(strict_types=1);

// all posts whose title contains "foo" or body contains "bar"
return [
    'SELECT post1 FROM GraphQLTests\Doctrine\Blog\Model\Post post1 WHERE post1.title LIKE :filter1 OR post1.body LIKE :filter2',
    \GraphQLTests\Doctrine\Blog\Model\Post::class,
    [
        'groups' => [
            [
                'groupLogic' => 'AND',
                'conditionsLogic' => 'OR',
                'conditions' => [
                    [
                        'title' => [
                            'like' => [
                                'value' => '%foo%',
                                'not' => false,
                            ],
                        ],
                        'body' => [
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
