<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use GraphQL\Doctrine\Attribute as API;
use GraphQLTests\Doctrine\Blog\Filtering\ModuloOperatorType;
use GraphQLTests\Doctrine\Blog\Sorting\PseudoRandom;
use GraphQLTests\Doctrine\Blog\Types\DateTimeType;

/**
 * Base class for all objects stored in database.
 */
#[ORM\MappedSuperclass]
#[API\Sorting(PseudoRandom::class)]
#[API\Filter(field: 'id', operator: ModuloOperatorType::class, type: 'int')]
abstract class AbstractModel
{
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    protected ?int $id = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private $creationDate;

    public function getId(): int
    {
        return $this->id;
    }

    #[API\Field(type: DateTimeType::class)]
    public function getCreationDate(): DateTimeImmutable
    {
        return $this->creationDate;
    }

    #[API\Input(type: DateTimeType::class)]
    public function setCreationDate(DateTimeImmutable $creationDate): void
    {
        $this->creationDate = $creationDate;
    }
}
