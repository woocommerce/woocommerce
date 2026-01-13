<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\EmailEditorVendor\Pelago\Emogrifier\Utilities;

/**
 * Provides a common method for parsing CSS declaration blocks.
 * These might be from actual CSS, or from the `style` attribute of an HTML DOM element.
 *
 * Caches results globally.
 *
 * @internal
 */
final class DeclarationBlockParser
{
    /**
     * @var array<string, array<non-empty-string, string>>
     */
    private static $cache = [];

    /**
     * CSS custom properties (variables) have case-sensitive names, so their case must be preserved.
     * Standard CSS properties have case-insensitive names, which are converted to lowercase.
     *
     * @param non-empty-string $name
     *
     * @return non-empty-string
     */
    public function normalizePropertyName(string $name): string
    {
        if (\substr($name, 0, 2) === '--') {
            return $name;
        } else {
            return \strtolower($name);
        }
    }

    /**
     * Parses a CSS declaration block into property name/value pairs.
     *
     * Example:
     *
     * The declaration block
     *
     *   "color: #000; font-weight: bold;"
     *
     * will be parsed into the following array:
     *
     *   "color" => "#000"
     *   "font-weight" => "bold"
     *
     * @param string $declarationBlock the CSS declarations block without the curly braces, may be empty
     *
     * @return array<non-empty-string, string>
     *         the CSS declarations with the property names as array keys and the property values as array values
     *
     * @throws \UnexpectedValueException if an empty property name is encountered (which cannot happen)
     */
    public function parse(string $declarationBlock): array
    {
        if (isset(self::$cache[$declarationBlock])) {
            return self::$cache[$declarationBlock];
        }

        $preg = new Preg();

        $declarations = $preg->split('/;(?!base64|charset)/', $declarationBlock);

        $properties = [];
        foreach ($declarations as $declaration) {
            $matches = [];
            if (
                $preg->match(
                    '/^([A-Za-z\\-]+)\\s*:\\s*(.+)$/s',
                    \trim($declaration),
                    $matches
                )
                === 0
            ) {
                continue;
            }

            $propertyName = $matches[1];
            if ($propertyName === '') {
                // This cannot happen since the regular epression matches one or more characters.
                throw new \UnexpectedValueException('An empty property name was encountered.', 1727046409);
            }
            $propertyValue = $matches[2];
            $properties[$this->normalizePropertyName($propertyName)] = $propertyValue;
        }
        self::$cache[$declarationBlock] = $properties;

        return $properties;
    }
}
