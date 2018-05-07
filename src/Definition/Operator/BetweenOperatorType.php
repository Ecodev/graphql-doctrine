<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Definition\Operator;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use GraphQL\Doctrine\Factory\UniqueNameFactory;
use GraphQL\Doctrine\Types;
use GraphQL\Type\Definition\LeafType;
use GraphQL\Type\Definition\Type;

final class BetweenOperatorType extends AbstractOperator
{
    protected function getConfiguration(Types $types, LeafType $leafType): array
    {
        return [
            'fields' => [
                [
                    'name' => 'from',
                    'type' => Type::nonNull($leafType),
                ],
                [
                    'name' => 'to',
                    'type' => Type::nonNull($leafType),
                ],
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
        $from = $uniqueNameFactory->createParameterName();
        $to = $uniqueNameFactory->createParameterName();
        $queryBuilder->setParameter($from, $args['from']);
        $queryBuilder->setParameter($to, $args['to']);
        $not = $args['not'] ? 'NOT ' : '';

        return $alias . '.' . $field . ' ' . $not . 'BETWEEN :' . $from . ' AND ' . ':' . $to;
    }
}
