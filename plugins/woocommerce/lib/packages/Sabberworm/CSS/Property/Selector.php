<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS\Property;

use Automattic\WooCommerce\Vendor\Sabberworm\CSS\OutputFormat;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Property\Selector\SpecificityCalculator;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Renderable;

use function Safe\preg_match;

/**
 * Class representing a single CSS selector. Selectors have to be split by the comma prior to being passed into this
 * class.
 */
class Selector implements Renderable
{
    /**
     * @var non-empty-string
     *
     * @internal since 8.5.2
     */
    public const SELECTOR_VALIDATION_RX = '/
        ^(
            (?:
                # any sequence of valid unescaped characters, except quotes
                [a-zA-Z0-9\\x{00A0}-\\x{FFFF}_^$|*=~\\[\\]()\\-\\s\\.:#+>,]++
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
        )$
        /ux';

    /**
     * @var string
     */
    private $selector;

    /**
     * @internal since V8.8.0
     */
    public static function isValid(string $selector): bool
    {
        // Note: We need to use `static::` here as the constant is overridden in the `KeyframeSelector` class.
        $numberOfMatches = preg_match(static::SELECTOR_VALIDATION_RX, $selector);

        return $numberOfMatches === 1;
    }

    public function __construct(string $selector)
    {
        $this->setSelector($selector);
    }

    public function getSelector(): string
    {
        return $this->selector;
    }

    public function setSelector(string $selector): void
    {
        $this->selector = \trim($selector);
    }

    /**
     * @return int<0, max>
     */
    public function getSpecificity(): int
    {
        return SpecificityCalculator::calculate($this->selector);
    }

    public function render(OutputFormat $outputFormat): string
    {
        return $this->getSelector();
    }
}
