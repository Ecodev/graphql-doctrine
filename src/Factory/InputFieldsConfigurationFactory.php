<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Factory;

use GraphQL\Doctrine\Annotation\Input;
use GraphQL\Doctrine\DocBlockReader;
use GraphQL\Type\Definition\Type;
use ReflectionMethod;
use ReflectionParameter;

/**
 * A factory to create a configuration for all setters of an entity
 */
class InputFieldsConfigurationFactory extends AbstractFieldsConfigurationFactory
{
    protected function getMethodPattern(): string
    {
        return '~^set[A-Z]~';
    }

    /**
     * Get the entire configuration for a method
     *
     * @param ReflectionMethod $method
     *
     * @return null|array
     */
    protected function methodToConfiguration(ReflectionMethod $method): ?array
    {
        // Silently ignore setter with anything than exactly 1 parameter
        $params = $method->getParameters();
        if (count($params) !== 1) {
            return null;
        }
        $param = reset($params);

        // Get a field from annotation, or an empty one
        $field = $this->getAnnotationReader()->getMethodAnnotation($method, Input::class) ?? new Input();

        if (!$field->type instanceof Type) {
            $this->convertTypeDeclarationsToInstances($method, $field);
            $this->completeField($method, $param, $field);
        }

        return $field->toArray();
    }

    /**
     * All its types will be converted from string to real instance of Type
     *
     * @param ReflectionMethod $method
     * @param Input $field
     */
    private function convertTypeDeclarationsToInstances(ReflectionMethod $method, Input $field): void
    {
        $field->type = $this->getTypeFromPhpDeclaration($method, $field->type);
    }

    /**
     * Complete field with info from doc blocks and type hints
     *
     * @param ReflectionMethod $method
     * @param ReflectionParameter $param
     * @param Input $field
     */
    private function completeField(ReflectionMethod $method, ReflectionParameter $param, Input $field): void
    {
        $fieldName = lcfirst(preg_replace('~^set~', '', $method->getName()));
        if (!$field->name) {
            $field->name = $fieldName;
        }

        $docBlock = new DocBlockReader($method);
        if (!$field->description) {
            $field->description = $docBlock->getMethodDescription();
        }

        if (!isset($field->defaultValue) && $param->isDefaultValueAvailable()) {
            $field->defaultValue = $param->getDefaultValue();
        }

        if (!isset($field->defaultValue)) {
            $field->defaultValue = $this->getPropertyDefaultValue($fieldName);
        }

        // If still no type, look for docblock
        if (!$field->type) {
            $typeDeclaration = $docBlock->getParameterType($param);
            $this->throwIfArray($param, $typeDeclaration);
            $field->type = $this->getTypeFromPhpDeclaration($method, $typeDeclaration, true);
        }

        // If still no type, look for type hint
        $type = $param->getType();
        if (!$field->type && $type) {
            $this->throwIfArray($param, (string) $type);
            $field->type = $this->reflectionTypeToType($type, true);
        }

        $field->type = $this->nonNullIfHasDefault($field->type, $field->defaultValue);

        // If still no type, cannot continue
        $this->throwIfNotInputType($param, $field->type, 'Input');
    }
}
