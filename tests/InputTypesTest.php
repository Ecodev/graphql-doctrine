<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine;

use GraphQLTests\Doctrine\Blog\Model\Post;
use GraphQLTests\Doctrine\Blog\Model\User;

class InputTypesTest extends \PHPUnit\Framework\TestCase
{
    use TypesTrait;

    public function testCanGetInputTypes(): void
    {
        $userType = $this->types->getInput(User::class);
        $this->assertInputType('data/UserInput.php', $userType);
        self::assertSame($userType, $this->types->getInput(User::class), 'must returns the same instance of user type');

        $postType = $this->types->getInput(Post::class);
        $this->assertInputType('data/PostInput.php', $postType);
        self::assertSame($postType, $this->types->getInput(Post::class), 'must returns the same instance of post type');
    }

    public function testDefaultValuesInput(): void
    {
        $actual = $this->types->getInput(Blog\Model\Special\DefaultValue::class);
        $this->assertInputType('data/DefaultValueInput.php', $actual);
    }

    public function testDefaultValuesPartialInput(): void
    {
        $actual = $this->types->getPartialInput(Blog\Model\Special\DefaultValue::class);
        $this->assertInputType('data/DefaultValuePartialInput.php', $actual);
    }

    public function testInputWithoutTypeMustThrow(): void
    {
        $this->expectExceptionMessage('Could not find type for parameter `$bar` for method `GraphQLTests\Doctrine\Blog\Model\Special\NoTypeInput::setFoo()`. Either type hint the parameter, or specify the type with `@API\Input` annotation.');
        $type = $this->types->getInput(Blog\Model\Special\NoTypeInput::class);
        $type->getFields();
    }
}
