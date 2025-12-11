<?php

declare(strict_types=1);

namespace WooCommerce\RestApiDocs\Util;

use WooCommerce\RestApiDocs\Parser\RouteRegexTypesParser;

/**
 * Utility class for formatting route patterns.
 */
final class RouteFormatter
{
    /**
     * @var RouteRegexTypesParser|null Parser for regex type mappings
     */
    private static ?RouteRegexTypesParser $regexTypesParser = null;

    /**
     * Set the regex types parser.
     *
     * @param RouteRegexTypesParser $parser The parser instance
     */
    public static function setRegexTypesParser(RouteRegexTypesParser $parser): void
    {
        self::$regexTypesParser = $parser;
    }

    /**
     * Format a route pattern for display.
     * Converts regex patterns like (?P<id>[\d]+) to {id}
     *
     * @param string $route The route pattern from WordPress schema
     * @return string The formatted route
     */
    public static function formatForDisplay(string $route): string
    {
        // Replace named capture groups with {name} format
        // Pattern: (?P<name>...) -> {name}
        return preg_replace(
            '/\(\?P<([^>]+)>[^)]+\)/',
            '{\1}',
            $route
        ) ?? $route;
    }

    /**
     * Normalize a route for use in filenames.
     * Converts /wc/v3/products/(?P<id>[\d]+) to wc_v3_products_id
     *
     * @param string $route The route pattern
     * @return string The normalized route for filename use
     */
    public static function normalizeForFilename(string $route): string
    {
        // First extract parameter names
        $route = preg_replace(
            '/\(\?P<([^>]+)>[^)]+\)/',
            '\1',
            $route
        ) ?? $route;

        // Remove leading slash
        $route = ltrim($route, '/');

        // Replace slashes with underscores
        $route = str_replace('/', '_', $route);

        // Remove any remaining special characters
        $route = preg_replace('/[^a-zA-Z0-9_]/', '', $route) ?? $route;

        return $route;
    }

    /**
     * Generate a filename for a descriptor file.
     *
     * @param array<string> $verbs The HTTP verbs
     * @param string $route The route pattern
     * @return string The filename (without .md extension)
     */
    public static function generateDescriptorFilename(array $verbs, string $route): string
    {
        $verbPart = strtolower(implode('_', $verbs));
        $routePart = self::normalizeForFilename($route);

        return $verbPart . '__' . $routePart;
    }

    /**
     * Extract parameter names from a route pattern.
     *
     * @param string $route The route pattern
     * @return array<string> The parameter names
     */
    public static function extractParameters(string $route): array
    {
        preg_match_all('/\(\?P<([^>]+)>/', $route, $matches);
        return $matches[1] ?? [];
    }

    /**
     * Extract parameter names with their regex patterns from a route.
     *
     * @param string $route The route pattern
     * @return array<string, array{pattern: string, type: string, isExotic: bool}> Parameter info keyed by name
     */
    public static function extractParametersWithPatterns(string $route): array
    {
        preg_match_all('/\(\?P<([^>]+)>([^)]+)\)/', $route, $matches, PREG_SET_ORDER);

        $result = [];
        foreach ($matches as $match) {
            $name = $match[1];
            $pattern = $match[2];
            $typeInfo = self::inferTypeFromPattern($pattern);

            $result[$name] = [
                'pattern' => $pattern,
                'type' => $typeInfo['type'],
                'isExotic' => $typeInfo['isExotic'],
            ];
        }

        return $result;
    }

    /**
     * Infer a type from a regex pattern.
     *
     * @param string $pattern The regex pattern
     * @return array{type: string, isExotic: bool} The inferred type and whether it's exotic
     */
    public static function inferTypeFromPattern(string $pattern): array
    {
        // Use parser if available
        if (self::$regexTypesParser !== null) {
            $type = self::$regexTypesParser->getTypeForPattern($pattern);
            if ($type !== null) {
                return ['type' => $type, 'isExotic' => false];
            }

            // Pattern not in config - it's exotic
            // Try to infer type from pattern structure
            $inferredType = self::inferTypeFromPatternStructure($pattern);
            return ['type' => $inferredType, 'isExotic' => true];
        }

        // Fallback if parser not set (shouldn't happen in normal use)
        return ['type' => 'string', 'isExotic' => true];
    }

    /**
     * Infer a type from the structure of a regex pattern (for exotic patterns).
     *
     * @param string $pattern The regex pattern
     * @return string The inferred type
     */
    private static function inferTypeFromPatternStructure(string $pattern): string
    {
        // If it contains only digit patterns, it's likely integer
        // Match patterns like \d, [\d], [0-9], etc.
        if (preg_match('/^[\[\]\\\\d0-9+*?{}|-]+$/', $pattern)) {
            return 'integer';
        }

        // Default to string
        return 'string';
    }

    /**
     * Check if a route matches a regex pattern.
     *
     * @param string $route The route to check
     * @param string $pattern The regex pattern
     * @return bool True if the route matches
     */
    public static function matchesPattern(string $route, string $pattern): bool
    {
        // Escape the pattern for use as regex, then convert it to a proper regex
        $regex = '#^' . $pattern . '$#';

        return (bool) preg_match($regex, $route);
    }
}
