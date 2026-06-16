<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

/**
 * Test exception for WooPayments cutover wp_die assertions.
 */
class WooPaymentsCutoverBlockedException extends \RuntimeException {}
