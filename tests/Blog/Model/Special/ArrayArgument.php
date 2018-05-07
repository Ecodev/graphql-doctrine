<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Model\Special;

use Doctrine\ORM\Mapping as ORM;
use GraphQLTests\Doctrine\Blog\Model\AbstractModel;

/**
 * @ORM\Entity
 */
final class ArrayArgument extends AbstractModel
{
    /**
     * @param array $arg1
     *
     * @return string
     */
    public function getWithParams(array $arg1): string
    {
        return __FUNCTION__;
    }
}
