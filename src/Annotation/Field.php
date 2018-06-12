<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Annotation;

/**
 * Annotation used to override values for an output field in GraphQL.
 *
 * All values are optional and should only be used to override
 * what is declared by the original method.
 *
 * @Annotation
 * @Target({"METHOD"})
 */
final class Field
{
    /**
     * @var string
     */
    public $name;

    /**
     * FQCN of PHP class implementing the GraphQL type
     *
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $description;

    /**
     * @var array<\GraphQL\Doctrine\Annotation\Argument>
     */
    public $args = [];

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
