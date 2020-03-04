<?php

namespace Automattic\WooCommerce\Blocks\Tests\Library;

use PHPUnit\Framework\TestCase;
use \WC_Order;
use Automattic\WooCommerce\Blocks\Library;

/**
 * Tests Delete Draft Orders functionality
 *
 * @since $VID:$
 * @group testing
 */
class DeleteDraftOrders extends TestCase {
	/**
	 * During setup create some draft orders.
	 *
	 * @return void
	 */
	public function setUp() {
		global $wpdb;

		$order = new WC_Order();
		$order->set_status( 'checkout-draft' );
		$order->save();

		$order = new WC_Order();
		$order->set_status( 'checkout-draft' );
		$order->save();
		$wpdb->update(
			$wpdb->posts,
			array(
				'post_modified'     => date( 'Y-m-d H:i:s', strtotime( '-1 DAY', current_time( 'timestamp' ) ) ),
				'post_modified_gmt' => gmdate( 'Y-m-d H:i:s', strtotime( '-1 DAY' ) )
			),
			array(
				'ID' => $order->get_id()
			)
		);

		$order = new WC_Order();
		$order->set_status( 'checkout-draft' );
		$order->save();
		$wpdb->update(
			$wpdb->posts,
			array(
				'post_modified'     => date( 'Y-m-d H:i:s', strtotime( '-2 DAY', current_time( 'timestamp' ) ) ),
				'post_modified_gmt' => gmdate( 'Y-m-d H:i:s', strtotime( '-2 DAY' ) )
			),
			array(
				'ID' => $order->get_id()
			)
		);
	}

	/**
	 * Delete draft orders older than a day.
	 *
	 * Ran on a daily cron schedule.
	 */
	public function test_delete_expired_draft_orders() {
		global $wpdb;

		// Check there are 3 draft orders from our setup before running tests.
		$this->assertEquals( 3, (int) $wpdb->get_var( "SELECT COUNT(ID) from $wpdb->posts posts WHERE posts.post_status = 'wc-checkout-draft'" ) );

		// Run delete query.
		Library::delete_expired_draft_orders();

		// Only 1 should remain.
		$this->assertEquals( 1, (int) $wpdb->get_var( "SELECT COUNT(ID) from $wpdb->posts posts WHERE posts.post_status = 'wc-checkout-draft'" ) );
	}
}
