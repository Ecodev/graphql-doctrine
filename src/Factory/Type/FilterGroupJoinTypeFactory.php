<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Factory\Type;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

/**
 * A factory to create an InputObjectType from a Doctrine entity to
 * specify joins.
 */
final class FilterGroupJoinTypeFactory extends AbstractTypeFactory
{
    /**
     * Create an InputObjectType from a Doctrine entity to
     * specify joins.
     *
     * @param class-string $className class name of Doctrine entity
     * @param string $typeName GraphQL type name
     */
    public function create(string $className, string $typeName): InputObjectType
    {
        $type = new InputObjectType([
            'name' => $typeName,
            'description' => 'Type to specify join tables in a filter',
            'fields' => fn (): array => $this->getJoinsFields($className),
        ]);

        return $type;
    }

    /**
     * Get the field for joins.
     *
     * @param class-string $className
     */
    public function getField(string $className): array
    {
        $joinsType = $this->types->getFilterGroupJoin($className);

        $joinsField = [
            'name' => 'joins',
            'description' => 'Optional joins to either filter the query or fetch related objects from DB in a single query',
            'type' => $joinsType,
        ];

        return $joinsField;
    }

    /**
     * Get the all the possible relations to be joined.
     *
     * @param class-string $className
     */
    private function getJoinsFields(string $className): array
    {
        $fields = [];
        $associations = $this->entityManager->getClassMetadata($className)->associationMappings;
        foreach ($associations as $association) {
            $field = [
                'name' => $association->fieldName,
                'type' => $this->types->getJoinOn($association->targetEntity),
            ];

            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * Return whether it is possible to create a valid type for join.
     *
     * @param class-string $className
     */
    public function canCreate(string $className): bool
    {
        return !empty($this->entityManager->getClassMetadata($className)->associationMappings);
    }
}
