<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Model\Special;

use Doctrine\ORM\Mapping as ORM;
use GraphQLTests\Doctrine\Blog\Model\AbstractModel;

/**
 * @ORM\Entity
 */
class IgnoredGetter extends AbstractModel
{
    private $privateProperty = 'privateProperty';
    protected $protectedProperty = 'protectedProperty';
    public $publicProperty = 'publicProperty';

    private function getPrivate(): string
    {
        return __FUNCTION__;
    }

    protected function getProtected(): string
    {
        return __FUNCTION__;
    }

    public function getPublic(): string
    {
        return __FUNCTION__;
    }

    public function getPublicWithArgs(string $arg1, int $arg2): string
    {
        return __FUNCTION__ . '(' . implode(', ', func_get_args()) . ')';
    }

    public function __call($name, $arguments): string
    {
        return __FUNCTION__;
    }

    public static function getSaticPublic(): string
    {
        return __FUNCTION__;
    }

    public function isValid(): bool
    {
        return true;
    }

    public function hasMoney(): bool
    {
        return true;
    }
}
