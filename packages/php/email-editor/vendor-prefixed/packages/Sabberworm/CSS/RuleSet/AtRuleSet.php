<?php

namespace Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\RuleSet;

use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\OutputFormat;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Property\AtRule;

/**
 * This class represents rule sets for generic at-rules which are not covered by specific classes, i.e., not
 * `@import`, `@charset` or `@media`.
 *
 * A common example for this is `@font-face`.
 */
class AtRuleSet extends RuleSet implements AtRule
{
    /**
     * @var string
     */
    private $sType;

    /**
     * @var string
     */
    private $sArgs;

    /**
     * @param string $sType
     * @param string $sArgs
     * @param int $iLineNo
     */
    public function __construct($sType, $sArgs = '', $iLineNo = 0)
    {
        parent::__construct($iLineNo);
        $this->sType = $sType;
        $this->sArgs = $sArgs;
    }

    /**
     * @return string
     */
    public function atRuleName()
    {
        return $this->sType;
    }

    /**
     * @return string
     */
    public function atRuleArgs()
    {
        return $this->sArgs;
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
        $sResult = $oOutputFormat->comments($this);
        $sArgs = $this->sArgs;
        if ($sArgs) {
            $sArgs = ' ' . $sArgs;
        }
        $sResult .= "@{$this->sType}$sArgs{$oOutputFormat->spaceBeforeOpeningBrace()}{";
        $sResult .= $this->renderRules($oOutputFormat);
        $sResult .= '}';
        return $sResult;
    }
}
