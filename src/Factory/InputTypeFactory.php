<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Factory;

use GraphQL\Doctrine\Utils;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

/**
 * A factory to create an InputObjectType from a Doctrine entity
 */
class InputTypeFactory extends AbstractTypeFactory
{
    /**
     * Create an InputObjectType from a Doctrine entity
     *
     * @param string $className class name of Doctrine entity
     *
     * @return InputObjectType
     */
    public function create(string $className): Type
    {
        $typeName = Utils::getInputTypeName($className);
        $description = $this->getDescription($className);

        $fieldGetter = function () use ($className): array {
            $factory = new InputFieldsConfigurationFactory($this->types, $this->entityManager);
            $configuration = $factory->create($className);

            return $configuration;
        };

        return new InputObjectType([
            'name' => $typeName,
            'description' => $description,
            'fields' => $fieldGetter,
        ]);
    }
}
