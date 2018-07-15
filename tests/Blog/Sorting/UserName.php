<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Sorting;

use Doctrine\ORM\QueryBuilder;
use GraphQL\Doctrine\Sorting\SortingInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use GraphQL\Doctrine\Factory\UniqueNameFactory;

final class UserName implements SortingInterface
{
    public function __construct()
    {
    }

    public function __invoke(UniqueNameFactory $uniqueNameFactory, ClassMetadata $metadata, QueryBuilder $queryBuilder, string $alias, string $order): void
    {
        $queryBuilder->join($alias . '.user', 'sortingUser');
        $queryBuilder->addOrderBy('sortingUser.name', $order);
    }
}
