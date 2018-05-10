<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Model;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use GraphQL\Doctrine\Annotation as API;

/**
 * Base class for all objects stored in database.
 *
 * @ORM\MappedSuperclass
 * @API\Sorting({"GraphQLTests\Doctrine\Blog\Sorting\PseudoRandom"})
 * @API\Filters({
 *     @API\Filter(field="id", operator="GraphQLTests\Doctrine\Blog\Filtering\ModuloOperatorType", type="int"),
 * })
 */
abstract class AbstractModel
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer", options={"unsigned" = true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $creationDate;

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @API\Field(type="GraphQLTests\Doctrine\Blog\Types\DateTimeType")
     */
    public function getCreationDate(): DateTime
    {
        return $this->creationDate;
    }

    /**
     * @API\Input(type="GraphQLTests\Doctrine\Blog\Types\DateTimeType")
     */
    public function setCreationDate(DateTime $creationDate): void
    {
        $this->creationDate = $creationDate;
    }
}
