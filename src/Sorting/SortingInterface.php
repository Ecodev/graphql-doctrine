<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Sorting;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use GraphQL\Doctrine\Factory\UniqueNameFactory;

/**
 * Interface that must be implemented to define custom sorting options.
 *
 * Once implemented its FQCN should be used via `API\Sorting` annotation.
 */
interface SortingInterface
{
    public function __construct();

    /**
     * Apply the sorting option to the query builder.
     *
     * This might be as simple as calling `addOrderBy()` method, or might also add JOIN if needed.
     * This method should be careful to never override the query builder state, but only add to it.
     *
     * @param UniqueNameFactory $uniqueNameFactory a helper to get unique names to be used in the query
     * @param string $alias the alias for the entity on which to apply the sorting
     * @param string $order either 'ASC' or 'DESC'
     */
    public function __invoke(UniqueNameFactory $uniqueNameFactory, ClassMetadata $metadata, QueryBuilder $queryBuilder, string $alias, string $order): void;
}
