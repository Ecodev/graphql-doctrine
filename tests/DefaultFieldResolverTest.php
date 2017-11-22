<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine;

use GraphQL\Doctrine\DefaultFieldResolver;
use GraphQL\Doctrine\Definition\EntityID;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQLTests\Doctrine\Blog\Model\Special\IgnoredGetter;

class DefaultFieldResolverTest extends \PHPUnit\Framework\TestCase
{
    public function providerDefaultFieldResolver(): array
    {
        $entityID = new class() extends EntityID {
            public function __construct()
            {
            }

            public function getEntity()
            {
                return 'real entity';
            }
        };

        return [
            [null, 'privateProperty'],
            [null, 'protectedProperty'],
            ['publicProperty', 'publicProperty'],
            [null, 'private'],
            [null, 'protected'],
            ['getPublic', 'public'],
            [['real entity', 2, ['foo']], 'publicWithArgs', ['arg2' => 2, 'arg1' => $entityID]],
            [null, 'nonExisting'],
            [null, '__call'],
            [true, 'isValid'],
            [true, 'hasMoney'],
        ];
    }

    /**
     * @dataProvider providerDefaultFieldResolver
     *
     * @param mixed $expected
     * @param string $fieldName
     * @param null|array $args
     */
    public function testDefaultFieldResolver($expected, string $fieldName, ?array $args = null): void
    {
        $object = new IgnoredGetter();

        $resolver = new DefaultFieldResolver();
        $info = new ResolveInfo(['fieldName' => $fieldName]);
        $actual = $resolver($object, $args, null, $info);
        self::assertSame($expected, $actual);
    }

    public function testDefaultFieldResolverOnArray(): void
    {
        $array = [
            'foo' => 'bar',
        ];

        $resolver = new DefaultFieldResolver();
        $info = new ResolveInfo(['fieldName' => 'foo']);
        $actual = $resolver($array, null, null, $info);
        self::assertSame('bar', $actual);
    }
}
