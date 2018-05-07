<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Types;

use GraphQL\Type\Definition\EnumType;

final class CustomType extends EnumType
{
    public $name = 'customName';

    public function __construct()
    {
        $config = [
            'values' => [
                'foo',
                'bar',
            ],
        ];

        parent::__construct($config);
    }
}
