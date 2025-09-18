<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS;

interface Renderable
{
    public function render(OutputFormat $outputFormat): string;
}
