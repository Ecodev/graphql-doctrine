<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Model\Special;

use Doctrine\ORM\Mapping as ORM;
use GraphQLTests\Doctrine\Blog\Enum\Status;
use GraphQLTests\Doctrine\Blog\Model\AbstractModel;

#[ORM\Entity]
final class EnumSupport extends AbstractModel
{
    #[ORM\Column(type: 'enum')]
    private Status $status;

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function setStatus(Status $status): void
    {
        $this->status = $status;
    }
}
