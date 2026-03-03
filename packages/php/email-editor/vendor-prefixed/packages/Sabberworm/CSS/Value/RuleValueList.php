<?php

namespace Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Value;

/**
 * This class is used to represent all multivalued rules like `font: bold 12px/3 Helvetica, Verdana, sans-serif;`
 * (where the value would be a whitespace-separated list of the primitive value `bold`, a slash-separated list
 * and a comma-separated list).
 */
class RuleValueList extends ValueList
{
    /**
     * @param string $sSeparator
     * @param int $iLineNo
     */
    public function __construct($sSeparator = ',', $iLineNo = 0)
    {
        parent::__construct([], $sSeparator, $iLineNo);
    }
}
