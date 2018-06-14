<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Definition\Operator;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use GraphQL\Doctrine\Factory\UniqueNameFactory;
use GraphQL\Type\Definition\LeafType;

final class InOperatorType extends AbstractOperator
{
    protected function getConfiguration(LeafType $leafType): array
    {
        return [
            'fields' => [
                [
                    'name' => 'values',
                    'type' => self::nonNull(self::listOf(self::nonNull($leafType))),
                ],
                [
                    'name' => 'not',
                    'type' => self::boolean(),
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

        return $alias . '.' . $field . ' ' . $not . 'IN (:' . $values . ')';
    }
}
