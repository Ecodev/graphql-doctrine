<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Model\Special;

use Doctrine\ORM\Mapping as ORM;

/**
 * This is intended to be invalid for graphql-doctrine because it has composite identifiers.
 *
 * @ORM\Entity
 */
final class CompositeIdentifier
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     */
    private int $id1;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     */
    private int $id2;
}
