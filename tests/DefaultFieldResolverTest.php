<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine;

use GraphQL\Doctrine\DefaultFieldResolver;
use GraphQL\Doctrine\Definition\EntityID;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQLTests\Doctrine\Blog\Model\Special\DefaultValue;
use GraphQLTests\Doctrine\Blog\Model\Special\IgnoredGetter;
use GraphQLTests\Doctrine\Blog\Model\User;
use PHPUnit\Framework\TestCase;

final class DefaultFieldResolverTest extends TestCase
{
    public function providerDefaultFieldResolver(): array
    {
        $fakeEntity = new User();
        $entityID = new class($fakeEntity) extends EntityID {
            public function __construct(private readonly User $fakeEntity)
            {
            }

            public function getEntity(): User
            {
                return $this->fakeEntity;
            }
        };

        return [
            [null, new IgnoredGetter(), 'privateProperty'],
            [null, new IgnoredGetter(), 'protectedProperty'],
            ['publicProperty', new IgnoredGetter(), 'publicProperty'],
            [null, new IgnoredGetter(), 'private'],
            [null, new IgnoredGetter(), 'protected'],
            ['getPublic', new IgnoredGetter(), 'public'],
            [[$fakeEntity, 2, ['foo']], new IgnoredGetter(), 'publicWithArgs', ['arg2' => 2, 'arg1' => $entityID]],
            [null, new IgnoredGetter(), 'nonExisting'],
            [null, new IgnoredGetter(), '__call'],
            [true, new IgnoredGetter(), 'isValid'],
            [true, new IgnoredGetter(), 'hasMoney'],
            ['john', new DefaultValue(), 'nameWithDefaultValueOnArgument'],
            ['jane', new DefaultValue(), 'nameWithDefaultValueOnArgument', ['name' => 'jane']],
            ['bar', ['foo' => 'bar'], 'foo'],
        ];
    }

    /**
     * @dataProvider providerDefaultFieldResolver
     *
     * @param mixed $expected
     * @param array|object $source
     */
    public function testDefaultFieldResolver($expected, $source, string $fieldName, array $args = []): void
    {
        $resolver = new DefaultFieldResolver();
        $fieldDefinition = FieldDefinition::create(['name' => $fieldName, 'type' => Type::boolean()]);
        $info = new ResolveInfo($fieldDefinition, [], new ObjectType(['name' => 'foo']), [], new Schema([]), [], null, null, []);
        $actual = $resolver($source, $args, null, $info);
        self::assertSame($expected, $actual);
    }
}
