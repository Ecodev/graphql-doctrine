<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Model\Special;

use Doctrine\ORM\Mapping as ORM;
use GraphQL\Doctrine\Attribute as API;
use GraphQLTests\Doctrine\Blog\Model\AbstractModel;

#[ORM\Entity]
/** @phpstan-ignore-next-line */
#[API\Filter(field: 'custom', operator: 'invalid_class_name', type: 'string')]
final class InvalidFilter extends AbstractModel
{
}
