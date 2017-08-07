<?php

declare(strict_types=1);

namespace GraphQL\Doctrine;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use GraphQL\Doctrine\Annotation\Argument;
use GraphQL\Doctrine\Annotation\Exclude;
use GraphQL\Doctrine\Annotation\Field;
use GraphQL\Type\Definition\Type;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionType;

/**
 * A factory to create a configuration for all fields of an entity
 */
class FieldsConfigurationFactory
{
    /**
     * @var Types
     */
    private $types;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * Doctrine metadata for the entity
     * @var ClassMetadata
     */
    private $metadata;

    /**
     * The identity field name, eg: "id"
     * @var string
     */
    private $identityField;

    public function __construct(Types $types, EntityManager $entityManager)
    {
        $this->types = $types;
        $this->entityManager = $entityManager;
    }

    /**
     * Create a configuration for all fields of Doctrine entity
     * @param string $className
     * @return array
     */
    public function create(string $className): array
    {
        $this->findIdentityField($className);

        $class = new ReflectionClass($className);
        $methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);
        $fieldConfigurations = [];
        foreach ($methods as $method) {
            // Skip non-callable, non-instance or non-getter methods
            if ($method->isAbstract() || $method->isStatic()) {
                continue;
            }

            // Skip non-getter methods
            $name = $method->getName();
            if (!preg_match('~^(get|is|has)[A-Z]~', $name)) {
                continue;
            }

            // Skip exclusion specified by user
            if ($this->isExcluded($method)) {
                continue;
            }

            $fieldConfigurations[] = $this->methodToConfiguration($method);
        }

        return $fieldConfigurations;
    }

    /**
     * Returns whether the getter is excluded
     * @param ReflectionMethod $method
     * @return bool
     */
    private function isExcluded(ReflectionMethod $method): bool
    {
        $exclude = $this->getAnnotationReader()->getMethodAnnotation($method, Exclude::class);

        return $exclude !== null;
    }

    /**
     * Get annotation reader
     * @return Reader
     */
    private function getAnnotationReader(): Reader
    {
        return $this->entityManager->getConfiguration()->getMetadataDriverImpl()->getReader();
    }

    /**
     * Get a field from annotation, or an empty one
     * All its types will be converted from string to real instance of Type
     *
     * @param ReflectionMethod $method
     * @return Field
     */
    private function getFieldFromAnnotation(ReflectionMethod $method): Field
    {
        $field = $this->getAnnotationReader()->getMethodAnnotation($method, Field::class) ?? new Field();

        $field->type = $this->phpDeclarationToInstance($method, $field->type);
        $args = [];
        foreach ($field->args as $arg) {
            $arg->type = $this->phpDeclarationToInstance($method, $arg->type);
            $args[$arg->name] = $arg;
        }
        $field->args = $args;

        return $field;
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
     * @param string|null $typeDeclaration
     * @return Type|null
     */
    private function phpDeclarationToInstance(ReflectionMethod $method, ?string $typeDeclaration): ?Type
    {
        if (!$typeDeclaration) {
            return null;
        }

        $isNullable = 0;
        $name = preg_replace('~(^\?|^null\||\|null$)~', '', $typeDeclaration, -1, $isNullable);

        $isList = 0;
        $name = preg_replace('~^(.*)\[\]$~', '$1', $name, -1, $isList);
        $name = $this->adjustNamespace($method, $name);
        $type = $this->types->get($name);

        if ($isList) {
            $type = Type::listOf($type);
        }

        if (!$isNullable) {
            $type = Type::nonNull($type);
        }

        return $type;
    }

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
     * Get the entire configuration for a method
     * @param ReflectionMethod $method
     * @throws Exception
     * @return array
     */
    private function methodToConfiguration(ReflectionMethod $method): array
    {
        // First get user specified values
        $field = $this->getFieldFromAnnotation($method);

        $fieldName = lcfirst(preg_replace('~^get~', '', $method->getName()));
        if (!$field->name) {
            $field->name = $fieldName;
        }

        $docBlock = new DocBlockReader($method);
        if (!$field->description) {
            $field->description = $docBlock->getMethodDescription();
        }

        if ($fieldName === $this->identityField) {
            $field->type = Type::nonNull(Type::id());
        }

        // If still no type, look for docblock
        if (!$field->type) {
            $field->type = $this->getTypeFromDocBock($method, $docBlock);
        }

        // If still no type, look for type hint
        if (!$field->type) {
            $field->type = $this->getTypeFromTypeHint($method, $fieldName);
        }

        // If still no args, look for type hint
        $field->args = $this->getArgumentsFromTypeHint($method, $field->args, $docBlock);

        // If still no type, cannot continue
        if (!$field->type) {
            throw new Exception('Could not find type for method ' . $this->getMethodFullName($method) . '. Either type hint the return value, or specify the type with `@API\Field` annotation.');
        }

        return $field->toArray();
    }

    /**
     * Get a GraphQL type instance from PHP type hinted type, possibly looking up the content of collections
     * @param ReflectionMethod $method
     * @param string $fieldName
     * @throws Exception
     * @return Type|null
     */
    private function getTypeFromTypeHint(ReflectionMethod $method, string $fieldName): ?Type
    {
        $returnType = $method->getReturnType();
        if (!$returnType) {
            return null;
        }

        $returnTypeName = (string) $returnType;
        if (is_a($returnTypeName, Collection::class, true) || $returnTypeName === 'array') {
            $mapping = $this->metadata->associationMappings[$fieldName] ?? false;
            if (!$mapping) {
                throw new Exception('The method ' . $this->getMethodFullName($method) . ' is type hinted with a return type of `' . $returnTypeName . '`, but the entity contained in that collection could not be automatically detected. Either fix the type hint, fix the doctrine mapping, or specify the type with `@API\Field` annotation.');
            }

            $type = Type::listOf($this->types->get($mapping['targetEntity']));
            if (!$returnType->allowsNull()) {
                $type = Type::nonNull($type);
            }

            return $type;
        }

        return $this->refelectionTypeToType($returnType);
    }

    /**
     * Convert a reflected type to GraphQL Type
     * @param ReflectionType $reflectionType
     * @return Type
     */
    private function refelectionTypeToType(ReflectionType $reflectionType): Type
    {
        $type = $this->types->get((string) $reflectionType);
        if (!$reflectionType->allowsNull()) {
            $type = Type::nonNull($type);
        }

        return $type;
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
     * @throws Exception
     */
    private function completeArgumentFromTypeHint(ReflectionMethod $method, ReflectionParameter $param, Argument $arg, DocBlockReader $docBlock)
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

        $type = $docBlock->getParameterType($param) ?? $param->getType();
        if (!$arg->type && $type) {
            if ((string) ($type) === 'array') {
                throw new Exception('The parameter `$' . $param->getName() . '` on method ' . $this->getMethodFullName($method) . ' is type hinted as `array` and is not overriden via `@API\Argument` annotation. Either change the type hint or specify the type with `@API\Argument` annotation.');
            }
            $arg->type = $this->refelectionTypeToType($type);
        }

        if (!$arg->type) {
            throw new Exception('Could not find type for parameter `$' . $arg->name . '` for method ' . $this->getMethodFullName($method) . '. Either type hint the parameter, or specify the type with `@API\Argument` annotation.');
        }
    }

    /**
     * Look up which field is the ID
     * @param string $className
     */
    private function findIdentityField(string $className)
    {
        $this->metadata = $this->entityManager->getClassMetadata($className);
        foreach ($this->metadata->fieldMappings as $meta) {
            if ($meta['id'] ?? false) {
                $this->identityField = $meta['fieldName'];
            }
        }
    }

    private function getMethodFullName(ReflectionMethod $method): string
    {
        return '`' . $method->getDeclaringClass()->getName() . '::' . $method->getName() . '()`';
    }

    private function getTypeFromDocBock(ReflectionMethod $method, DocBlockReader $docBlock): ?Type
    {
        $typeDeclaration = $docBlock->getReturnType();
        $blacklist = [
            'Collection',
            'array',
        ];

        if ($typeDeclaration && !in_array($typeDeclaration, $blacklist, true)) {
            return $this->phpDeclarationToInstance($method, $typeDeclaration);
        }

        return null;
    }
}
