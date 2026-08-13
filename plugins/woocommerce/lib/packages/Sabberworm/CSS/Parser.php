<?php

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS;

use Automattic\WooCommerce\Vendor\Sabberworm\CSS\CSSList\Document;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\ParserState;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\SourceException;

/**
 * This class parses CSS from text into a data structure.
 */
class Parser
{
    /**
     * @var ParserState
     */
    private $oParserState;

    /**
     * @param string $sText the complete CSS as text (i.e., usually the contents of a CSS file)
     * @param Settings|null $oParserSettings
     * @param int $iLineNo the line number (starting from 1, not from 0)
     */
    public function __construct($sText, $oParserSettings = null, $iLineNo = 1)
    {
        if ($oParserSettings === null) {
            $oParserSettings = Settings::create();
        }
        $this->oParserState = new ParserState($sText, $oParserSettings, $iLineNo);
    }

    /**
     * Sets the charset to be used if the CSS does not contain an `@charset` declaration.
     *
     * @param string $sCharset
     *
     * @return void
     *
     * @deprecated since 8.7.0, will be removed in version 9.0.0 with #687
     */
    public function setCharset($sCharset)
    {
        $this->oParserState->setCharset($sCharset);
    }

    /**
     * Returns the charset that is used if the CSS does not contain an `@charset` declaration.
     *
     * @return void
     *
     * @deprecated since 8.7.0, will be removed in version 9.0.0 with #687
     */
    public function getCharset()
    {
        // Note: The `return` statement is missing here. This is a bug that needs to be fixed.
        $this->oParserState->getCharset();
    }

    /**
     * Parses the CSS provided to the constructor and creates a `Document` from it.
     *
     * @return Document
     *
     * @throws SourceException
     */
    public function parse()
    {
        return Document::parse($this->oParserState);
    }
}
