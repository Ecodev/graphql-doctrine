<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Definition;

use GraphQL\Type\Definition\EnumType;

/**
 * An enum for join types to be used in DQL
 */
final class JoinTypeType extends EnumType
{
    public function __construct()
    {
        $config = [
            'description' => 'Join types to be used in DQL',
            'values' => [
                'innerJoin',
                'leftJoin',
            ],
        ];

        parent::__construct($config);
    }
}
