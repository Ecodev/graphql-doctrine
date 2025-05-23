<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Factory;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\ClassMetadata;
use GraphQL\Doctrine\Attribute\AbstractAttribute;
use GraphQL\Doctrine\Attribute\Exclude;
use GraphQL\Doctrine\Exception;
use GraphQL\Type\Definition\InputType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\WrappingType;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * A factory to create a configuration for all fields of an entity.
 */
abstract class AbstractFieldsConfigurationFactory extends AbstractFactory
{
    /**
     * Doctrine metadata for the entity.
     */
    private ClassMetadata $metadata;

    /**
     * The identity field name, eg: "id".
     */
    private string $identityField;

    /**
     * Returns the regexp pattern to filter method names.
     */
    abstract protected function getMethodPattern(): string;

    /**
     * Get the entire configuration for a method.
     */
    abstract protected function methodToConfiguration(ReflectionMethod $method): ?array;

    /**
     * Create a configuration for all fields of Doctrine entity.
     *
     * @param class-string $className
     */
    public function create(string $className): array
    {
        $this->findIdentityField($className);

        $class = $this->metadata->getReflectionClass();
        $methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);
        $fieldConfigurations = [];
        foreach ($methods as $method) {
            // Skip non-callable or non-instance
            if ($method->isAbstract() || $method->isStatic()) {
                continue;
            }

            // Skip non-getter methods
            $name = $method->getName();
            if (!preg_match($this->getMethodPattern(), $name)) {
                continue;
            }

            // Skip exclusion specified by user
            if ($this->isExcluded($method)) {
                continue;
            }

            $configuration = $this->methodToConfiguration($method);
            if ($configuration) {
                $fieldConfigurations[] = $configuration;
            }
        }

        return $fieldConfigurations;
    }

    /**
     * Returns whether the getter is excluded.
     */
    private function isExcluded(ReflectionMethod $method): bool
    {
        $exclude = $this->reader->getAttribute($method, Exclude::class);

        return $exclude !== null;
    }

    /**
     * Get a GraphQL type instance from PHP type hinted type, possibly looking up the content of collections.
     */
    final protected function getTypeFromReturnTypeHint(ReflectionMethod $method, string $fieldName): ?Type
    {
        $returnType = $method->getReturnType();
        if (!$returnType instanceof ReflectionNamedType) {
            return null;
        }

        $returnTypeName = $returnType->getName();
        if (is_a($returnTypeName, Collection::class, true) || $returnTypeName === 'array') {
            $targetEntity = $this->getTargetEntity($fieldName);
            if (!$targetEntity) {
                throw new Exception('The method ' . $this->getMethodFullName($method) . ' is type hinted with a return type of `' . $returnTypeName . '`, but the entity contained in that collection could not be automatically detected. Either fix the type hint, fix the doctrine mapping, or specify the type with `#[API\Field]` attribute.');
            }

            $type = Type::listOf(Type::nonNull($this->getTypeFromRegistry($targetEntity, false)));
            if (!$returnType->allowsNull()) {
                $type = Type::nonNull($type);
            }

            return $type;
        }

        return $this->reflectionTypeToType($returnType);
    }

    /**
     * Convert a reflected type to GraphQL Type.
     */
    final protected function reflectionTypeToType(ReflectionNamedType $reflectionType, bool $isEntityId = false): Type
    {
        $name = $reflectionType->getName();
        if ($name === 'self') {
            $name = $this->metadata->name;
        }

        $type = $this->getTypeFromRegistry($name, $isEntityId);
        if (!$reflectionType->allowsNull()) {
            $type = Type::nonNull($type);
        }

        // @phpstan-ignore-next-line
        return $type;
    }

    /**
     * Look up which field is the ID.
     *
     * @param class-string $className
     */
    private function findIdentityField(string $className): void
    {
        $this->metadata = $this->entityManager->getClassMetadata($className);
        foreach ($this->metadata->fieldMappings as $meta) {
            if ($meta->id ?? false) {
                $this->identityField = $meta->fieldName;
            }
        }
    }

    /**
     * Returns the fully qualified method name.
     */
    final protected function getMethodFullName(ReflectionMethod $method): string
    {
        return '`' . $method->getDeclaringClass()->getName() . '::' . $method->getName() . '()`';
    }

    /**
     * Throws exception if type is an array.
     */
    final protected function throwIfArray(ReflectionParameter $param, ?string $type): void
    {
        if ($type === 'array') {
            throw new Exception('The parameter `$' . $param->getName() . '` on method ' . $this->getMethodFullName($param->getDeclaringFunction()) . ' is type hinted as `array` and is not overridden via `#[API\Argument]` attribute. Either change the type hint or specify the type with `#[API\Argument]` attribute.');
        }
    }

    /**
     * Returns whether the given field name is the identity for the entity.
     */
    final protected function isIdentityField(string $fieldName): bool
    {
        return $this->identityField === $fieldName;
    }

    /**
     * Finds the target entity in the given association.
     */
    private function getTargetEntity(string $fieldName): ?string
    {
        return $this->metadata->associationMappings[$fieldName]->targetEntity ?? null;
    }

    /**
     * Return the default value, if any, of the property for the current entity.
     *
     * It does take into account that the property might be defined on a parent class
     * of entity. And it will find it if that is the case.
     */
    final protected function getPropertyDefaultValue(string $fieldName): mixed
    {
        $property = $this->metadata->getReflectionProperties()[$fieldName] ?? null;
        if (!$property) {
            return null;
        }

        return $property->getDeclaringClass()->getDefaultProperties()[$fieldName] ?? null;
    }

    /**
     * Input with default values cannot be non-null.
     */
    final protected function nonNullIfHasDefault(AbstractAttribute $attribute): void
    {
        $type = $attribute->getTypeInstance();
        if ($type instanceof NonNull && $attribute->hasDefaultValue()) {
            $attribute->setTypeInstance($type->getWrappedType());
        }
    }

    /**
     * Throws exception if argument type is invalid.
     */
    final protected function throwIfNotInputType(ReflectionParameter $param, AbstractAttribute $attribute): void
    {
        $type = $attribute->getTypeInstance();
        $class = new ReflectionClass($attribute);
        $attributeName = $class->getShortName();

        if (!$type) {
            throw new Exception('Could not find type for parameter `$' . $param->name . '` for method ' . $this->getMethodFullName($param->getDeclaringFunction()) . '. Either type hint the parameter, or specify the type with `#[API\\' . $attributeName . ']` attribute.');
        }

        if ($type instanceof WrappingType) {
            $type = $type->getInnermostType();
        }

        if (!($type instanceof InputType)) {
            throw new Exception('Type for parameter `$' . $param->name . '` for method ' . $this->getMethodFullName($param->getDeclaringFunction()) . ' must be an instance of `' . InputType::class . '`, but was `' . $type::class . '`. Use `#[API\\' . $attributeName . ']` attribute to specify a custom InputType.');
        }
    }
}
