<?php

namespace Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS;

interface Renderable
{
    /**
     * @return string
     *
     * @deprecated in V8.8.0, will be removed in V9.0.0. Use `render` instead.
     */
    public function __toString();

    /**
     * @param OutputFormat|null $oOutputFormat
     *
     * @return string
     */
    public function render($oOutputFormat);

    /**
     * @return int
     */
    public function getLineNo();
}
