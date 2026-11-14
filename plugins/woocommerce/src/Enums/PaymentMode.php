<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Enum class for the payment mode a payment gateway records on an order with
 * WC_Order::set_payment_mode(), which tells whether it was paid in test or live mode.
 *
 * @since 11.4.0
 */
final class PaymentMode {
	/**
	 * The gateway charged the order in test mode.
	 *
	 * @var string
	 */
	public const TEST = 'test';

	/**
	 * The gateway charged the order in live mode.
	 *
	 * @var string
	 */
	public const LIVE = 'live';
}
