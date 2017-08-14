<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Factory;

use GraphQL\Doctrine\Annotation\Input;
use GraphQL\Doctrine\DocBlockReader;
use GraphQL\Doctrine\Types;
use GraphQL\Type\Definition\Type;
use ReflectionMethod;

/**
 * A factory to create a configuration for all fields of an entity
 */
class InputFieldsConfigurationFactory extends AbstractFieldsConfigurationFactory
{
    protected function getMethodPattern(): string
    {
        return '~^set[A-Z]~';
    }

    /**
     * Get the entire configuration for a method
     * @param ReflectionMethod $method
     * @return array
     */
    protected function methodToConfiguration(ReflectionMethod $method): ?array
    {
        // Silently ignore setter with anything than exactly 1 parameter
        $params = $method->getParameters();
        if (count($params) !== 1) {
            return null;
        }
        $param = reset($params);

        // First get user specified values
        $field = $this->getInputFieldFromAnnotation($method);

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
            $field->type = $this->refelectionTypeToType($type, true);
        }

        $field->type = $this->nonNullIfHasDefault($param, $field->type);

        // If still no type, cannot continue
        $this->throwIfNotInputType($param, $field->type, 'Input');

        return $field->toArray();
    }

    /**
     * Get a field from annotation, or an empty one
     * All its types will be converted from string to real instance of Type
     *
     * @param ReflectionMethod $method
     * @return Input
     */
    private function getInputFieldFromAnnotation(ReflectionMethod $method): Input
    {
        $field = $this->getAnnotationReader()->getMethodAnnotation($method, Input::class) ?? new Input();
        $field->type = $this->getTypeFromPhpDeclaration($method, $field->type);

        return $field;
    }
}
