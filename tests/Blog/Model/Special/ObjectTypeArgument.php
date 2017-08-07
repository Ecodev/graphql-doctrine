<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Model\Special;

use Doctrine\ORM\Mapping as ORM;
use GraphQLTests\Doctrine\Blog\Model\AbstractModel;
use GraphQLTests\Doctrine\Blog\Model\User;

/**
 * @ORM\Entity
 */
class ObjectTypeArgument extends AbstractModel
{
    public function getWithParams(User $user): string
    {
        return __FUNCTION__;
    }
}
