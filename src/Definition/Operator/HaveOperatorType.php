<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Definition\Operator;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\OneToManyAssociationMapping;
use Doctrine\ORM\QueryBuilder;
use GraphQL\Doctrine\Factory\UniqueNameFactory;
use GraphQL\Type\Definition\LeafType;

final class HaveOperatorType extends AbstractAssociationOperatorType
{
    protected function getConfiguration(LeafType $leafType): array
    {
        return [
            'description' => 'When used on single valued association, it will use `IN` operator. On collection valued association it will use `MEMBER OF` operator.',
            'fields' => [
                [
                    'name' => 'values',
                    'type' => self::nonNull(self::listOf(self::nonNull(self::id()))),
                ],
                [
                    'name' => 'not',
                    'type' => self::boolean(),
                    'defaultValue' => false,
                ],
            ],
        ];
    }

    protected function getSingleValuedDqlCondition(UniqueNameFactory $uniqueNameFactory, ClassMetadata $metadata, QueryBuilder $queryBuilder, string $alias, string $field, array $args): ?string
    {
        $in = $this->types->getOperator(InOperatorType::class, self::id());

        return $in->getDqlCondition($uniqueNameFactory, $metadata, $queryBuilder, $alias, $field, $args);
    }

    protected function getCollectionValuedDqlCondition(UniqueNameFactory $uniqueNameFactory, ClassMetadata $metadata, QueryBuilder $queryBuilder, string $alias, string $field, array $args): string
    {
        $association = $metadata->associationMappings[$field];
        $values = $uniqueNameFactory->createParameterName();
        $queryBuilder->setParameter($values, $args['values']);
        $not = $args['not'] ? 'NOT ' : '';

        // For one-to-many we cannot rely on MEMBER OF, because it does not support multiple values (in SQL it always
        // use `=`, and not `IN()`). So we simulate an approximation of MEMBER OF that support multiple values. But it
        // does **not** support composite identifiers. And that is fine because it is an official limitation of this
        // library anyway.
        if ($association instanceof OneToManyAssociationMapping) {
            $id = $metadata->identifier[0];

            $otherClassName = $association->targetEntity;
            $otherAlias = $uniqueNameFactory->createAliasName($otherClassName);
            $otherField = $association->mappedBy;
            $otherMetadata = $queryBuilder->getEntityManager()->getClassMetadata($otherClassName);
            $otherId = $otherMetadata->identifier[0];

            $result = $not . "EXISTS (SELECT 1 FROM $otherClassName $otherAlias WHERE $otherAlias.$otherField = $alias.$id AND $otherAlias.$otherId IN (:$values))";

            return $result;
        }

        return ':' . $values . ' ' . $not . 'MEMBER OF ' . $alias . '.' . $field;
    }
}
