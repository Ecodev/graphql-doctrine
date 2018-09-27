<?php

namespace GraphQL\Doctrine\Factory\MetadataReader;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Persistence\Mapping\Driver\AnnotationDriver;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use GraphQL\Doctrine\Exception;

class MappingDriverChainAdapter implements Reader
{

    private $chainDriver;

    public function __construct(MappingDriverChain $chainDriver)
    {
        $this->chainDriver = $chainDriver;
    }

    /**
     * Find the reader for the class
     * @return Reader
     * @throws Exception
     */
    protected function findReader($className): Reader {
        foreach ($this->chainDriver->getDrivers() as $namespace => $driver) {
            if (stripos($className, $namespace) === 0) {
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
     * @param \ReflectionClass $class The ReflectionClass of the class from which
     *                                the class annotations should be read.
     *
     * @return array An array of Annotations.
     * @throws Exception
     */
    function getClassAnnotations(\ReflectionClass $class)
    {
        return $this->findReader($class->getName())
            ->getClassAnnotations($class);
    }

    /**
     * Gets a class annotation.
     *
     * @param \ReflectionClass $class The ReflectionClass of the class from which
     *                                         the class annotations should be read.
     * @param string $annotationName The name of the annotation.
     *
     * @return object|null The Annotation or NULL, if the requested annotation does not exist.
     * @throws Exception
     */
    function getClassAnnotation(\ReflectionClass $class, $annotationName)
    {
        return $this->findReader($class->getName())
            ->getClassAnnotation($class, $annotationName);
    }

    /**
     * Gets the annotations applied to a method.
     *
     * @param \ReflectionMethod $method The ReflectionMethod of the method from which
     *                                  the annotations should be read.
     *
     * @return array An array of Annotations.
     * @throws Exception
     */
    function getMethodAnnotations(\ReflectionMethod $method)
    {
        return $this->findReader($method->getDeclaringClass()->getName())
            ->getMethodAnnotations($method);
    }

    /**
     * Gets a method annotation.
     *
     * @param \ReflectionMethod $method The ReflectionMethod to read the annotations from.
     * @param string $annotationName The name of the annotation.
     *
     * @return object|null The Annotation or NULL, if the requested annotation does not exist.
     * @throws Exception
     */
    function getMethodAnnotation(\ReflectionMethod $method, $annotationName)
    {
        return $this->findReader($method->getDeclaringClass()->getName())
            ->getMethodAnnotation($method, $annotationName);
    }

    /**
     * Gets the annotations applied to a property.
     *
     * @param \ReflectionProperty $property The ReflectionProperty of the property
     *                                      from which the annotations should be read.
     *
     * @return array An array of Annotations.
     * @throws Exception
     */
    function getPropertyAnnotations(\ReflectionProperty $property)
    {
        return $this->findReader($property->getDeclaringClass()->getName())
            ->getPropertyAnnotations($property);
    }

    /**
     * Gets a property annotation.
     *
     * @param \ReflectionProperty $property The ReflectionProperty to read the annotations from.
     * @param string $annotationName The name of the annotation.
     *
     * @return object|null The Annotation or NULL, if the requested annotation does not exist.
     * @throws Exception
     */
    function getPropertyAnnotation(\ReflectionProperty $property, $annotationName)
    {
        return $this->findReader($property->getDeclaringClass()->getName())
            ->getPropertyAnnotation($property, $annotationName);
    }
}