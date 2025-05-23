<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Factory;

use GraphQL\Doctrine\Attribute\Argument;
use GraphQL\Doctrine\Attribute\Field;
use GraphQL\Doctrine\DocBlockReader;
use GraphQL\Doctrine\Exception;
use GraphQL\Type\Definition\Type;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * A factory to create a configuration for all getters of an entity.
 */
final class OutputFieldsConfigurationFactory extends AbstractFieldsConfigurationFactory
{
    protected function getMethodPattern(): string
    {
        return '~^(get|is|has)[A-Z]~';
    }

    /**
     * Get the entire configuration for a method.
     */
    protected function methodToConfiguration(ReflectionMethod $method): array
    {
        // Get a field from attribute, or an empty one
        $field = $this->reader->getAttribute($method, Field::class) ?? new Field();

        if (!$field->type instanceof Type) {
            $field->type = $this->getTypeFromPhpDeclaration($method->getDeclaringClass(), $field->type);
            $this->completeField($field, $method);
        }

        return $field->toArray();
    }

    /**
     * Complete field with info from doc blocks and type hints.
     */
    private function completeField(Field $field, ReflectionMethod $method): void
    {
        $fieldName = lcfirst(preg_replace('~^get~', '', $method->getName()) ?? '');
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
     * Complete arguments configuration from existing type hints.
     */
    private function completeFieldArguments(Field $field, ReflectionMethod $method, DocBlockReader $docBlock): void
    {
        $args = [];
        foreach ($method->getParameters() as $param) {
            // Either get existing, or create new argument
            $arg = $this->reader->getAttribute($param, Argument::class) ?? new Argument();
            $arg->setName($param->getName());

            $arg->setTypeInstance($this->getTypeFromPhpDeclaration($method->getDeclaringClass(), $arg->getType()));

            $args[$param->getName()] = $arg;

            $this->completeArgumentFromTypeHint($arg, $method, $param, $docBlock);
        }

        $field->args = $args;
    }

    /**
     * Complete a single argument from its type hint.
     */
    private function completeArgumentFromTypeHint(Argument $arg, ReflectionMethod $method, ReflectionParameter $param, DocBlockReader $docBlock): void
    {
        if (!$arg->getDescription()) {
            $arg->setDescription($docBlock->getParameterDescription($param));
        }

        if (!$arg->hasDefaultValue() && $param->isDefaultValueAvailable()) {
            $arg->setDefaultValue($param->getDefaultValue());
        }

        $this->completeArgumentTypeFromTypeHint($arg, $method, $param, $docBlock);
    }

    /**
     * Complete a single argument type from its type hint and doc block.
     */
    private function completeArgumentTypeFromTypeHint(Argument $arg, ReflectionMethod $method, ReflectionParameter $param, DocBlockReader $docBlock): void
    {
        if (!$arg->getTypeInstance()) {
            $typeDeclaration = $docBlock->getParameterType($param);
            $this->throwIfArray($param, $typeDeclaration);
            $arg->setTypeInstance($this->getTypeFromPhpDeclaration($method->getDeclaringClass(), $typeDeclaration, true));
        }

        $type = $param->getType();
        if (!$arg->getTypeInstance() && $type instanceof ReflectionNamedType) {
            $this->throwIfArray($param, $type->getName());
            $arg->setTypeInstance($this->reflectionTypeToType($type, true));
        }

        $this->nonNullIfHasDefault($arg);

        $this->throwIfNotInputType($param, $arg);
    }

    /**
     * Get a GraphQL type instance from dock block return type.
     */
    private function getTypeFromDocBock(ReflectionMethod $method, DocBlockReader $docBlock): ?Type
    {
        $typeDeclaration = $docBlock->getReturnType();
        $blacklist = [
            'Collection',
            'array',
        ];

        if ($typeDeclaration && !in_array($typeDeclaration, $blacklist, true)) {
            return $this->getTypeFromPhpDeclaration($method->getDeclaringClass(), $typeDeclaration);
        }

        return null;
    }

    /**
     * Complete field type from doc blocks and type hints.
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
            throw new Exception('Could not find type for method ' . $this->getMethodFullName($method) . '. Either type hint the return value, or specify the type with `#[API\Field]` attribute.');
        }
    }
}
