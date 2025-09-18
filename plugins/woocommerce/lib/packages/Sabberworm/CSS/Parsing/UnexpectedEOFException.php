<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing;

/**
 * Thrown if the CSS parser encounters end of file it did not expect.
 *
 * Extends `UnexpectedTokenException` in order to preserve backwards compatibility.
 */
final class UnexpectedEOFException extends UnexpectedTokenException {}
