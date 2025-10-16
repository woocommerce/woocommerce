<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Orders;

/**
 * Enum class for order note groups. This is stored as meta data to categorize order notes.
 *
 * This is not surfaced in core UI presently.
 */
final class OrderNoteGroup {
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
	public const EMAIL_NOTIFICATION = 'email_notification';

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

	/**
	 * Get the default group title for a given group.
	 *
	 * @param string $group The group.
	 * @return string The default group title.
	 */
	public static function get_default_group_title( string $group ): string {
		switch ( $group ) {
			case self::PRODUCT_STOCK:
				return __( 'Product stock', 'woocommerce' );
			case self::PAYMENT:
				return __( 'Payment', 'woocommerce' );
			case self::EMAIL_NOTIFICATION:
				return __( 'Email notification', 'woocommerce' );
			case self::ERROR:
				return __( 'Error', 'woocommerce' );
			default:
				return __( 'Order updated', 'woocommerce' );
		}
	}
}
