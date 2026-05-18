<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\OrderMilestoneEasterEgg;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;

/**
 * Unit tests for OrderMilestoneEasterEgg.
 */
class OrderMilestoneEasterEggTest extends \WC_Unit_Test_Case {
	use HPOSToggleTrait;

	/** @var OrderMilestoneEasterEgg */
	private OrderMilestoneEasterEgg $sut;

	/** @var int */
	private int $admin_user_id;

	public function setUp(): void {
		parent::setUp();
		$this->setup_cot();
		$this->toggle_cot_feature_and_usage( true );

		$this->sut           = new OrderMilestoneEasterEgg();
		$this->admin_user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->admin_user_id );
	}

	public function tearDown(): void {
		$this->clean_up_cot_setup();
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	// -------------------------------------------------------------------------
	// Hook registration
	// -------------------------------------------------------------------------

	public function test_init_registers_all_hooks(): void {
		$this->sut->init();

		$this->assertNotFalse(
			has_action( 'admin_enqueue_scripts', array( $this->sut, 'handle_admin_enqueue_scripts' ) )
		);
		$this->assertNotFalse(
			has_action( 'wp_ajax_wc_egg_dismiss', array( $this->sut, 'handle_ajax_dismiss' ) )
		);
		$this->assertNotFalse(
			has_action( 'wp_ajax_wc_egg_opt_out', array( $this->sut, 'handle_ajax_opt_out' ) )
		);
	}

	// -------------------------------------------------------------------------
	// AJAX handlers
	// -------------------------------------------------------------------------

	public function test_handle_ajax_dismiss_saves_seen_meta(): void {
		$order = $this->create_wcpay_live_order();

		$_POST['order_id'] = $order->get_id();
		$_POST['nonce']    = wp_create_nonce( 'wc_egg_dismiss' );
		$_REQUEST['nonce'] = $_POST['nonce'];

		try {
			$this->sut->handle_ajax_dismiss();
		} catch ( \WPDieException $e ) {
			// wp_die() throws in test context — expected.
		}

		$this->assertEquals(
			'1',
			get_user_meta( $this->admin_user_id, '_wc_egg_seen_' . $order->get_id(), true )
		);
	}

	public function test_handle_ajax_dismiss_ignores_zero_order_id(): void {
		$_POST['order_id'] = 0;
		$_POST['nonce']    = wp_create_nonce( 'wc_egg_dismiss' );
		$_REQUEST['nonce'] = $_POST['nonce'];

		try {
			$this->sut->handle_ajax_dismiss();
		} catch ( \WPDieException $e ) {
			// expected.
		}

		$meta = get_user_metadata( $this->admin_user_id, '', false );
		$keys = array_keys( $meta );
		$seen = array_filter( $keys, fn( $k ) => str_starts_with( $k, '_wc_egg_seen_' ) );
		$this->assertEmpty( $seen );
	}

	public function test_handle_ajax_opt_out_saves_opted_out_meta(): void {
		$_POST['nonce']    = wp_create_nonce( 'wc_egg_dismiss' );
		$_REQUEST['nonce'] = $_POST['nonce'];

		try {
			$this->sut->handle_ajax_opt_out();
		} catch ( \WPDieException $e ) {
			// expected.
		}

		$this->assertEquals(
			'1',
			get_user_meta( $this->admin_user_id, '_wc_egg_opted_out', true )
		);
	}

	// -------------------------------------------------------------------------
	// is_wcpay_live_order
	// -------------------------------------------------------------------------

	public function test_is_wcpay_live_order_returns_true_for_qualifying_order(): void {
		$order = $this->create_wcpay_live_order();
		$this->assertTrue( $this->sut->is_wcpay_live_order( $order->get_id() ) );
	}

	public function test_is_wcpay_live_order_returns_false_without_transaction_id(): void {
		$order = new \WC_Order();
$order->update_meta_data( 'wcpay_mode', 'live' );
		$order->save();

		$this->assertFalse( $this->sut->is_wcpay_live_order( $order->get_id() ) );
	}

	public function test_is_wcpay_live_order_returns_false_for_test_mode(): void {
		$order = new \WC_Order();
$order->set_transaction_id( 'txn_test_123' );
		$order->update_meta_data( 'wcpay_mode', 'test' );
		$order->save();

		$this->assertFalse( $this->sut->is_wcpay_live_order( $order->get_id() ) );
	}

	public function test_is_wcpay_live_order_returns_false_for_missing_wcpay_meta(): void {
		$order = new \WC_Order();
$order->set_transaction_id( 'txn_live_456' );
		$order->save();

		$this->assertFalse( $this->sut->is_wcpay_live_order( $order->get_id() ) );
	}

	public function test_is_wcpay_live_order_returns_false_for_nonexistent_order(): void {
		$this->assertFalse( $this->sut->is_wcpay_live_order( 999999 ) );
	}

	// -------------------------------------------------------------------------
	// get_milestone_map (via filter hook to inspect)
	// -------------------------------------------------------------------------

	public function test_get_milestone_map_identifies_first_order(): void {
		$order = $this->create_wcpay_live_order();

		$map = $this->get_milestone_map_via_filter();

		$this->assertArrayHasKey( $order->get_id(), $map );
		$this->assertEquals( 'lama', $map[ $order->get_id() ]['variant'] );
	}

	public function test_get_milestone_map_is_empty_when_no_wcpay_live_orders(): void {
		$order = new \WC_Order();
$order->set_transaction_id( 'txn_test' );
		$order->update_meta_data( 'wcpay_mode', 'test' );
		$order->save();

		$map = $this->get_milestone_map_via_filter();
		$this->assertEmpty( $map );
	}

	public function test_get_milestone_map_is_empty_when_transaction_id_missing(): void {
		$order = new \WC_Order();
$order->update_meta_data( 'wcpay_mode', 'live' );
		$order->save();

		$map = $this->get_milestone_map_via_filter();
		$this->assertEmpty( $map );
	}

	public function test_get_milestone_map_applies_wc_order_milestone_egg_map_filter(): void {
		$order = $this->create_wcpay_live_order();

		add_filter(
			'wc_order_milestone_egg_map',
			function ( $map ) {
				$map[99999] = array( 'variant' => 'test_variant' );
				return $map;
			}
		);

		$map = $this->get_milestone_map_via_filter();

		remove_all_filters( 'wc_order_milestone_egg_map' );

		$this->assertArrayHasKey( 99999, $map );
	}

	// -------------------------------------------------------------------------
	// Opt-out gate (handle_admin_enqueue_scripts)
	// -------------------------------------------------------------------------

	public function test_enqueue_skipped_when_user_opted_out(): void {
		// Simulate opted-out user on order edit page with WCPay active.
		update_user_meta( $this->admin_user_id, '_wc_egg_opted_out', '1' );

		$_GET['page']   = 'wc-orders';
		$_GET['action'] = 'edit';
		$_GET['id']     = '1';

		$this->mock_wc_payments_active( true );
		$this->sut->handle_admin_enqueue_scripts();

		$this->assertFalse( wp_script_is( 'wc-order-milestone-easter-egg', 'enqueued' ) );

		unset( $_GET['page'], $_GET['action'], $_GET['id'] );
		delete_user_meta( $this->admin_user_id, '_wc_egg_opted_out' );
	}

	public function test_enqueue_skipped_when_not_order_edit_page(): void {
		$_GET['page'] = 'woocommerce';

		$this->mock_wc_payments_active( true );
		$this->sut->handle_admin_enqueue_scripts();

		$this->assertFalse( wp_script_is( 'wc-order-milestone-easter-egg', 'enqueued' ) );

		unset( $_GET['page'] );
	}

	public function test_enqueue_skipped_when_current_order_is_not_wcpay_live(): void {
		$order = new \WC_Order();
$order->save();

		$_GET['page']   = 'wc-orders';
		$_GET['action'] = 'edit';
		$_GET['id']     = (string) $order->get_id();

		$this->mock_wc_payments_active( true );
		$this->sut->handle_admin_enqueue_scripts();

		$this->assertFalse( wp_script_is( 'wc-order-milestone-easter-egg', 'enqueued' ) );

		unset( $_GET['page'], $_GET['action'], $_GET['id'] );
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Creates a WooPayments live-mode order with a transaction ID.
	 */
	private function create_wcpay_live_order(): \WC_Order {
		$order = new \WC_Order();
$order->set_transaction_id( 'txn_live_' . wp_rand( 1000, 9999 ) );
		$order->update_meta_data( 'wcpay_mode', 'live' );
		$order->save();
		return $order;
	}

	/**
	 * Calls get_milestone_map() via a filter that captures the result before it's returned.
	 *
	 * @return array<int, array<string, string>>
	 */
	private function get_milestone_map_via_filter(): array {
		$captured = array();
		add_filter(
			'wc_order_milestone_egg_map',
			function ( $map ) use ( &$captured ) {
				$captured = $map;
				return $map;
			}
		);

		// Use reflection to call the private method.
		$ref = new \ReflectionMethod( OrderMilestoneEasterEgg::class, 'get_milestone_map' );
		$ref->setAccessible( true );
		$ref->invoke( $this->sut );

		remove_all_filters( 'wc_order_milestone_egg_map' );

		return $captured;
	}

	/**
	 * Mocks WC_Payments class existence for the duration of a test.
	 * Uses a workaround via class_alias when the class doesn't exist.
	 */
	private function mock_wc_payments_active( bool $active ): void {
		if ( $active && ! class_exists( 'WC_Payments' ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_class_alias
			class_alias( \stdClass::class, 'WC_Payments' );
		}
	}
}
