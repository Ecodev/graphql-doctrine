<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Factory;

use GraphQL\Doctrine\Annotation\Argument;
use GraphQL\Doctrine\Annotation\Field;
use GraphQL\Doctrine\DocBlockReader;
use GraphQL\Doctrine\Exception;
use GraphQL\Type\Definition\Type;
use ReflectionMethod;
use ReflectionParameter;

/**
 * A factory to create a configuration for all getters of an entity
 */
class OutputFieldsConfigurationFactory extends AbstractFieldsConfigurationFactory
{
    protected function getMethodPattern(): string
    {
        return '~^(get|is|has)[A-Z]~';
    }

    /**
     * Get the entire configuration for a method
     * @param ReflectionMethod $method
     * @return array
     */
    protected function methodToConfiguration(ReflectionMethod $method): ?array
    {
        // Get a field from annotation, or an empty one
        $field = $this->getAnnotationReader()->getMethodAnnotation($method, Field::class) ?? new Field();

        if (!$field->type instanceof Type) {
            $this->convertTypeDeclarationsToInstances($method, $field);
            $this->completeField($method, $field);
        }

        return $field->toArray();
    }

    /**
     * All its types will be converted from string to real instance of Type
     *
     * @param ReflectionMethod $method
     * @param Field $field
     */
    private function convertTypeDeclarationsToInstances(ReflectionMethod $method, Field $field): void
    {
        $field->type = $this->getTypeFromPhpDeclaration($method, $field->type);
        $args = [];
        foreach ($field->args as $arg) {
            $arg->type = $this->getTypeFromPhpDeclaration($method, $arg->type);
            $args[$arg->name] = $arg;
        }
        $field->args = $args;
    }

    /**
     * Complete field with info from doc blocks and type hints
     * @param ReflectionMethod $method
     * @param Field $field
     * @throws Exception
     */
    private function completeField(ReflectionMethod $method, Field $field): void
    {
        $fieldName = lcfirst(preg_replace('~^get~', '', $method->getName()));
        if (!$field->name) {
            $field->name = $fieldName;
        }

        $docBlock = new DocBlockReader($method);
        if (!$field->description) {
            $field->description = $docBlock->getMethodDescription();
        }

        if ($this->isIdentityField($fieldName)) {
            $field->type = Type::nonNull(Type::id());
        }

        // If still no type, look for docblock
        if (!$field->type) {
            $field->type = $this->getTypeFromDocBock($method, $docBlock);
        }

        // If still no type, look for type hint
        if (!$field->type) {
            $field->type = $this->getTypeFromReturnTypeHint($method, $fieldName);
        }

        // If still no args, look for type hint
        $field->args = $this->getArgumentsFromTypeHint($method, $field->args, $docBlock);

        // If still no type, cannot continue
        if (!$field->type) {
            throw new Exception('Could not find type for method ' . $this->getMethodFullName($method) . '. Either type hint the return value, or specify the type with `@API\Field` annotation.');
        }
    }

    /**
     * Complete arguments configuration from existing type hints
     * @param ReflectionMethod $method
     * @param Argument[] $argsFromAnnotations
     * @throws Exception
     * @return array
     */
    private function getArgumentsFromTypeHint(ReflectionMethod $method, array $argsFromAnnotations, DocBlockReader $docBlock): array
    {
        $args = [];
        foreach ($method->getParameters() as $param) {
            //Either get existing, or create new argument
            $arg = $argsFromAnnotations[$param->getName()] ?? new Argument();
            $args[$param->getName()] = $arg;

            $this->completeArgumentFromTypeHint($method, $param, $arg, $docBlock);
        }

        $extraAnnotations = array_diff(array_keys($argsFromAnnotations), array_keys($args));
        if ($extraAnnotations) {
            throw new Exception('The following arguments were declared via `@API\Argument` annotation but do not match actual parameter names on method ' . $this->getMethodFullName($method) . '. Either rename or remove the annotations: ' . implode(', ', $extraAnnotations));
        }

        return $args;
    }

    /**
     * Complete a single argument from its type hint
     * @param ReflectionMethod $method
     * @param ReflectionParameter $param
     * @param Argument $arg
     */
    private function completeArgumentFromTypeHint(ReflectionMethod $method, ReflectionParameter $param, Argument $arg, DocBlockReader $docBlock): void
    {
        if (!$arg->name) {
            $arg->name = $param->getName();
        }

        if (!$arg->description) {
            $arg->description = $docBlock->getParameterDescription($param);
        }

        if (!isset($arg->defaultValue) && $param->isDefaultValueAvailable()) {
            $arg->defaultValue = $param->getDefaultValue();
        }

        if (!$arg->type) {
            $typeDeclaration = $docBlock->getParameterType($param);
            $this->throwIfArray($param, $typeDeclaration);
            $arg->type = $this->getTypeFromPhpDeclaration($method, $typeDeclaration, true);
        }

        $type = $param->getType();
        if (!$arg->type && $type) {
            $this->throwIfArray($param, (string) $type);
            $arg->type = $this->refelectionTypeToType($type, true);
        }

        $arg->type = $this->nonNullIfHasDefault($param, $arg->type);

        $this->throwIfNotInputType($param, $arg->type, 'Argument');
    }

    /**
     * Get a GraphQL type instance from dock block return type
     * @param ReflectionMethod $method
     * @param \GraphQL\Doctrine\DocBlockReader $docBlock
     * @return null|Type
     */
    private function getTypeFromDocBock(ReflectionMethod $method, DocBlockReader $docBlock): ?Type
    {
        $typeDeclaration = $docBlock->getReturnType();
        $blacklist = [
            'Collection',
            'array',
        ];

        if ($typeDeclaration && !in_array($typeDeclaration, $blacklist, true)) {
            return $this->getTypeFromPhpDeclaration($method, $typeDeclaration);
        }

        return null;
    }
}
