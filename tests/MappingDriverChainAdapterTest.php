<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use GraphQL\Doctrine\Factory\MetadataReader\MappingDriverChainAdapter;
use GraphQLTests\Doctrine\Blog\Model\Post;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

final class MappingDriverChainAdapterTest extends \PHPUnit\Framework\TestCase
{
    use TypesTrait {
        setUp as typeSetup;
    }

    /**
     * @var MappingDriverChainAdapter
     */
    private $chainAdapter;

    protected function setUp(): void
    {
        $this->typeSetup();

        $config = $this->entityManager->getConfiguration();
        $chain = new MappingDriverChain();
        $chain->setDefaultDriver($config->getMetadataDriverImpl());
        $this->chainAdapter = new MappingDriverChainAdapter($chain);
    }

    public function testGetClassAnnotations(): void
    {
        self::assertNotEmpty($this->chainAdapter->getClassAnnotations(new ReflectionClass(Post::class)));
    }

    public function testGetClassAnnotation(): void
    {
        self::assertNotNull($this->chainAdapter->getClassAnnotation(new ReflectionClass(Post::class), Entity::class));
    }

    public function testGetMethodAnnotations(): void
    {
        self::assertNotEmpty($this->chainAdapter->getMethodAnnotations(new ReflectionMethod(Post::class, 'getBody')));
    }

    public function testGetMethodAnnotation(): void
    {
        self::assertNotNull($this->chainAdapter->getMethodAnnotations(new ReflectionMethod(Post::class, 'getBody')));
    }

    public function testGetPropertyAnnotations(): void
    {
        self::assertNotEmpty($this->chainAdapter->getPropertyAnnotations(new ReflectionProperty(Post::class, 'body')));
    }

    public function testGetPropertyAnnotation(): void
    {
        self::assertNotNull($this->chainAdapter->getPropertyAnnotation(new ReflectionProperty(Post::class, 'body'), Column::class));
    }
}
