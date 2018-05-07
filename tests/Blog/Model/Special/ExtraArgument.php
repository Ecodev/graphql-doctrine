<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Model\Special;

use Doctrine\ORM\Mapping as ORM;
use GraphQL\Doctrine\Annotation as API;
use GraphQLTests\Doctrine\Blog\Model\AbstractModel;

/**
 * @ORM\Entity
 */
final class ExtraArgument extends AbstractModel
{
    /**
     * @API\Field(args={@API\Argument(name="misspelled_name")})
     *
     * @param string $arg1
     *
     * @return string
     */
    public function getWithParams(string $arg1): string
    {
        return __FUNCTION__;
    }
}
