<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Orders\MetaBoxes;

use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore as OrdersStatsDataStore;
use Automattic\WooCommerce\Internal\Admin\Orders\MetaBoxes\CustomerHistory;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use WC_Helper_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the CustomerHistory class.
 */
class CustomerHistoryTest extends WC_Unit_Test_Case {
	use HPOSToggleTrait;

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
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		$this->setup_cot();
		$this->sut = new CustomerHistory();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$this->clean_up_cot_setup();
		remove_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		parent::tearDown();
	}

	/**
	 * @testdox Should return correct count, total, and average for a registered customer with multiple orders (HPOS).
	 */
	public function test_registered_customer_with_multiple_orders(): void {
		$this->toggle_cot_feature_and_usage( true );

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

		$this->assertMatchesRegularExpression( '/order-attribution-total-orders">\s*2\s*</', $output, 'Should show 2 orders for the customer' );
		$this->assertMatchesRegularExpression( '/order-attribution-total-spend">\s*.*300\.00/', $output, 'Should show total spend of 300' );
		$this->assertMatchesRegularExpression( '/order-attribution-average-order-value">\s*.*150\.00/', $output, 'Should show average order value of 150' );
	}

	/**
	 * @testdox Should fetch data correctly for a guest customer matched by billing email (HPOS).
	 */
	public function test_guest_customer_by_email(): void {
		$this->toggle_cot_feature_and_usage( true );

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

		$this->assertMatchesRegularExpression( '/order-attribution-total-orders">\s*2\s*</', $output, 'Should show 2 orders for the guest customer' );
		$this->assertMatchesRegularExpression( '/order-attribution-total-spend">\s*.*100\.00/', $output, 'Should show total spend of 100' );
		$this->assertMatchesRegularExpression( '/order-attribution-average-order-value">\s*.*50\.00/', $output, 'Should show average order value of 50' );
	}

	/**
	 * @testdox Should not count orders with excluded statuses like pending, cancelled, and failed (HPOS).
	 */
	public function test_excluded_statuses_not_counted(): void {
		$this->toggle_cot_feature_and_usage( true );

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

		$order_pending = WC_Helper_Order::create_order( $customer_id );
		$order_pending->set_status( 'pending' );
		$order_pending->set_total( 20 );
		$order_pending->save();

		ob_start();
		$this->sut->output( $order_good );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'order-attribution-total-orders', $output );
		$this->assertMatchesRegularExpression( '/order-attribution-total-orders">\s*1\s*</', $output, 'Should only count the completed order' );
		$this->assertMatchesRegularExpression( '/order-attribution-total-spend">\s*.*100\.00/', $output, 'Should only sum spend from the completed order' );
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
	 * @testdox Should show zero data for guest order with no billing email (HPOS).
	 */
	public function test_guest_with_no_email_shows_zero(): void {
		$this->toggle_cot_feature_and_usage( true );

		$order = WC_Helper_Order::create_order( 0 );
		$order->set_billing_email( '' );
		$order->set_status( 'completed' );
		$order->set_total( 50 );
		$order->save();

		ob_start();
		$this->sut->output( $order );
		$output = ob_get_clean();

		$this->assertMatchesRegularExpression( '/order-attribution-total-orders">\s*0\s*</', $output, 'Should show 0 orders for guest with no email' );
	}

	/**
	 * @testdox Should show zero data when no matching orders exist for the customer (HPOS).
	 */
	public function test_no_matching_orders_shows_zero(): void {
		$this->toggle_cot_feature_and_usage( true );

		$customer_id = $this->factory->user->create();

		$order = WC_Helper_Order::create_order( $customer_id );
		$order->set_status( 'cancelled' );
		$order->set_total( 100 );
		$order->save();

		ob_start();
		$this->sut->output( $order );
		$output = ob_get_clean();

		$this->assertMatchesRegularExpression( '/order-attribution-total-orders">\s*0\s*</', $output, 'Should show 0 orders when all are excluded' );
	}

	/**
	 * @testdox Should deduct partial refund from total spend (HPOS).
	 */
	public function test_partial_refund_deducted_from_total_spend(): void {
		$this->toggle_cot_feature_and_usage( true );

		$customer_id = $this->factory->user->create();

		$order = WC_Helper_Order::create_order( $customer_id );
		$order->set_status( 'completed' );
		$order->set_total( 200 );
		$order->save();

		wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 50,
				'reason'   => 'Partial refund test',
			)
		);

		ob_start();
		$this->sut->output( $order );
		$output = ob_get_clean();

		$this->assertMatchesRegularExpression( '/order-attribution-total-orders">\s*1\s*</', $output, 'Should still count 1 order after partial refund' );
		$this->assertMatchesRegularExpression( '/order-attribution-total-spend">\s*.*150\.00/', $output, 'Should show net spend of 150 after 50 refund' );
		$this->assertMatchesRegularExpression( '/order-attribution-average-order-value">\s*.*150\.00/', $output, 'Should show average of 150 after partial refund' );
	}

	/**
	 * @testdox Should deduct full refund from total spend (HPOS).
	 */
	public function test_full_refund_deducted_from_total_spend(): void {
		$this->toggle_cot_feature_and_usage( true );

		$customer_id = $this->factory->user->create();

		$order1 = WC_Helper_Order::create_order( $customer_id );
		$order1->set_status( 'completed' );
		$order1->set_total( 100 );
		$order1->save();

		$order2 = WC_Helper_Order::create_order( $customer_id );
		$order2->set_status( 'completed' );
		$order2->set_total( 200 );
		$order2->save();

		wc_create_refund(
			array(
				'order_id' => $order1->get_id(),
				'amount'   => 100,
				'reason'   => 'Full refund test',
			)
		);

		ob_start();
		$this->sut->output( $order1 );
		$output = ob_get_clean();

		$this->assertMatchesRegularExpression( '/order-attribution-total-orders">\s*2\s*</', $output, 'Should still count 2 orders after full refund' );
		$this->assertMatchesRegularExpression( '/order-attribution-total-spend">\s*.*200\.00/', $output, 'Should show net spend of 200 after full refund of first order' );
		$this->assertMatchesRegularExpression( '/order-attribution-average-order-value">\s*.*100\.00/', $output, 'Should show average of 100 (200 net / 2 orders)' );
	}

	/**
	 * @testdox Should deduct refund from guest order total spend (HPOS).
	 */
	public function test_guest_order_refund_deducted_from_total_spend(): void {
		$this->toggle_cot_feature_and_usage( true );

		$email = 'guest-refund@example.com';

		$order = WC_Helper_Order::create_order( 0 );
		$order->set_billing_email( $email );
		$order->set_status( 'completed' );
		$order->set_total( 100 );
		$order->save();

		wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 30,
				'reason'   => 'Guest partial refund test',
			)
		);

		ob_start();
		$this->sut->output( $order );
		$output = ob_get_clean();

		$this->assertMatchesRegularExpression( '/order-attribution-total-orders">\s*1\s*</', $output, 'Should still count 1 order after guest refund' );
		$this->assertMatchesRegularExpression( '/order-attribution-total-spend">\s*.*70\.00/', $output, 'Should show net spend of 70 after 30 refund on guest order' );
	}

	/**
	 * @testdox Should only count orders for the specific registered customer, not other customers (HPOS).
	 */
	public function test_registered_customer_isolation(): void {
		$this->toggle_cot_feature_and_usage( true );

		$customer_a = $this->factory->user->create();
		$customer_b = $this->factory->user->create();

		$order_a = WC_Helper_Order::create_order( $customer_a );
		$order_a->set_status( 'completed' );
		$order_a->set_total( 100 );
		$order_a->save();

		$order_b1 = WC_Helper_Order::create_order( $customer_b );
		$order_b1->set_status( 'completed' );
		$order_b1->set_total( 200 );
		$order_b1->save();

		$order_b2 = WC_Helper_Order::create_order( $customer_b );
		$order_b2->set_status( 'completed' );
		$order_b2->set_total( 300 );
		$order_b2->save();

		ob_start();
		$this->sut->output( $order_a );
		$output_a = ob_get_clean();

		$this->assertMatchesRegularExpression( '/order-attribution-total-orders">\s*1\s*</', $output_a, 'Customer A should see only their 1 order' );
		$this->assertMatchesRegularExpression( '/order-attribution-total-spend">\s*.*100\.00/', $output_a, 'Customer A should see total spend of 100' );

		ob_start();
		$this->sut->output( $order_b1 );
		$output_b = ob_get_clean();

		$this->assertMatchesRegularExpression( '/order-attribution-total-orders">\s*2\s*</', $output_b, 'Customer B should see their 2 orders' );
		$this->assertMatchesRegularExpression( '/order-attribution-total-spend">\s*.*500\.00/', $output_b, 'Customer B should see total spend of 500' );
	}

	/**
	 * @testdox Should only count orders for the specific guest email, not other guest emails (HPOS).
	 */
	public function test_guest_customer_email_isolation(): void {
		$this->toggle_cot_feature_and_usage( true );

		$email_a = 'guest-a@example.com';
		$email_b = 'guest-b@example.com';

		$order_a = WC_Helper_Order::create_order( 0 );
		$order_a->set_billing_email( $email_a );
		$order_a->set_status( 'completed' );
		$order_a->set_total( 50 );
		$order_a->save();

		$order_b1 = WC_Helper_Order::create_order( 0 );
		$order_b1->set_billing_email( $email_b );
		$order_b1->set_status( 'completed' );
		$order_b1->set_total( 75 );
		$order_b1->save();

		$order_b2 = WC_Helper_Order::create_order( 0 );
		$order_b2->set_billing_email( $email_b );
		$order_b2->set_status( 'completed' );
		$order_b2->set_total( 125 );
		$order_b2->save();

		ob_start();
		$this->sut->output( $order_a );
		$output_a = ob_get_clean();

		$this->assertMatchesRegularExpression( '/order-attribution-total-orders">\s*1\s*</', $output_a, 'Guest A should see only their 1 order' );
		$this->assertMatchesRegularExpression( '/order-attribution-total-spend">\s*.*50\.00/', $output_a, 'Guest A should see total spend of 50' );

		ob_start();
		$this->sut->output( $order_b1 );
		$output_b = ob_get_clean();

		$this->assertMatchesRegularExpression( '/order-attribution-total-orders">\s*2\s*</', $output_b, 'Guest B should see their 2 orders' );
		$this->assertMatchesRegularExpression( '/order-attribution-total-spend">\s*.*200\.00/', $output_b, 'Guest B should see total spend of 200' );
	}

	/**
	 * @testdox CPT fallback should render correct customer history from analytics tables.
	 */
	public function test_cpt_fallback_renders_with_analytics_data(): void {
		$this->toggle_cot_feature_and_usage( false );

		\WC_Helper_Reports::reset_stats_dbs();

		// Register the Override\Order class so wc_get_order() returns an instance
		// with get_report_customer_id(), which the CPT path requires.
		\Automattic\WooCommerce\Admin\Overrides\Order::add_filters();

		$customer_id = $this->factory->user->create();

		$order = WC_Helper_Order::create_order( $customer_id );
		$order->set_status( 'completed' );
		$order->set_total( 100 );
		$order->save();

		OrdersStatsDataStore::sync_order( $order->get_id() );

		// Re-fetch with Override class so output() takes the CPT path.
		$override_order = wc_get_order( $order->get_id() );

		ob_start();
		$this->sut->output( $override_order );
		$output = ob_get_clean();

		remove_filter( 'woocommerce_order_class', array( \Automattic\WooCommerce\Admin\Overrides\Order::class, 'order_class_name' ) );

		$this->assertStringContainsString( 'order-attribution-total-orders', $output, 'Should render the metabox template' );
		$this->assertMatchesRegularExpression( '/order-attribution-total-orders">\s*1\s*</', $output, 'Should show 1 order from analytics data' );
		$this->assertMatchesRegularExpression( '/order-attribution-total-spend">\s*.*100\.00/', $output, 'Should show total spend of 100' );
	}
}
