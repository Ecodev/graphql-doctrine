<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Factory\Type;

use GraphQL\Doctrine\Annotation\Filter;
use GraphQL\Doctrine\Annotation\Filters;
use GraphQL\Doctrine\Definition\Operator\AbstractOperator;
use GraphQL\Doctrine\Definition\Operator\BetweenOperatorType;
use GraphQL\Doctrine\Definition\Operator\ContainOperatorType;
use GraphQL\Doctrine\Definition\Operator\EmptyOperatorType;
use GraphQL\Doctrine\Definition\Operator\EqualOperatorType;
use GraphQL\Doctrine\Definition\Operator\GreaterOperatorType;
use GraphQL\Doctrine\Definition\Operator\GreaterOrEqualOperatorType;
use GraphQL\Doctrine\Definition\Operator\InOperatorType;
use GraphQL\Doctrine\Definition\Operator\LessOperatorType;
use GraphQL\Doctrine\Definition\Operator\LessOrEqualOperatorType;
use GraphQL\Doctrine\Definition\Operator\LikeOperatorType;
use GraphQL\Doctrine\Definition\Operator\NullOperatorType;
use GraphQL\Doctrine\Utils;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\LeafType;
use GraphQL\Type\Definition\Type;
use ReflectionClass;

/**
 * A factory to create an InputObjectType from a Doctrine entity to filter them.
 */
final class FilterTypeFactory extends AbstractTypeFactory
{
    /**
     * Create an InputObjectType from a Doctrine entity
     *
     * @param string $className class name of Doctrine entity
     * @param string $typeName GraphQL type name
     *
     * @return InputObjectType
     */
    public function create(string $className, string $typeName): Type
    {
        $description = 'To be used as a filter for objects of type `' . Utils::getTypeName($className) . '`';

        $fieldsGetter = function () use ($className, $typeName): array {
            $configuration = [
                [
                    'name' => 'joins',
                    'description' => 'Optional joins to either filter the query or fetch related objects from DB in a single query',
                    'type' => $this->getJoinsType($className, $typeName),
                ],
                [
                    'name' => 'conditions',
                    'type' => Type::listOf(Type::nonNull($this->getConditionType($className, $typeName))),
                ],
            ];

            return $configuration;
        };

        $filterType = new InputObjectType([
            'name' => $typeName,
            'description' => $description,
            'fields' => $fieldsGetter,
        ]);
        $this->types->registerInstance($filterType);

        return $filterType;
    }

    /**
     * Get the all the possible relations to be joined
     *
     * @param string $className
     *
     * @return array
     */
    private function getJoinsFields(string $className): array
    {
        $fields = [];
        $associations = $this->entityManager->getClassMetadata($className)->associationMappings;
        foreach ($associations as $association) {
            $field = [
                'name' => $association['fieldName'],
                'type' => $this->types->getJoin($association['targetEntity']),
            ];

            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * Get the type for join
     *
     * @param string $className
     * @param string $typeName
     *
     * @return InputObjectType
     */
    private function getJoinsType(string $className, string $typeName): InputObjectType
    {
        $joinsFields = $this->getJoinsFields($className);

        $joinsType = new InputObjectType([
            'name' => $typeName . 'Joins',
            'description' => 'Type to specify join tables in a filter',
            'fields' => $joinsFields,
        ]);

        $this->types->registerInstance($joinsType);

        return $joinsType;
    }

    /**
     * Get the type for condition
     *
     * @param string $className
     * @param string $typeName
     *
     * @return InputObjectType
     */
    private function getConditionType(string $className, string $typeName): InputObjectType
    {
        $conditionType = new InputObjectType([
            'name' => $typeName . 'Condition',
            'description' => 'Type to specify conditions to filter `' . Utils::getTypeName($className) . '`',
            'fields' => [
                [
                    'name' => 'conditionLogic',
                    'type' => $this->types->get('LogicalOperator'),
                    'description' => 'The logic operator to be used to append this condition',
                    'defaultValue' => 'AND',
                ],
                [
                    'name' => 'fieldsLogic',
                    'type' => $this->types->get('LogicalOperator'),
                    'description' => 'The logic operator to be used within all fields below',
                    'defaultValue' => 'AND',
                ],
                [
                    'name' => 'fields',
                    'description' => 'Fields on which we want to apply a condition',
                    'type' => $this->getConditionFieldsType($className, $typeName),
                ],
            ],
        ]);

        $this->types->registerInstance($conditionType);

        return $conditionType;
    }

    /**
     * Get the type for conditions on all fields
     *
     * @param string $className
     * @param string $typeName
     *
     * @return InputObjectType
     */
    private function getConditionFieldsType(string $className, string $typeName): InputObjectType
    {
        $conditionFieldsType = new InputObjectType([
            'name' => $typeName . 'ConditionFields',
            'description' => 'Type to specify conditions on fields',
            'fields' => function () use ($className, $typeName) {
                $standardFilters = [];
                $metadata = $this->entityManager->getClassMetadata($className);

                // Get all entity scalar fields
                foreach ($metadata->fieldMappings as $mapping) {
                    $fieldName = $mapping['fieldName'];
                    $leafType = $this->types->get($mapping['type']);

                    $field = [
                        'name' => $fieldName,
                        'type' => $this->getFieldType($typeName, $fieldName, $leafType, false),
                    ];
                    $standardFilters[] = $field;
                }

                // Get all entity collection fields
                foreach ($metadata->associationMappings as $mapping) {
                    $fieldName = $mapping['fieldName'];

                    $field = [
                        'name' => $fieldName,
                        'type' => $this->getFieldType($typeName, $fieldName, Type::id(), $metadata->isCollectionValuedAssociation($fieldName)),
                    ];
                    $standardFilters[] = $field;
                }

                // Get custom fields
                $customFilters = $this->getCustomFiltersFromAnnotation($metadata->reflClass);

                return array_merge($standardFilters, $customFilters);
            },
        ]);

        $this->types->registerInstance($conditionFieldsType);

        return $conditionFieldsType;
    }

    /**
     * Get the custom filters declared on the class via annotations
     *
     * @param ReflectionClass $class
     *
     * @return array
     */
    private function getCustomFiltersFromAnnotation(ReflectionClass $class): array
    {
        $result = [];

        $filters = $this->getAnnotationReader()->getClassAnnotation($class, Filters::class);
        if ($filters) {

            /** @var Filter $filter */
            foreach ($filters->filters as $filter) {
                $className = $filter->operator;
                $this->throwIfInvalidAnnotation($class, 'Filter', AbstractOperator::class, $className);

                $leafType = $this->types->get($filter->type);
                $instance = $this->types->getOperator($className, $leafType);

                $result[] = [
                    'name' => $filter->field,
                    'type' => $instance,
                ];
            }
        }

        if ($class->getParentClass()) {
            return array_merge($result, $this->getCustomFiltersFromAnnotation($class->getParentClass()));
        }

        return $result;
    }

    /**
     * Get the type for a specific field
     *
     * @param string $typeName
     * @param string $fieldName
     * @param LeafType $leafType
     * @param bool $isCollection
     *
     * @return InputObjectType
     */
    private function getFieldType(string $typeName, string $fieldName, LeafType $leafType, bool $isCollection): InputObjectType
    {
        $fieldType = new InputObjectType([
            'name' => $typeName . 'ConditionField' . ucfirst($fieldName),
            'description' => 'Type to specify a condition on a specific field',
            'fields' => $this->getOperators($leafType, $isCollection),
        ]);

        $this->types->registerInstance($fieldType);

        return $fieldType;
    }

    /**
     * Get standard operators for a specific leaf type
     *
     * @param LeafType $leafType
     * @param bool $isCollection
     *
     * @return array
     */
    private function getOperators(LeafType $leafType, bool $isCollection): array
    {
        if ($isCollection) {
            $operators = [
                ContainOperatorType::class,
                EmptyOperatorType::class,
            ];
        } else {
            $operators = [
                BetweenOperatorType::class,
                EqualOperatorType::class,
                GreaterOperatorType::class,
                GreaterOrEqualOperatorType::class,
                InOperatorType::class,
                LessOperatorType::class,
                LessOrEqualOperatorType::class,
                LikeOperatorType::class,
                NullOperatorType::class,
            ];
        }
        $conf = [];

        foreach ($operators as $operator) {
            $instance = $this->types->getOperator($operator, $leafType);
            $field = [
                'name' => $this->getOperatorFieldName($operator),
                'type' => $instance,
            ];

            $conf[] = $field;
        }

        return $conf;
    }

    /**
     * Get the name for the operator to be used as field name
     *
     * @param string $className
     *
     * @return string
     */
    private function getOperatorFieldName(string $className): string
    {
        $name = preg_replace('~OperatorType$~', '', Utils::getTypeName($className));

        return lcfirst($name);
    }
}
