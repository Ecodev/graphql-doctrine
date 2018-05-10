<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Definition\Operator;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use GraphQL\Doctrine\Factory\UniqueNameFactory;
use GraphQL\Doctrine\Types;
use GraphQL\Type\Definition\LeafType;
use GraphQL\Type\Definition\Type;

final class ContainOperatorType extends AbstractOperator
{
    protected function getConfiguration(Types $types, LeafType $leafType): array
    {
        return [
            'fields' => [
                [
                    'name' => 'values',
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(Type::id()))),
                ],
                [
                    'name' => 'not',
                    'type' => Type::boolean(),
                    'defaultValue' => false,
                ],
            ],
        ];
    }

    public function getDqlCondition(UniqueNameFactory $uniqueNameFactory, ClassMetadata $metadata, QueryBuilder $queryBuilder, string $alias, string $field, ?array $args): ?string
    {
        if ($args === null) {
            return null;
        }

        $values = $uniqueNameFactory->createParameterName();
        $queryBuilder->setParameter($values, $args['values']);
        $not = $args['not'] ? 'NOT ' : '';

        return ':' . $values . ' ' . $not . 'MEMBER OF ' . $alias . '.' . $field;
    }
}
