<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Domain\Services;

use Automattic\WooCommerce\Blocks\Domain\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\DraftOrders;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Order;

/**
 * Tests Delete Draft Orders functionality
 *
 * @since $VID:$
 */
class DeleteDraftOrders extends \WC_Unit_Test_Case {

	/**
	 * Draft order service under test.
	 *
	 * @var DraftOrders|null
	 */
	private $draft_orders_instance;

	/**
	 * Exception caught by the service's error handler.
	 *
	 * @var \Throwable|null
	 */
	private $caught_exception;

	/**
	 * Original PHP error log destination.
	 *
	 * @var string|false
	 */
	private $original_logging_destination;

	/**
	 * Whether HPOS was authoritative before the test.
	 *
	 * @var bool
	 */
	private $previous_hpos_state;

	/**
	 * During setup create some draft orders.
	 *
	 * @return void
	 */
	public function setUp(): void {
		global $wpdb;

		parent::setUp();

		$this->previous_hpos_state = OrderUtil::custom_orders_table_usage_is_enabled();
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		OrderHelper::toggle_cot_feature_and_usage( false );

		$this->draft_orders_instance = new DraftOrders( new Package( 'test', './' ) );

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
		$order->set_status( OrderStatus::ON_HOLD );
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

		// Listen for exceptions.
		add_action( 'woocommerce_caught_exception', array( $this, 'capture_exception' ) );

		// temporarily hide error logging we don't care about (and keeps from polluting stdout)
		$this->original_logging_destination = ini_get('error_log');
		ini_set('error_log', '/dev/null');
	}

	/**
	 * Restore test state.
	 */
	public function tearDown(): void {
		$this->draft_orders_instance = null;
		remove_action( 'woocommerce_caught_exception', array( $this, 'capture_exception' ) );
		//restore original logging destination
		ini_set('error_log', $this->original_logging_destination);
		OrderHelper::toggle_cot_feature_and_usage( $this->previous_hpos_state );
		remove_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		parent::tearDown();
	}

	/**
	 * Capture an exception reported by the service.
	 *
	 * @param \Throwable $exception_object Reported exception.
	 */
	public function capture_exception( $exception_object ): void {
		$this->caught_exception = $exception_object;
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

	/**
	 * Test that a custom batch size filter allows more than the default 20 results without error.
	 */
	public function test_custom_batch_size_filter_allows_larger_results() {
		add_filter(
			'woocommerce_delete_expired_draft_orders_batch_size',
			function () {
				return 50;
			}
		);

		$sample_results = function ( $results, $args ) {
			if ( isset( $args['status'] ) && DraftOrders::DB_STATUS === $args['status'] ) {
				$orders = array();
				for ( $i = 0; $i < 50; $i++ ) {
					$order = new WC_Order();
					$order->set_status( DraftOrders::STATUS );
					$orders[] = $order;
				}
				return $orders;
			}
			return $results;
		};
		$this->mock_results_for_wc_query( $sample_results );
		$this->draft_orders_instance->delete_expired_draft_orders();
		$this->assertNull( $this->caught_exception, 'No exception should be thrown when batch size filter allows more results.' );
		$this->unset_mock_results_for_wc_query( $sample_results );

		remove_all_filters( 'woocommerce_delete_expired_draft_orders_batch_size' );
	}

	public function test_greater_than_batch_results_error() {
		$sample_results = function( $results, $args ) {
			if ( isset( $args[ 'status' ] ) && DraftOrders::DB_STATUS === $args[ 'status' ] ) {
				return array_fill( 0, 21, ( new WC_Order ) );
			}
			return $results;
		};
		$this->mock_results_for_wc_query($sample_results);
		$this->draft_orders_instance->delete_expired_draft_orders();
		$this->assertStringContainsString( 'unexpected number of results', $this->caught_exception->getMessage() );
		$this->unset_mock_results_for_wc_query( $sample_results );
	}

	public function test_order_not_instance_of_wc_order_error() {
		$sample_results = function( $results, $args ) {
			if ( isset( $args[ 'status' ] ) && DraftOrders::DB_STATUS === $args[ 'status' ] ) {
				return [ 10 ];
			}
			return $results;
		};
		$this->mock_results_for_wc_query( $sample_results );
		$this->draft_orders_instance->delete_expired_draft_orders();
		$this->assertStringContainsString( 'value that is not a WC_Order', $this->caught_exception->getMessage() );
		$this->unset_mock_results_for_wc_query( $sample_results );
	}

	public function test_order_incorrect_status_error() {
		$sample_results = function( $results, $args ) {
			if ( isset( $args[ 'status' ] ) && DraftOrders::DB_STATUS === $args[ 'status' ] ) {
				$test_order = new WC_Order();
				$test_order->set_status( OrderStatus::ON_HOLD );
				return [ $test_order ];
			}
			return $results;
		};
		$this->mock_results_for_wc_query( $sample_results );
		$this->draft_orders_instance->delete_expired_draft_orders();
		$this->assertStringContainsString( 'order that is not a `wc-checkout-draft`', $this->caught_exception->getMessage() );
		$this->unset_mock_results_for_wc_query( $sample_results );
	}

	public function test_order_status_verification() {
		global $wp_post_statuses, $wpdb;
		$original_statuses = $wp_post_statuses;
		// simulate registered draft status getting clobbered
		foreach( $wp_post_statuses as $index => $status ) {
			if ( DraftOrders::DB_STATUS === $status->name ) {
				unset( $wp_post_statuses[ $index ] );
				break;
			}
		}
		$status = DraftOrders::DB_STATUS;
		// Check there are 3 draft orders from our setup before running tests.
		$this->assertEquals( 3, (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) from $wpdb->posts posts WHERE posts.post_status = '%s'", [ $status ] ) ) );

		// Run delete query.
		$this->draft_orders_instance->delete_expired_draft_orders();

		// Only 1 should remain.
		$this->assertEquals( 1, (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) from $wpdb->posts posts WHERE posts.post_status = '%s'", [ $status ] ) ) );

		// The non-draft order should still be present
		$this->assertEquals( 1, (int) $wpdb->get_var( "SELECT COUNT(ID) from $wpdb->posts posts WHERE posts.post_status = 'wc-on-hold'" ) );
		// restore global
		$wp_post_statuses = $original_statuses;
	}

	private function mock_results_for_wc_query( $mock_callback ) {
		add_filter( 'woocommerce_order_query', $mock_callback, 10, 2 );
	}

	private function unset_mock_results_for_wc_query( $mock_callback ) {
		$removed = remove_filter( 'woocommerce_order_query', $mock_callback );
		$this->assertTrue( $removed );
	}
}
