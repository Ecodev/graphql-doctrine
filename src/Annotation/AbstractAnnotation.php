<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Annotation;

use GraphQL\Type\Definition\Type;

/**
 * Abstract annotation with common logic for Argument and Field
 */
abstract class AbstractAnnotation
{
    /**
     * The name of the argument, it must matches the actual PHP argument name
     *
     * @var null|string
     * @Required
     */
    private $name;

    /**
     * FQCN of PHP class implementing the GraphQL type
     *
     * @var null|string
     */
    private $type;

    /**
     * Instance of the GraphQL type
     *
     * @var null|Type
     */
    private $typeInstance;

    /**
     * @var null|string
     */
    private $description;

    /**
     * @var mixed
     */
    private $defaultValue;

    /**
     * @var bool
     */
    private $hasDefaultValue = false;

    public function __construct($values = [])
    {
        foreach ($values as $key => $value) {
            $setter = 'set' . ucfirst($key);
            $this->$setter($value);
        }
    }

    public function toArray(): array
    {
        $data = [
            'name' => $this->getName(),
            'type' => $this->getTypeInstance(),
            'description' => $this->getDescription(),
        ];

        if ($this->hasDefaultValue()) {
            $data['defaultValue'] = $this->getDefaultValue();
        }

        return $data;
    }

    /**
     * @return null|string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param null|string $name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return null|string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param null|string $type
     */
    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return null|string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param null|string $description
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return bool
     */
    public function hasDefaultValue(): bool
    {
        return $this->hasDefaultValue;
    }

    /**
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @param mixed $defaultValue
     */
    public function setDefaultValue($defaultValue): void
    {
        $this->defaultValue = $defaultValue;
        $this->hasDefaultValue = true;
    }

    /**
     * @return null|Type
     */
    public function getTypeInstance(): ?Type
    {
        return $this->typeInstance;
    }

    /**
     * @param null|Type $typeInstance
     */
    public function setTypeInstance(?Type $typeInstance): void
    {
        $this->typeInstance = $typeInstance;
    }
}
