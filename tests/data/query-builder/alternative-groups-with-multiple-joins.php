<?php

declare(strict_types=1);

// posts whose author contains John and Jane, OR posts whose author contains Jake and post title contains foo
return [
    'SELECT post1, user1 FROM GraphQLTests\Doctrine\Blog\Model\Post post1 INNER JOIN post1.user user1 WHERE (user1.name LIKE :filter1 AND user1.name LIKE :filter2) OR (user1.name LIKE :filter3 AND post1.title LIKE :filter4)',
    \GraphQLTests\Doctrine\Blog\Model\Post::class,
    [
        'groups' => [
            [
                'groupLogic' => 'OR',
                'conditionsLogic' => 'AND',
                'joins' => [
                    'user' => [
                        'type' => 'innerJoin',
                        'conditions' => [
                            [
                                'name' => [
                                    'like' => [
                                        'value' => '%John%',
                                        'not' => false,
                                    ],
                                ],
                            ],
                            [
                                'name' => [
                                    'like' => [
                                        'value' => '%Jane%',
                                        'not' => false,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'groupLogic' => 'OR',
                'conditionsLogic' => 'AND',
                'joins' => [
                    'user' => [
                        'type' => 'innerJoin',
                        'conditions' => [
                            [
                                'name' => [
                                    'like' => [
                                        'value' => '%Jake%',
                                        'not' => false,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
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
        ],
    ],
    [],
];
