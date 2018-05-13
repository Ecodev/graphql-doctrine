<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Definition\Operator;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use GraphQL\Doctrine\Factory\UniqueNameFactory;
use GraphQL\Type\Definition\LeafType;

final class HaveOperatorType extends AbstractAssociationOperatorType
{
    protected function getConfiguration(LeafType $leafType): array
    {
        return [
            'description' => 'When used on single valued association, it will use `IN` operator. On collection valued association it will use `MEMBER OF` operator.',
            'fields' => [
                [
                    'name' => 'values',
                    'type' => self::nonNull(self::listOf(self::nonNull(self::id()))),
                ],
                [
                    'name' => 'not',
                    'type' => self::boolean(),
                    'defaultValue' => false,
                ],
            ],
        ];
    }

    protected function getSingleValuedDqlCondition(UniqueNameFactory $uniqueNameFactory, ClassMetadata $metadata, QueryBuilder $queryBuilder, string $alias, string $field, array $args): ?string
    {
        $in = $this->types->getOperator(InOperatorType::class, self::id());

        return $in->getDqlCondition($uniqueNameFactory, $metadata, $queryBuilder, $alias, $field, $args);
    }

    protected function getCollectionValuedDqlCondition(UniqueNameFactory $uniqueNameFactory, ClassMetadata $metadata, QueryBuilder $queryBuilder, string $alias, string $field, array $args): ?string
    {
        $values = $uniqueNameFactory->createParameterName();
        $queryBuilder->setParameter($values, $args['values']);
        $not = $args['not'] ? 'NOT ' : '';

        return ':' . $values . ' ' . $not . 'MEMBER OF ' . $alias . '.' . $field;
    }
}
