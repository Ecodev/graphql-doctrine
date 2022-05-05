<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\AttributeBlog\Model\Special;

use Doctrine\ORM\Mapping as ORM;
use GraphQL\Doctrine\Annotation as API;
use GraphQLTests\Doctrine\AttributeBlog\Model\AbstractModel;

#[ORM\Entity]
final class InvalidFilterGroupCondition extends AbstractModel
{
    #[API\FilterGroupCondition(type: '?GraphQLTests\Doctrine\AttributeBlog\Model\Post')]
    #[ORM\Column(type: 'decimal')]
    private $foo;
}
