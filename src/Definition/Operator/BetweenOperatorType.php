<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Definition\Operator;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use GraphQL\Doctrine\Factory\UniqueNameFactory;
use GraphQL\Type\Definition\LeafType;

final class BetweenOperatorType extends AbstractOperator
{
    protected function getConfiguration(LeafType $leafType): array
    {
        return [
            'fields' => [
                [
                    'name' => 'from',
                    'type' => self::nonNull($leafType),
                ],
                [
                    'name' => 'to',
                    'type' => self::nonNull($leafType),
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

        $from = $uniqueNameFactory->createParameterName();
        $to = $uniqueNameFactory->createParameterName();
        $queryBuilder->setParameter($from, $args['from']);
        $queryBuilder->setParameter($to, $args['to']);
        $not = $args['not'] ? 'NOT ' : '';

        return $alias . '.' . $field . ' ' . $not . 'BETWEEN :' . $from . ' AND ' . ':' . $to;
    }
}
