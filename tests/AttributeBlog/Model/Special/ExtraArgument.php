<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\AttributeBlog\Model\Special;

use Doctrine\ORM\Mapping as ORM;
use GraphQL\Doctrine\Annotation as API;
use GraphQLTests\Doctrine\AttributeBlog\Model\AbstractModel;

#[ORM\Entity]
final class ExtraArgument extends AbstractModel
{
    #[API\Field(args: [new API\Argument(name: 'misspelled_name')])]
    public function getWithParams(string $arg1): string
    {
        return __FUNCTION__;
    }
}
