<?php

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS\Value;

abstract class PrimitiveValue extends Value
{
    /**
     * @param int $iLineNo
     */
    public function __construct($iLineNo = 0)
    {
        parent::__construct($iLineNo);
    }
}
