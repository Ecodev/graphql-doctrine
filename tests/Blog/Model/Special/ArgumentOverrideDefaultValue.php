<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Model\Special;

use Doctrine\ORM\Mapping as ORM;
use GraphQL\Doctrine\Attribute\Argument;
use GraphQLTests\Doctrine\Blog\Model\AbstractModel;

#[ORM\Entity]
final class ArgumentOverrideDefaultValue extends AbstractModel
{
    public function getWithParams(#[Argument(defaultValue: 2)] int $param = 1): string
    {
        return __FUNCTION__;
    }
}
