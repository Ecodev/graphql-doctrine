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

    public function __construct(Types $types, EntityManager $entityManager, SortingTypeFactory $sortingTypeFactory)
    {
        parent::__construct($types, $entityManager);
        $this->sortingTypeFactory = $sortingTypeFactory;
    }

    public function create(string $className, array $filter, array $sorting): QueryBuilder
    {
        $this->uniqueNameFactory = new UniqueNameFactory();
        $alias = $this->uniqueNameFactory->createAliasName($className);

        $queryBuilder = $this->entityManager->getRepository($className)->createQueryBuilder($alias);
        $metadata = $this->entityManager->getClassMetadata($className);
        $type = $this->types->getFilter($className);

        $this->applyJoinsAndFilters($metadata, $type, $filter, $queryBuilder, $alias);
        $this->applySorting($className, $sorting, $queryBuilder, $alias);

        return $queryBuilder;
    }

    /**
     * Apply both joins and filters to the query builder
     *
     * @param ClassMetadata $metadata
     * @param InputObjectType $type
     * @param array $filter
     * @param QueryBuilder $queryBuilder
     * @param string $alias
     */
    private function applyJoinsAndFilters(ClassMetadata $metadata, InputObjectType $type, array $filter, QueryBuilder $queryBuilder, string $alias): void
    {
        $this->applyJoins($metadata, $filter, $queryBuilder, $alias);
        $this->applyFilters($metadata, $type, $filter, $queryBuilder, $alias);
    }

    /**
     * Apply filters to the query builder
     *
     * @param ClassMetadata $metadata
     * @param InputObjectType $type
     * @param array $filter
     * @param QueryBuilder $queryBuilder
     * @param string $alias
     *
     * @throws \Exception
     */
    private function applyFilters(ClassMetadata $metadata, InputObjectType $type, array $filter, QueryBuilder $queryBuilder, string $alias): void
    {
        /** @var InputObjectType $typeFields */
        $typeFields = $type->getField('conditions')->type->getWrappedType(true)->getField('fields')->type;
        foreach ($filter['conditions'] ?? [] as $conditions) {
            $dqlConditions = [];
            foreach ($conditions['fields'] as $field => $fieldConditions) {
                if ($fieldConditions === null) {
                    continue;
                }

                /** @var InputObjectType $typeField */
                $typeField = $typeFields->getField($field)->type;

                foreach ($fieldConditions as $operator => $operatorConfig) {
                    $operatorField = $typeField->getField($operator);

                    /** @var AbstractOperator $operatorType */
                    $operatorType = $operatorField->type;

                    $condition = $operatorType->getDqlCondition($this->uniqueNameFactory, $metadata, $queryBuilder, $alias, $field, $operatorConfig);
                    if ($condition) {
                        $dqlConditions[] = $condition;
                    }
                }
            }

            $this->applyDqlConditions($queryBuilder, $conditions, $dqlConditions);
        }
    }

    /**
     * Apply joins to the query builder
     *
     * @param ClassMetadata $metadata
     * @param array $filter
     * @param QueryBuilder $queryBuilder
     * @param string $alias
     *
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    private function applyJoins(ClassMetadata $metadata, array $filter, QueryBuilder $queryBuilder, string $alias): void
    {
        foreach ($filter['joins'] ?? [] as $field => $join) {
            $relationship = $alias . '.' . $field;
            $joinedAlias = $this->uniqueNameFactory->createAliasName($field);

            if ($join['type'] === 'innerJoin') {
                $queryBuilder->innerJoin($relationship, $joinedAlias);
            } else {
                $queryBuilder->leftJoin($relationship, $joinedAlias);
            }

            // TODO: For now we assume the query will always access some field on the relation, so we optimize SQL by
            // fetching those objects in a single SQL query. But this should be revisited by either exposing an option
            // to the API so the client could decide to select or not the relations, or even better to detect in GraphQL
            // query if it's actually used or not.
            $queryBuilder->addSelect($joinedAlias);

            if (isset($join['filter'])) {
                $targetClassName = $metadata->getAssociationMapping($field)['targetEntity'];
                $targetMetadata = $this->entityManager->getClassMetadata($targetClassName);
                $type = $this->types->getFilter($targetClassName);
                $this->applyJoinsAndFilters($targetMetadata, $type, $join['filter'], $queryBuilder, $joinedAlias);
            }
        }
    }

    /**
     * Apply sorting to the query builder
     *
     * @param string $className
     * @param array $sorting
     * @param QueryBuilder $queryBuilder
     * @param string $alias
     */
    private function applySorting(string $className, array $sorting, QueryBuilder $queryBuilder, string $alias): void
    {
        foreach ($sorting as $sort) {
            $customSort = $this->sortingTypeFactory->getCustomSorting($className, $sort['field']);
            if ($customSort) {
                $customSort($queryBuilder, $sort['order']);
            } else {
                $queryBuilder->addOrderBy($alias . '.' . $sort['field'], $sort['order']);
            }
        }
    }

    /**
     * Apply DQL conditions on the query builder
     *
     * @param QueryBuilder $queryBuilder
     * @param array $conditions
     * @param array $dqlConditions
     */
    private function applyDqlConditions(QueryBuilder $queryBuilder, array $conditions, array $dqlConditions): void
    {
        if (!$dqlConditions) {
            return;
        }

        if ($conditions['fieldsLogic'] === 'AND') {
            $fieldsDql = $queryBuilder->expr()->andX(...$dqlConditions);
        } else {
            $fieldsDql = $queryBuilder->expr()->orX(...$dqlConditions);
        }

        if ($conditions['conditionLogic'] === 'AND') {
            $queryBuilder->andWhere($fieldsDql);
        } else {
            $queryBuilder->orWhere($fieldsDql);
        }
    }
}
