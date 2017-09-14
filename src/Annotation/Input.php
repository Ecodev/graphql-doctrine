<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Annotation;

/**
 * Annotation used to override values for an input field in GraphQL.
 *
 * All values are optional and should only be used to override
 * what is declared by the original method.
 *
 * @Annotation
 * @Target({"METHOD"})
 */
class Input
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
     * @var mixed
     */
    public $defaultValue;

    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
        ];

        if ($this->defaultValue !== null) {
            $data['defaultValue'] = $this->defaultValue;
        }

        return $data;
    }
}
