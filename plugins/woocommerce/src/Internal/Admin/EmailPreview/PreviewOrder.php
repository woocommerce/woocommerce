<?php
/**
 * Non-persistent WC_Order used for email preview.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\EmailPreview;

use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Order subclass for the email preview. Its writes are no-ops and its
 * items/meta caches start empty, so it can't accidentally touch a real order
 * in the database.
 *
 * The preview gives its dummy order a hard-coded id of 12345. If a store has
 * a real order with that id, $order->save() (called directly or via
 * update_status, payment_complete, etc.) would overwrite that real row, and
 * $order->get_items() / get_meta() would lazy-load the real order's data
 * into the dummy.
 *
 * Other reads still go through the data store (get_total_refunded etc.) and
 * return 0/empty against the non-existent row, which keeps the rendering code
 * unchanged.
 */
class PreviewOrder extends WC_Order {

	/**
	 * Constructor.
	 *
	 * @param int|object|WC_Order $order Order to read. Defaults to 0 (a new, empty order).
	 */
	public function __construct( $order = 0 ) {
		parent::__construct( $order );

		// Pre-populate so get_items() / get_meta() never lazy-read from a real row.
		foreach ( $this->item_types_to_group as $group ) {
			$this->items[ $group ] = array();
		}
		$this->meta_data = array();
	}

	/**
	 * Block save(). Replaces the parent's data-store write path with a no-op.
	 *
	 * @return int The order id (unchanged).
	 */
	public function save() {
		wc_get_logger()->warning(
			'Email preview order save() blocked to prevent overwriting a real order.',
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
	 * Block delete(). A preview order has no row to delete; allowing the call
	 * would target a real order with the same id.
	 *
	 * @param bool $force_delete Should the order be deleted permanently.
	 * @return bool Always false.
	 */
	public function delete( $force_delete = false ) {
		return false;
	}
}
