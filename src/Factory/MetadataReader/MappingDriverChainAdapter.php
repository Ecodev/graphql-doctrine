<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Factory\MetadataReader;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Persistence\Mapping\Driver\AnnotationDriver;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use GraphQL\Doctrine\Exception;
use ReflectionClass;

class MappingDriverChainAdapter implements Reader
{
    /**
     * @var MappingDriverChain
     */
    private $chainDriver;

    public function __construct(MappingDriverChain $chainDriver)
    {
        $this->chainDriver = $chainDriver;
    }

    /**
     * Find the reader for the class
     *
     * @param ReflectionClass $class
     *
     * @return Reader
     */
    private function findReader(ReflectionClass $class): Reader
    {
        $className = $class->getName();
        foreach ($this->chainDriver->getDrivers() as $namespace => $driver) {
            if (mb_stripos($className, $namespace) === 0) {
                if ($driver instanceof AnnotationDriver) {
                    return $driver->getReader();
                }
            }
        }

        if ($this->chainDriver->getDefaultDriver() instanceof AnnotationDriver) {
            return $this->chainDriver->getDefaultDriver()->getReader();
        }

        throw new Exception('graphql-doctrine requires ' . $className . ' entity to be configured with a `' . AnnotationDriver::class . '`.');
    }

    /**
     * Gets the annotations applied to a class.
     *
     * @param ReflectionClass $class the ReflectionClass of the class from which
     *                                the class annotations should be read
     *
     * @return array an array of Annotations
     */
    public function getClassAnnotations(ReflectionClass $class)
    {
        return $this->findReader($class)
            ->getClassAnnotations($class);
    }

    /**
     * Gets a class annotation.
     *
     * @param ReflectionClass $class the ReflectionClass of the class from which
     *                                         the class annotations should be read
     * @param string $annotationName the name of the annotation
     *
     * @return null|object the Annotation or NULL, if the requested annotation does not exist
     */
    public function getClassAnnotation(ReflectionClass $class, $annotationName)
    {
        return $this->findReader($class)
            ->getClassAnnotation($class, $annotationName);
    }

    /**
     * Gets the annotations applied to a method.
     *
     * @param \ReflectionMethod $method the ReflectionMethod of the method from which
     *                                  the annotations should be read
     *
     * @return array an array of Annotations
     */
    public function getMethodAnnotations(\ReflectionMethod $method)
    {
        return $this->findReader($method->getDeclaringClass())
            ->getMethodAnnotations($method);
    }

    /**
     * Gets a method annotation.
     *
     * @param \ReflectionMethod $method the ReflectionMethod to read the annotations from
     * @param string $annotationName the name of the annotation
     *
     * @return null|object the Annotation or NULL, if the requested annotation does not exist
     */
    public function getMethodAnnotation(\ReflectionMethod $method, $annotationName)
    {
        return $this->findReader($method->getDeclaringClass())
            ->getMethodAnnotation($method, $annotationName);
    }

    /**
     * Gets the annotations applied to a property.
     *
     * @param \ReflectionProperty $property the ReflectionProperty of the property
     *                                      from which the annotations should be read
     *
     * @return array an array of Annotations
     */
    public function getPropertyAnnotations(\ReflectionProperty $property)
    {
        return $this->findReader($property->getDeclaringClass())
            ->getPropertyAnnotations($property);
    }

    /**
     * Gets a property annotation.
     *
     * @param \ReflectionProperty $property the ReflectionProperty to read the annotations from
     * @param string $annotationName the name of the annotation
     *
     * @return null|object the Annotation or NULL, if the requested annotation does not exist
     */
    public function getPropertyAnnotation(\ReflectionProperty $property, $annotationName)
    {
        return $this->findReader($property->getDeclaringClass())
            ->getPropertyAnnotation($property, $annotationName);
    }
}
