<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine;

use GraphQLTests\Doctrine\Blog\Model\Post;
use GraphQLTests\Doctrine\Blog\Model\User;
use PHPUnit\Framework\TestCase;

final class InputTypesTest extends TestCase
{
    use TypesTrait;

    public function testCanGetInputTypes(): void
    {
        $userType = $this->types->getInput(User::class);
        $this->assertType('tests/data/UserInput.graphqls', $userType);
        self::assertSame($userType, $this->types->getInput(User::class), 'must returns the same instance of user type');

        $postType = $this->types->getInput(Post::class);
        $this->assertType('tests/data/PostInput.graphqls', $postType);
        self::assertSame($postType, $this->types->getInput(Post::class), 'must returns the same instance of post type');
    }

    public function testDefaultValuesInput(): void
    {
        $actual = $this->types->getInput(Blog\Model\Special\DefaultValue::class);
        $this->assertType('tests/data/DefaultValueInput.graphqls', $actual);
    }

    public function testDefaultValuesPartialInput(): void
    {
        $actual = $this->types->getPartialInput(Blog\Model\Special\DefaultValue::class);
        $this->assertType('tests/data/DefaultValuePartialInput.graphqls', $actual);
    }

    public function testSelfSupportInput(): void
    {
        $actual = $this->types->getInput(Blog\Model\Special\SelfSupport::class);
        $this->assertType('tests/data/SelfSupportInput.graphqls', $actual);
    }

    public function testEnumSupportInput(): void
    {
        $actual = $this->types->getInput(Blog\Model\Special\EnumSupport::class);
        $this->assertType('tests/data/EnumSupportInput.graphqls', $actual);
    }

    public function testNamespaceSupportInput(): void
    {
        $actual = $this->types->getInput(Blog\Model\Special\NamespaceSupport::class);
        $this->assertType('tests/data/NamespaceSupportInput.graphqls', $actual);
    }

    public function testInputWithId(): void
    {
        $actual = $this->types->getInput(Blog\Model\Special\InputWithId::class);
        $this->assertType('tests/data/InputWithId.graphqls', $actual);
    }

    public function testInputWithoutTypeMustThrow(): void
    {
        $type = $this->types->getInput(Blog\Model\Special\NoTypeInput::class);

        $this->expectExceptionMessage('Could not find type for parameter `$bar` for method `GraphQLTests\Doctrine\Blog\Model\Special\NoTypeInput::setFoo()`. Either type hint the parameter, or specify the type with `#[API\Input]` attribute.');
        $type->getFields();
    }
}
