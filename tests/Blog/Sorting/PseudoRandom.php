<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Sorting;

use Doctrine\ORM\QueryBuilder;
use GraphQL\Doctrine\Sorting\SortingInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use GraphQL\Doctrine\Factory\UniqueNameFactory;

final class PseudoRandom implements SortingInterface
{
    public function __construct()
    {
    }

    public function __invoke(UniqueNameFactory $uniqueNameFactory, ClassMetadata $metadata, QueryBuilder $queryBuilder, string $alias, string $order): void
    {
        $queryBuilder->addSelect('MOD(' . $alias . '.id, 5) AS HIDDEN score');
        $queryBuilder->addOrderBy('score', $order);
    }
}
