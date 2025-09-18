<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing;

use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Position\Position;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Position\Positionable;

class SourceException extends \Exception implements Positionable
{
    use Position;

    /**
     * @param int<1, max>|null $lineNumber
     */
    public function __construct(string $message, ?int $lineNumber = null)
    {
        $this->setPosition($lineNumber);
        if ($lineNumber !== null) {
            $message .= " [line no: $lineNumber]";
        }
        parent::__construct($message);
    }
}
