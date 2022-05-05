<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Model\Special;

use GraphQL\Doctrine\Attribute as API;
use GraphQLTests\Doctrine\Blog\Filtering\SearchOperatorType;
use GraphQLTests\Doctrine\Blog\Sorting\UserName;

#[API\Sorting(UserName::class)]
#[API\Filter(field: 'customFromTrait', operator: SearchOperatorType::class, type: 'string')]
trait TraitWithSortingAndFilter
{
}
