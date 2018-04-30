<?php

declare(strict_types=1);

// all posts sorted by title
return [
    'SELECT post1 FROM GraphQLTests\Doctrine\Blog\Model\Post post1 ORDER BY post1.title ASC',
    \GraphQLTests\Doctrine\Blog\Model\Post::class,
    [],
    [
        [
            'field' => 'title',
            'order' => 'ASC',
        ],
    ],
];
