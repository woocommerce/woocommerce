<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS\Value;

/**
 * This class is used to represent all multivalued rules like `font: bold 12px/3 Helvetica, Verdana, sans-serif;`
 * (where the value would be a whitespace-separated list of the primitive value `bold`, a slash-separated list
 * and a comma-separated list).
 */
class RuleValueList extends ValueList
{
    /**
     * @param non-empty-string $separator
     * @param int<1, max>|null $lineNumber
     */
    public function __construct(string $separator = ',', ?int $lineNumber = null)
    {
        parent::__construct([], $separator, $lineNumber);
    }
}
