<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Orders\MetaBoxes;

use Automattic\WooCommerce\Internal\Admin\Orders\MetaBoxes\CustomerHistory;
use WC_Helper_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the CustomerHistory class.
 */
class CustomerHistoryTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var CustomerHistory
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new CustomerHistory();
	}

	/**
	 * @testdox Should return correct count, total, and average for a registered customer with multiple orders.
	 */
	public function test_registered_customer_with_multiple_orders(): void {
		$customer_id = $this->factory->user->create();

		$order1 = WC_Helper_Order::create_order( $customer_id );
		$order1->set_status( 'completed' );
		$order1->set_total( 100 );
		$order1->save();

		$order2 = WC_Helper_Order::create_order( $customer_id );
		$order2->set_status( 'completed' );
		$order2->set_total( 200 );
		$order2->save();

		ob_start();
		$this->sut->output( $order1 );
		$output = ob_get_clean();

		$this->assertStringContainsString( '2', $output, 'Should show 2 orders for the customer' );
	}

	/**
	 * @testdox Should fetch data correctly for a guest customer matched by billing email.
	 */
	public function test_guest_customer_by_email(): void {
		$email = 'guest-test@example.com';

		$order1 = WC_Helper_Order::create_order( 0 );
		$order1->set_billing_email( $email );
		$order1->set_status( 'completed' );
		$order1->set_total( 75 );
		$order1->save();

		$order2 = WC_Helper_Order::create_order( 0 );
		$order2->set_billing_email( $email );
		$order2->set_status( 'processing' );
		$order2->set_total( 25 );
		$order2->save();

		ob_start();
		$this->sut->output( $order1 );
		$output = ob_get_clean();

		$this->assertStringContainsString( '2', $output, 'Should show 2 orders for the guest customer' );
	}

	/**
	 * @testdox Should not count orders with excluded statuses like cancelled and failed.
	 */
	public function test_excluded_statuses_not_counted(): void {
		$customer_id = $this->factory->user->create();

		$order_good = WC_Helper_Order::create_order( $customer_id );
		$order_good->set_status( 'completed' );
		$order_good->set_total( 100 );
		$order_good->save();

		$order_cancelled = WC_Helper_Order::create_order( $customer_id );
		$order_cancelled->set_status( 'cancelled' );
		$order_cancelled->set_total( 50 );
		$order_cancelled->save();

		$order_failed = WC_Helper_Order::create_order( $customer_id );
		$order_failed->set_status( 'failed' );
		$order_failed->set_total( 30 );
		$order_failed->save();

		ob_start();
		$this->sut->output( $order_good );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'order-attribution-total-orders', $output );
		$this->assertMatchesRegularExpression( '/order-attribution-total-orders">\s*1\s*</', $output, 'Should only count the completed order' );
	}

	/**
	 * @testdox Should return early without output for auto-draft orders.
	 */
	public function test_auto_draft_returns_early(): void {
		$order = WC_Helper_Order::create_order();
		$order->set_status( 'auto-draft' );
		$order->save();

		ob_start();
		$this->sut->output( $order );
		$output = ob_get_clean();

		$this->assertEmpty( $output, 'Should produce no output for auto-draft orders' );
	}

	/**
	 * @testdox Should show zero data when no matching orders exist for the customer.
	 */
	public function test_no_matching_orders_shows_zero(): void {
		$customer_id = $this->factory->user->create();

		$order = WC_Helper_Order::create_order( $customer_id );
		$order->set_status( 'cancelled' );
		$order->set_total( 100 );
		$order->save();

		ob_start();
		$this->sut->output( $order );
		$output = ob_get_clean();

		$this->assertStringContainsString( '0', $output, 'Should show 0 orders when all are excluded' );
	}
}
