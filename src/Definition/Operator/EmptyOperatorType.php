<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Definition\Operator;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use GraphQL\Doctrine\Factory\UniqueNameFactory;
use GraphQL\Type\Definition\LeafType;

final class EmptyOperatorType extends AbstractAssociationOperatorType
{
    protected function getConfiguration(LeafType $leafType): array
    {
        return [
            'description' => 'When used on single valued association, it will use `IS NULL` operator. On collection valued association it will use `IS EMPTY` operator.',
            'fields' => [
                [
                    'name' => 'not',
                    'type' => self::boolean(),
                    'defaultValue' => false,
                ],
            ],
        ];
    }

    protected function getSingleValuedDqlCondition(UniqueNameFactory $uniqueNameFactory, ClassMetadata $metadata, QueryBuilder $queryBuilder, string $alias, string $field, array $args): string
    {
        $null = $this->types->getOperator(NullOperatorType::class, self::id());

        return $null->getDqlCondition($uniqueNameFactory, $metadata, $queryBuilder, $alias, $field, $args);
    }

    protected function getCollectionValuedDqlCondition(UniqueNameFactory $uniqueNameFactory, ClassMetadata $metadata, QueryBuilder $queryBuilder, string $alias, string $field, array $args): string
    {
        $not = $args['not'] ? 'NOT ' : '';

        return $alias . '.' . $field . ' IS ' . $not . 'EMPTY';
    }
}
