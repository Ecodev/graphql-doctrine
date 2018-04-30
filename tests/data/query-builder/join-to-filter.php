<?php

declare(strict_types=1);

// all posts whose author is somebody called "john"
return [
    'SELECT post1, user1 FROM GraphQLTests\Doctrine\Blog\Model\Post post1 INNER JOIN post1.user user1 WHERE user1.name LIKE :filter1',
    \GraphQLTests\Doctrine\Blog\Model\Post::class,
    [
        'joins' => [
            'user' => [
                'type' => 'innerJoin',
                'filter' => [
                    'conditions' => [
                        [
                            'conditionLogic' => 'AND',
                            'fieldsLogic' => 'AND',
                            'fields' => [
                                'name' => [
                                    'like' => [
                                        'value' => '%john%',
                                        'not' => false,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    [],
];
