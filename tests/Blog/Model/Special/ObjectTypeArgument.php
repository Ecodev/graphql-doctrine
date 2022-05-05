<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Model\Special;

use Doctrine\ORM\Mapping as ORM;
use GraphQL\Doctrine\Attribute as API;
use GraphQLTests\Doctrine\Blog\Model\AbstractModel;
use GraphQLTests\Doctrine\Blog\Model\User;

#[ORM\Entity]
final class ObjectTypeArgument extends AbstractModel
{
    /**
     * This is an incorrect attribute, it should be entirely deleted to let the
     * system auto-create an input type matching the entity.
     */
    public function getWithParams(#[API\Argument(type: User::class)] User $user): string
    {
        return __FUNCTION__;
    }
}
