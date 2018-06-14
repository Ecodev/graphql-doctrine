<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Definition\Operator;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use GraphQL\Doctrine\Factory\UniqueNameFactory;
use GraphQL\Type\Definition\LeafType;

/**
 * A simple operator with two operands
 */
abstract class AbstractSimpleOperator extends AbstractOperator
{
    abstract protected function getDqlOperator(bool $isNot): string;

    final protected function getConfiguration(LeafType $leafType): array
    {
        return [
            'fields' => [
                [
                    'name' => 'value',
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

    final public function getDqlCondition(UniqueNameFactory $uniqueNameFactory, ClassMetadata $metadata, QueryBuilder $queryBuilder, string $alias, string $field, ?array $args): ?string
    {
        if ($args === null) {
            return null;
        }

        $param = $uniqueNameFactory->createParameterName();
        $queryBuilder->setParameter($param, $args['value']);

        return $alias . '.' . $field . ' ' . $this->getDqlOperator($args['not']) . ' :' . $param;
    }
}
