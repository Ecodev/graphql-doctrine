<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Model\Special;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use GraphQLTests\Doctrine\Blog\Model\AbstractModel;

/**
 * @ORM\Entity
 */
class NoTypeCollection extends AbstractModel
{
    public function getFoos(): Collection
    {
        return __FUNCTION__;
    }
}
