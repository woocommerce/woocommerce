<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\OrderDetailRedesign;

use Automattic\WooCommerce\Admin\Features\OrderDetailRedesign\OrderListNav;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use WC_Order;

/**
 * Tests for OrderListNav.
 *
 * @testdox OrderListNav
 * @since 10.9.0
 */
class OrderListNavTest extends \WC_Unit_Test_Case {

	/**
	 * Orders created for the test, ordered newest → oldest.
	 *
	 * @var WC_Order[]
	 */
	private array $orders = array();

	/**
	 * Set up each test case.
	 */
	public function setUp(): void {
		parent::setUp();

		$_SERVER['HTTP_REFERER'] = '';
		unset( $_GET['status'], $_GET['s'], $_GET['_customer_user'], $_GET['_created_via'] );
	}

	/**
	 * Tear down each test case.
	 */
	public function tearDown(): void {
		foreach ( $this->orders as $order ) {
			$order->delete( true );
		}
		$this->orders = array();

		unset( $_SERVER['HTTP_REFERER'] );
		unset( $_GET['status'], $_GET['s'], $_GET['_customer_user'], $_GET['_created_via'] );

		parent::tearDown();
	}

	/**
	 * Creates `$count` orders with strictly decreasing `date_created` so
	 * `$this->orders[0]` is the newest. Optionally tags each order with a
	 * status to enable filter-based tests.
	 *
	 * @param int           $count    Number of orders to create.
	 * @param string[]|null $statuses One status per order (newest first), or null for default.
	 * @return WC_Order[]
	 */
	private function create_orders( int $count, ?array $statuses = null ): array {
		$base = time() - ( DAY_IN_SECONDS * $count );
		$out  = array();

		for ( $i = 0; $i < $count; $i++ ) {
			$order = OrderHelper::create_order();
			$order->set_date_created( $base + ( ( $count - $i ) * DAY_IN_SECONDS ) );
			if ( null !== $statuses ) {
				$order->set_status( $statuses[ $i ] );
			}
			$order->save();
			$out[] = $order;
		}

		$this->orders = $out;
		return $out;
	}

	/**
	 * @testdox Should return no prev when called on the newest order.
	 */
	public function test_returns_no_prev_for_newest_order(): void {
		$orders = $this->create_orders( 5 );

		$data = OrderListNav::get_navigation_data( $orders[0] );

		$this->assertNull( $data['prev_id'], 'Newest order has no previous' );
		$this->assertSame( $orders[1]->get_id(), $data['next_id'], 'Next is the second-newest order' );
	}

	/**
	 * @testdox Should return correct neighbours for an order in the middle.
	 */
	public function test_returns_correct_neighbours_for_middle_order(): void {
		$orders = $this->create_orders( 5 );

		$data = OrderListNav::get_navigation_data( $orders[2] );

		$this->assertSame( $orders[3]->get_id(), $data['prev_id'], 'Previous is the next-older order' );
		$this->assertSame( $orders[1]->get_id(), $data['next_id'], 'Next is the next-newer order' );
	}

	/**
	 * @testdox Should return no next when called on the oldest order.
	 */
	public function test_returns_no_next_for_oldest_order(): void {
		$orders = $this->create_orders( 5 );

		$data = OrderListNav::get_navigation_data( end( $orders ) );

		$this->assertSame( $orders[3]->get_id(), $data['prev_id'], 'Previous is the second-oldest order' );
		$this->assertNull( $data['next_id'], 'Oldest order has no next' );
	}

	/**
	 * @testdox Should restrict the walk to orders matching the status filter.
	 */
	public function test_restricts_walk_to_status_filter(): void {
		$orders = $this->create_orders(
			5,
			array( 'completed', 'processing', 'completed', 'processing', 'completed' )
		);

		$_GET['status'] = 'processing';

		$data = OrderListNav::get_navigation_data( $orders[1] );

		$this->assertSame( $orders[3]->get_id(), $data['prev_id'], 'Walk skips completed orders' );
		$this->assertNull( $data['next_id'], 'No newer processing order' );
		$this->assertArrayHasKey( 'status', $data['list_query'], 'Filter is preserved in list_query' );
		$this->assertSame( 'processing', $data['list_query']['status'] );
	}

	/**
	 * @testdox Should fall back to unfiltered set when filter excludes the current order.
	 */
	public function test_falls_back_when_filter_excludes_current_order(): void {
		$orders = $this->create_orders(
			3,
			array( 'completed', 'completed', 'completed' )
		);

		$_GET['status'] = 'processing';

		$data = OrderListNav::get_navigation_data( $orders[1] );

		$this->assertSame( $orders[0]->get_id(), $data['next_id'], 'Falls back to walking the unfiltered set' );
		$this->assertSame( $orders[2]->get_id(), $data['prev_id'], 'Falls back to walking the unfiltered set' );
		$this->assertSame( array(), $data['list_query'], 'Filters dropped after fallback' );
	}

	/**
	 * @testdox Should drop unrecognised query params and ignore the all/trash status keywords.
	 */
	public function test_drops_unknown_params_and_ignores_pseudo_statuses(): void {
		$orders = $this->create_orders( 2 );

		$_GET['unknown_param'] = 'value';
		$_GET['status']        = 'all';

		$data = OrderListNav::get_navigation_data( $orders[0] );

		$this->assertSame( array(), $data['list_query'], 'Unknown params and status=all are dropped' );
		$this->assertSame( $orders[1]->get_id(), $data['next_id'], 'Walk still works without filters' );
	}

	/**
	 * @testdox Should sanitize bogus _customer_user values down to an empty filter.
	 */
	public function test_sanitizes_invalid_customer_user(): void {
		$orders = $this->create_orders( 2 );

		$_GET['_customer_user'] = 'not-a-number';

		$data = OrderListNav::get_navigation_data( $orders[0] );

		$this->assertArrayNotHasKey( 'customer', $data['list_query'], 'Bogus customer ID dropped' );
	}

	/**
	 * @testdox Should pull filter params from the referer when the current URL has none.
	 */
	public function test_pulls_filter_from_referer_when_request_is_empty(): void {
		$orders = $this->create_orders(
			3,
			array( 'completed', 'processing', 'processing' )
		);

		$_SERVER['HTTP_REFERER'] = admin_url( 'admin.php?page=wc-orders&status=processing' );

		$data = OrderListNav::get_navigation_data( $orders[1] );

		$this->assertArrayHasKey( 'status', $data['list_query'], 'Status pulled from referer' );
		$this->assertSame( 'processing', $data['list_query']['status'] );
		$this->assertSame( $orders[2]->get_id(), $data['prev_id'], 'Walk only includes processing orders from referer filter' );
		$this->assertNull( $data['next_id'], 'Newer-than-current is completed, so excluded from filtered walk' );
	}

	/**
	 * @testdox Should ignore referers that point at the order edit screen, not the list.
	 */
	public function test_ignores_referer_when_it_is_an_edit_screen(): void {
		$orders = $this->create_orders( 2 );

		$_SERVER['HTTP_REFERER'] = admin_url( 'admin.php?page=wc-orders&action=edit&id=1&status=processing' );

		$data = OrderListNav::get_navigation_data( $orders[0] );

		$this->assertSame( array(), $data['list_query'], 'Edit-screen referer does not bleed filters in' );
	}

	/**
	 * @testdox Should produce no output when render() is called with a refund.
	 */
	public function test_render_is_noop_for_refund(): void {
		$order          = OrderHelper::create_order();
		$this->orders[] = $order;

		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 5,
				'reason'   => 'Test refund',
			)
		);

		$this->assertNotInstanceOf( \WP_Error::class, $refund, 'Refund creation must succeed' );

		ob_start();
		OrderListNav::render( $refund );
		$output = ob_get_clean();

		$this->assertSame( '', $output, 'render() must not emit markup for non-shop_order types' );
	}

	/**
	 * @testdox Should emit the nav markup for a shop_order.
	 */
	public function test_render_emits_markup_for_shop_order(): void {
		$orders = $this->create_orders( 3 );

		ob_start();
		OrderListNav::render( $orders[1] );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'woocommerce-order-list-nav', $output );
		$this->assertStringContainsString( 'aria-label="Order navigation"', $output );
		$this->assertStringContainsString( '>Prev<', $output, 'Visible "Prev" label is present' );
		$this->assertStringContainsString( '>Next<', $output, 'Visible "Next" label is present' );
		$this->assertStringContainsString( 'aria-label="Previous order"', $output );
		$this->assertStringContainsString( 'aria-label="Next order"', $output );
	}
}
