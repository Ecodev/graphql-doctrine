<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Types;

use GraphQL\Type\Definition\EnumType;
use GraphQLTests\Doctrine\Blog\Model\Post;

final class PostStatusType extends EnumType
{
    public function __construct()
    {
        $config = [
            'values' => [
                Post::STATUS_PRIVATE,
                Post::STATUS_PUBLIC,
            ],
        ];

        parent::__construct($config);
    }
}
