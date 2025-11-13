<?php

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS\Value;

use Automattic\WooCommerce\Vendor\Sabberworm\CSS\OutputFormat;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\ParserState;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\SourceException;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\UnexpectedEOFException;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\UnexpectedTokenException;

/**
 * This class represents URLs in CSS. `URL`s always output in `URL("")` notation.
 */
class URL extends PrimitiveValue
{
    /**
     * @var CSSString
     */
    private $oURL;

    /**
     * @param int $iLineNo
     */
    public function __construct(CSSString $oURL, $iLineNo = 0)
    {
        parent::__construct($iLineNo);
        $this->oURL = $oURL;
    }

    /**
     * @return URL
     *
     * @throws SourceException
     * @throws UnexpectedEOFException
     * @throws UnexpectedTokenException
     *
     * @internal since V8.8.0
     */
    public static function parse(ParserState $oParserState)
    {
        $oAnchor = $oParserState->anchor();
        $sIdentifier = '';
        for ($i = 0; $i < 3; $i++) {
            $sChar = $oParserState->parseCharacter(true);
            if ($sChar === null) {
                break;
            }
            $sIdentifier .= $sChar;
        }
        $bUseUrl = $oParserState->streql($sIdentifier, 'url');
        if ($bUseUrl) {
            $oParserState->consumeWhiteSpace();
            $oParserState->consume('(');
        } else {
            $oAnchor->backtrack();
        }
        $oParserState->consumeWhiteSpace();
        $oResult = new URL(CSSString::parse($oParserState), $oParserState->currentLine());
        if ($bUseUrl) {
            $oParserState->consumeWhiteSpace();
            $oParserState->consume(')');
        }
        return $oResult;
    }

    /**
     * @return void
     */
    public function setURL(CSSString $oURL)
    {
        $this->oURL = $oURL;
    }

    /**
     * @return CSSString
     */
    public function getURL()
    {
        return $this->oURL;
    }

    /**
     * @return string
     *
     * @deprecated in V8.8.0, will be removed in V9.0.0. Use `render` instead.
     */
    public function __toString()
    {
        return $this->render(new OutputFormat());
    }

    /**
     * @param OutputFormat|null $oOutputFormat
     *
     * @return string
     */
    public function render($oOutputFormat)
    {
        return "url({$this->oURL->render($oOutputFormat)})";
    }
}
