<?php

namespace Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Value;

use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\OutputFormat;

/**
 * A `ValueList` represents a lists of `Value`s, separated by some separation character
 * (mostly `,`, whitespace, or `/`).
 *
 * There are two types of `ValueList`s: `RuleValueList` and `CSSFunction`
 */
abstract class ValueList extends Value
{
    /**
     * @var array<int, RuleValueList|CSSFunction|CSSString|LineName|Size|URL|string>
     *
     * @internal since 8.8.0
     */
    protected $aComponents;

    /**
     * @var string
     *
     * @internal since 8.8.0
     */
    protected $sSeparator;

    /**
     * phpcs:ignore Generic.Files.LineLength
     * @param array<int, RuleValueList|CSSFunction|CSSString|LineName|Size|URL|string>|RuleValueList|CSSFunction|CSSString|LineName|Size|URL|string $aComponents
     * @param string $sSeparator
     * @param int $iLineNo
     */
    public function __construct($aComponents = [], $sSeparator = ',', $iLineNo = 0)
    {
        parent::__construct($iLineNo);
        if (!is_array($aComponents)) {
            $aComponents = [$aComponents];
        }
        $this->aComponents = $aComponents;
        $this->sSeparator = $sSeparator;
    }

    /**
     * @param RuleValueList|CSSFunction|CSSString|LineName|Size|URL|string $mComponent
     *
     * @return void
     */
    public function addListComponent($mComponent)
    {
        $this->aComponents[] = $mComponent;
    }

    /**
     * @return array<int, RuleValueList|CSSFunction|CSSString|LineName|Size|URL|string>
     */
    public function getListComponents()
    {
        return $this->aComponents;
    }

    /**
     * @param array<int, RuleValueList|CSSFunction|CSSString|LineName|Size|URL|string> $aComponents
     *
     * @return void
     */
    public function setListComponents(array $aComponents)
    {
        $this->aComponents = $aComponents;
    }

    /**
     * @return string
     */
    public function getListSeparator()
    {
        return $this->sSeparator;
    }

    /**
     * @param string $sSeparator
     *
     * @return void
     */
    public function setListSeparator($sSeparator)
    {
        $this->sSeparator = $sSeparator;
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
        return $oOutputFormat->implode(
            $oOutputFormat->spaceBeforeListArgumentSeparator($this->sSeparator) . $this->sSeparator
            . $oOutputFormat->spaceAfterListArgumentSeparator($this->sSeparator),
            $this->aComponents
        );
    }
}
