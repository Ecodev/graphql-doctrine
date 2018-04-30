<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Filtering;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use GraphQL\Doctrine\Definition\Operator\AbstractOperator;
use GraphQL\Doctrine\Factory\UniqueNameFactory;
use GraphQL\Doctrine\Types;
use GraphQL\Type\Definition\LeafType;
use GraphQL\Type\Definition\Type;

class Search extends AbstractOperator
{
    protected function getConfiguration(Types $types, LeafType $leafType): array
    {
        return [
            'fields' => [
                [
                    'name' => 'term',
                    'type' => Type::nonNull($leafType),
                ],
            ],
        ];
    }

    public function getDqlCondition(UniqueNameFactory $uniqueNameFactory, ClassMetadata $metadata, QueryBuilder $queryBuilder, string $alias, string $field, array $args): string
    {
        $search = $args['term'];

        $fields = [];

        // Find all textual fields for the entity
        $textType = ['string', 'text'];
        foreach ($metadata->fieldMappings as $g) {
            if (in_array($g['type'], $textType, true)) {
                $fields[] = $alias . '.' . $g['name'];
            }
        }

        // Build the WHERE clause
        $wordWheres = [];
        foreach (preg_split('/[[:space:]]+/', $search, -1, PREG_SPLIT_NO_EMPTY) as $i => $word) {
            $parameterName = $uniqueNameFactory->createParameterName();

            $fieldWheres = [];
            foreach ($fields as $field) {
                $fieldWheres[] = $field . ' LIKE :' . $parameterName;
            }

            if ($fieldWheres) {
                $wordWheres[] = '(' . implode(' OR ', $fieldWheres) . ')';
                $queryBuilder->setParameter($parameterName, '%' . $word . '%');
            }
        }

        return '(' . implode(' AND ', $wordWheres) . ')';
    }
}
