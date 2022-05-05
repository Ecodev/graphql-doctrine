<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\AttributeBlog\Model\Special;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use GraphQLTests\Doctrine\AttributeBlog\Model\AbstractModel;

#[ORM\Entity]
final class NoTypeCollection extends AbstractModel
{
    public function getFoos(): Collection
    {
        return new ArrayCollection();
    }
}
