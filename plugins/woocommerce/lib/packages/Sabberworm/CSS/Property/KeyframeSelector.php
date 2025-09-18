<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS\Property;

class KeyframeSelector extends Selector
{
    /**
     * This differs from the parent class:
     * - comma is not allowed unless escaped or quoted;
     * - percentage value is allowed by itself.
     *
     * @var non-empty-string
     *
     * @internal since 8.5.2
     */
    public const SELECTOR_VALIDATION_RX = '/
        ^(
            (?:
                # any sequence of valid unescaped characters, except quotes
                [a-zA-Z0-9\\x{00A0}-\\x{FFFF}_^$|*=~\\[\\]()\\-\\s\\.:#+>]++
                |
                # one or more escaped characters
                (?:\\\\.)++
                |
                # quoted text, like in `[id="example"]`
                (?:
                    # opening quote
                    ([\'"])
                    (?:
                        # sequence of characters except closing quote or backslash
                        (?:(?!\\g{-1}|\\\\).)++
                        |
                        # one or more escaped characters
                        (?:\\\\.)++
                    )*+ # zero or more times
                    # closing quote or end (unmatched quote is currently allowed)
                    (?:\\g{-1}|$)
                )
            )*+ # zero or more times
            |
            # keyframe animation progress percentage (e.g. 50%), untrimmed
            \\s*+(\\d++%)\\s*+
        )$
        /ux';
}
