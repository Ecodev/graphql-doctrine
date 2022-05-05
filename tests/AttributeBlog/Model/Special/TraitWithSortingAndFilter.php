<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\AttributeBlog\Model\Special;

use GraphQL\Doctrine\Annotation as API;
use GraphQLTests\Doctrine\Blog\Filtering\SearchOperatorType;
use GraphQLTests\Doctrine\Blog\Sorting\UserName;

#[API\Sorting([UserName::class])]
#[API\Filters([new API\Filter(field: 'customFromTrait', operator: SearchOperatorType::class, type: 'string')])]
trait TraitWithSortingAndFilter
{
}
