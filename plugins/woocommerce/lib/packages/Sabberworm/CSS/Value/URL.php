<?php

declare(strict_types=1);

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
    private $url;

    /**
     * @param int<1, max>|null $lineNumber
     */
    public function __construct(CSSString $url, ?int $lineNumber = null)
    {
        parent::__construct($lineNumber);
        $this->url = $url;
    }

    /**
     * @throws SourceException
     * @throws UnexpectedEOFException
     * @throws UnexpectedTokenException
     *
     * @internal since V8.8.0
     */
    public static function parse(ParserState $parserState): URL
    {
        $anchor = $parserState->anchor();
        $identifier = '';
        for ($i = 0; $i < 3; $i++) {
            $character = $parserState->parseCharacter(true);
            if ($character === null) {
                break;
            }
            $identifier .= $character;
        }
        $useUrl = $parserState->streql($identifier, 'url');
        if ($useUrl) {
            $parserState->consumeWhiteSpace();
            $parserState->consume('(');
        } else {
            $anchor->backtrack();
        }
        $parserState->consumeWhiteSpace();
        $result = new URL(CSSString::parse($parserState), $parserState->currentLine());
        if ($useUrl) {
            $parserState->consumeWhiteSpace();
            $parserState->consume(')');
        }
        return $result;
    }

    public function setURL(CSSString $url): void
    {
        $this->url = $url;
    }

    public function getURL(): CSSString
    {
        return $this->url;
    }

    /**
     * @return non-empty-string
     */
    public function render(OutputFormat $outputFormat): string
    {
        return "url({$this->url->render($outputFormat)})";
    }
}
