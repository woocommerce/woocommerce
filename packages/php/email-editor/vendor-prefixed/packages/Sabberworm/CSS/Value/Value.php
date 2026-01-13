<?php

namespace Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Value;

use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\CSSElement;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Parsing\ParserState;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Parsing\SourceException;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Parsing\UnexpectedEOFException;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Parsing\UnexpectedTokenException;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Position\Position;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Position\Positionable;

/**
 * Abstract base class for specific classes of CSS values: `Size`, `Color`, `CSSString` and `URL`, and another
 * abstract subclass `ValueList`.
 */
abstract class Value implements CSSElement, Positionable
{
    use Position;

    /**
     * @param int $iLineNo
     */
    public function __construct($iLineNo = 0)
    {
        $this->setPosition($iLineNo);
    }

    /**
     * @param array<array-key, string> $aListDelimiters
     *
     * @return RuleValueList|CSSFunction|CSSString|LineName|Size|URL|string
     *
     * @throws UnexpectedTokenException
     * @throws UnexpectedEOFException
     *
     * @internal since V8.8.0
     */
    public static function parseValue(ParserState $oParserState, array $aListDelimiters = [])
    {
        /** @var array<int, RuleValueList|CSSFunction|CSSString|LineName|Size|URL|string> $aStack */
        $aStack = [];
        $oParserState->consumeWhiteSpace();
        //Build a list of delimiters and parsed values
        while (
            !($oParserState->comes('}') || $oParserState->comes(';') || $oParserState->comes('!')
                || $oParserState->comes(')')
                || $oParserState->comes('\\')
                || $oParserState->isEnd())
        ) {
            if (count($aStack) > 0) {
                $bFoundDelimiter = false;
                foreach ($aListDelimiters as $sDelimiter) {
                    if ($oParserState->comes($sDelimiter)) {
                        array_push($aStack, $oParserState->consume($sDelimiter));
                        $oParserState->consumeWhiteSpace();
                        $bFoundDelimiter = true;
                        break;
                    }
                }
                if (!$bFoundDelimiter) {
                    //Whitespace was the list delimiter
                    array_push($aStack, ' ');
                }
            }
            array_push($aStack, self::parsePrimitiveValue($oParserState));
            $oParserState->consumeWhiteSpace();
        }
        // Convert the list to list objects
        foreach ($aListDelimiters as $sDelimiter) {
            $iStackLength = count($aStack);
            if ($iStackLength === 1) {
                return $aStack[0];
            }
            $aNewStack = [];
            for ($iStartPosition = 0; $iStartPosition < $iStackLength; ++$iStartPosition) {
                if ($iStartPosition === ($iStackLength - 1) || $sDelimiter !== $aStack[$iStartPosition + 1]) {
                    $aNewStack[] = $aStack[$iStartPosition];
                    continue;
                }
                $iLength = 2; //Number of elements to be joined
                for ($i = $iStartPosition + 3; $i < $iStackLength; $i += 2, ++$iLength) {
                    if ($sDelimiter !== $aStack[$i]) {
                        break;
                    }
                }
                $oList = new RuleValueList($sDelimiter, $oParserState->currentLine());
                for ($i = $iStartPosition; $i - $iStartPosition < $iLength * 2; $i += 2) {
                    $oList->addListComponent($aStack[$i]);
                }
                $aNewStack[] = $oList;
                $iStartPosition += $iLength * 2 - 2;
            }
            $aStack = $aNewStack;
        }
        if (!isset($aStack[0])) {
            throw new UnexpectedTokenException(
                " {$oParserState->peek()} ",
                $oParserState->peek(1, -1) . $oParserState->peek(2),
                'literal',
                $oParserState->currentLine()
            );
        }
        return $aStack[0];
    }

    /**
     * @param bool $bIgnoreCase
     *
     * @return CSSFunction|string
     *
     * @throws UnexpectedEOFException
     * @throws UnexpectedTokenException
     *
     * @internal since V8.8.0
     */
    public static function parseIdentifierOrFunction(ParserState $oParserState, $bIgnoreCase = false)
    {
        $oAnchor = $oParserState->anchor();
        $mResult = $oParserState->parseIdentifier($bIgnoreCase);

        if ($oParserState->comes('(')) {
            $oAnchor->backtrack();
            if ($oParserState->streql('url', $mResult)) {
                $mResult = URL::parse($oParserState);
            } elseif (
                $oParserState->streql('calc', $mResult)
                || $oParserState->streql('-webkit-calc', $mResult)
                || $oParserState->streql('-moz-calc', $mResult)
            ) {
                $mResult = CalcFunction::parse($oParserState);
            } else {
                $mResult = CSSFunction::parse($oParserState, $bIgnoreCase);
            }
        }

        return $mResult;
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
    public static function parsePrimitiveValue(ParserState $oParserState)
    {
        $oValue = null;
        $oParserState->consumeWhiteSpace();
        if (
            is_numeric($oParserState->peek())
            || ($oParserState->comes('-.')
                && is_numeric($oParserState->peek(1, 2)))
            || (($oParserState->comes('-') || $oParserState->comes('.')) && is_numeric($oParserState->peek(1, 1)))
        ) {
            $oValue = Size::parse($oParserState);
        } elseif ($oParserState->comes('#') || $oParserState->comes('rgb', true) || $oParserState->comes('hsl', true)) {
            $oValue = Color::parse($oParserState);
        } elseif ($oParserState->comes("'") || $oParserState->comes('"')) {
            $oValue = CSSString::parse($oParserState);
        } elseif ($oParserState->comes("progid:") && $oParserState->getSettings()->bLenientParsing) {
            $oValue = self::parseMicrosoftFilter($oParserState);
        } elseif ($oParserState->comes("[")) {
            $oValue = LineName::parse($oParserState);
        } elseif ($oParserState->comes("U+")) {
            $oValue = self::parseUnicodeRangeValue($oParserState);
        } else {
            $sNextChar = $oParserState->peek(1);
            try {
                $oValue = self::parseIdentifierOrFunction($oParserState);
            } catch (UnexpectedTokenException $e) {
                if (\in_array($sNextChar, ['+', '-', '*', '/'], true)) {
                    $oValue = $oParserState->consume(1);
                } else {
                    throw $e;
                }
            }
        }
        $oParserState->consumeWhiteSpace();
        return $oValue;
    }

    /**
     * @return CSSFunction
     *
     * @throws UnexpectedEOFException
     * @throws UnexpectedTokenException
     */
    private static function parseMicrosoftFilter(ParserState $oParserState)
    {
        $sFunction = $oParserState->consumeUntil('(', false, true);
        $aArguments = Value::parseValue($oParserState, [',', '=']);
        return new CSSFunction($sFunction, $aArguments, ',', $oParserState->currentLine());
    }

    /**
     * @return string
     *
     * @throws UnexpectedEOFException
     * @throws UnexpectedTokenException
     */
    private static function parseUnicodeRangeValue(ParserState $oParserState)
    {
        $iCodepointMaxLength = 6; // Code points outside BMP can use up to six digits
        $sRange = "";
        $oParserState->consume("U+");
        do {
            if ($oParserState->comes('-')) {
                $iCodepointMaxLength = 13; // Max length is 2 six digit code points + the dash(-) between them
            }
            $sRange .= $oParserState->consume(1);
        } while (strlen($sRange) < $iCodepointMaxLength && preg_match("/[A-Fa-f0-9\?-]/", $oParserState->peek()));
        return "U+{$sRange}";
    }
}
