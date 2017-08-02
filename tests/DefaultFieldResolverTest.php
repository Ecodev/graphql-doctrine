<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine;

use GraphQL\Doctrine\DefaultFieldResolver;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQLTests\Doctrine\Blog\Model\Special\IgnoredGetter;

class DefaultFieldResolverTest extends \PHPUnit\Framework\TestCase
{
    public function providerDefaultFieldResolver(): array
    {
        return [
            [null, 'privateProperty'],
            [null, 'protectedProperty'],
            ['publicProperty', 'publicProperty'],
            [null, 'private'],
            [null, 'protected'],
            ['getPublic', 'public'],
            ['getPublicWithArgs(arg1, 123)', 'publicWithArgs', ['arg1', 123]],
            [null, 'nonExisting'],
            [null, '__call'],
            [true, 'isValid'],
            [true, 'hasMoney'],
        ];
    }

    /**
     * @dataProvider providerDefaultFieldResolver
     * @param mixed $expected
     */
    public function testDefaultFieldResolver($expected, string $fieldName, array $args = null): void
    {
        $object = new IgnoredGetter();

        $resolver = new DefaultFieldResolver();
        $info = new ResolveInfo(['fieldName' => $fieldName]);
        $actual = $resolver($object, $args, null, $info);
        $this->assertSame($expected, $actual);
    }

    public function testDefaultFieldResolverOnArray(): void
    {
        $array = [
            'foo' => 'bar',
        ];

        $resolver = new DefaultFieldResolver();
        $info = new ResolveInfo(['fieldName' => 'foo']);
        $actual = $resolver($array, null, null, $info);
        $this->assertSame('bar', $actual);
    }
}
