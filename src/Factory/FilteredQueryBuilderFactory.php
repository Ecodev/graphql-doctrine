<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Factory;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use GraphQL\Doctrine\Definition\Operator\AbstractOperator;
use GraphQL\Doctrine\Factory\Type\SortingTypeFactory;
use GraphQL\Doctrine\Types;
use GraphQL\Type\Definition\InputObjectType;

/**
 * A factory to create a QueryBuilder filtered and sorted according to arguments
 */
final class FilteredQueryBuilderFactory extends AbstractFactory
{
    /**
     * @var UniqueNameFactory
     */
    private $uniqueNameFactory;

    /**
     * @var SortingTypeFactory
     */
    private $sortingTypeFactory;

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var string[]
     */
    private $dqlConditions = [];

    /**
     * @var string[]
     */
    private $uniqueJoins = [];

    public function __construct(Types $types, EntityManager $entityManager, SortingTypeFactory $sortingTypeFactory)
    {
        parent::__construct($types, $entityManager);
        $this->sortingTypeFactory = $sortingTypeFactory;
    }

    public function create(string $className, array $filter, array $sorting): QueryBuilder
    {
        $this->uniqueNameFactory = new UniqueNameFactory();
        $alias = $this->uniqueNameFactory->createAliasName($className);
        $this->dqlConditions = [];
        $this->uniqueJoins = [];

        $this->queryBuilder = $this->entityManager->getRepository($className)->createQueryBuilder($alias);
        $metadata = $this->entityManager->getClassMetadata($className);
        $type = $this->types->getFilter($className);

        $this->applyGroups($metadata, $type, $filter, $alias);
        $this->applySorting($className, $sorting, $alias);

        return $this->queryBuilder;
    }

    /**
     * Apply filters to the query builder
     *
     * @param ClassMetadata $metadata
     * @param InputObjectType $type
     * @param array $filter
     * @param string $alias
     *
     * @throws \Exception
     */
    private function applyGroups(ClassMetadata $metadata, InputObjectType $type, array $filter, string $alias): void
    {
        $typeFields = $type->getField('groups')->type->getWrappedType(true)->getField('conditions')->type->getWrappedType(true);
        foreach ($filter['groups'] ?? [] as $group) {
            $this->applyJoinsAndFilters($metadata, $alias, $typeFields, $group['joins'] ?? [], $group['conditions'] ?? []);
            $this->applyCollectedDqlConditions($group);
        }
    }

    /**
     * Apply both joins and filters to the query builder
     *
     * @param ClassMetadata $metadata
     * @param string $alias
     * @param InputObjectType $typeFields
     * @param array $joins
     * @param array $conditions
     *
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    private function applyJoinsAndFilters(ClassMetadata $metadata, string $alias, InputObjectType $typeFields, array $joins, array $conditions): void
    {
        $this->applyJoins($metadata, $joins, $alias);
        $this->collectDqlConditions($metadata, $conditions, $typeFields, $alias);
    }

    /**
     * Gather all DQL conditions for the given array of fields
     *
     * @param ClassMetadata $metadata
     * @param array $allConditions
     * @param InputObjectType $typeFields
     * @param string $alias
     */
    private function collectDqlConditions(ClassMetadata $metadata, array $allConditions, InputObjectType $typeFields, string $alias): void
    {
        foreach ($allConditions as $conditions) {
            foreach ($conditions as $field => $operators) {
                if ($operators === null) {
                    continue;
                }

                /** @var InputObjectType $typeField */
                $typeField = $typeFields->getField($field)->type;

                foreach ($operators as $operatorName => $operatorArgs) {
                    $operatorField = $typeField->getField($operatorName);

                    /** @var AbstractOperator $operatorType */
                    $operatorType = $operatorField->type;

                    $dqlCondition = $operatorType->getDqlCondition($this->uniqueNameFactory, $metadata, $this->queryBuilder, $alias, $field, $operatorArgs);
                    if ($dqlCondition) {
                        $this->dqlConditions[] = $dqlCondition;
                    }
                }
            }
        }
    }

    /**
     * Apply joins to the query builder
     *
     * @param ClassMetadata $metadata
     * @param array $joins
     * @param string $alias
     *
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    private function applyJoins(ClassMetadata $metadata, array $joins, string $alias): void
    {
        foreach ($joins as $field => $join) {
            $joinedAlias = $this->createJoin($alias, $field, $join['type']);

            if (isset($join['joins']) || isset($join['conditions'])) {
                $targetClassName = $metadata->getAssociationMapping($field)['targetEntity'];
                $targetMetadata = $this->entityManager->getClassMetadata($targetClassName);
                $type = $this->types->getFilterGroupCondition($targetClassName);
                $this->applyJoinsAndFilters($targetMetadata, $joinedAlias, $type, $join['joins'] ?? [], $join['conditions'] ?? []);
            }
        }
    }

    /**
     * Apply sorting to the query builder
     *
     * @param string $className
     * @param array $sorting
     * @param string $alias
     */
    private function applySorting(string $className, array $sorting, string $alias): void
    {
        foreach ($sorting as $sort) {
            $customSort = $this->sortingTypeFactory->getCustomSorting($className, $sort['field']);
            if ($customSort) {
                $customSort($this->queryBuilder, $sort['order']);
            } else {
                $this->queryBuilder->addOrderBy($alias . '.' . $sort['field'], $sort['order']);
            }
        }
    }

    /**
     * Apply collected DQL conditions on the query builder and reset them
     *
     * @param array $group
     */
    private function applyCollectedDqlConditions(array $group): void
    {
        if (!$this->dqlConditions) {
            return;
        }

        if ($group['conditionsLogic'] === 'AND') {
            $fieldsDql = $this->queryBuilder->expr()->andX(...$this->dqlConditions);
        } else {
            $fieldsDql = $this->queryBuilder->expr()->orX(...$this->dqlConditions);
        }

        if ($group['groupLogic'] === 'AND') {
            $this->queryBuilder->andWhere($fieldsDql);
        } else {
            $this->queryBuilder->orWhere($fieldsDql);
        }

        $this->dqlConditions = [];
    }

    /**
     * Create a join, but only if it does not exist yet
     *
     * @param string $alias
     * @param string $field
     * @param string $joinType
     *
     * @return string
     */
    private function createJoin(string $alias, string $field, string $joinType): string
    {
        $relationship = $alias . '.' . $field;
        $key = $relationship . '.' . $joinType;

        if (!isset($this->uniqueJoins[$key])) {
            $joinedAlias = $this->uniqueNameFactory->createAliasName($field);

            if ($joinType === 'innerJoin') {
                $this->queryBuilder->innerJoin($relationship, $joinedAlias);
            } else {
                $this->queryBuilder->leftJoin($relationship, $joinedAlias);
            }

            // TODO: For now we assume the query will always access some field on the relation, so we optimize SQL by
            // fetching those objects in a single SQL query. But this should be revisited by either exposing an option
            // to the API so the client could decide to select or not the relations, or even better to detect in GraphQL
            // query if it's actually used or not.
            $this->queryBuilder->addSelect($joinedAlias);

            $this->uniqueJoins[$key] = $joinedAlias;
        }

        return $this->uniqueJoins[$key];
    }
}
