<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Definition\Operator;

class LessOperatorType extends AbstractSimpleOperator
{
    protected function getDqlOperator(bool $isNot): string
    {
        return $isNot ? '>=' : '<';
    }
}
