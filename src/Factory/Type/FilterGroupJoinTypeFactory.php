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
     * @param string $className class name of Doctrine entity
     * @param string $typeName GraphQL type name
     *
     * @return InputObjectType
     */
    public function create(string $className, string $typeName): Type
    {
        $type = new InputObjectType([
            'name' => $typeName,
            'description' => 'Type to specify join tables in a filter',
            'fields' => function () use ($className): array {
                return $this->getJoinsFields($className);
            },
        ]);

        return $type;
    }

    /**
     * Get the field for joins
     *
     * @param string $className
     *
     * @return array
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
     * Get the all the possible relations to be joined
     *
     * @param string $className
     *
     * @return array
     */
    private function getJoinsFields(string $className): array
    {
        $fields = [];
        $associations = $this->entityManager->getClassMetadata($className)->associationMappings;
        foreach ($associations as $association) {
            $field = [
                'name' => $association['fieldName'],
                'type' => $this->types->getJoinOn($association['targetEntity']),
            ];

            $fields[] = $field;
        }

        return $fields;
    }
}
