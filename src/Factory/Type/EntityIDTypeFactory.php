<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Factory\Type;

use GraphQL\Doctrine\Definition\EntityIDType;
use GraphQL\Type\Definition\Type;

/**
 * A factory to create an EntityIDType from a Doctrine entity.
 */
final class EntityIDTypeFactory extends AbstractTypeFactory
{
    /**
     * Create an EntityIDType from a Doctrine entity
     *
     * @param string $className class name of Doctrine entity
     * @param string $typeName GraphQL type name
     *
     * @return EntityIDType
     */
    public function create(string $className, string $typeName): Type
    {
        return new EntityIDType($this->entityManager, $className, $typeName);
    }
}
