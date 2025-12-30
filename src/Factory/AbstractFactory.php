<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Factory;

use Doctrine\ORM\EntityManager;
use GraphQL\Doctrine\Attribute\Exclude;
use GraphQL\Doctrine\Attribute\Reader\Reader;
use GraphQL\Doctrine\Types;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\Type;
use ReflectionClass;
use ReflectionProperty;

/**
 * Abstract factory to be aware of types and entityManager.
 */
abstract class AbstractFactory
{
    protected readonly Reader $reader;

    public function __construct(
        protected readonly Types $types,
        protected readonly EntityManager $entityManager,
    ) {
        $this->reader = new Reader();
    }

    /**
     * Returns whether the property is excluded.
     */
    final protected function isPropertyExcluded(ReflectionProperty $property): bool
    {
        $exclude = $this->reader->getAttribute($property, Exclude::class);

        return $exclude !== null;
    }

    /**
     * Get instance of GraphQL type from a PHP class name.
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
     *  - `Collection<int, MyType>`
     */
    final protected function getTypeFromPhpDeclaration(ReflectionClass $class, null|string|Type $typeDeclaration, bool $isEntityId = false): ?Type
    {
        if ($typeDeclaration === null || $typeDeclaration instanceof Type) {
            return $typeDeclaration;
        }

        $isNullable = 0;
        $name = preg_replace('~(^\?|^null\||\|null$)~', '', $typeDeclaration, count: $isNullable);

        $isList = 0;
        $name = preg_replace_callback(
            '~^([^<]*)\[]$|^Collection<(.*),(.*)>$~',
            fn (array $m) => $m[1] . mb_trim($m[3] ?? ''),
            $name ?? '',
            count: $isList,
        );
        $name = $this->adjustNamespace($class, $name);
        $type = $this->getTypeFromRegistry($name, $isEntityId);

        if ($isList) {
            $type = Type::listOf(Type::nonNull($type));
        }

        if (!$isNullable) {
            $type = Type::nonNull($type);
        }

        // @phpstan-ignore-next-line
        return $type;
    }

    /**
     * Prepend namespace of the method if the class actually exists.
     */
    private function adjustNamespace(ReflectionClass $class, string $type): string
    {
        if ($type === 'self') {
            $type = $class->getName();
        }

        $namespace = $class->getNamespaceName();
        if ($namespace) {
            $namespacedType = $namespace . '\\' . $type;

            if (class_exists($namespacedType)) {
                return $namespacedType;
            }
        }

        return $type;
    }

    /**
     * Returns a type from our registry.
     */
    final protected function getTypeFromRegistry(string $type, bool $isEntityId): NamedType
    {
        if ($type === 'ID') {
            return Type::id();
        }

        if ($this->types->isEntity($type) && $isEntityId) {
            // @phpstan-ignore-next-line
            return $this->types->getId($type);
        }

        if ($this->types->isEntity($type) && !$isEntityId) {
            // @phpstan-ignore-next-line
            return $this->types->getOutput($type);
        }

        return $this->types->get($type);
    }
}
