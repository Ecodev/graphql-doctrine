<?php

declare(strict_types=1);

// computed sorting inherited from parent class
return [
    'SELECT post1, MOD(post1.id, 5) AS HIDDEN score FROM GraphQLTests\Doctrine\Blog\Model\Post post1 ORDER BY score ASC',
    \GraphQLTests\Doctrine\Blog\Model\Post::class,
    [],
    [
        [
            'field' => 'pseudoRandom',
            'order' => 'ASC',
        ],
    ],
];
