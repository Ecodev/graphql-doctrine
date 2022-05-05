<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Factory\MetadataReader;

use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\Mapping\Driver\AttributeReader;
use Doctrine\ORM\Mapping\Driver\RepeatableAttributeCollection;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

final class AttributeReaderAdapter implements Reader
{
    private AttributeReader $attributeReader;

    public function __construct(AttributeReader $attributeReader)
    {
        $this->attributeReader = $attributeReader;
    }

    public function getClassAnnotations(ReflectionClass $class)
    {
        return $this->attributeReader->getClassAnnotations($class);
    }

    public function getClassAnnotation(ReflectionClass $class, $annotationName)
    {
        $result = $this->attributeReader->getClassAnnotation($class, $annotationName);
        if ($result instanceof RepeatableAttributeCollection) {
            $result = $result[0];
        }

        assert($result instanceof $annotationName);

        return $result;
    }

    public function getMethodAnnotations(ReflectionMethod $method)
    {
        return $this->attributeReader->getMethodAnnotations($method);
    }

    public function getMethodAnnotation(ReflectionMethod $method, $annotationName)
    {
        $result = $this->attributeReader->getMethodAnnotation($method, $annotationName);
        if ($result instanceof RepeatableAttributeCollection) {
            $result = $result[0];
        }

        assert($result instanceof $annotationName);

        return $result;
    }

    public function getPropertyAnnotations(ReflectionProperty $property)
    {
        return $this->attributeReader->getPropertyAnnotations($property);
    }

    public function getPropertyAnnotation(ReflectionProperty $property, $annotationName)
    {
        $result = $this->attributeReader->getPropertyAnnotation($property, $annotationName);
        if ($result instanceof RepeatableAttributeCollection) {
            $result = $result[0];
        }

        assert($result instanceof $annotationName);

        return $result;
    }
}
