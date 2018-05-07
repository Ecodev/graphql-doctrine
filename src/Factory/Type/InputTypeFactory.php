<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Factory\Type;

use GraphQL\Doctrine\Factory\InputFieldsConfigurationFactory;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

/**
 * A factory to create an InputObjectType from a Doctrine entity
 */
final class InputTypeFactory extends AbstractTypeFactory
{
    /**
     * Create an InputObjectType from a Doctrine entity
     *
     * @param string $className class name of Doctrine entity
     * @param string $typeName GraphQL type name
     *
     * @return InputObjectType
     */
    public function create(string $className, string $typeName): Type
    {
        $description = $this->getDescription($className);

        $fieldsGetter = function () use ($className): array {
            $factory = new InputFieldsConfigurationFactory($this->types, $this->entityManager);
            $configuration = $factory->create($className);

            return $configuration;
        };

        return new InputObjectType([
            'name' => $typeName,
            'description' => $description,
            'fields' => $fieldsGetter,
        ]);
    }
}
