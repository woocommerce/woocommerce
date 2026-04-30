<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language;

use Automattic\WooCommerce\Vendor\GraphQL\Utils\Utils;

/**
 * @see \Automattic\WooCommerce\Vendor\GraphQL\Tests\Language\BlockStringTest
 */
class BlockString
{
    /**
     * Produces the value of a block string from its parsed raw value, similar to
     * CoffeeScript's block string, Python's docstring trim or Ruby's strip_heredoc.
     *
     * This implements the Automattic\WooCommerce\Vendor\GraphQL spec's BlockStringValue() static algorithm.
     */
    public static function dedentBlockStringLines(string $rawString): string
    {
        $lines = Utils::splitLines($rawString);

        // Remove common indentation from all lines but first.
        $commonIndent = self::getIndentation($rawString);
        $linesLength = count($lines);

        if ($commonIndent > 0) {
            for ($i = 1; $i < $linesLength; ++$i) {
                $lines[$i] = mb_substr($lines[$i], $commonIndent);
            }
        }

        // Remove leading and trailing blank lines.
        $startLine = 0;
        while ($startLine < $linesLength && self::isBlank($lines[$startLine])) {
            ++$startLine;
        }

        $endLine = $linesLength;
        while ($endLine > $startLine && self::isBlank($lines[$endLine - 1])) {
            --$endLine;
        }

        // Return a string of the lines joined with U+000A.
        return implode("\n", array_slice($lines, $startLine, $endLine - $startLine));
    }

    private static function isBlank(string $str): bool
    {
        $strLength = mb_strlen($str);
        for ($i = 0; $i < $strLength; ++$i) {
            if ($str[$i] !== ' ' && $str[$i] !== '\t') {
                return false;
            }
        }

        return true;
    }

    public static function getIndentation(string $value): int
    {
        $isFirstLine = true;
        $isEmptyLine = true;
        $indent = 0;
        $commonIndent = null;
        $valueLength = mb_strlen($value);

        for ($i = 0; $i < $valueLength; ++$i) {
            switch (Utils::charCodeAt($value, $i)) {
                case 13: //  \r
                    if (Utils::charCodeAt($value, $i + 1) === 10) {
                        ++$i; // skip \r\n as one symbol
                    }
                    // falls through
                    // no break
                case 10: //  \n
                    $isFirstLine = false;
                    $isEmptyLine = true;
                    $indent = 0;
                    break;
                case 9: //   \t
                case 32: //  <space>
                    ++$indent;
                    break;
                default:
                    if (
                        $isEmptyLine
                        && ! $isFirstLine
                        && ($commonIndent === null || $indent < $commonIndent)
                    ) {
                        $commonIndent = $indent;
                    }

                    $isEmptyLine = false;
            }
        }

        return $commonIndent ?? 0;
    }

    /**
     * Print a block string in the indented block form by adding a leading and
     * trailing blank line. However, if a block string starts with whitespace and is
     * a single-line, adding a leading blank line would strip that whitespace.
     */
    public static function print(string $value): string
    {
        $escapedValue = str_replace('"""', '\\"""', $value);

        // Expand a block string's raw value into independent lines.
        $lines = Utils::splitLines($escapedValue);
        $isSingleLine = count($lines) === 1;

        // If common indentation is found we can fix some of those cases by adding leading new line
        $forceLeadingNewLine = count($lines) > 1;
        foreach ($lines as $i => $line) {
            if ($i === 0) {
                continue;
            }

            if ($line !== '' && preg_match('/^\s/', $line) !== 1) {
                $forceLeadingNewLine = false;
            }
        }

        // Trailing triple quotes just looks confusing but doesn't force trailing new line
        $hasTrailingTripleQuotes = preg_match('/\\\\"""$/', $escapedValue) === 1;

        // Trailing quote (single or double) or slash forces trailing new line
        $hasTrailingQuote = preg_match('/"$/', $value) === 1 && ! $hasTrailingTripleQuotes;
        $hasTrailingSlash = preg_match('/\\\\$/', $value) === 1;
        $forceTrailingNewline = $hasTrailingQuote || $hasTrailingSlash;

        // add leading and trailing new lines only if it improves readability
        $printAsMultipleLines = ! $isSingleLine
            || mb_strlen($value) > 70
            || $forceTrailingNewline
            || $forceLeadingNewLine
            || $hasTrailingTripleQuotes;

        $result = '';

        // Format a multi-line block quote to account for leading space.
        $skipLeadingNewLine = $isSingleLine && preg_match('/^\s/', $value) === 1;
        if (($printAsMultipleLines && ! $skipLeadingNewLine) || $forceLeadingNewLine) {
            $result .= "\n";
        }

        $result .= $escapedValue;
        if ($printAsMultipleLines) {
            $result .= "\n";
        }

        return '"""' . $result . '"""';
    }
}
