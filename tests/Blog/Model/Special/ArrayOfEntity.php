<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Model\Special;

use Doctrine\ORM\Mapping as ORM;
use GraphQL\Doctrine\Annotation as API;
use GraphQLTests\Doctrine\Blog\Model\AbstractModel;
use GraphQLTests\Doctrine\Blog\Model\User;

/**
 * @ORM\Entity
 */
final class ArrayOfEntity extends AbstractModel
{
    /**
     * @API\Field(type="GraphQLTests\Doctrine\Blog\Model\User[]")
     */
    public function getUsers(): array
    {
        return [new User(), new User()];
    }
}
