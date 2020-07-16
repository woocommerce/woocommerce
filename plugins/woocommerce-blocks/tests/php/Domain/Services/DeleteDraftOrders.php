<?php

namespace Automattic\WooCommerce\Blocks\Tests\Library;

use PHPUnit\Framework\TestCase;
use \WC_Order;
use Automattic\WooCommerce\Blocks\Domain\Services\DraftOrders;
use Automattic\WooCommerce\Blocks\Domain\Package;

/**
 * Tests Delete Draft Orders functionality
 *
 * @since $VID:$
 */
class DeleteDraftOrders extends TestCase {

	private $draft_orders_instance;
	private $caught_exception;

	/**
	 * During setup create some draft orders.
	 *
	 * @return void
	 */
	public function setUp() {
		global $wpdb;

		$this->draft_orders_instance = new DraftOrders( new Package( 'test', './') );

		$order = new WC_Order();
		$order->set_status( DraftOrders::STATUS );
		$order->save();

		$order = new WC_Order();
		$order->set_status( DraftOrders::STATUS );
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
		$order->set_status( DraftOrders::STATUS );
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

		// set a non-draft order to make sure it's unaffected
		$order = new WC_Order();
		$order->set_status( 'on-hold' );
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

		// set listening for exceptions
		add_action( 'woocommerce_caught_exception', function($exception_object){
			$this->caught_exception = $exception_object;
		});
	}

	public function tearDown() {
		$this->draft_orders_instance = null;
		remove_all_actions( 'woocommerce_caught_exception' );
	}

	/**
	 * Delete draft orders older than a day.
	 *
	 * Ran on a daily cron schedule.
	 */
	public function test_delete_expired_draft_orders() {
		global $wpdb;
		$status = DraftOrders::DB_STATUS;

		// Check there are 3 draft orders from our setup before running tests.
		$this->assertEquals( 3, (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) from $wpdb->posts posts WHERE posts.post_status = '%s'", [ $status ] ) ) );

		// Run delete query.
		$this->draft_orders_instance->delete_expired_draft_orders();

		// Only 1 should remain.
		$this->assertEquals( 1, (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) from $wpdb->posts posts WHERE posts.post_status = '%s'", [ $status ] ) ) );

		// The non-draft order should still be present
		$this->assertEquals( 1, (int) $wpdb->get_var( "SELECT COUNT(ID) from $wpdb->posts posts WHERE posts.post_status = 'wc-on-hold'" ) );
	}

	public function test_greater_than_batch_results_error() {
		$sample_results = function() {
			return array_fill( 0, 21, ( new WC_Order ) );

		};
		$this->mock_results_for_wc_query($sample_results);
		$this->draft_orders_instance->delete_expired_draft_orders();
		$this->assertContains( 'unexpected number of results', $this->caught_exception->getMessage() );
		$this->unset_mock_results_for_wc_query( $sample_results );
	}

	public function test_order_not_instance_of_wc_order_error() {
		$sample_results = function() {
			return [ 10 ];
		};
		$this->mock_results_for_wc_query( $sample_results );
		$this->draft_orders_instance->delete_expired_draft_orders();
		$this->assertContains( 'value that is not a WC_Order', $this->caught_exception->getMessage() );
		$this->unset_mock_results_for_wc_query( $sample_results );
	}

	public function test_order_incorrect_status_error() {
		$sample_results = function() {
			$test_order = new WC_Order();
			$test_order->set_status( 'on-hold' );
			return [ $test_order ];
		};
		$this->mock_results_for_wc_query( $sample_results );
		$this->draft_orders_instance->delete_expired_draft_orders();
		$this->assertContains( 'order that is not a `wc-checkout-draft`', $this->caught_exception->getMessage() );
		$this->unset_mock_results_for_wc_query( $sample_results );
	}

	private function mock_results_for_wc_query( $mock_callback ) {
		add_filter( 'woocommerce_order_query', $mock_callback );
	}

	private function unset_mock_results_for_wc_query( $mock_callback ) {
		remove_filter( 'woocommerce_order_query', $mock_callback );
	}
}
