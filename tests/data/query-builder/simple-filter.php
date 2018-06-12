<?php

declare(strict_types=1);

// all posts whose title contains "foo"
return [
    'SELECT post1 FROM GraphQLTests\Doctrine\Blog\Model\Post post1 WHERE post1.title LIKE :filter1',
    \GraphQLTests\Doctrine\Blog\Model\Post::class,
    [
        'conditions' => [
            [
                'conditionLogic' => 'AND',
                'fieldsLogic' => 'AND',
                'fields' => [
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
        ],
    ],
    [],
];
