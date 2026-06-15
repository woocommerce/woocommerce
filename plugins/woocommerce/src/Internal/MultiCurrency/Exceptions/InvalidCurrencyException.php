<?php
/**
 * InvalidCurrencyException class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Exceptions;

/**
 * Exception thrown when a currency code is not available for conversion.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class InvalidCurrencyException extends \RuntimeException {}
