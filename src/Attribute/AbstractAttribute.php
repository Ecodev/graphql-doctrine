<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Attribute;

use GraphQL\Type\Definition\Type;

/**
 * Abstract attribute with common logic for Argument and Field.
 */
abstract class AbstractAttribute implements ApiAttribute
{
    protected const NO_VALUE_PASSED = '_hacky_no_value_passed_marker_';

    /**
     * Instance of the GraphQL type.
     */
    private ?Type $typeInstance = null;

    private mixed $defaultValue;

    private bool $hasDefaultValue = false;

    /**
     * @param null|string $type FQCN of PHP class implementing the GraphQL type, see README.md#type-syntax
     */
    public function __construct(
        private ?string $name,
        private readonly ?string $type,
        private ?string $description,
        mixed $defaultValue,
    ) {
        if ($defaultValue !== self::NO_VALUE_PASSED) {
            $this->setDefaultValue($defaultValue);
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

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getType(): ?string
    {
        return $this->type;
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
