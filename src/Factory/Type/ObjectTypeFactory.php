<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Factory\Type;

use GraphQL\Doctrine\Factory\OutputFieldsConfigurationFactory;
use GraphQL\Doctrine\Utils;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * A factory to create an ObjectType from a Doctrine entity
 */
final class ObjectTypeFactory extends AbstractTypeFactory
{
    /**
     * Create an ObjectType from a Doctrine entity
     *
     * @param string $className class name of Doctrine entity
     * @param string $typeName GraphQL type name
     *
     * @return ObjectType
     */
    public function create(string $className, string $typeName): Type
    {
        $typeName = Utils::getTypeName($className);
        $description = $this->getDescription($className);

        $fieldsGetter = function () use ($className): array {
            $factory = new OutputFieldsConfigurationFactory($this->types, $this->entityManager);
            $configuration = $factory->create($className);

            return $configuration;
        };

        return new ObjectType([
            'name' => $typeName,
            'description' => $description,
            'fields' => $fieldsGetter,
        ]);
    }
}
