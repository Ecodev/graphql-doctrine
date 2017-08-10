<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Definition;

use Doctrine\ORM\Tools\Setup;
use GraphQL\Doctrine\Definition\EntityIDType;
use GraphQLTests\Doctrine\Blog\Model\User;
use GraphQLTests\Doctrine\EntityManagerTrait;

class EntityIDTypeTest extends \PHPUnit\Framework\TestCase
{
    use EntityManagerTrait;

    /**
     * @var EntityIDType
     */
    private $type;

    public function setUp()
    {
        $this->setUpEntityManager();
        $this->type = new EntityIDType($this->entityManager, User::class);
    }

    public function testMetadata()
    {
        $this->assertSame('UserID', $this->type->name);
        $this->assertSame('Automatically generated type to be used as input where an object of type `User` is needed', $this->type->description);
    }

    public function testCanGetEntityFromRepository()
    {
        $actual = $this->type->parseValue('123');
        $this->assertInstanceOf(User::class, $actual);
        $this->assertSame(123, $actual->getId());
    }

    public function testCanGetIdFromEntity()
    {
        $user = new User(456);

        $actual = $this->type->serialize($user);
        $this->assertSame('456', $actual);
    }
}
