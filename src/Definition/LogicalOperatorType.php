<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Definition;

use GraphQL\Type\Definition\EnumType;

/**
 * An enum for logical operator to be used in DQL
 */
final class LogicalOperatorType extends EnumType
{
    public function __construct()
    {
        $config = [
            'description' => 'Logical operator to be used in conditions',
            'values' => [
                'AND',
                'OR',
            ],
        ];

        parent::__construct($config);
    }
}
