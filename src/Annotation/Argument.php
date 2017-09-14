<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Annotation;

/**
 * Annotation used to override values for an field argument in GraphQL.
 *
 * All values are optional and should only be used to override
 * what is declared by the original argument of the method.
 *
 * @Annotation
 * @Target({"ANNOTATION"})
 */
class Argument
{
    /**
     * The name of the argument, it must matches the actual PHP argument name
     *
     * @var string
     * @Required
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
