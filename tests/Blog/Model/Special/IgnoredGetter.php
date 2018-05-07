<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Blog\Model\Special;

use Doctrine\ORM\Mapping as ORM;
use GraphQL\Doctrine\Annotation as API;
use GraphQLTests\Doctrine\Blog\Model\AbstractModel;

/**
 * @ORM\Entity
 */
final class IgnoredGetter extends AbstractModel
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

    /**
     * @API\Field(type="string[]")
     *
     * @param string $arg1
     * @param int $arg2
     * @param string[] $arg3
     *
     * @return array
     */
    public function getPublicWithArgs(string $arg1, int $arg2, array $arg3 = ['foo']): array
    {
        return [$arg1, $arg2, $arg3];
    }

    public function __call($name, $arguments): string
    {
        return __FUNCTION__;
    }

    public static function getStaticPublic(): string
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
