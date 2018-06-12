<?php

declare(strict_types=1);

// all posts whose title contains "foo"
return [
    'SELECT post1 FROM GraphQLTests\Doctrine\Blog\Model\Post post1 WHERE MOD(post1.id, :filter1) = 0',
    \GraphQLTests\Doctrine\Blog\Model\Post::class,
    [
        'conditions' => [
            [
                'conditionLogic' => 'AND',
                'fieldsLogic' => 'AND',
                'fields' => [
                    [
                        'id' => [
                            'modulo' => [
                                'value' => 2,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    [],
];
