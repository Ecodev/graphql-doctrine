<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine;

use DateTime;
use GraphQL\Doctrine\Types;
use GraphQL\Type\Definition\BooleanType;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils\SchemaPrinter;
use GraphQLTests\Doctrine\Blog\Types\CustomType;
use GraphQLTests\Doctrine\Blog\Types\DateTimeType;
use GraphQLTests\Doctrine\Blog\Types\PostStatusType;
use stdClass;
use Zend\ServiceManager\ServiceManager;

/**
 * Trait to easily set up types and assert them
 */
trait TypesTrait
{
    use EntityManagerTrait;

    /**
     * @var Types
     */
    private $types;

    public function setUp(): void
    {
        $this->setUpEntityManager();

        $customTypes = new ServiceManager([
            'invokables' => [
                BooleanType::class => BooleanType::class,
                DateTime::class => DateTimeType::class,
                stdClass::class => CustomType::class,
                'PostStatus' => PostStatusType::class,
            ],
        ]);

        $this->types = new Types($this->entityManager, $customTypes);
    }

    private function assertType(string $expectedFile, Type $type): void
    {
        $actual = SchemaPrinter::printType($type) . PHP_EOL;
        self::assertStringEqualsFile($expectedFile, $actual, 'Should equals expectation from: ' . $expectedFile);
    }
}
