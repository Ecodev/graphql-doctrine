<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Factory\MetadataReader;

use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\Driver\AttributeReader;
use Doctrine\Persistence\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use GraphQL\Doctrine\Exception;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

final class MappingDriverChainAdapter implements Reader
{
    public function __construct(private readonly MappingDriverChain $chainDriver)
    {
    }

    /**
     * Find the reader for the class.
     */
    private function findReader(ReflectionClass $class): Reader
    {
        $className = $class->getName();
        foreach ($this->chainDriver->getDrivers() as $namespace => $driver) {
            if (mb_stripos($className, $namespace) === 0) {
                if ($driver instanceof AttributeDriver) {
                    /**
                     * doctrine lies about the return value of getReader here.
                     *
                     * @var AttributeReader $attributeReader
                     */
                    $attributeReader = $driver->getReader();

                    return new AttributeReaderAdapter($attributeReader);
                }

                if ($driver instanceof AnnotationDriver) {
                    return $driver->getReader();
                }
            }
        }

        $defaultDriver = $this->chainDriver->getDefaultDriver();
        if ($defaultDriver instanceof AttributeDriver) {
            /**
             * doctrine lies about the return value of getReader here.
             *
             * @var AttributeReader $attributeReader
             */
            $attributeReader = $defaultDriver->getReader();

            return new AttributeReaderAdapter($attributeReader);
        }

        if ($defaultDriver instanceof AnnotationDriver) {
            return $defaultDriver->getReader();
        }

        throw new Exception('graphql-doctrine requires ' . $className . ' entity to be configured with a `' . AnnotationDriver::class . '`.');
    }

    public function getClassAnnotations(ReflectionClass $class)
    {
        return $this->findReader($class)
            ->getClassAnnotations($class);
    }

    public function getClassAnnotation(ReflectionClass $class, $annotationName)
    {
        return $this->findReader($class)
            ->getClassAnnotation($class, $annotationName);
    }

    public function getMethodAnnotations(ReflectionMethod $method)
    {
        return $this->findReader($method->getDeclaringClass())
            ->getMethodAnnotations($method);
    }

    public function getMethodAnnotation(ReflectionMethod $method, $annotationName)
    {
        return $this->findReader($method->getDeclaringClass())
            ->getMethodAnnotation($method, $annotationName);
    }

    public function getPropertyAnnotations(ReflectionProperty $property)
    {
        return $this->findReader($property->getDeclaringClass())
            ->getPropertyAnnotations($property);
    }

    public function getPropertyAnnotation(ReflectionProperty $property, $annotationName)
    {
        return $this->findReader($property->getDeclaringClass())
            ->getPropertyAnnotation($property, $annotationName);
    }
}
