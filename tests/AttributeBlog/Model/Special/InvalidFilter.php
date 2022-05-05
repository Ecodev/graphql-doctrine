<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\AttributeBlog\Model\Special;

use Doctrine\ORM\Mapping as ORM;
use GraphQL\Doctrine\Annotation as API;
use GraphQLTests\Doctrine\AttributeBlog\Model\AbstractModel;

#[ORM\Entity]
#[API\Filters([new API\Filter(field: 'custom', operator: 'invalid_class_name', type: 'string')])]
final class InvalidFilter extends AbstractModel
{
}
