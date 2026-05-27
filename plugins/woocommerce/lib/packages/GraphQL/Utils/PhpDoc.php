<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Utils;

class PhpDoc
{
    /** @param string|false|null $docBlock */
    public static function unwrap($docBlock): ?string
    {
        if ($docBlock === false || $docBlock === null) {
            return null;
        }

        $content = preg_replace('~([\r\n]) \* (.*)~i', '$1$2', $docBlock); // strip *
        assert(is_string($content), 'regex is statically known to be valid');

        $content = preg_replace('~([\r\n])[\* ]+([\r\n])~i', '$1$2', $content); // strip single-liner *
        assert(is_string($content), 'regex is statically known to be valid');

        $content = substr($content, 3); // strip leading /**
        $content = substr($content, 0, -2); // strip trailing */

        return static::nonEmptyOrNull($content);
    }

    /** @param string|false|null $docBlock */
    public static function unpad($docBlock): ?string
    {
        if ($docBlock === false || $docBlock === null) {
            return null;
        }

        $lines = explode("\n", $docBlock);
        $lines = array_map(
            static fn (string $line): string => ' ' . trim($line),
            $lines
        );

        $content = implode("\n", $lines);

        return static::nonEmptyOrNull($content);
    }

    protected static function nonEmptyOrNull(string $maybeEmptyString): ?string
    {
        $trimmed = trim($maybeEmptyString);

        return $trimmed === ''
            ? null
            : $trimmed;
    }
}
