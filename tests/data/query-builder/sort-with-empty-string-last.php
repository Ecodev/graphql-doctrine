<?php

declare(strict_types=1);

// all posts sorted by title but with null title last
return [
    'SELECT post1, CASE WHEN post1.title = \'\' THEN 1 ELSE 0 END AS HIDDEN sorting1 FROM GraphQLTests\Doctrine\Blog\Model\Post post1 ORDER BY sorting1 ASC, post1.title ASC',
    GraphQLTests\Doctrine\Blog\Model\Post::class,
    [],
    [
        [
            'field' => 'title',
            'emptyStringAsHighest' => true,
            'order' => 'ASC',
        ],
    ],
];
