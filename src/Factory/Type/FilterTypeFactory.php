<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Factory\Type;

use Doctrine\ORM\EntityManager;
use GraphQL\Doctrine\Types;
use GraphQL\Doctrine\Utils;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

/**
 * A factory to create an InputObjectType from a Doctrine entity to filter them.
 */
final class FilterTypeFactory extends AbstractTypeFactory
{
    /**
     * @var FilterGroupJoinTypeFactory
     */
    private $filterGroupJoinTypeFactory;

    /**
     * @var FilterGroupConditionTypeFactory
     */
    private $filterGroupConditionTypeFactory;

    public function __construct(Types $types, EntityManager $entityManager, FilterGroupJoinTypeFactory $filterGroupJoinTypeFactory, FilterGroupConditionTypeFactory $filterGroupConditionTypeFactory)
    {
        parent::__construct($types, $entityManager);
        $this->filterGroupJoinTypeFactory = $filterGroupJoinTypeFactory;
        $this->filterGroupConditionTypeFactory = $filterGroupConditionTypeFactory;
    }

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
                    'name' => 'groups',
                    'type' => Type::listOf(Type::nonNull($this->getGroupType($className, $typeName))),
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
     * Get the type for condition
     *
     * @param string $className
     * @param string $typeName
     *
     * @return InputObjectType
     */
    private function getGroupType(string $className, string $typeName): InputObjectType
    {
        $conditionType = new InputObjectType([
            'name' => $typeName . 'Group',
            'description' => 'Specify a set of joins and conditions to filter `' . Utils::getTypeName($className) . '`',
            'fields' => [
                [
                    'name' => 'groupLogic',
                    'type' => $this->types->get('LogicalOperator'),
                    'description' => 'The logic operator to be used to append this group',
                    'defaultValue' => 'AND',
                ],
                [
                    'name' => 'conditionsLogic',
                    'type' => $this->types->get('LogicalOperator'),
                    'description' => 'The logic operator to be used within all conditions in this group',
                    'defaultValue' => 'AND',
                ],
                $this->filterGroupJoinTypeFactory->getField($className),
                $this->filterGroupConditionTypeFactory->getField($className),
            ],
        ]);

        $this->types->registerInstance($conditionType);

        return $conditionType;
    }
}
