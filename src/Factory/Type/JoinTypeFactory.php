<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Factory\Type;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

/**
 * A factory to create an InputObjectType from a Doctrine entity to
 * sort them by their fields and custom sorter.
 */
class JoinTypeFactory extends AbstractTypeFactory
{
    /**
     * Create an InputObjectType from a Doctrine entity to
     * sort them by their fields and custom sorter.
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
            'fields' => function () use ($className) {
                return [
                    [
                        'name' => 'type',
                        'type' => $this->types->get('JoinType'),
                        'defaultValue' => 'innerJoin',
                    ],
                    [
                        'name' => 'filter',
                        'type' => $this->types->getFilter($className),
                    ],
                ];
            },
        ]);

        return $type;
    }
}
