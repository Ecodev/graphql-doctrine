<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Definition;

use GraphQL\Type\Definition\EnumType;

/**
 * An enum for join types to be used in DQL
 */
final class SortingOrderType extends EnumType
{
    public function __construct()
    {
        $config = [
            'description' => 'Order to be used in DQL',
            'values' => [
                'ASC',
                'DESC',
            ],
        ];

        parent::__construct($config);
    }
}
