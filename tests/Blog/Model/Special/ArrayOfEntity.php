<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Model\Special;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use GraphQL\Doctrine\Attribute as API;
use GraphQLTests\Doctrine\Blog\Model\AbstractModel;
use GraphQLTests\Doctrine\Blog\Model\User;

#[ORM\Entity]
final class ArrayOfEntity extends AbstractModel
{
    #[API\Field(type: 'GraphQLTests\Doctrine\Blog\Model\User[]')]
    public function getUsers(): array
    {
        return [new User(), new User()];
    }

    /**
     * @return Collection<int, User>
     */
    public function getOtherUsers(): Collection
    {
        return new ArrayCollection([new User(), new User()]);
    }
}
