<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Model\Special;

use Doctrine\ORM\Mapping as ORM;

/**
 * This is intended to be invalid for graphql-doctrine because it has composite identifiers.
 */
#[ORM\Entity]
final class CompositeIdentifier
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    private int $id1;

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    private int $id2;
}
