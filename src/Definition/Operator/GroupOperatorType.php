<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Definition\Operator;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use GraphQL\Doctrine\Factory\UniqueNameFactory;
use GraphQL\Type\Definition\LeafType;

final class GroupOperatorType extends AbstractOperator
{
    protected function getConfiguration(LeafType $leafType): array
    {
        $description = <<<STRING
Will apply a `GROUP BY` on the field to select unique values existing in database.

This is typically useful to present a list of suggestions to the end-user, while still allowing him to enter arbitrary values.
STRING;

        return [
            'description' => $description,
            'fields' => [
                [
                    'name' => 'value',
                    'type' => self::boolean(),
                    'defaultValue' => null,
                    'description' => 'This field is never used and can be ignored',
                ],
            ],
        ];
    }

    public function getDqlCondition(UniqueNameFactory $uniqueNameFactory, ClassMetadata $metadata, QueryBuilder $queryBuilder, string $alias, string $field, ?array $args): ?string
    {
        $queryBuilder->addGroupBy($alias . '.' . $field);

        return null;
    }
}
