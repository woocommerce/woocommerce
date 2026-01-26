<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Enum class for all the order statuses.
 *
 * For a full documentation on the public order statuses, please refer to the following link:
 * https://woocommerce.com/document/managing-orders/order-statuses/
 */
final class OrderStatus {
	/**
	 * The order has been received, but no payment has been made.
	 *
	 * @var string
	 */
	public const PENDING = 'pending';

	/**
	 * The customer’s payment failed or was declined, and no payment has been successfully made.
	 *
	 * @var string
	 */
	public const FAILED = 'failed';

	/**
	 * The order is awaiting payment confirmation.
	 *
	 * @var string
	 */
	public const ON_HOLD = 'on-hold';

	/**
	 * Order fulfilled and complete.
	 *
	 * @var string
	 */
	public const COMPLETED = 'completed';

	/**
	 * Payment has been received (paid), and the stock has been reduced.
	 *
	 * @var string
	 */
	public const PROCESSING = 'processing';

	/**
	 * Orders are automatically put in the Refunded status when an admin or shop manager has fully refunded the order’s value after payment.
	 *
	 * @var string
	 */
	public const REFUNDED = 'refunded';

	/**
	 * The order was canceled by an admin or the customer.
	 *
	 * @var string
	 */
	public const CANCELLED = 'cancelled';

	/**
	 * The order is in the trash.
	 *
	 * @var string
	 */
	public const TRASH = 'trash';

	/**
	 * The order is a draft (legacy status).
	 *
	 * @var string
	 */
	public const NEW = 'new';

	/**
	 * The order is an automatically generated draft.
	 *
	 * @var string
	 */
	public const AUTO_DRAFT = 'auto-draft';

	/**
	 * Draft orders are created when customers start the checkout process while the block version of the checkout is in place.
	 *
	 * @var string
	 */
	public const DRAFT = 'draft';

	/**
	 * Checkout Draft orders are created when customers start the checkout process while the block version of the checkout is in place.
	 *
	 * @var string
	 */
	public const CHECKOUT_DRAFT = 'checkout-draft';

	/**
	 * Array of all the valid order statuses for a complete payment.
	 *
	 * @var string[]
	 */
	public const PAYMENT_COMPLETE_STATUSES = array(
		self::ON_HOLD,
		self::PENDING,
		self::FAILED,
		self::CANCELLED,
	);
}
