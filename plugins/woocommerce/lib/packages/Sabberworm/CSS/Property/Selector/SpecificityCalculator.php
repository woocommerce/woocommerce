<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS\Property\Selector;

/**
 * Utility class to calculate the specificity of a CSS selector.
 *
 * The results are cached to avoid recalculating the specificity of the same selector multiple times.
 */
final class SpecificityCalculator
{
    /**
     * regexp for specificity calculations
     *
     * @var non-empty-string
     */
    private const NON_ID_ATTRIBUTES_AND_PSEUDO_CLASSES_RX = '/
        (\\.[\\w]+)                   # classes
        |
        \\[(\\w+)                     # attributes
        |
        (\\:(                         # pseudo classes
            link|visited|active
            |hover|focus
            |lang
            |target
            |enabled|disabled|checked|indeterminate
            |root
            |nth-child|nth-last-child|nth-of-type|nth-last-of-type
            |first-child|last-child|first-of-type|last-of-type
            |only-child|only-of-type
            |empty|contains
        ))
        /ix';

    /**
     * regexp for specificity calculations
     *
     * @var non-empty-string
     */
    private const ELEMENTS_AND_PSEUDO_ELEMENTS_RX = '/
        ((^|[\\s\\+\\>\\~]+)[\\w]+   # elements
        |
        \\:{1,2}(                    # pseudo-elements
            after|before|first-letter|first-line|selection
        ))
        /ix';

    /**
     * @var array<string, int<0, max>>
     */
    private static $cache = [];

    /**
     * Calculates the specificity of the given CSS selector.
     *
     * @return int<0, max>
     *
     * @internal
     */
    public static function calculate(string $selector): int
    {
        if (!isset(self::$cache[$selector])) {
            $a = 0;
            /// @todo should exclude \# as well as "#"
            $matches = null;
            $b = \substr_count($selector, '#');
            $c = \preg_match_all(self::NON_ID_ATTRIBUTES_AND_PSEUDO_CLASSES_RX, $selector, $matches);
            $d = \preg_match_all(self::ELEMENTS_AND_PSEUDO_ELEMENTS_RX, $selector, $matches);
            self::$cache[$selector] = ($a * 1000) + ($b * 100) + ($c * 10) + $d;
        }

        return self::$cache[$selector];
    }

    /**
     * Clears the cache in order to lower memory usage.
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
