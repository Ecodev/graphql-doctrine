<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\AttributeBlog\Model\Special;

use Doctrine\ORM\Mapping as ORM;
use GraphQL\Doctrine\Annotation as API;
use GraphQLTests\Doctrine\AttributeBlog\Model\AbstractModel;

#[ORM\Entity]
final class IgnoredGetter extends AbstractModel
{
    private string $privateProperty = 'privateProperty';

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
     * @param string[] $arg3
     */
    #[API\Field(type: 'string[]')]
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
