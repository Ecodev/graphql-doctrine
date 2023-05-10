<?php

declare(strict_types=1);

// all posts sorted by reversed title but with null title first
return [
    'SELECT post1, CASE WHEN post1.title = \'\' THEN 1 ELSE 0 END AS HIDDEN sorting1 FROM GraphQLTests\Doctrine\Blog\Model\Post post1 ORDER BY sorting1 DESC, post1.title DESC',
    \GraphQLTests\Doctrine\Blog\Model\Post::class,
    [],
    [
        [
            'field' => 'title',
            'order' => 'DESC',
            'emptyStringAsHighest' => true,
        ],
    ],
];
