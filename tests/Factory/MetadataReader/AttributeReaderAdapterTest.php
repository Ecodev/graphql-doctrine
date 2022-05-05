<?php

declare(strict_types=1);

namespace GraphQLTests\Doctrine\Factory\MetadataReader;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\Driver\AttributeReader;
use Doctrine\ORM\Mapping\Entity;
use GraphQL\Doctrine\Factory\MetadataReader\AttributeReaderAdapter;
use GraphQLTests\Doctrine\AttributeBlog\Model\Post;
use GraphQLTests\Doctrine\TypesTrait;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;

class AttributeReaderAdapterTest extends TestCase
{
    use TypesTrait {
        setUpWithAttributes as typeSetup;
    }

    private AttributeReaderAdapter $chainAdapter;

    protected function setUp(): void
    {
        $this->typeSetup();

        $config = $this->entityManager->getConfiguration();
        $mappingDriver = $config->getMetadataDriverImpl();
        if (!$mappingDriver instanceof AttributeDriver) {
            throw new RuntimeException('invalid runtime configuration: expected the metadata driver to be a ' . AttributeDriver::class);
        }
        /** @var AttributeReader $attributeReader */
        $attributeReader = $mappingDriver->getReader();
        $this->chainAdapter = new AttributeReaderAdapter($attributeReader);
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
        self::assertNotNull($this->chainAdapter->getPropertyAnnotation(
            new ReflectionProperty(Post::class, 'body'),
            Column::class
        ));
    }
}
