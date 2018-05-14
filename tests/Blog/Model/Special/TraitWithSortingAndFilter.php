<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Model\Special;

use GraphQL\Doctrine\Annotation as API;

/**
 * @API\Sorting({"GraphQLTests\Doctrine\Blog\Sorting\UserName"})
 * @API\Filters({
 *     @API\Filter(field="customFromTrait", operator="GraphQLTests\Doctrine\Blog\Filtering\SearchOperatorType", type="string"),
 * })
 */
trait TraitWithSortingAndFilter
{
}
