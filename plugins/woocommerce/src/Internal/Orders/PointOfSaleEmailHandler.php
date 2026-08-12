<?php
/**
 * PointOfSaleEmailHandler class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Orders;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Abstract_Order;

/**
 * Suppresses standard automated emails for orders paid at POS.
 *
 * POS has its own email templates (customer_pos_completed_order,
 * customer_pos_refunded_order) that are sent automatically or via REST API.
 * This handler prevents the standard transactional emails from firing
 * when an order is paid at POS, regardless of where it was created.
 *
 * @internal Just for internal use.
 *
 * @since 10.6.0
 */
class PointOfSaleEmailHandler implements RegisterHooksInterface {

	/**
	 * Standard email IDs to suppress for POS-paid orders.
	 */
	private const SUPPRESSED_EMAIL_IDS = array(
		'customer_processing_order',
		'customer_completed_order',
		'customer_on_hold_order',
		'customer_refunded_order',
		'customer_partially_refunded_order',
		'new_order',
	);

	/**
	 * Register hooks and filters.
	 */
	public function register(): void {
		foreach ( self::SUPPRESSED_EMAIL_IDS as $email_id ) {
			add_filter( 'woocommerce_email_enabled_' . $email_id, array( $this, 'maybe_suppress_email' ), 10, 2 );
		}
	}

	/**
	 * Suppress email if the order was paid at POS.
	 *
	 * @param bool  $enabled Whether the email is enabled.
	 * @param mixed $order   The order object (or null).
	 * @return bool False if the order was paid at POS, original value otherwise.
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function maybe_suppress_email( bool $enabled, $order ): bool {
		if ( ! $order instanceof WC_Abstract_Order ) {
			return $enabled;
		}

		if ( PointOfSaleOrderUtil::is_order_paid_at_pos( $order ) ) {
			return false;
		}

		return $enabled;
	}
}
