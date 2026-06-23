<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Utils;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Error\Warning;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\Node;

class Utils
{
    public static function undefined(): \stdClass
    {
        static $undefined;

        return $undefined ??= new \stdClass();
    }

    /** @param array<string, mixed> $vars */
    public static function assign(object $obj, array $vars): object
    {
        foreach ($vars as $key => $value) {
            if (! property_exists($obj, $key)) {
                $cls = get_class($obj);
                Warning::warn(
                    "Trying to set non-existing property '{$key}' on class '{$cls}'",
                    Warning::WARNING_ASSIGN
                );
            }

            $obj->{$key} = $value;
        }

        return $obj;
    }

    /**
     * Print a value that came from JSON for debugging purposes.
     *
     * @param mixed $value
     */
    public static function printSafeJson($value): string
    {
        if ($value instanceof \stdClass) {
            return static::jsonEncodeOrSerialize($value);
        }

        return static::printSafeInternal($value);
    }

    /**
     * Print a value that came from PHP for debugging purposes.
     *
     * @param mixed $value
     */
    public static function printSafe($value): string
    {
        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return $value->__toString();
            }

            return 'instance of ' . get_class($value);
        }

        return static::printSafeInternal($value);
    }

    /** @param \stdClass|array<mixed> $value */
    protected static function jsonEncodeOrSerialize($value): string
    {
        try {
            return json_encode($value, JSON_THROW_ON_ERROR);
        } catch (\JsonException $jsonException) {
            return serialize($value);
        }
    }

    /** @param mixed $value */
    protected static function printSafeInternal($value): string
    {
        if (is_array($value)) {
            return static::jsonEncodeOrSerialize($value);
        }

        if ($value === '') {
            return '(empty string)';
        }

        if ($value === null) {
            return 'null';
        }

        if ($value === false) {
            return 'false';
        }

        if ($value === true) {
            return 'true';
        }

        if (is_string($value)) {
            return "\"{$value}\"";
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return gettype($value);
    }

    /** UTF-8 compatible chr(). */
    public static function chr(int $ord, string $encoding = 'UTF-8'): string
    {
        if ($encoding === 'UCS-4BE') {
            return pack('N', $ord);
        }

        return mb_convert_encoding(self::chr($ord, 'UCS-4BE'), $encoding, 'UCS-4BE');
    }

    /** UTF-8 compatible ord(). */
    public static function ord(string $char, string $encoding = 'UTF-8'): int
    {
        if (! isset($char[1])) {
            return ord($char);
        }

        if ($encoding !== 'UCS-4BE') {
            $char = mb_convert_encoding($char, 'UCS-4BE', $encoding);
            assert(is_string($char), 'format string is statically known to be correct');
        }

        $unpacked = unpack('N', $char);
        assert(is_array($unpacked), 'format string is statically known to be correct');

        return $unpacked[1];
    }

    /** Returns UTF-8 char code at given $positing of the $string. */
    public static function charCodeAt(string $string, int $position): int
    {
        $char = mb_substr($string, $position, 1, 'UTF-8');

        return self::ord($char);
    }

    /** @throws \JsonException */
    public static function printCharCode(?int $code): string
    {
        if ($code === null) {
            return '<EOF>';
        }

        return $code < 0x007F
            // Trust JSON for ASCII
            ? json_encode(self::chr($code), JSON_THROW_ON_ERROR)
            // Otherwise, print the escaped form
            : '"\\u' . dechex($code) . '"';
    }

    /**
     * Upholds the spec rules about naming.
     *
     * @throws Error
     */
    public static function assertValidName(string $name): void
    {
        $error = self::isValidNameError($name);
        if ($error !== null) {
            throw $error;
        }
    }

    /** Returns an Error if a name is invalid. */
    public static function isValidNameError(string $name, ?Node $node = null): ?Error
    {
        if (isset($name[1]) && $name[0] === '_' && $name[1] === '_') {
            return new Error(
                "Name \"{$name}\" must not begin with \"__\", which is reserved by Automattic\WooCommerce\Vendor\GraphQL introspection.",
                $node
            );
        }

        if (preg_match('/^[_a-zA-Z][_a-zA-Z0-9]*$/', $name) !== 1) {
            return new Error(
                "Names must match /^[_a-zA-Z][_a-zA-Z0-9]*\$/ but \"{$name}\" does not.",
                $node
            );
        }

        return null;
    }

    /** @param array<string> $items */
    public static function quotedOrList(array $items): string
    {
        $quoted = array_map(
            static fn (string $item): string => "\"{$item}\"",
            $items
        );

        return self::orList($quoted);
    }

    /** @param array<string> $items */
    public static function orList(array $items): string
    {
        if ($items === []) {
            return '';
        }

        $selected = array_slice($items, 0, 5);
        $selectedLength = count($selected);
        $firstSelected = $selected[0];

        if ($selectedLength === 1) {
            return $firstSelected;
        }

        return array_reduce(
            range(1, $selectedLength - 1),
            static fn ($list, $index): string => $list
                . ($selectedLength > 2 ? ', ' : ' ')
                . ($index === $selectedLength - 1 ? 'or ' : '')
                . $selected[$index],
            $firstSelected
        );
    }

    /**
     * Given an invalid input string and a list of valid options, returns a filtered
     * list of valid options sorted based on their similarity with the input.
     *
     * @param array<string> $options
     *
     * @return array<int, string>
     */
    public static function suggestionList(string $input, array $options): array
    {
        /** @var array<string, int> $optionsByDistance */
        $optionsByDistance = [];
        $lexicalDistance = new LexicalDistance($input);
        $threshold = mb_strlen($input) * 0.4 + 1;
        foreach ($options as $option) {
            $distance = $lexicalDistance->measure($option, $threshold);

            if ($distance !== null) {
                $optionsByDistance[$option] = $distance;
            }
        }

        uksort($optionsByDistance, static function (string $a, string $b) use ($optionsByDistance) {
            $distanceDiff = $optionsByDistance[$a] - $optionsByDistance[$b];

            return $distanceDiff !== 0 ? $distanceDiff : strnatcmp($a, $b);
        });

        return array_map('strval', array_keys($optionsByDistance));
    }

    /**
     * Try to extract the value for a key from an object like value.
     *
     * @param mixed $objectLikeValue
     *
     * @return mixed
     */
    public static function extractKey($objectLikeValue, string $key)
    {
        if (is_array($objectLikeValue) || $objectLikeValue instanceof \ArrayAccess) {
            return $objectLikeValue[$key] ?? null;
        }

        if (is_object($objectLikeValue)) {
            return $objectLikeValue->{$key} ?? null;
        }

        return null;
    }

    /**
     * Split a string that has either Unix, Windows or Mac style newlines into lines.
     *
     * @return list<string>
     */
    public static function splitLines(string $value): array
    {
        $lines = preg_split("/\r\n|\r|\n/", $value);
        assert(is_array($lines), 'given the regex is valid');

        return $lines;
    }
}
