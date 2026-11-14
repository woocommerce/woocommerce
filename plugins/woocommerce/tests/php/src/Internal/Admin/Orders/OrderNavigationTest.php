<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Orders;

use Automattic\WooCommerce\Internal\Admin\Orders\OrderNavigation;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use WC_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the OrderNavigation class.
 */
class OrderNavigationTest extends WC_Unit_Test_Case {

	/**
	 * Creates orders one day apart, returned newest first.
	 *
	 * @param int $count Number of orders to create.
	 * @return WC_Order[]
	 */
	private function create_orders( int $count ): array {
		$orders = array();

		for ( $i = 0; $i < $count; $i++ ) {
			$order = OrderHelper::create_order();
			$order->set_date_created( time() - ( ( $i + 1 ) * DAY_IN_SECONDS ) );
			$order->save();
			$orders[] = $order;
		}

		return $orders;
	}

	/**
	 * Returns the navigation markup for an order.
	 *
	 * @param \WC_Abstract_Order $order The order.
	 * @return string
	 */
	private function get_markup( \WC_Abstract_Order $order ): string {
		ob_start();
		OrderNavigation::render( $order );
		return (string) ob_get_clean();
	}

	/**
	 * @testdox Should only return an older order for the newest order.
	 */
	public function test_newest_order_only_has_older_neighbor(): void {
		$orders = $this->create_orders( 3 );

		$neighbors = OrderNavigation::get_neighbors( $orders[0] );

		$this->assertNull( $neighbors['newer_id'], 'The newest order has nothing newer' );
		$this->assertSame( $orders[1]->get_id(), $neighbors['older_id'], 'Older is the next order back' );
	}

	/**
	 * @testdox Should return both neighbors for an order in the middle.
	 */
	public function test_middle_order_has_both_neighbors(): void {
		$orders = $this->create_orders( 3 );

		$neighbors = OrderNavigation::get_neighbors( $orders[1] );

		$this->assertSame( $orders[0]->get_id(), $neighbors['newer_id'], 'Newer is the next order forward' );
		$this->assertSame( $orders[2]->get_id(), $neighbors['older_id'], 'Older is the next order back' );
	}

	/**
	 * @testdox Should only return a newer order for the oldest order.
	 */
	public function test_oldest_order_only_has_newer_neighbor(): void {
		$orders = $this->create_orders( 3 );

		$neighbors = OrderNavigation::get_neighbors( $orders[2] );

		$this->assertSame( $orders[1]->get_id(), $neighbors['newer_id'], 'Newer is the next order forward' );
		$this->assertNull( $neighbors['older_id'], 'The oldest order has nothing older' );
	}

	/**
	 * @testdox Should visit every order when several share the same second.
	 */
	public function test_orders_sharing_a_second_are_not_skipped(): void {
		$timestamp = time() - DAY_IN_SECONDS;
		$orders    = array();

		for ( $i = 0; $i < 3; $i++ ) {
			$order = OrderHelper::create_order();
			$order->set_date_created( $timestamp );
			$order->save();
			$orders[] = $order;
		}

		$neighbors = OrderNavigation::get_neighbors( $orders[1] );

		$this->assertSame( $orders[2]->get_id(), $neighbors['newer_id'], 'The higher ID counts as newer' );
		$this->assertSame( $orders[0]->get_id(), $neighbors['older_id'], 'The lower ID counts as older' );
	}

	/**
	 * @testdox Should ignore the orders list filters in the request.
	 */
	public function test_ignores_list_filters(): void {
		$orders = $this->create_orders( 3 );
		$orders[1]->set_status( 'completed' );
		$orders[1]->save();
		$_GET['status'] = 'wc-pending';

		$neighbors = OrderNavigation::get_neighbors( $orders[0] );

		unset( $_GET['status'] );
		$this->assertSame( $orders[1]->get_id(), $neighbors['older_id'], 'Orders with other statuses are still reached' );
	}

	/**
	 * @testdox Should render a link for an available neighbor and a disabled button otherwise.
	 */
	public function test_renders_links_and_disabled_state(): void {
		$orders = $this->create_orders( 2 );

		$markup = $this->get_markup( $orders[0] );

		$this->assertStringContainsString( '<button type="button"', $markup, 'The disabled state is a real button' );
		$this->assertStringContainsString( 'disabled aria-label="Newer order"', $markup, 'Newer is disabled on the newest order' );
		$this->assertMatchesRegularExpression( '/<a href="[^"]*id=' . $orders[1]->get_id() . '"[^>]*aria-label="Older order"/', $markup, 'Older links to the next order back' );
	}

	/**
	 * @testdox Should use the design system classes for a button with an icon and text.
	 */
	public function test_renders_design_system_classes(): void {
		$orders = $this->create_orders( 2 );

		$markup = $this->get_markup( $orders[1] );

		$this->assertStringContainsString( 'components-button is-tertiary has-icon has-text', $markup, 'Buttons carry the icon and text classes' );
		$this->assertSame( 1, substr_count( $markup, 'has-icon-right' ), 'Only the Older button has its icon on the right' );
	}

	/**
	 * @testdox Should render nothing for a refund.
	 */
	public function test_renders_nothing_for_refund(): void {
		$order  = OrderHelper::create_order();
		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 0,
			)
		);

		$this->assertInstanceOf( \WC_Order_Refund::class, $refund, 'The refund was created' );
		$this->assertSame( '', $this->get_markup( $refund ), 'Refunds have no navigation' );
	}
}
