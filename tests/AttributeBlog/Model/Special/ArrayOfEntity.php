<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\AttributeBlog\Model\Special;

use Doctrine\ORM\Mapping as ORM;
use GraphQL\Doctrine\Annotation as API;
use GraphQLTests\Doctrine\AttributeBlog\Model\AbstractModel;
use GraphQLTests\Doctrine\AttributeBlog\Model\User;

#[ORM\Entity]
final class ArrayOfEntity extends AbstractModel
{
    /**
     * @API\Field(type="GraphQLTests\Doctrine\AttributeBlog\Model\User[]")
     */
    public function getUsers(): array
    {
        return [new User(), new User()];
    }
}
