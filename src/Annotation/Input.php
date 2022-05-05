<?php

declare(strict_types=1);

namespace GraphQL\Doctrine\Annotation;

use Doctrine\Common\Annotations\Annotation\Attribute;
use Doctrine\Common\Annotations\Annotation\Attributes;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Annotation used to override values for an input field in GraphQL.
 *
 * All values are optional and should only be used to override
 * what is declared by the original method.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"METHOD"})
 * @Attributes({
 *     @Attribute("name", required=false, type="string"),
 *     @Attribute("type", required=false, type="string"),
 *     @Attribute("description", required=false, type="string"),
 *     @Attribute("defaultValue", required=false, type="mixed"),
 * })
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class Input extends AbstractAnnotation
{
    private const NO_VALUE_PASSED = '_hacky_no_value_past_find_a_better_solution_for_this_';

    public function __construct(
        ?string $name = null,
        ?string $type = null,
        ?string $description = null,
        mixed $defaultValue = self::NO_VALUE_PASSED
    ) {
        $settings = [
            'name' => $name,
            'type' => $type,
            'description' => $description,
        ];
        if ($defaultValue !== self::NO_VALUE_PASSED) {
            $settings['defaultValue'] = $defaultValue;
        }

        parent::__construct($settings);
    }
}
