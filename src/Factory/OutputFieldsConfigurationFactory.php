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
final class OutputFieldsConfigurationFactory extends AbstractFieldsConfigurationFactory
{
    protected function getMethodPattern(): string
    {
        return '~^(get|is|has)[A-Z]~';
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
        // Get a field from annotation, or an empty one
        /** @var Field $field */
        $field = $this->getAnnotationReader()->getMethodAnnotation($method, Field::class) ?? new Field();

        if (!$field->type instanceof Type) {
            $this->convertTypeDeclarationsToInstances($method, $field);
            $this->completeField($field, $method);
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

        /** @var Argument $arg */
        foreach ($field->args as $arg) {
            $arg->setTypeInstance($this->getTypeFromPhpDeclaration($method, $arg->getType()));
            $args[$arg->getName()] = $arg;
        }
        $field->args = $args;
    }

    /**
     * Complete field with info from doc blocks and type hints
     *
     * @param Field $field
     * @param ReflectionMethod $method
     *
     * @throws Exception
     */
    private function completeField(Field $field, ReflectionMethod $method): void
    {
        $fieldName = lcfirst(preg_replace('~^get~', '', $method->getName()));
        if (!$field->name) {
            $field->name = $fieldName;
        }

        $docBlock = new DocBlockReader($method);
        if (!$field->description) {
            $field->description = $docBlock->getMethodDescription();
        }

        $this->completeFieldArguments($field, $method, $docBlock);
        $this->completeFieldType($field, $method, $fieldName, $docBlock);
    }

    /**
     * Complete arguments configuration from existing type hints
     *
     * @param Field $field
     * @param ReflectionMethod $method
     * @param DocBlockReader $docBlock
     */
    private function completeFieldArguments(Field $field, ReflectionMethod $method, DocBlockReader $docBlock): void
    {
        $argsFromAnnotations = $field->args;
        $args = [];
        foreach ($method->getParameters() as $param) {
            // Either get existing, or create new argument
            $arg = $argsFromAnnotations[$param->getName()] ?? new Argument();
            $args[$param->getName()] = $arg;

            $this->completeArgumentFromTypeHint($arg, $method, $param, $docBlock);
        }

        $extraAnnotations = array_diff(array_keys($argsFromAnnotations), array_keys($args));
        if ($extraAnnotations) {
            throw new Exception('The following arguments were declared via `@API\Argument` annotation but do not match actual parameter names on method ' . $this->getMethodFullName($method) . '. Either rename or remove the annotations: ' . implode(', ', $extraAnnotations));
        }

        $field->args = $args;
    }

    /**
     * Complete a single argument from its type hint
     *
     * @param Argument $arg
     * @param ReflectionMethod $method
     * @param ReflectionParameter $param
     * @param DocBlockReader $docBlock
     */
    private function completeArgumentFromTypeHint(Argument $arg, ReflectionMethod $method, ReflectionParameter $param, DocBlockReader $docBlock): void
    {
        if (!$arg->getName()) {
            $arg->setName($param->getName());
        }

        if (!$arg->getDescription()) {
            $arg->setDescription($docBlock->getParameterDescription($param));
        }

        if (!$arg->hasDefaultValue() && $param->isDefaultValueAvailable()) {
            $arg->setDefaultValue($param->getDefaultValue());
        }

        $this->completeArgumentTypeFromTypeHint($arg, $method, $param, $docBlock);
    }

    /**
     * Complete a single argument type from its type hint and doc block
     *
     * @param Argument $arg
     * @param ReflectionMethod $method
     * @param ReflectionParameter $param
     * @param DocBlockReader $docBlock
     */
    private function completeArgumentTypeFromTypeHint(Argument $arg, ReflectionMethod $method, ReflectionParameter $param, DocBlockReader $docBlock): void
    {
        if (!$arg->getTypeInstance()) {
            $typeDeclaration = $docBlock->getParameterType($param);
            $this->throwIfArray($param, $typeDeclaration);
            $arg->setTypeInstance($this->getTypeFromPhpDeclaration($method, $typeDeclaration, true));
        }

        $type = $param->getType();
        if (!$arg->getTypeInstance() && $type) {
            $this->throwIfArray($param, (string) $type);
            $arg->setTypeInstance($this->reflectionTypeToType($type, true));
        }

        $this->nonNullIfHasDefault($arg);

        $this->throwIfNotInputType($param, $arg);
    }

    /**
     * Get a GraphQL type instance from dock block return type
     *
     * @param ReflectionMethod $method
     * @param \GraphQL\Doctrine\DocBlockReader $docBlock
     *
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

    /**
     * Complete field type from doc blocks and type hints
     *
     * @param Field $field
     * @param ReflectionMethod $method
     * @param string $fieldName
     * @param DocBlockReader $docBlock
     */
    private function completeFieldType(Field $field, ReflectionMethod $method, string $fieldName, DocBlockReader $docBlock): void
    {
        if ($this->isIdentityField($fieldName)) {
            $field->type = Type::nonNull(Type::id());
        }

        // If still no type, look for docBlock
        if (!$field->type) {
            $field->type = $this->getTypeFromDocBock($method, $docBlock);
        }

        // If still no type, look for type hint
        if (!$field->type) {
            $field->type = $this->getTypeFromReturnTypeHint($method, $fieldName);
        }

        // If still no type, cannot continue
        if (!$field->type) {
            throw new Exception('Could not find type for method ' . $this->getMethodFullName($method) . '. Either type hint the return value, or specify the type with `@API\Field` annotation.');
        }
    }
}
