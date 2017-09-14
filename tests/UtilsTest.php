<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine;

use GraphQL\Doctrine\Utils;

class UtilsTest extends \PHPUnit\Framework\TestCase
{
    public function providerGetTypeName(): array
    {
        return [
            ['\Blog\Model\Post', 'Post'],
            ['Blog\Model\Post', 'Post'],
            ['\Post', 'Post'],
            ['Post', 'Post'],
        ];
    }

    /**
     * @dataProvider providerGetTypeName
     */
    public function testGetTypeName(string $input, string $expected): void
    {
        $this->assertSame($expected, Utils::getTypeName($input));
    }

    public function providerGetIDTypeName(): array
    {
        return [
            ['\Blog\Model\Post', 'PostID'],
            ['Blog\Model\Post', 'PostID'],
            ['\Post', 'PostID'],
            ['Post', 'PostID'],
        ];
    }

    /**
     * @dataProvider providerGetIDTypeName
     */
    public function testGetIDTypeName(string $input, string $expected): void
    {
        $this->assertSame($expected, Utils::getIDTypeName($input));
    }

    public function providerGetInputTypeName(): array
    {
        return [
            ['\Blog\Model\Post', 'PostInput'],
            ['Blog\Model\Post', 'PostInput'],
            ['\Post', 'PostInput'],
            ['Post', 'PostInput'],
        ];
    }

    /**
     * @dataProvider providerGetInputTypeName
     *
     * @return voInput
     */
    public function testGetInputTypeName(string $input, string $expected): void
    {
        $this->assertSame($expected, Utils::getInputTypeName($input));
    }
}
