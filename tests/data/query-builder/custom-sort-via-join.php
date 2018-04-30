<?php

declare(strict_types=1);

// custom sorting that adds a JOIN
return [
    'SELECT post1 FROM GraphQLTests\Doctrine\Blog\Model\Post post1 INNER JOIN post1.user sortingUser ORDER BY sortingUser.name DESC',
    \GraphQLTests\Doctrine\Blog\Model\Post::class,
    [],
    [
        [
            'field' => 'userName',
            'order' => 'DESC',
        ],
    ],
];
