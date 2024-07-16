<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Model\Special;

use Doctrine\ORM\Mapping as ORM;
use GraphQL\Doctrine\Attribute as API;
use GraphQLTests\Doctrine\Blog\Model\AbstractModel;

#[ORM\Entity]
final class InputWithId extends AbstractModel
{
    #[API\Input(type: 'ID[]')]
    public function setIds(array $ids): void
    {
    }
}
