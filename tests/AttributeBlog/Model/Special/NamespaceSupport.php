<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\AttributeBlog\Model\Special;

use Doctrine\ORM\Mapping as ORM;
use GraphQLTests\Doctrine\AttributeBlog\Model\AbstractModel;

#[ORM\Entity]
final class NamespaceSupport extends AbstractModel
{
    private $value;

    /**
     * @return SelfSupport
     */
    public function getOtherModelViaPhpDoc()
    {
        return new SelfSupport();
    }

    /**
     * @param SelfSupport $value
     */
    public function setOtherModelViaPhpDoc($value): void
    {
        $this->value = $value;
    }
}
