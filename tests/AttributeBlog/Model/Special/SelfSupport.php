<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\AttributeBlog\Model\Special;

use Doctrine\ORM\Mapping as ORM;
use GraphQLTests\Doctrine\AttributeBlog\Model\AbstractModel;

#[ORM\Entity]
final class SelfSupport extends AbstractModel
{
    private $sibling;

    public function getSibling(): ?self
    {
        return $this->sibling;
    }

    public function setSibling(self $sibling): void
    {
        $this->sibling = $sibling;
    }

    /**
     * @return null|self
     */
    public function getSiblingViaPhpDoc()
    {
        return $this->sibling;
    }

    /**
     * @param self $sibling
     */
    public function setSiblingViaPhpDoc($sibling): void
    {
        $this->sibling = $sibling;
    }
}
