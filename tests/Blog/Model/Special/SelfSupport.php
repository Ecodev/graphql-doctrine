<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Model\Special;

use Doctrine\ORM\Mapping as ORM;
use GraphQLTests\Doctrine\Blog\Model\AbstractModel;

/**
 * @ORM\Entity
 */
final class SelfSupport extends AbstractModel
{
    private $sibling;

    /**
     * @return null|self
     */
    public function getSibling(): ?self
    {
        return $this->sibling;
    }

    /**
     * @param self $sibling
     */
    public function setSibling(self $sibling): void
    {
        $this->sibling = $sibling;
    }
}
