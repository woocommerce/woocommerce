<?php

namespace Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Property;

use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Comment\Comment;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\OutputFormat;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Position\Position;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Position\Positionable;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Value\CSSString;

/**
 * Class representing an `@charset` rule.
 *
 * The following restrictions apply:
 * - May not be found in any CSSList other than the Document.
 * - May only appear at the very top of a Document’s contents.
 * - Must not appear more than once.
 */
class Charset implements AtRule, Positionable
{
    use Position;

    /**
     * @var CSSString
     */
    private $oCharset;

    /**
     * @var int
     *
     * @internal since 8.8.0
     */
    protected $iLineNo;

    /**
     * @var array<array-key, Comment>
     *
     * @internal since 8.8.0
     */
    protected $aComments;

    /**
     * @param CSSString $oCharset
     * @param int $iLineNo
     */
    public function __construct(CSSString $oCharset, $iLineNo = 0)
    {
        $this->oCharset = $oCharset;
        $this->setPosition($iLineNo);
        $this->aComments = [];
    }

    /**
     * @param string|CSSString $oCharset
     *
     * @return void
     */
    public function setCharset($sCharset)
    {
        $sCharset = $sCharset instanceof CSSString ? $sCharset : new CSSString($sCharset);
        $this->oCharset = $sCharset;
    }

    /**
     * @return string
     */
    public function getCharset()
    {
        return $this->oCharset->getString();
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
        return "{$oOutputFormat->comments($this)}@charset {$this->oCharset->render($oOutputFormat)};";
    }

    /**
     * @return string
     */
    public function atRuleName()
    {
        return 'charset';
    }

    /**
     * @return string
     */
    public function atRuleArgs()
    {
        return $this->oCharset;
    }

    /**
     * @param array<array-key, Comment> $aComments
     *
     * @return void
     */
    public function addComments(array $aComments)
    {
        $this->aComments = array_merge($this->aComments, $aComments);
    }

    /**
     * @return array<array-key, Comment>
     */
    public function getComments()
    {
        return $this->aComments;
    }

    /**
     * @param array<array-key, Comment> $aComments
     *
     * @return void
     */
    public function setComments(array $aComments)
    {
        $this->aComments = $aComments;
    }
}
