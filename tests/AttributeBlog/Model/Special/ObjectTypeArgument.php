<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\AttributeBlog\Model\Special;

use Doctrine\ORM\Mapping as ORM;
use GraphQL\Doctrine\Annotation as API;
use GraphQLTests\Doctrine\AttributeBlog\Model\AbstractModel;
use GraphQLTests\Doctrine\AttributeBlog\Model\User;

#[ORM\Entity]
final class ObjectTypeArgument extends AbstractModel
{
    /**
     * This is an incorrect annotation, it should be entirely deleted to let the
     * system auto-create an input type matching the entity.
     */
    #[API\Field(args: [new API\Argument(name: 'user', type: User::class)])]
    public function getWithParams(User $user): string
    {
        return __FUNCTION__;
    }
}
