<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine;

use GraphQL\Doctrine\DocBlockReader;
use ReflectionMethod;
use ReflectionParameter;

class DocBlockReaderTest extends \PHPUnit\Framework\TestCase
{
    private const EMPTY_COMMENT = '
    /**
     */';

    private const COMMENT = '
    /**
     * Get interesting data
     *
     * Long description for the method
     * spanning lines
     *
     * @param null|string $foo Some param description
     * @param   User   $bar
     * @param \DateTimeImmutable $bazAbsolute
     * @param DateTimeImmutable $bazRelative
     * @return bool
     */';

    /**
     * @dataProvider providerGetMethodDescription
     */
    public function testGetMethodDescription(?string $comment, ?string $expected): void
    {
        $reader = $this->create($comment);
        $actual = $reader->getMethodDescription();
        static::assertSame($expected, $actual);
    }

    public function providerGetMethodDescription(): array
    {
        return [
            [null, null],
            [self::EMPTY_COMMENT, null],
            [self::COMMENT, 'Interesting data

Long description for the method
spanning lines'],
        ];
    }

    /**
     * @dataProvider providerGetParameterDescription
     */
    public function testGetParameterDescription(?string $comment, string $parameterName, ?string $expected): void
    {
        $reader = $this->create($comment);
        $parameter = $this->createParameter($parameterName);
        $actual = $reader->getParameterDescription($parameter);
        static::assertSame($expected, $actual);
    }

    public function providerGetParameterDescription(): array
    {
        return [
            [null, 'foo', null],
            [self::EMPTY_COMMENT, 'foo', null],
            [self::COMMENT, 'foo', 'Some param description'],
            'non-existing param' => [self::COMMENT, 'fo', null],
            [self::COMMENT, 'bar', null],
        ];
    }

    /**
     * @dataProvider providerGetParameterType
     */
    public function testGetParameterType(?string $comment, string $parameterName, ?string $expected): void
    {
        $reader = $this->create($comment);
        $parameter = $this->createParameter($parameterName);
        $actual = $reader->getParameterType($parameter);
        static::assertSame($expected, $actual);
    }

    public function providerGetParameterType(): array
    {
        return [
            [null, 'foo', null],
            [self::EMPTY_COMMENT, 'foo', null],
            [self::COMMENT, 'foo', 'null|string'],
            'non-existing param' => [self::COMMENT, 'fo', null],
            [self::COMMENT, 'bar', 'User'],
            'do not make assumption on absolute types' => [self::COMMENT, 'bazAbsolute', '\DateTimeImmutable'],
            'do not make assumption on relative types' => [self::COMMENT, 'bazRelative', 'DateTimeImmutable'],
        ];
    }

    /**
     * @dataProvider providerGetReturnType
     */
    public function testGetReturnType(?string $comment, ?string $expected): void
    {
        $reader = $this->create($comment);
        $actual = $reader->getReturnType();
        static::assertSame($expected, $actual);
    }

    public function providerGetReturnType(): array
    {
        return [
            [null, null],
            [self::EMPTY_COMMENT, null],
            [self::COMMENT, 'bool'],
        ];
    }

    private function create(?string $comment): DocBlockReader
    {
        $method = new class($comment) extends ReflectionMethod {
            /**
             * @var null|string
             */
            private $comment;

            public function __construct(?string $comment)
            {
                $this->comment = $comment;
            }

            public function getDocComment(): ?string
            {
                return $this->comment;
            }
        };

        return new DocBlockReader($method);
    }

    private function createParameter(string $name): ReflectionParameter
    {
        return new class($name) extends ReflectionParameter {
            /**
             * @var string
             */
            public $mockedName;

            public function __construct(string $name)
            {
                $this->mockedName = $name;
            }

            public function getName(): string
            {
                return $this->mockedName;
            }
        };
    }
}
