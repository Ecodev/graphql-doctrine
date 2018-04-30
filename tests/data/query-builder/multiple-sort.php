<?php

declare(strict_types=1);

// all posts sorted by title then by reverse body then by reverse id
return [
    'SELECT post1 FROM GraphQLTests\Doctrine\Blog\Model\Post post1 ORDER BY post1.title ASC, post1.body DESC, post1.id DESC',
    \GraphQLTests\Doctrine\Blog\Model\Post::class,
    [],
    [
        [
            'field' => 'title',
            'order' => 'ASC',
        ],
        [
            'field' => 'body',
            'order' => 'DESC',
        ],
        [
            'field' => 'id',
            'order' => 'DESC',
        ],
    ],
];
