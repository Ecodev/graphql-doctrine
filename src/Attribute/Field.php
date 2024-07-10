<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Attribute;

use Attribute;
use GraphQL\Type\Definition\Type;

/**
 * Attribute used to override values for an output field in GraphQL.
 *
 * All values are optional and should only be used to override
 * what is declared by the original method.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class Field implements ApiAttribute
{
    /**
     * @var Argument[]
     */
    public array $args = [];

    public null|string|Type $type = null;

    /**
     * @param null|string $name Can be used to alias the field
     * @param null|string $type FQCN of PHP class implementing the GraphQL type, see README.md#type-syntax
     */
    public function __construct(
        public ?string $name = null,
        null|string $type = null,
        public ?string $description = null,
    ) {
        $this->type = $type;
    }

    public function toArray(): array
    {
        $args = [];
        foreach ($this->args as $arg) {
            $args[] = $arg->toArray();
        }

        return [
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
            'args' => $args,
        ];
    }
}
