<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS\Value;

use Automattic\WooCommerce\Vendor\Sabberworm\CSS\CSSElement;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\ParserState;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\SourceException;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\UnexpectedEOFException;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\UnexpectedTokenException;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Position\Position;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Position\Positionable;

/**
 * Abstract base class for specific classes of CSS values: `Size`, `Color`, `CSSString` and `URL`, and another
 * abstract subclass `ValueList`.
 */
abstract class Value implements CSSElement, Positionable
{
    use Position;

    /**
     * @param int<1, max>|null $lineNumber
     */
    public function __construct(?int $lineNumber = null)
    {
        $this->setPosition($lineNumber);
    }

    /**
     * @param array<non-empty-string> $listDelimiters
     *
     * @return Value|string
     *
     * @throws UnexpectedTokenException
     * @throws UnexpectedEOFException
     *
     * @internal since V8.8.0
     */
    public static function parseValue(ParserState $parserState, array $listDelimiters = [])
    {
        /** @var list<Value|string> $stack */
        $stack = [];
        $parserState->consumeWhiteSpace();
        //Build a list of delimiters and parsed values
        while (
        !($parserState->comes('}') || $parserState->comes(';') || $parserState->comes('!')
            || $parserState->comes(')')
            || $parserState->isEnd())
        ) {
            if (\count($stack) > 0) {
                $foundDelimiter = false;
                foreach ($listDelimiters as $delimiter) {
                    if ($parserState->comes($delimiter)) {
                        \array_push($stack, $parserState->consume($delimiter));
                        $parserState->consumeWhiteSpace();
                        $foundDelimiter = true;
                        break;
                    }
                }
                if (!$foundDelimiter) {
                    //Whitespace was the list delimiter
                    \array_push($stack, ' ');
                }
            }
            \array_push($stack, self::parsePrimitiveValue($parserState));
            $parserState->consumeWhiteSpace();
        }
        // Convert the list to list objects
        foreach ($listDelimiters as $delimiter) {
            $stackSize = \count($stack);
            if ($stackSize === 1) {
                return $stack[0];
            }
            $newStack = [];
            for ($offset = 0; $offset < $stackSize; ++$offset) {
                if ($offset === ($stackSize - 1) || $delimiter !== $stack[$offset + 1]) {
                    $newStack[] = $stack[$offset];
                    continue;
                }
                $length = 2; //Number of elements to be joined
                for ($i = $offset + 3; $i < $stackSize; $i += 2, ++$length) {
                    if ($delimiter !== $stack[$i]) {
                        break;
                    }
                }
                $list = new RuleValueList($delimiter, $parserState->currentLine());
                for ($i = $offset; $i - $offset < $length * 2; $i += 2) {
                    $list->addListComponent($stack[$i]);
                }
                $newStack[] = $list;
                $offset += $length * 2 - 2;
            }
            $stack = $newStack;
        }
        if (!isset($stack[0])) {
            throw new UnexpectedTokenException(
                " {$parserState->peek()} ",
                $parserState->peek(1, -1) . $parserState->peek(2),
                'literal',
                $parserState->currentLine()
            );
        }
        return $stack[0];
    }

    /**
     * @return CSSFunction|string
     *
     * @throws UnexpectedEOFException
     * @throws UnexpectedTokenException
     *
     * @internal since V8.8.0
     */
    public static function parseIdentifierOrFunction(ParserState $parserState, bool $ignoreCase = false)
    {
        $anchor = $parserState->anchor();
        $result = $parserState->parseIdentifier($ignoreCase);

        if ($parserState->comes('(')) {
            $anchor->backtrack();
            if ($parserState->streql('url', $result)) {
                $result = URL::parse($parserState);
            } elseif ($parserState->streql('calc', $result)) {
                $result = CalcFunction::parse($parserState);
            } else {
                $result = CSSFunction::parse($parserState, $ignoreCase);
            }
        }

        return $result;
    }

    /**
     * @return CSSFunction|CSSString|LineName|Size|URL|string
     *
     * @throws UnexpectedEOFException
     * @throws UnexpectedTokenException
     * @throws SourceException
     *
     * @internal since V8.8.0
     */
    public static function parsePrimitiveValue(ParserState $parserState)
    {
        $value = null;
        $parserState->consumeWhiteSpace();
        if (
            \is_numeric($parserState->peek())
            || ($parserState->comes('-.')
                && \is_numeric($parserState->peek(1, 2)))
            || (($parserState->comes('-') || $parserState->comes('.')) && \is_numeric($parserState->peek(1, 1)))
        ) {
            $value = Size::parse($parserState);
        } elseif ($parserState->comes('#') || $parserState->comes('rgb', true) || $parserState->comes('hsl', true)) {
            $value = Color::parse($parserState);
        } elseif ($parserState->comes("'") || $parserState->comes('"')) {
            $value = CSSString::parse($parserState);
        } elseif ($parserState->comes('progid:') && $parserState->getSettings()->usesLenientParsing()) {
            $value = self::parseMicrosoftFilter($parserState);
        } elseif ($parserState->comes('[')) {
            $value = LineName::parse($parserState);
        } elseif ($parserState->comes('U+')) {
            $value = self::parseUnicodeRangeValue($parserState);
        } else {
            $nextCharacter = $parserState->peek(1);
            try {
                $value = self::parseIdentifierOrFunction($parserState);
            } catch (UnexpectedTokenException $e) {
                if (\in_array($nextCharacter, ['+', '-', '*', '/'], true)) {
                    $value = $parserState->consume(1);
                } else {
                    throw $e;
                }
            }
        }
        $parserState->consumeWhiteSpace();

        return $value;
    }

    /**
     * @throws UnexpectedEOFException
     * @throws UnexpectedTokenException
     */
    private static function parseMicrosoftFilter(ParserState $parserState): CSSFunction
    {
        $function = $parserState->consumeUntil('(', false, true);
        $arguments = Value::parseValue($parserState, [',', '=']);
        return new CSSFunction($function, $arguments, ',', $parserState->currentLine());
    }

    /**
     * @throws UnexpectedEOFException
     * @throws UnexpectedTokenException
     */
    private static function parseUnicodeRangeValue(ParserState $parserState): string
    {
        $codepointMaxLength = 6; // Code points outside BMP can use up to six digits
        $range = '';
        $parserState->consume('U+');
        do {
            if ($parserState->comes('-')) {
                $codepointMaxLength = 13; // Max length is 2 six-digit code points + the dash(-) between them
            }
            $range .= $parserState->consume(1);
        } while (\strlen($range) < $codepointMaxLength && \preg_match('/[A-Fa-f0-9\\?-]/', $parserState->peek()));

        return "U+{$range}";
    }
}
