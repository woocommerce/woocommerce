<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\OrderDetailRedesign;

use Automattic\WooCommerce\Admin\Features\OrderDetailRedesign\OrderListNav;
use WC_Helper_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the OrderListNav class.
 */
class OrderListNavTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var OrderListNav
	 */
	private $sut;

	/**
	 * IDs of orders created in setUp, oldest → newest.
	 *
	 * @var int[]
	 */
	private $order_ids = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new OrderListNav();

		$_GET    = array();
		$_SERVER = array_merge( $_SERVER, array( 'HTTP_REFERER' => '' ) );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		foreach ( $this->order_ids as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				$order->delete( true );
			}
		}
		$this->order_ids = array();

		$_GET = array();
		unset( $_SERVER['HTTP_REFERER'] );

		parent::tearDown();
	}

	/**
	 * Creates N processing orders with strictly-increasing `date_created` values.
	 *
	 * @param int $count Number of orders.
	 * @return int[] Order IDs in chronological order (oldest first).
	 */
	private function create_orders( int $count ): array {
		$base_time = time() - ( $count * 3600 );
		$ids       = array();
		for ( $i = 0; $i < $count; $i++ ) {
			$order = WC_Helper_Order::create_order();
			$order->set_status( 'processing' );
			$order->set_date_created( $base_time + ( $i * 3600 ) );
			$order->save();
			$ids[] = $order->get_id();
		}
		$this->order_ids = array_merge( $this->order_ids, $ids );
		return $ids;
	}

	/**
	 * @testdox Should render nothing for non-shop_order types.
	 */
	public function test_render_skips_refunds(): void {
		$order             = WC_Helper_Order::create_order();
		$refund            = wc_create_refund(
			array(
				'amount'   => 1,
				'order_id' => $order->get_id(),
			)
		);
		$this->order_ids[] = $order->get_id();

		ob_start();
		$this->sut->render( $refund );
		$output = ob_get_clean();

		$this->assertSame( '', $output, 'Refund should not produce nav markup.' );
	}

	/**
	 * @testdox Should disable Prev (left) at the newest order in the set.
	 */
	public function test_no_prev_at_newest_order(): void {
		$ids   = $this->create_orders( 5 );
		$order = wc_get_order( end( $ids ) );

		$data = $this->sut->get_navigation_data( $order );

		$this->assertNull( $data['prev_id'], 'Prev link should be disabled at the newest order.' );
		$this->assertSame( $ids[3], $data['next_id'], 'Next link should point at the second-newest order.' );
	}

	/**
	 * @testdox Should disable Next (right) at the oldest order in the set.
	 */
	public function test_no_next_at_oldest_order(): void {
		$ids   = $this->create_orders( 5 );
		$order = wc_get_order( $ids[0] );

		$data = $this->sut->get_navigation_data( $order );

		$this->assertNull( $data['next_id'], 'Next link should be disabled at the oldest order.' );
		$this->assertSame( $ids[1], $data['prev_id'], 'Prev link should point at the second-oldest order.' );
	}

	/**
	 * @testdox Should narrow prev/next to the active status filter.
	 */
	public function test_status_filter_narrows_the_set(): void {
		$ids = $this->create_orders( 5 );

		$completed_order = wc_get_order( $ids[2] );
		$completed_order->set_status( 'completed' );
		$completed_order->save();

		$_GET['status'] = 'completed';

		$data = $this->sut->get_navigation_data( $completed_order );

		$this->assertNull( $data['prev_id'], 'No prev order matches the filter.' );
		$this->assertNull( $data['next_id'], 'No next order matches the filter.' );
		$this->assertArrayHasKey( 'status', $data['list_query'] );
		$this->assertSame( 'completed', $data['list_query']['status'], 'Filter should be carried forward.' );
	}

	/**
	 * @testdox Should fall back to unfiltered set when current order does not match the active filter.
	 */
	public function test_falls_back_when_filter_excludes_current_order(): void {
		$ids = $this->create_orders( 3 );

		$_GET['status'] = 'completed';

		$processing_order = wc_get_order( $ids[1] );
		$data             = $this->sut->get_navigation_data( $processing_order );

		$this->assertSame( $ids[2], $data['prev_id'], 'Prev should walk the unfiltered set after fallback.' );
		$this->assertSame( $ids[0], $data['next_id'], 'Next should walk the unfiltered set after fallback.' );
		$this->assertSame( array(), $data['list_query'], 'Filter should be dropped after fallback.' );
	}

	/**
	 * @testdox Should drop unknown URL params from the forwarded list query.
	 */
	public function test_unknown_params_are_dropped(): void {
		$ids   = $this->create_orders( 2 );
		$order = wc_get_order( $ids[0] );

		$_GET['status']     = 'processing';
		$_GET['evil']       = 'should-be-dropped';
		$_GET['javascript'] = 'alert(1)';

		$data = $this->sut->get_navigation_data( $order );

		$this->assertArrayHasKey( 'status', $data['list_query'], 'Whitelisted param should be kept.' );
		$this->assertArrayNotHasKey( 'evil', $data['list_query'], 'Unknown param should be dropped.' );
		$this->assertArrayNotHasKey( 'javascript', $data['list_query'], 'Unknown param should be dropped.' );
	}
}
