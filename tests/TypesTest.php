<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine;

use DateTime;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\ORM\Tools\SchemaValidator;
use GraphQL\Type\Definition\BooleanType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQLTests\Doctrine\Blog\Model\Post;
use GraphQLTests\Doctrine\Blog\Model\User;
use GraphQLTests\Doctrine\Blog\Types\CustomType;
use GraphQLTests\Doctrine\Blog\Types\DateTimeType;
use GraphQLTests\Doctrine\Blog\Types\PostStatusType;
use stdClass;

class TypesTest extends \PHPUnit\Framework\TestCase
{
    use TypesTrait;

    public function testBlogMapping(): void
    {
        $validator = new SchemaValidator($this->entityManager);
        $errors = $validator->validateMapping();

        self::assertEmpty($errors, 'doctrine annotations should be valid');
    }

    public function testGraphQLSchemaFromDocumentationMustBeValid(): void
    {
        $schema = new Schema([
            'query' => new ObjectType([
                'name' => 'query',
                'fields' => [
                    'users' => [
                        'type' => Type::listOf($this->types->getOutput(User::class)), // Use automated ObjectType for output
                        'resolve' => function ($root, $args): void {
                            // call to repository...
                        },
                    ],
                    'posts' => [
                        'type' => Type::listOf($this->types->getOutput(Post::class)), // Use automated ObjectType for output
                        'resolve' => function ($root, $args): void {
                            // call to repository...
                        },
                    ],
                ],
            ]),
            'mutation' => new ObjectType([
                'name' => 'mutation',
                'fields' => [
                    'createUser' => [
                        'type' => Type::nonNull($this->types->getOutput(User::class)),
                        'args' => [
                            'input' => Type::nonNull($this->types->getInput(User::class)), // Use automated InputObjectType for input
                        ],
                        'resolve' => function ($root, $args): void {
                            // create new user and flush...
                        },
                    ],
                    'updateUser' => [
                        'type' => Type::nonNull($this->types->getOutput(User::class)),
                        'args' => [
                            'id' => Type::nonNull(Type::id()), // Use standard API when needed
                            'input' => $this->types->getInput(User::class),
                        ],
                        'resolve' => function ($root, $args): void {
                            // update existing user and flush...
                        },
                    ],
                    'createPost' => [
                        'type' => Type::nonNull($this->types->getOutput(Post::class)),
                        'args' => [
                            'input' => Type::nonNull($this->types->getInput(Post::class)), // Use automated InputObjectType for input
                        ],
                        'resolve' => function ($root, $args): void {
                            // create new post and flush...
                        },
                    ],
                    'updatePost' => [
                        'type' => Type::nonNull($this->types->getOutput(Post::class)),
                        'args' => [
                            'id' => Type::nonNull(Type::id()), // Use standard API when needed
                            'input' => $this->types->getInput(Post::class),
                        ],
                        'resolve' => function ($root, $args): void {
                            // update existing post and flush...
                        },
                    ],
                ],
            ]),
        ]);

        $schema->assertValid();
        self::assertTrue(true, 'passed validation successfully');
    }

    public function testCanGetUserDefinedScalarTypes(): void
    {
        $bool = $this->types->get(BooleanType::class);
        $status = $this->types->get(PostStatusType::class);

        self::assertInstanceOf(BooleanType::class, $bool, 'must be a instance of bool');
        self::assertInstanceOf(PostStatusType::class, $status, 'must be an instance of post status');

        self::assertSame($bool, $this->types->get(BooleanType::class), 'must returns the same instance of bool');
        self::assertSame($status, $this->types->get(PostStatusType::class), 'must returns the same instance of post status');
    }

    public function testCanGetUserMappedTypes(): void
    {
        $type = $this->types->get(stdClass::class);

        self::assertInstanceOf(CustomType::class, $type, 'must be a instance of CustomType');
        self::assertSame($type, $this->types->get('customName'));
    }

    public function testCanGetMappedTypesEitherByMappedPhpClassOrDirectTypeClass(): void
    {
        $viaPhp = $this->types->get(DateTime::class);
        $viaType = $this->types->get(DateTimeType::class);
        self::assertSame($viaPhp, $viaType);
    }

    public function testDoctrineWithoutAnnotationDriverMustThrow(): void
    {
        // Replace annotation driver with a driver chain
        $config = $this->entityManager->getConfiguration();
        $chain = new MappingDriverChain();
        $chain->setDefaultDriver($config->getMetadataDriverImpl());
        $config->setMetadataDriverImpl($chain);

        $type = $this->types->getOutput(Post::class);

        $this->expectExceptionMessage('graphql-doctrine requires Doctrine to be configured with a `Doctrine\Common\Persistence\Mapping\Driver\AnnotationDriver`.');
        $type->getFields();
    }

    public function testNonRegisteredCustomTypeMustThrow(): void
    {
        $this->expectExceptionMessage('No type registered with key `foo`. Either correct the usage, or register it in your custom types container when instantiating `GraphQL\Doctrine\Types`');
        $this->types->get('foo');
    }

    public function testHas(): void
    {
        $this->assertTrue($this->types->has(stdClass::class), 'should have custom registered key');
        $this->assertFalse($this->types->has('non-existing'), 'should not have non-existing things');

        $this->types->get(stdClass::class);
        $this->assertTrue($this->types->has('customName'), 'should have custom registered type by its name, even if custom key was different, once type is created');
    }
}
