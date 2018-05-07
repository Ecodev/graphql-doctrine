<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine;

use GraphQLTests\Doctrine\Blog\Model\Post;
use GraphQLTests\Doctrine\Blog\Model\User;

final class OutputTypesTest extends \PHPUnit\Framework\TestCase
{
    use TypesTrait;

    public function testCanGetTypesWithBackslashPrefix(): void
    {
        $type = $this->types->getOutput(Post::class);
        self::assertSame($type, $this->types->getOutput('\\' . Post::class));
    }

    public function testCanGetOutputTypes(): void
    {
        $userType = $this->types->getOutput(User::class);

        $this->assertType('tests/data/UserOutput.graphqls', $userType);
        self::assertSame($userType, $this->types->getOutput(User::class), 'must returns the same instance of user type');

        $postType = $this->types->getOutput(Post::class);
        $this->assertType('tests/data/PostOutput.graphqls', $postType);
        self::assertSame($postType, $this->types->getOutput(Post::class), 'must returns the same instance of post type');
    }

    public function testNonPublicGetterMustBeIgnored(): void
    {
        $actual = $this->types->getOutput(Blog\Model\Special\IgnoredGetter::class);
        $this->assertType('tests/data/IgnoredGetter.graphqls', $actual);
    }

    public function testCanDeclareArrayOfEntity(): void
    {
        $actual = $this->types->getOutput(Blog\Model\Special\ArrayOfEntity::class);
        $this->assertType('tests/data/ArrayOfEntity.graphqls', $actual);
    }

    public function testDefaultValuesOutput(): void
    {
        $actual = $this->types->getOutput(Blog\Model\Special\DefaultValue::class);
        $this->assertType('tests/data/DefaultValue.graphqls', $actual);
    }

    public function testFieldWithoutTypeMustThrow(): void
    {
        $this->expectExceptionMessage('Could not find type for method `GraphQLTests\Doctrine\Blog\Model\Special\NoType::getWithoutTypeHint()`. Either type hint the return value, or specify the type with `@API\Field` annotation.');
        $type = $this->types->getOutput(Blog\Model\Special\NoType::class);
        $type->getFields();
    }

    public function testFieldReturningCollectionWithoutTypeMustThrow(): void
    {
        $this->expectExceptionMessage('The method `GraphQLTests\Doctrine\Blog\Model\Special\NoTypeCollection::getFoos()` is type hinted with a return type of `Doctrine\Common\Collections\Collection`, but the entity contained in that collection could not be automatically detected. Either fix the type hint, fix the doctrine mapping, or specify the type with `@API\Field` annotation.');
        $type = $this->types->getOutput(Blog\Model\Special\NoTypeCollection::class);
        $type->getFields();
    }

    public function testCannotGetInvalidType(): void
    {
        $this->expectExceptionMessage('Given class name `DateTimeImmutable` is not a Doctrine entity. Either register a custom GraphQL type for `DateTimeImmutable` when instantiating `GraphQL\Doctrine\Types`, or change the usage of that class to something else.');
        $this->types->getOutput(\DateTimeImmutable::class);
    }

    public function testArgumentWithoutTypeMustThrow(): void
    {
        $this->expectExceptionMessage('Could not find type for parameter `$bar` for method `GraphQLTests\Doctrine\Blog\Model\Special\NoTypeArgument::getFoo()`. Either type hint the parameter, or specify the type with `@API\Argument` annotation.');
        $type = $this->types->getOutput(Blog\Model\Special\NoTypeArgument::class);
        $type->getFields();
    }

    public function testFieldWithExtraArgumentMustThrow(): void
    {
        $this->expectExceptionMessage('The following arguments were declared via `@API\Argument` annotation but do not match actual parameter names on method `GraphQLTests\Doctrine\Blog\Model\Special\ExtraArgument::getWithParams()`. Either rename or remove the annotations: misspelled_name');
        $type = $this->types->getOutput(Blog\Model\Special\ExtraArgument::class);
        $type->getFields();
    }

    public function testFieldWithArrayArgumentMustThrow(): void
    {
        $this->expectExceptionMessage('The parameter `$arg1` on method `GraphQLTests\Doctrine\Blog\Model\Special\ArrayArgument::getWithParams()` is type hinted as `array` and is not overridden via `@API\Argument` annotation. Either change the type hint or specify the type with `@API\Argument` annotation.');
        $type = $this->types->getOutput(Blog\Model\Special\ArrayArgument::class);
        $type->getFields();
    }

    public function testFieldWithObjectTypeArgumentMustThrow(): void
    {
        $this->expectExceptionMessage('Type for parameter `$user` for method `GraphQLTests\Doctrine\Blog\Model\Special\ObjectTypeArgument::getWithParams()` must be an instance of `GraphQL\Type\Definition\InputType`, but was `GraphQL\Type\Definition\ObjectType`. Use `@API\Argument` annotation to specify a custom InputType.');
        $type = $this->types->getOutput(Blog\Model\Special\ObjectTypeArgument::class);
        $type->getFields();
    }
}
