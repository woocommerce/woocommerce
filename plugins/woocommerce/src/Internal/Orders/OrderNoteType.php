<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Orders;

/**
 * Enum class for order note types. This is stored as meta data to categorize order notes.
 *
 * If a note is not defined, the note will be assumed to be a customer/private note added by the merchant.
 *
 * This is not surfaced in core UI presently.
 */
final class OrderNoteType {
	/**
	 * Any note that is not categorized.
	 *
	 * @var string
	 */
	public const DEFAULT = '';

	/**
	 * Any note concerning errors.
	 *
	 * @var string
	 */
	public const ERROR = 'error';

	/**
	 * Any note concerning emails to customers.
	 *
	 * @var string
	 */
	public const CONFIRMATION_EMAIL = 'confirmation_email';

	/**
	 * Any note concerning stock levels.
	 *
	 * @var string
	 */
	public const PRODUCT_STOCK = 'product_stock';

	/**
	 * Any note concerning payments.
	 *
	 * @var string
	 */
	public const PAYMENT = 'payment';

	/**
	 * Any note concerning order updates.
	 *
	 * @var string
	 */
	public const ORDER_UPDATE = 'order_update';
}
