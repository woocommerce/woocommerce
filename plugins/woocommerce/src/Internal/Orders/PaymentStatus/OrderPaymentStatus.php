<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Orders\PaymentStatus;

/**
 * Enum class for all the internal order payment statuses.
 */
final class OrderPaymentStatus {
	public const PENDING            = 'pending'; // Payment pending e.g. payment not received yet but is required.
	public const PAID               = 'paid'; // Payment received.
	public const ON_SITE            = 'on_site_payment'; // Payment received on site e.g. cash on delivery.
	public const FAILED             = 'failed'; // Payment failed.
	public const CANCELLED          = 'cancelled'; // Payment cancelled.
	public const REFUNDED           = 'refunded'; // Payment refunded.
	public const PARTIALLY_REFUNDED = 'partially_refunded'; // Payment partially refunded.
}
