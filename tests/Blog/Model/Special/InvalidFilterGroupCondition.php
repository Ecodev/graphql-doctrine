<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Model\Special;

use Doctrine\ORM\Mapping as ORM;
use GraphQL\Doctrine\Annotation as API;
use GraphQLTests\Doctrine\Blog\Model\AbstractModel;

/**
 * @ORM\Entity
 */
final class InvalidFilterGroupCondition extends AbstractModel
{
    /**
     * @API\FilterGroupCondition(type="?GraphQLTests\Doctrine\Blog\Model\Post")
     * @ORM\Column(type="decimal")
     */
    private $foo;
}
