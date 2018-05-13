<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Definition\Operator;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use GraphQL\Doctrine\Factory\UniqueNameFactory;

abstract class AbstractAssociationOperatorType extends AbstractOperator
{
    final public function getDqlCondition(UniqueNameFactory $uniqueNameFactory, ClassMetadata $metadata, QueryBuilder $queryBuilder, string $alias, string $field, ?array $args): ?string
    {
        if ($args === null) {
            return null;
        }

        if ($metadata->isSingleValuedAssociation($field)) {
            return $this->getSingleValuedDqlCondition($uniqueNameFactory, $metadata, $queryBuilder, $alias, $field, $args);
        }

        return $this->getCollectionValuedDqlCondition($uniqueNameFactory, $metadata, $queryBuilder, $alias, $field, $args);
    }

    abstract protected function getSingleValuedDqlCondition(UniqueNameFactory $uniqueNameFactory, ClassMetadata $metadata, QueryBuilder $queryBuilder, string $alias, string $field, array $args): ?string;

    abstract protected function getCollectionValuedDqlCondition(UniqueNameFactory $uniqueNameFactory, ClassMetadata $metadata, QueryBuilder $queryBuilder, string $alias, string $field, array $args): ?string;
}
