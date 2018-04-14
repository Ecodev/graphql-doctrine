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
     *
     * @param string $input
     * @param string $expected
     */
    public function testGetTypeName(string $input, string $expected): void
    {
        self::assertSame($expected, Utils::getTypeName($input));
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
     *
     * @param string $input
     * @param string $expected
     */
    public function testGetIDTypeName(string $input, string $expected): void
    {
        self::assertSame($expected, Utils::getIDTypeName($input));
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
     * @param string $input
     * @param string $expected
     */
    public function testGetInputTypeName(string $input, string $expected): void
    {
        self::assertSame($expected, Utils::getInputTypeName($input));
    }

    public function providerGetPartialInputTypeName(): array
    {
        return [
            ['\Blog\Model\Post', 'PostPartialInput'],
            ['Blog\Model\Post', 'PostPartialInput'],
            ['\Post', 'PostPartialInput'],
            ['Post', 'PostPartialInput'],
        ];
    }

    /**
     * @dataProvider providerGetPartialInputTypeName
     *
     * @param string $PartialInput
     * @param string $expected
     */
    public function testGetPartialInputTypeName(string $PartialInput, string $expected): void
    {
        self::assertSame($expected, Utils::getPartialInputTypeName($PartialInput));
    }
}
