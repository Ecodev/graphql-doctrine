<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\ORM\Mapping\Annotation;

/**
 * Annotation used to override values for an output field in GraphQL.
 *
 * All values are optional and should only be used to override
 * what is declared by the original method.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"METHOD"})
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class Field implements Annotation
{
    /**
     * @var null|string
     */
    public $name;

    /**
     * FQCN of PHP class implementing the GraphQL type.
     *
     * @var null|string
     */
    public $type;

    /**
     * @var null|string
     */
    public $description;

    /**
     * @var array<\GraphQL\Doctrine\Annotation\Argument>
     */
    public $args = [];

    public function __construct(
        ?string $name = null,
        ?string $type = null,
        ?string $description = null,
        array $args = []
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->description = $description;
        $this->args = $args;
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
