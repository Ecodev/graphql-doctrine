<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine;

use GraphQL\Doctrine\Definition\Operator\AbstractOperator;
use GraphQL\Doctrine\Definition\Operator\BetweenOperatorType;
use GraphQL\Doctrine\Definition\Operator\ContainOperatorType;
use GraphQL\Doctrine\Definition\Operator\EmptyOperatorType;
use GraphQL\Doctrine\Definition\Operator\EqualOperatorType;
use GraphQL\Doctrine\Definition\Operator\GreaterOperatorType;
use GraphQL\Doctrine\Definition\Operator\GreaterOrEqualOperatorType;
use GraphQL\Doctrine\Definition\Operator\InOperatorType;
use GraphQL\Doctrine\Definition\Operator\LessOperatorType;
use GraphQL\Doctrine\Definition\Operator\LessOrEqualOperatorType;
use GraphQL\Doctrine\Definition\Operator\LikeOperatorType;
use GraphQL\Doctrine\Definition\Operator\NullOperatorType;
use GraphQL\Doctrine\Factory\UniqueNameFactory;
use GraphQL\Type\Definition\Type;
use GraphQLTests\Doctrine\Blog\Model\Post;

final class OperatorsTest extends \PHPUnit\Framework\TestCase
{
    use TypesTrait;

    /**
     * @dataProvider providerOperator
     *
     * @param string $expected
     * @param string $className
     * @param array $args
     */
    public function testOperator(?string $expected, string $className, ?array $args): void
    {
        /** @var AbstractOperator $operator */
        $operator = new $className($this->types, Type::string());
        $uniqueNameFactory = new UniqueNameFactory();
        $metadata = $this->entityManager->getClassMetadata(Post::class);
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $alias = 'alias';
        $field = 'field';

        $actual = $operator->getDqlCondition($uniqueNameFactory, $metadata, $queryBuilder, $alias, $field, $args);

        self::assertEquals($expected, $actual, 'DQL condition should match');
        if (is_string($expected)) {
            self::assertSame(mb_substr_count($expected, ':'), $queryBuilder->getParameters()->count(), 'should declare the same number of parameters that are actually used');
        }
    }

    public function providerOperator(): array
    {
        return [
            [
                null,
                BetweenOperatorType::class,
                null,
            ],
            ['alias.field BETWEEN :filter1 AND :filter2',
                BetweenOperatorType::class,
                [
                    'from' => 123,
                    'to' => 456,
                    'not' => false,
                ],
            ],
            [
                'alias.field NOT BETWEEN :filter1 AND :filter2',
                BetweenOperatorType::class,
                [
                    'from' => 123,
                    'to' => 456,
                    'not' => true,
                ],
            ],
            [
                null,
                ContainOperatorType::class,
                null,
            ],
            [
                ':filter1 MEMBER OF alias.field',
                ContainOperatorType::class,
                [
                    'values' => [123, 456],
                    'not' => false,
                ],
            ],
            [
                ':filter1 NOT MEMBER OF alias.field',
                ContainOperatorType::class,
                [
                    'values' => [123, 456],
                    'not' => true,
                ],
            ],
            [
                null,
                EmptyOperatorType::class,
                null,
            ],
            [
                'alias.field IS EMPTY',
                EmptyOperatorType::class,
                [
                    'not' => false,
                ],
            ],
            [
                'alias.field IS NOT EMPTY',
                EmptyOperatorType::class,
                [
                    'not' => true,
                ],
            ],
            [
                null,
                EqualOperatorType::class,
                null,
            ],
            [
                'alias.field = :filter1',
                EqualOperatorType::class,
                [
                    'value' => 123,
                    'not' => false,
                ],
            ],
            [
                'alias.field != :filter1',
                EqualOperatorType::class,
                [
                    'value' => 123,
                    'not' => true,
                ],
            ],
            [
                null,
                GreaterOperatorType::class,
                null,
            ],
            [
                'alias.field > :filter1',
                GreaterOperatorType::class,
                [
                    'value' => 123,
                    'not' => false,
                ],
            ],
            [
                'alias.field <= :filter1',
                GreaterOperatorType::class,
                [
                    'value' => 123,
                    'not' => true,
                ],
            ],
            [
                null,
                GreaterOrEqualOperatorType::class,
                null,
            ],
            [
                'alias.field >= :filter1',
                GreaterOrEqualOperatorType::class,
                [
                    'value' => 123,
                    'not' => false,
                ],
            ],
            [
                'alias.field < :filter1',
                GreaterOrEqualOperatorType::class,
                [
                    'value' => 123,
                    'not' => true,
                ],
            ],
            [
                null,
                InOperatorType::class,
                null,
            ],
            [
                'alias.field IN (:filter1)',
                InOperatorType::class,
                [
                    'values' => [123, 456],
                    'not' => false,
                ],
            ],
            [
                'alias.field NOT IN (:filter1)',
                InOperatorType::class,
                [
                    'values' => [123, 456],
                    'not' => true,
                ],
            ],
            [
                null,
                LessOperatorType::class,
                null,
            ],
            [
                'alias.field < :filter1',
                LessOperatorType::class,
                [
                    'value' => 123,
                    'not' => false,
                ],
            ],
            [
                'alias.field >= :filter1',
                LessOperatorType::class,
                [
                    'value' => 123,
                    'not' => true,
                ],
            ],
            [
                null,
                LessOrEqualOperatorType::class,
                null,
            ],
            [
                'alias.field <= :filter1',
                LessOrEqualOperatorType::class,
                [
                    'value' => 123,
                    'not' => false,
                ],
            ],
            [
                'alias.field > :filter1',
                LessOrEqualOperatorType::class,
                [
                    'value' => 123,
                    'not' => true,
                ],
            ],
            [
                null,
                LikeOperatorType::class,
                null,
            ],
            [
                'alias.field LIKE :filter1',
                LikeOperatorType::class,
                [
                    'value' => 123,
                    'not' => false,
                ],
            ],
            [
                'alias.field NOT LIKE :filter1',
                LikeOperatorType::class,
                [
                    'value' => 123,
                    'not' => true,
                ],
            ],
            [
                null,
                NullOperatorType::class,
                null,
            ],
            [
                'alias.field IS NULL',
                NullOperatorType::class,
                [
                    'value' => 123,
                    'not' => false,
                ],
            ],
            [
                'alias.field IS NOT NULL',
                NullOperatorType::class,
                [
                    'value' => 123,
                    'not' => true,
                ],
            ],
        ];
    }
}
