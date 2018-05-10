<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Definition\Operator;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use GraphQL\Doctrine\Factory\UniqueNameFactory;
use GraphQL\Doctrine\Types;
use GraphQL\Type\Definition\LeafType;
use GraphQL\Type\Definition\Type;

/**
 * A simple operator with two operands
 */
abstract class AbstractSimpleOperator extends AbstractOperator
{
    abstract protected function getDqlOperator(bool $isNot): string;

    final protected function getConfiguration(Types $types, LeafType $leafType): array
    {
        return [
            'fields' => [
                [
                    'name' => 'value',
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
