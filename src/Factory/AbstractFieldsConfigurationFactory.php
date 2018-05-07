<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Factory;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\ClassMetadata;
use GraphQL\Doctrine\Annotation\AbstractAnnotation;
use GraphQL\Doctrine\Annotation\Exclude;
use GraphQL\Doctrine\Exception;
use GraphQL\Type\Definition\InputType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\WrappingType;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;

/**
 * A factory to create a configuration for all fields of an entity
 */
abstract class AbstractFieldsConfigurationFactory extends AbstractFactory
{
    /**
     * Doctrine metadata for the entity
     *
     * @var ClassMetadata
     */
    private $metadata;

    /**
     * The identity field name, eg: "id"
     *
     * @var string
     */
    private $identityField;

    /**
     * Returns the regexp pattern to filter method names
     */
    abstract protected function getMethodPattern(): string;

    /**
     * Get the entire configuration for a method
     *
     * @param ReflectionMethod $method
     *
     * @return null|array
     */
    abstract protected function methodToConfiguration(ReflectionMethod $method): ?array;

    /**
     * Create a configuration for all fields of Doctrine entity
     *
     * @param string $className
     *
     * @return array
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
     * Returns whether the getter is excluded
     *
     * @param ReflectionMethod $method
     *
     * @return bool
     */
    private function isExcluded(ReflectionMethod $method): bool
    {
        $exclude = $this->getAnnotationReader()->getMethodAnnotation($method, Exclude::class);

        return $exclude !== null;
    }

    /**
     * Get instance of GraphQL type from a PHP class name
     *
     * Supported syntaxes are the following:
     *
     *  - `?MyType`
     *  - `null|MyType`
     *  - `MyType|null`
     *  - `MyType[]`
     *  - `?MyType[]`
     *  - `null|MyType[]`
     *  - `MyType[]|null`
     *
     * @param ReflectionMethod $method
     * @param null|string $typeDeclaration
     * @param bool $isEntityId
     *
     * @return null|Type
     */
    final protected function getTypeFromPhpDeclaration(ReflectionMethod $method, ?string $typeDeclaration, bool $isEntityId = false): ?Type
    {
        if (!$typeDeclaration) {
            return null;
        }

        $isNullable = 0;
        $name = preg_replace('~(^\?|^null\||\|null$)~', '', $typeDeclaration, -1, $isNullable);

        $isList = 0;
        $name = preg_replace('~^(.*)\[\]$~', '$1', $name, -1, $isList);
        $name = $this->adjustNamespace($method, $name);
        $type = $this->getTypeFromRegistry($name, $isEntityId);

        if ($isList) {
            $type = Type::listOf($type);
        }

        if (!$isNullable) {
            $type = Type::nonNull($type);
        }

        return $type;
    }

    /**
     * Prepend namespace of the method if the class actually exists
     *
     * @param ReflectionMethod $method
     * @param string $type
     *
     * @return string
     */
    private function adjustNamespace(ReflectionMethod $method, string $type): string
    {
        $namespace = $method->getDeclaringClass()->getNamespaceName();
        if ($namespace) {
            $namespace = $namespace . '\\';
        }
        $namespacedType = $namespace . $type;

        return class_exists($namespacedType) ? $namespacedType : $type;
    }

    /**
     * Get a GraphQL type instance from PHP type hinted type, possibly looking up the content of collections
     *
     * @param ReflectionMethod $method
     * @param string $fieldName
     *
     * @throws Exception
     *
     * @return null|Type
     */
    final protected function getTypeFromReturnTypeHint(ReflectionMethod $method, string $fieldName): ?Type
    {
        $returnType = $method->getReturnType();
        if (!$returnType) {
            return null;
        }

        $returnTypeName = (string) $returnType;
        if (is_a($returnTypeName, Collection::class, true) || $returnTypeName === 'array') {
            $targetEntity = $this->getTargetEntity($fieldName);
            if (!$targetEntity) {
                throw new Exception('The method ' . $this->getMethodFullName($method) . ' is type hinted with a return type of `' . $returnTypeName . '`, but the entity contained in that collection could not be automatically detected. Either fix the type hint, fix the doctrine mapping, or specify the type with `@API\Field` annotation.');
            }

            $type = Type::listOf($this->getTypeFromRegistry($targetEntity, false));
            if (!$returnType->allowsNull()) {
                $type = Type::nonNull($type);
            }

            return $type;
        }

        return $this->reflectionTypeToType($returnType);
    }

    /**
     * Convert a reflected type to GraphQL Type
     *
     * @param ReflectionType $reflectionType
     * @param bool $isEntityId
     *
     * @return Type
     */
    final protected function reflectionTypeToType(ReflectionType $reflectionType, bool $isEntityId = false): Type
    {
        $name = $reflectionType->getName();
        if ($name === 'self') {
            $name = $this->metadata->name;
        }

        $type = $this->getTypeFromRegistry($name, $isEntityId);
        if (!$reflectionType->allowsNull()) {
            $type = Type::nonNull($type);
        }

        return $type;
    }

    /**
     * Look up which field is the ID
     *
     * @param string $className
     */
    private function findIdentityField(string $className): void
    {
        $this->metadata = $this->entityManager->getClassMetadata($className);
        foreach ($this->metadata->fieldMappings as $meta) {
            if ($meta['id'] ?? false) {
                $this->identityField = $meta['fieldName'];
            }
        }
    }

    /**
     * Returns the fully qualified method name
     *
     * @param ReflectionMethod $method
     *
     * @return string
     */
    final protected function getMethodFullName(ReflectionMethod $method): string
    {
        return '`' . $method->getDeclaringClass()->getName() . '::' . $method->getName() . '()`';
    }

    /**
     * Throws exception if type is an array
     *
     * @param ReflectionParameter $param
     * @param null|string $type
     *
     * @throws Exception
     */
    final protected function throwIfArray(ReflectionParameter $param, ?string $type): void
    {
        if ($type === 'array') {
            throw new Exception('The parameter `$' . $param->getName() . '` on method ' . $this->getMethodFullName($param->getDeclaringFunction()) . ' is type hinted as `array` and is not overridden via `@API\Argument` annotation. Either change the type hint or specify the type with `@API\Argument` annotation.');
        }
    }

    /**
     * Returns whether the given field name is the identity for the entity
     *
     * @param string $fieldName
     *
     * @return bool
     */
    final protected function isIdentityField(string $fieldName): bool
    {
        return $this->identityField === $fieldName;
    }

    /**
     * Finds the target entity in the given association
     *
     * @param string $fieldName
     *
     * @return null|string
     */
    private function getTargetEntity(string $fieldName): ?string
    {
        return $this->metadata->associationMappings[$fieldName]['targetEntity'] ?? null;
    }

    /**
     * Return the default value, if any, of the property for the current entity
     *
     * It does take into account that the property might be defined on a parent class
     * of entity. And it will find it if that is the case.
     *
     * @param string $fieldName
     *
     * @return mixed
     */
    final protected function getPropertyDefaultValue(string $fieldName)
    {
        /** @var null|ReflectionProperty $property */
        $property = $this->metadata->getReflectionProperties()[$fieldName] ?? null;
        if (!$property) {
            return null;
        }

        return $property->getDeclaringClass()->getDefaultProperties()[$fieldName] ?? null;
    }

    /**
     * Returns a type from our registry
     *
     * @param string $type
     * @param bool $isEntityId
     *
     * @return Type
     */
    private function getTypeFromRegistry(string $type, bool $isEntityId): Type
    {
        if ($this->types->isEntity($type) && $isEntityId) {
            return $this->types->getId($type);
        }

        if ($this->types->isEntity($type) && !$isEntityId) {
            return $this->types->getOutput($type);
        }

        return $this->types->get($type);
    }

    /**
     * Input with default values cannot be non-null
     *
     * @param AbstractAnnotation $annotation
     */
    final protected function nonNullIfHasDefault(AbstractAnnotation $annotation): void
    {
        $type = $annotation->getTypeInstance();
        if ($type instanceof NonNull && $annotation->hasDefaultValue()) {
            $annotation->setTypeInstance($type->getWrappedType());
        }
    }

    /**
     * Throws exception if argument type is invalid
     *
     * @param ReflectionParameter $param
     * @param AbstractAnnotation $annotation
     *
     * @throws Exception
     */
    final protected function throwIfNotInputType(ReflectionParameter $param, AbstractAnnotation $annotation): void
    {
        $type = $annotation->getTypeInstance();
        $class = new ReflectionClass($annotation);
        $annotationName = $class->getShortName();

        if (!$type) {
            throw new Exception('Could not find type for parameter `$' . $param->name . '` for method ' . $this->getMethodFullName($param->getDeclaringFunction()) . '. Either type hint the parameter, or specify the type with `@API\\' . $annotationName . '` annotation.');
        }

        if ($type instanceof WrappingType) {
            $type = $type->getWrappedType(true);
        }

        if (!($type instanceof InputType)) {
            throw new Exception('Type for parameter `$' . $param->name . '` for method ' . $this->getMethodFullName($param->getDeclaringFunction()) . ' must be an instance of `' . InputType::class . '`, but was `' . get_class($type) . '`. Use `@API\\' . $annotationName . '` annotation to specify a custom InputType.');
        }
    }
}
