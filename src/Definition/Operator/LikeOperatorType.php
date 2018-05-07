<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Definition\Operator;

final class LikeOperatorType extends AbstractSimpleOperator
{
    protected function getDqlOperator(bool $isNot): string
    {
        return $isNot ? 'NOT LIKE' : 'LIKE';
    }
}
