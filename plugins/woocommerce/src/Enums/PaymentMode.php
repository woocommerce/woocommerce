<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Enum class for the payment mode recorded on a paid order, which tells whether
 * its payment gateway was in test or live mode.
 *
 * @since 11.4.0
 */
final class PaymentMode {
	/**
	 * The order was paid while the gateway was in test mode.
	 *
	 * @var string
	 */
	public const TEST = 'test';

	/**
	 * The order was paid while the gateway was in live mode.
	 *
	 * @var string
	 */
	public const LIVE = 'live';
}
