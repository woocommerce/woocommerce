<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing;

/**
 * Thrown if the CSS parser encounters a token it did not expect.
 */
class UnexpectedTokenException extends SourceException
{
    /**
     * @param 'literal'|'identifier'|'count'|'expression'|'search'|'custom' $matchType
     * @param int<1, max>|null $lineNumber
     */
    public function __construct(string $expected, string $found, string $matchType = 'literal', ?int $lineNumber = null)
    {
        $message = "Token “{$expected}” ({$matchType}) not found. Got “{$found}”.";
        if ($matchType === 'search') {
            $message = "Search for “{$expected}” returned no results. Context: “{$found}”.";
        } elseif ($matchType === 'count') {
            $message = "Next token was expected to have {$expected} chars. Context: “{$found}”.";
        } elseif ($matchType === 'identifier') {
            $message = "Identifier expected. Got “{$found}”";
        } elseif ($matchType === 'custom') {
            $message = \trim("$expected $found");
        }

        parent::__construct($message, $lineNumber);
    }
}
