<?php
/**
 * Non-persistent WC_Order used for email preview.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\EmailPreview;

use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Order subclass for the email preview.
 *
 * The preview needs an order to render, but it must never read from or write
 * to a real order in the database. The key to that is the id: order methods
 * that touch the database key off get_id() and resolve to nothing when it is
 * 0 (add_order_note(), remove_order_items(), get_refunds(), the refund totals,
 * item/meta reads, etc.). So this order keeps id 0 and exposes the preview's
 * display number through get_order_number() instead.
 *
 * Two cases aren't safe at id 0 and are handled explicitly:
 * - save() and its siblings would insert a new row, so they are no-ops.
 * - get_customer_order_notes() passes the id to get_comments(), which treats
 *   0 as "no filter" and would return every order note on the site, so it is
 *   overridden to return nothing.
 *
 * The item/meta caches are pre-filled as empty too, as a guard against any
 * future read path that doesn't check the id first.
 */
class PreviewOrder extends WC_Order {

	/**
	 * The order number shown in the preview. Not a real order id, so it can't
	 * collide with a row in the database.
	 */
	const PREVIEW_ORDER_NUMBER = '12345';

	/**
	 * Constructor.
	 *
	 * @param int|object|WC_Order $order Order to read. Defaults to 0 (a new, empty order).
	 */
	public function __construct( $order = 0 ) {
		parent::__construct( $order );

		foreach ( $this->item_types_to_group as $group ) {
			$this->items[ $group ] = array();
		}
		$this->meta_data = array();
	}

	/**
	 * Get the order number to display.
	 *
	 * The real id stays 0, so this provides a representative number for the
	 * preview without tying the order to a database row.
	 *
	 * @return string
	 */
	public function get_order_number() {
		return self::PREVIEW_ORDER_NUMBER;
	}

	/**
	 * Block save(). A preview order should never be written to the database.
	 *
	 * @return int The order id (unchanged).
	 */
	public function save() {
		wc_get_logger()->warning(
			'Email preview order save() blocked to prevent writing to the database.',
			array( 'source' => 'email-preview' )
		);
		return $this->get_id();
	}

	/**
	 * Block save_meta_data(). Extensions sometimes call update_meta_data()
	 * followed by save_meta_data() directly, bypassing save().
	 */
	public function save_meta_data(): void {
		// Intentionally empty.
	}

	/**
	 * Block delete(). A preview order has no row to delete.
	 *
	 * @param bool $force_delete Should the order be deleted permanently.
	 * @return bool Always false.
	 */
	public function delete( $force_delete = false ) {
		return false;
	}

	/**
	 * A preview order has no customer notes. The parent passes the id straight
	 * to get_comments(), which treats id 0 as "return every order note".
	 *
	 * @return array
	 */
	public function get_customer_order_notes() {
		return array();
	}
}
