<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine;

use DateTimeImmutable;
use Exception;
use GraphQL\Doctrine\Types;
use GraphQL\Type\Definition\BooleanType;
use GraphQL\Type\Definition\InputType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\OutputType;
use GraphQL\Type\Definition\PhpEnumType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\WrappingType;
use GraphQL\Type\Schema;
use GraphQL\Utils\SchemaPrinter;
use GraphQLTests\Doctrine\Blog\Enum\Status;
use GraphQLTests\Doctrine\Blog\Types\CustomType;
use GraphQLTests\Doctrine\Blog\Types\DateTimeType;
use GraphQLTests\Doctrine\Blog\Types\PostStatusType;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use Laminas\ServiceManager\ServiceManager;
use Psr\Container\ContainerInterface;
use stdClass;

/**
 * Trait to easily set up types and assert them.
 */
trait TypesTrait
{
    use EntityManagerTrait;

    private Types $types;

    public function setUp(): void
    {
        $this->setUpEntityManager();

        $customTypes = new ServiceManager([
            'invokables' => [
                BooleanType::class => BooleanType::class,
                DateTimeImmutable::class => DateTimeType::class,
                stdClass::class => CustomType::class,
                'PostStatus' => PostStatusType::class,
            ],
            'aliases' => [
                'datetime_immutable' => DateTimeImmutable::class, // Declare alias for Doctrine type to be used for filters
            ],
            'abstract_factories' => [
                new class() implements AbstractFactoryInterface {
                    public function canCreate(ContainerInterface $container, string $requestedName): bool
                    {
                        return $requestedName === Status::class;
                    }

                    public function __invoke(ContainerInterface $container, string $requestedName, ?array $options = null): PhpEnumType
                    {
                        return new PhpEnumType(Status::class);
                    }
                },
            ],
        ]);

        $this->types = new Types($this->entityManager, $customTypes);
    }

    private function assertType(string $expectedFile, Type $type): void
    {
        $actual = SchemaPrinter::printType($type) . "\n";
        self::assertStringEqualsFile($expectedFile, $actual, 'Should equals expectation from: ' . $expectedFile);
    }

    private function assertAllTypes(string $expectedFile, Type $type): void
    {
        $schema = $this->getSchemaForType($type);
        $actual = SchemaPrinter::doPrint($schema);

        self::assertStringEqualsFile($expectedFile, $actual, 'Should equals expectation from: ' . $expectedFile);
    }

    /**
     * Create a temporary schema for the given type.
     */
    private function getSchemaForType(Type $type): Schema
    {
        if ($type instanceof WrappingType) {
            $wrappedType = $type->getInnermostType();
        } else {
            $wrappedType = $type;
        }

        if ($wrappedType instanceof OutputType) {
            $outputType = $type;
            $args = [];
        } elseif ($wrappedType instanceof InputType) {
            $outputType = Type::boolean();
            $args = [
                'defaultArg' => $type,
            ];
        } else {
            throw new Exception('Unsupported type: ' . $wrappedType::class);
        }

        $config = [
            'query' => new ObjectType([
                'name' => 'query',
                'fields' => [
                    'defaultField' => [
                        'type' => $outputType,
                        'args' => $args,
                    ],
                ],
            ]),
        ];

        $schema = new Schema($config);
        $schema->assertValid();

        return $schema;
    }
}
