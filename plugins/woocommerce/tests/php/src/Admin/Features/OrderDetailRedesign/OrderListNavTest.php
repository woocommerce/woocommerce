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
	 * @testdox Should report position 1 / N for the newest order in the set.
	 */
	public function test_position_is_one_for_newest_order(): void {
		$ids   = $this->create_orders( 5 );
		$order = wc_get_order( end( $ids ) );

		$data = $this->sut->get_navigation_data( $order );

		$this->assertSame( 1, $data['position'], 'Newest order should be position 1.' );
		$this->assertSame( 5, $data['total'], 'Total should reflect all 5 orders.' );
		$this->assertNull( $data['prev_id'], 'No previous-position link at position 1.' );
		$this->assertSame( $ids[3], $data['next_id'], 'Next-position link should be the second-newest order.' );
	}

	/**
	 * @testdox Should report position N / N for the oldest order in the set.
	 */
	public function test_position_is_total_for_oldest_order(): void {
		$ids   = $this->create_orders( 5 );
		$order = wc_get_order( $ids[0] );

		$data = $this->sut->get_navigation_data( $order );

		$this->assertSame( 5, $data['position'], 'Oldest order should be at position equal to total.' );
		$this->assertSame( 5, $data['total'], 'Total should reflect all 5 orders.' );
		$this->assertNull( $data['next_id'], 'No next-position link at the last position.' );
		$this->assertSame( $ids[1], $data['prev_id'], 'Previous-position link should be the second-oldest order.' );
	}

	/**
	 * @testdox Should narrow position and total to the active status filter.
	 */
	public function test_status_filter_narrows_the_set(): void {
		$ids = $this->create_orders( 5 );

		$completed_order = wc_get_order( $ids[2] );
		$completed_order->set_status( 'completed' );
		$completed_order->save();

		$_GET['status'] = 'completed';

		$data = $this->sut->get_navigation_data( $completed_order );

		$this->assertSame( 1, $data['position'], 'The single completed order should be at position 1 within its filter.' );
		$this->assertSame( 1, $data['total'], 'Total should reflect the filtered set (1 completed order).' );
		$this->assertNull( $data['prev_id'], 'No prev order matches the filter.' );
		$this->assertNull( $data['next_id'], 'No next order matches the filter.' );
		$this->assertSame( array( 'status' => 'completed' ), $data['list_query'], 'Filter should be carried forward.' );
	}

	/**
	 * @testdox Should fall back to unfiltered set when current order does not match the active filter.
	 */
	public function test_falls_back_when_filter_excludes_current_order(): void {
		$ids = $this->create_orders( 3 );

		$_GET['status'] = 'completed';

		$processing_order = wc_get_order( $ids[1] );
		$data             = $this->sut->get_navigation_data( $processing_order );

		$this->assertSame( 3, $data['total'], 'Total should reflect the unfiltered set after fallback.' );
		$this->assertSame( 2, $data['position'], 'Position should reflect the unfiltered set (middle of 3).' );
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
