<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Definition\Operator;

use GraphQL\Doctrine\Definition\Operator\AbstractOperator;
use GraphQL\Doctrine\Definition\Operator\BetweenOperatorType;
use GraphQL\Doctrine\Definition\Operator\EmptyOperatorType;
use GraphQL\Doctrine\Definition\Operator\EqualOperatorType;
use GraphQL\Doctrine\Definition\Operator\GreaterOperatorType;
use GraphQL\Doctrine\Definition\Operator\GreaterOrEqualOperatorType;
use GraphQL\Doctrine\Definition\Operator\GroupOperatorType;
use GraphQL\Doctrine\Definition\Operator\HaveOperatorType;
use GraphQL\Doctrine\Definition\Operator\InOperatorType;
use GraphQL\Doctrine\Definition\Operator\LessOperatorType;
use GraphQL\Doctrine\Definition\Operator\LessOrEqualOperatorType;
use GraphQL\Doctrine\Definition\Operator\LikeOperatorType;
use GraphQL\Doctrine\Definition\Operator\NullOperatorType;
use GraphQL\Doctrine\Factory\UniqueNameFactory;
use GraphQL\Type\Definition\Type;
use GraphQLTests\Doctrine\Blog\Model\User;
use GraphQLTests\Doctrine\TypesTrait;

final class OperatorsTest extends \PHPUnit\Framework\TestCase
{
    use TypesTrait;

    /**
     * @dataProvider providerOperator
     *
     * @param string $expected
     * @param string $className
     * @param array $args
     * @param string $field
     */
    public function testOperator(?string $expected, string $className, ?array $args, string $field = 'field'): void
    {
        /** @var AbstractOperator $operator */
        $operator = new $className($this->types, Type::string());
        $uniqueNameFactory = new UniqueNameFactory();
        $metadata = $this->entityManager->getClassMetadata(User::class);
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $alias = 'alias';

        $actual = $operator->getDqlCondition($uniqueNameFactory, $metadata, $queryBuilder, $alias, $field, $args);

        self::assertSame($expected, $actual, 'DQL condition should match');

        if (is_string($expected)) {
            self::assertSame(mb_substr_count($expected, ':'), $queryBuilder->getParameters()->count(), 'should declare the same number of parameters that are actually used');
        }

        if ($className === GroupOperatorType::class) {
            self::assertCount(1, $queryBuilder->getDQLPart('groupBy'));
            self::assertSame(['alias.field'], $queryBuilder->getDQLPart('groupBy')[0]->getParts());
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
                HaveOperatorType::class,
                null,
                'posts',
            ],
            [
                ':filter1 MEMBER OF alias.posts',
                HaveOperatorType::class,
                [
                    'values' => [123, 456],
                    'not' => false,
                ],
                'posts',
            ],
            [
                ':filter1 NOT MEMBER OF alias.posts',
                HaveOperatorType::class,
                [
                    'values' => [123, 456],
                    'not' => true,
                ],
                'posts',
            ],
            [
                null,
                HaveOperatorType::class,
                null,
                'manager',
            ],
            [
                'alias.manager IN (:filter1)',
                HaveOperatorType::class,
                [
                    'values' => [123, 456],
                    'not' => false,
                ],
                'manager',
            ],
            [
                'alias.manager NOT IN (:filter1)',
                HaveOperatorType::class,
                [
                    'values' => [123, 456],
                    'not' => true,
                ],
                'manager',
            ],
            [
                null,
                EmptyOperatorType::class,
                null,
                'posts',
            ],
            [
                'alias.posts IS EMPTY',
                EmptyOperatorType::class,
                [
                    'not' => false,
                ],
                'posts',
            ],
            [
                'alias.posts IS NOT EMPTY',
                EmptyOperatorType::class,
                [
                    'not' => true,
                ],
                'posts',
            ],
            [
                null,
                EmptyOperatorType::class,
                null,
                'manager',
            ],
            [
                'alias.manager IS NULL',
                EmptyOperatorType::class,
                [
                    'not' => false,
                ],
                'manager',
            ],
            [
                'alias.manager IS NOT NULL',
                EmptyOperatorType::class,
                [
                    'not' => true,
                ],
                'manager',
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
            [
                null,
                GroupOperatorType::class,
                null,
            ],
            [
                null,
                GroupOperatorType::class,
                [
                    'value' => null,
                ],
            ],
            [
                null,
                GroupOperatorType::class,
                [
                    'value' => true,
                ],
            ],
        ];
    }
}
