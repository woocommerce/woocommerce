<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\OrderMilestoneEasterEgg;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;

/**
 * Unit tests for OrderMilestoneEasterEgg.
 */
class OrderMilestoneEasterEggTest extends \WC_Unit_Test_Case {
	use HPOSToggleTrait;

	/** @var OrderMilestoneEasterEgg */
	private OrderMilestoneEasterEgg $sut;

	/** @var int */
	private int $admin_user_id;

	/**
	 * Set up the test case.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->setup_cot();
		$this->toggle_cot_feature_and_usage( true );

		$this->sut           = new OrderMilestoneEasterEgg();
		$this->admin_user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->admin_user_id );
	}

	/**
	 * Tear down the test case.
	 */
	public function tearDown(): void {
		delete_option( $this->get_cache_option_name() );
		remove_action( 'admin_enqueue_scripts', array( $this->sut, 'handle_admin_enqueue_scripts' ) );
		remove_action( 'wp_ajax_wc_egg_dismiss', array( $this->sut, 'handle_ajax_dismiss' ) );
		remove_action( 'wp_ajax_wc_egg_opt_out', array( $this->sut, 'handle_ajax_opt_out' ) );
		remove_action( 'woocommerce_new_order', array( $this->sut, 'clear_milestone_cache' ) );
		remove_action( 'woocommerce_update_order', array( $this->sut, 'clear_milestone_cache' ) );
		remove_action( 'woocommerce_delete_order', array( $this->sut, 'clear_milestone_cache' ) );
		remove_action( 'woocommerce_trash_order', array( $this->sut, 'clear_milestone_cache' ) );

		// Drop HPOS tables before toggling off — avoids the "orders out of sync" exception
		// that fires when HPOS is disabled while the table still holds unsync'd rows.
		OrderHelper::delete_order_custom_tables();
		$this->clean_up_cot_setup();
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	// -------------------------------------------------------------------------
	// Hook registration
	// -------------------------------------------------------------------------

	/**
	 * @testdox init registers all expected hooks.
	 */
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
		$this->assertNotFalse(
			has_action( 'woocommerce_new_order', array( $this->sut, 'clear_milestone_cache' ) )
		);
		$this->assertNotFalse(
			has_action( 'woocommerce_update_order', array( $this->sut, 'clear_milestone_cache' ) )
		);
		$this->assertNotFalse(
			has_action( 'woocommerce_delete_order', array( $this->sut, 'clear_milestone_cache' ) )
		);
		$this->assertNotFalse(
			has_action( 'woocommerce_trash_order', array( $this->sut, 'clear_milestone_cache' ) )
		);
	}

	// -------------------------------------------------------------------------
	// AJAX handlers
	// -------------------------------------------------------------------------

	/**
	 * @testdox handle_ajax_dismiss saves the seen meta for the given order.
	 */
	public function test_handle_ajax_dismiss_saves_seen_meta(): void {
		$order = $this->create_real_paid_order();

		$nonce             = wp_create_nonce( 'wc_egg_dismiss' );
		$_POST['order_id'] = $order->get_id();
		$_POST['nonce']    = $nonce;
		$_REQUEST['nonce'] = $nonce;

		try {
			$this->sut->handle_ajax_dismiss();
		} catch ( \WPDieException $e ) {
			$this->assertInstanceOf( \WPDieException::class, $e );
		}

		$this->assertEquals(
			'1',
			get_user_meta( $this->admin_user_id, '_wc_egg_seen_' . $order->get_id(), true )
		);
	}

	/**
	 * @testdox handle_ajax_dismiss does nothing when order_id is zero.
	 */
	public function test_handle_ajax_dismiss_ignores_zero_order_id(): void {
		$nonce             = wp_create_nonce( 'wc_egg_dismiss' );
		$_POST['order_id'] = 0;
		$_POST['nonce']    = $nonce;
		$_REQUEST['nonce'] = $nonce;

		try {
			$this->sut->handle_ajax_dismiss();
		} catch ( \WPDieException $e ) {
			$this->assertInstanceOf( \WPDieException::class, $e );
		}

		$meta = get_user_meta( $this->admin_user_id );
		$keys = array_keys( $meta );
		$seen = array_filter( $keys, fn( $k ) => str_starts_with( $k, '_wc_egg_seen_' ) );
		$this->assertEmpty( $seen );
	}

	/**
	 * @testdox handle_ajax_opt_out saves the opted-out meta for the current user.
	 */
	public function test_handle_ajax_opt_out_saves_opted_out_meta(): void {
		$nonce             = wp_create_nonce( 'wc_egg_dismiss' );
		$_POST['nonce']    = $nonce;
		$_REQUEST['nonce'] = $nonce;

		try {
			$this->sut->handle_ajax_opt_out();
		} catch ( \WPDieException $e ) {
			$this->assertInstanceOf( \WPDieException::class, $e );
		}

		$this->assertEquals(
			'1',
			get_user_meta( $this->admin_user_id, '_wc_egg_opted_out', true )
		);
	}

	// -------------------------------------------------------------------------
	// is_qualifying_order
	// -------------------------------------------------------------------------

	/**
	 * @testdox is_qualifying_order returns true for a processing order with a transaction ID.
	 */
	public function test_is_qualifying_order_returns_true_for_processing_order(): void {
		$order = $this->create_real_paid_order();
		$this->assertTrue( $this->sut->is_qualifying_order( $order->get_id() ) );
	}

	/**
	 * @testdox is_qualifying_order returns true for a completed order with a transaction ID.
	 */
	public function test_is_qualifying_order_returns_true_for_completed_order(): void {
		$order = new \WC_Order();
		$order->set_transaction_id( 'txn_live_' . wp_rand( 1000, 9999 ) );
		$order->set_status( 'completed' );
		$order->save();

		$this->assertTrue( $this->sut->is_qualifying_order( $order->get_id() ) );
	}

	/**
	 * @testdox is_qualifying_order returns false when no transaction ID is set.
	 */
	public function test_is_qualifying_order_returns_false_without_transaction_id(): void {
		$order = new \WC_Order();
		$order->set_status( 'processing' );
		$order->save();

		$this->assertFalse( $this->sut->is_qualifying_order( $order->get_id() ) );
	}

	/**
	 * @testdox is_qualifying_order returns false when status is not processing or completed.
	 */
	public function test_is_qualifying_order_returns_false_for_pending_status(): void {
		$order = new \WC_Order();
		$order->set_transaction_id( 'txn_live_789' );
		$order->set_status( 'pending' );
		$order->save();

		$this->assertFalse( $this->sut->is_qualifying_order( $order->get_id() ) );
	}

	/**
	 * @testdox is_qualifying_order returns false for a non-existent order ID.
	 */
	public function test_is_qualifying_order_returns_false_for_nonexistent_order(): void {
		$this->assertFalse( $this->sut->is_qualifying_order( 999999 ) );
	}

	// -------------------------------------------------------------------------
	// get_milestone_map (via filter hook to inspect)
	// -------------------------------------------------------------------------

	/**
	 * @testdox get_milestone_map identifies the first qualifying order as a milestone.
	 */
	public function test_get_milestone_map_identifies_first_order(): void {
		$order = $this->create_real_paid_order();

		$map = $this->get_milestone_map_via_filter();

		$this->assertArrayHasKey( $order->get_id(), $map );
		$this->assertEquals( 'llama', $map[ $order->get_id() ]['variant'] );
	}

	/**
	 * @testdox get_milestone_map applies the wc_order_milestone_egg_map filter.
	 */
	public function test_get_milestone_map_applies_wc_order_milestone_egg_map_filter(): void {
		$this->create_real_paid_order();

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

	/**
	 * @testdox get_milestone_map caches computed milestone order IDs.
	 */
	public function test_get_milestone_map_caches_computed_milestone_order_ids(): void {
		$order = $this->create_real_paid_order();

		$this->get_milestone_map_via_filter();

		$this->assertSame(
			array( 'first' => $order->get_id() ),
			get_option( $this->get_cache_option_name(), array() )
		);
	}

	/**
	 * @testdox get_milestone_map uses cached milestone order IDs when available.
	 */
	public function test_get_milestone_map_uses_cached_milestone_order_ids(): void {
		update_option( $this->get_cache_option_name(), array( 'first' => 12345 ), false );

		$map = $this->get_milestone_map_via_filter();

		$this->assertArrayHasKey( 12345, $map );
	}

	/**
	 * @testdox clear_milestone_cache deletes cached milestone order IDs.
	 */
	public function test_clear_milestone_cache_deletes_cached_milestone_order_ids(): void {
		update_option( $this->get_cache_option_name(), array( 'first' => 12345 ), false );

		$this->sut->clear_milestone_cache();

		$this->assertFalse( get_option( $this->get_cache_option_name(), false ) );
	}

	// -------------------------------------------------------------------------
	// Opt-out gate (handle_admin_enqueue_scripts)
	// -------------------------------------------------------------------------

	/**
	 * @testdox handle_admin_enqueue_scripts skips enqueue when the user has opted out.
	 */
	public function test_enqueue_skipped_when_user_opted_out(): void {
		$order = $this->create_real_paid_order();
		update_user_meta( $this->admin_user_id, '_wc_egg_opted_out', '1' );

		$_GET['page']   = 'wc-orders';
		$_GET['action'] = 'edit';
		$_GET['id']     = (string) $order->get_id();

		$this->sut->handle_admin_enqueue_scripts();

		$this->assertFalse( wp_script_is( 'wc-order-milestone-easter-egg', 'enqueued' ) );

		unset( $_GET['page'], $_GET['action'], $_GET['id'] );
		delete_user_meta( $this->admin_user_id, '_wc_egg_opted_out' );
	}

	/**
	 * @testdox handle_admin_enqueue_scripts skips enqueue when not on the order edit page.
	 */
	public function test_enqueue_skipped_when_not_order_edit_page(): void {
		$_GET['page'] = 'woocommerce';

		$this->sut->handle_admin_enqueue_scripts();

		$this->assertFalse( wp_script_is( 'wc-order-milestone-easter-egg', 'enqueued' ) );

		unset( $_GET['page'] );
	}

	/**
	 * @testdox handle_admin_enqueue_scripts skips enqueue when the current order is not qualifying.
	 */
	public function test_enqueue_skipped_when_current_order_is_not_qualifying(): void {
		$order = new \WC_Order();
		$order->set_status( 'pending' );
		$order->save();

		$_GET['page']   = 'wc-orders';
		$_GET['action'] = 'edit';
		$_GET['id']     = (string) $order->get_id();

		$this->sut->handle_admin_enqueue_scripts();

		$this->assertFalse( wp_script_is( 'wc-order-milestone-easter-egg', 'enqueued' ) );

		unset( $_GET['page'], $_GET['action'], $_GET['id'] );
	}

	/**
	 * @testdox handle_admin_enqueue_scripts skips enqueue when the current order qualifies but is not itself a milestone.
	 */
	public function test_enqueue_skipped_when_qualifying_order_is_not_a_milestone(): void {
		// Create a first milestone order so the milestone map is non-empty.
		$milestone_order = $this->create_real_paid_order();
		unset( $milestone_order );

		// Create a second qualifying order — it passes is_qualifying_order() but
		// is not at a milestone position in the ordered list.
		$non_milestone_order = $this->create_real_paid_order();

		$_GET['page']   = 'wc-orders';
		$_GET['action'] = 'edit';
		$_GET['id']     = (string) $non_milestone_order->get_id();

		$this->sut->handle_admin_enqueue_scripts();

		$this->assertFalse( wp_script_is( 'wc-order-milestone-easter-egg', 'enqueued' ) );

		unset( $_GET['page'], $_GET['action'], $_GET['id'] );
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Creates a real paid order (processing status with a transaction ID).
	 */
	private function create_real_paid_order(): \WC_Order {
		$order = new \WC_Order();
		$order->set_transaction_id( 'txn_live_' . wp_rand( 1000, 9999 ) );
		$order->set_status( 'processing' );
		$order->save();
		return $order;
	}

	/**
	 * Returns the private milestone cache option name.
	 *
	 * @return string
	 */
	private function get_cache_option_name(): string {
		$ref = new \ReflectionClass( OrderMilestoneEasterEgg::class );
		return (string) $ref->getConstant( 'MILESTONE_CACHE_OPTION' );
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
}
