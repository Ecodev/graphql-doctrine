<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Sorting;

use Doctrine\ORM\QueryBuilder;

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
     * @param QueryBuilder $queryBuilder
     * @param string $order either 'ASC' or 'DESC'
     */
    public function __invoke(QueryBuilder $queryBuilder, string $order): void;
}
