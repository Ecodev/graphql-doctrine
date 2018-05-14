<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Sorting;

use Doctrine\ORM\QueryBuilder;
use GraphQL\Doctrine\Sorting\SortingInterface;

final class UserName implements SortingInterface
{
    public function __construct()
    {
    }

    public function __invoke(QueryBuilder $queryBuilder, string $order): void
    {
        $alias = $queryBuilder->getRootAliases()[0];

        $queryBuilder->join($alias . '.user', 'sortingUser');
        $queryBuilder->addOrderBy('sortingUser.name', $order);
    }
}
