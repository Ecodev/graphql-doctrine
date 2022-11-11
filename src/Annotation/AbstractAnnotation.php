<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Annotation;

use GraphQL\Type\Definition\Type;

/**
 * Abstract annotation with common logic for Argument and Field.
 */
abstract class AbstractAnnotation
{
    /**
     * The name of the argument, it must matches the actual PHP argument name.
     *
     * @Required
     */
    private ?string $name = null;

    /**
     * FQCN of PHP class implementing the GraphQL type.
     */
    private ?string $type = null;

    /**
     * Instance of the GraphQL type.
     */
    private ?Type $typeInstance = null;

    private ?string $description = null;

    private mixed $defaultValue;

    private bool $hasDefaultValue = false;

    public function __construct(array $values = [])
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function hasDefaultValue(): bool
    {
        return $this->hasDefaultValue;
    }

    public function getDefaultValue(): mixed
    {
        return $this->defaultValue;
    }

    public function setDefaultValue(mixed $defaultValue): void
    {
        $this->defaultValue = $defaultValue;
        $this->hasDefaultValue = true;
    }

    public function getTypeInstance(): ?Type
    {
        return $this->typeInstance;
    }

    public function setTypeInstance(?Type $typeInstance): void
    {
        $this->typeInstance = $typeInstance;
    }
}
