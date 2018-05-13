<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Filtering;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use GraphQL\Doctrine\Definition\Operator\AbstractOperator;
use GraphQL\Doctrine\Factory\UniqueNameFactory;
use GraphQL\Type\Definition\LeafType;
use GraphQL\Type\Definition\Type;

final class SearchOperatorType extends AbstractOperator
{
    protected function getConfiguration(LeafType $leafType): array
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

    public function getDqlCondition(UniqueNameFactory $uniqueNameFactory, ClassMetadata $metadata, QueryBuilder $queryBuilder, string $alias, string $field, ?array $args): ?string
    {
        $words = preg_split('/[[:space:]]+/', $args['term'], -1, PREG_SPLIT_NO_EMPTY);
        if (!$words) {
            return null;
        }

        $fields = $this->getSearchableFields($metadata, $alias);

        // Build the WHERE clause
        $wordWheres = [];
        foreach ($words as $word) {
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

    /**
     * Find all textual fields for the entity
     *
     * @param ClassMetadata $metadata
     * @param string $alias
     *
     * @return array
     */
    private function getSearchableFields(ClassMetadata $metadata, string $alias): array
    {
        $fields = [];
        $textType = ['string', 'text'];
        foreach ($metadata->fieldMappings as $g) {
            if (in_array($g['type'], $textType, true)) {
                $fields[] = $alias . '.' . $g['fieldName'];
            }
        }

        return $fields;
    }
}
