<?php

declare(strict_types=1);

// all posts whose author has written at least one post whose title contains "foo"
return [
    'SELECT post1, user1, posts1 FROM GraphQLTests\Doctrine\Blog\Model\Post post1 INNER JOIN post1.user user1 INNER JOIN user1.posts posts1 WHERE posts1.title LIKE :filter1',
    \GraphQLTests\Doctrine\Blog\Model\Post::class,
    [
        'joins' => [
            'user' => [
                'type' => 'innerJoin',
                'filter' => [
                    'joins' => [
                        'posts' => [
                            'type' => 'innerJoin',
                            'filter' => [
                                'conditions' => [
                                    [
                                        'conditionLogic' => 'AND',
                                        'fieldsLogic' => 'AND',
                                        'fields' => [
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
                    ],
                ],
            ],
        ],
    ],
    [],
];
