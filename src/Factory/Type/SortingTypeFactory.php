<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Factory\Type;

use GraphQL\Doctrine\Annotation\Sorting;
use GraphQL\Doctrine\Sorting\SortingInterface;
use GraphQL\Doctrine\Utils;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use ReflectionClass;

/**
 * A factory to create an InputObjectType from a Doctrine entity to
 * sort them by their fields and custom sorter.
 */
final class SortingTypeFactory extends AbstractTypeFactory
{
    /**
     * Map of entity class and their custom sorting class instances.
     *
     * @var SortingInterface[][]
     */
    private $customSortings = [];

    /**
     * Create an InputObjectType from a Doctrine entity to
     * sort them by their fields and custom sorter.
     *
     * @param string $className class name of Doctrine entity
     * @param string $typeName GraphQL type name
     *
     * @return InputObjectType
     */
    public function create(string $className, string $typeName): Type
    {
        $type = new InputObjectType([
            'name' => $typeName,
            'fields' => function () use ($className, $typeName): array {
                $fieldsEnum = new EnumType([
                    'name' => $typeName . 'Field',
                    'values' => $this->getPossibleValues($className),
                    'description' => 'Fields available for `' . $typeName . '`',
                ]);
                $this->types->registerInstance($fieldsEnum);

                return [
                    [
                        'name' => 'field',
                        'type' => Type::nonNull($fieldsEnum),
                    ],
                    [
                        'name' => 'nullAsHighest',
                        'type' => Type::boolean(),
                        'description' => 'If true `NULL` values will be considered as the highest value, so appearing last in a `ASC` order, and first in a `DESC` order.',
                        'defaultValue' => false,
                    ],
                    [
                        'name' => 'order',
                        'type' => $this->types->get('SortingOrder'),
                        'defaultValue' => 'ASC',
                    ],
                ];
            },
        ]);

        return $type;
    }

    /**
     * Get names for all possible sorting, including the custom one.
     *
     * @return string[]
     */
    private function getPossibleValues(string $className): array
    {
        $metadata = $this->entityManager->getClassMetadata($className);
        $standard = array_values(array_filter($metadata->fieldNames, function ($fieldName) use ($metadata) {
            $property = $metadata->getReflectionProperty($fieldName);

            return !$this->isPropertyExcluded($property);
        }));
        $custom = $this->getCustomSortingNames($className);

        return array_merge($standard, $custom);
    }

    /**
     * Get names for all custom sorting.
     *
     * @return string[]
     */
    private function getCustomSortingNames(string $className): array
    {
        $this->fillCache($className);

        return array_keys($this->customSortings[$className]);
    }

    /**
     * Get instance of custom sorting for the given entity and sorting name.
     */
    public function getCustomSorting(string $className, string $name): ?SortingInterface
    {
        $this->fillCache($className);

        return $this->customSortings[$className][$name] ?? null;
    }

    /**
     * Fill the cache for custom sorting.
     */
    private function fillCache(string $className): void
    {
        if (array_key_exists($className, $this->customSortings)) {
            return;
        }

        $class = new ReflectionClass($className);
        $this->customSortings[$className] = $this->getFromAnnotation($class);
    }

    /**
     * Get all instance of custom sorting from the annotation.
     *
     * @return SortingInterface[]
     */
    private function getFromAnnotation(ReflectionClass $class): array
    {
        $sortings = Utils::getRecursiveClassAnnotations($this->getAnnotationReader(), $class, Sorting::class);

        $result = [];
        foreach ($sortings as $classWithAnnotation => $sorting) {
            /** @var class-string<SortingInterface> $className */
            foreach ($sorting->classes as $className) {
                $this->throwIfInvalidAnnotation($classWithAnnotation, 'Sorting', SortingInterface::class, $className);

                $name = lcfirst(Utils::getTypeName($className));
                $result[$name] = new $className();
            }
        }

        return $result;
    }
}
