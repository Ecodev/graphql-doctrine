<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Definition\Operator;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use GraphQL\Doctrine\Factory\UniqueNameFactory;
use GraphQL\Doctrine\Types;
use GraphQL\Type\Definition\LeafType;
use GraphQL\Type\Definition\Type;

final class EmptyOperatorType extends AbstractOperator
{
    protected function getConfiguration(Types $types, LeafType $leafType): array
    {
        return [
            'fields' => [
                [
                    'name' => 'not',
                    'type' => Type::boolean(),
                    'defaultValue' => false,
                ],
            ],
        ];
    }

    public function getDqlCondition(UniqueNameFactory $uniqueNameFactory, ClassMetadata $metadata, QueryBuilder $queryBuilder, string $alias, string $field, array $args): string
    {
        $not = $args['not'] ? 'NOT ' : '';

        return $alias . '.' . $field . ' IS ' . $not . 'EMPTY';
    }
}
