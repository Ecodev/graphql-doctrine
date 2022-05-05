<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Factory\Type;

use GraphQL\Doctrine\Definition\EntityIDType;
use GraphQL\Doctrine\Factory\Type\EntityIDTypeFactory;
use GraphQL\Doctrine\Types;
use GraphQLTests\Doctrine\AttributeBlog\Model\Special\CompositeIdentifier;
use GraphQLTests\Doctrine\AttributeBlog\Model\User;
use GraphQLTests\Doctrine\EntityManagerTrait;
use PHPUnit\Framework\TestCase;

final class EntityIDTypeFactoryWithAttributesTest extends TestCase
{
    use EntityManagerTrait;

    private EntityIDTypeFactory $entityIDTypeFactory;

    protected function setUp(): void
    {
        $this->setUpAttributeEntityManager();

        $types = new Types($this->entityManager);
        $this->entityIDTypeFactory = new EntityIDTypeFactory($types, $this->entityManager);
    }

    public function testCreateEntityIDType(): void
    {
        self::assertInstanceOf(EntityIDType::class, $this->entityIDTypeFactory->create(User::class, 'foo'));
    }

    public function testEntityWithCompositeIdentifierMustThrow(): void
    {
        $this->expectExceptionMessage('Entities with composite identifiers are not supported by graphql-doctrine. The entity `GraphQLTests\Doctrine\AttributeBlog\Model\Special\CompositeIdentifier` cannot be used as input type.');
        $this->entityIDTypeFactory->create(CompositeIdentifier::class, 'foo');
    }
}
