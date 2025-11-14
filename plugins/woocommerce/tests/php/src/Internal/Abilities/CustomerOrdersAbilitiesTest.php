<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

use Automattic\WooCommerce\Internal\Abilities\CustomerOrdersAbilities;

/**
 * Tests for Customer Orders Abilities
 *
 * @since 10.5.0
 */
class CustomerOrdersAbilitiesTest extends \WC_REST_Unit_Test_Case {

	/**
	 * Action name for abilities API initialization.
	 *
	 * @var string
	 */
	private $abilities_init_action;

	/**
	 * Test customer user ID.
	 *
	 * @var int
	 */
	private $customer_id;

	/**
	 * Test order ID.
	 *
	 * @var int
	 */
	private $order_id;

	/**
	 * Check if Abilities API is in WordPress core.
	 *
	 * @return bool
	 */
	private function are_abilities_in_wp_core(): bool {
		return class_exists( 'WP_Ability_Categories_Registry' );
	}

	/**
	 * Set up before each test.
	 */
	public function set_up() {
		global $wp_actions;

		parent::set_up();

		// Detect WordPress 6.9+ for action names.
		$are_abilities_in_wp_core = $this->are_abilities_in_wp_core();
		$this->abilities_init_action = $are_abilities_in_wp_core ? 'wp_abilities_api_init' : 'abilities_api_init';

		// Ensure abilities API is loaded.
		if ( ! function_exists( 'wp_register_ability' ) ) {
			$bootstrap_file = WP_PLUGIN_DIR . '/woocommerce/vendor/wordpress/abilities-api/includes/bootstrap.php';
			if ( file_exists( $bootstrap_file ) ) {
				require $bootstrap_file;
			}
		}

		// Set init action counter.
		$wp_actions['init'] = 1; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		// Create test customer.
		$this->customer_id = $this->factory->user->create(
			array(
				'role' => 'customer',
			)
		);

		// Create test order for customer.
		$this->order_id = \WC_Helper_Order::create_order( $this->customer_id );

		// Register abilities for testing.
		CustomerOrdersAbilities::init();
		do_action( $this->abilities_init_action );
	}

	/**
	 * Tear down after each test.
	 */
	public function tear_down() {
		global $wp_actions;

		// Clean up abilities.
		$abilities_to_cleanup = array(
			'woocommerce/list-my-orders',
			'woocommerce/get-my-order',
			'woocommerce/update-my-order',
		);

		foreach ( $abilities_to_cleanup as $ability_id ) {
			if ( function_exists( 'wp_unregister_ability' ) ) {
				wp_unregister_ability( $ability_id );
			}
		}

		// Delete test order.
		if ( $this->order_id ) {
			wp_delete_post( $this->order_id, true );
		}

		// Delete test customer.
		if ( $this->customer_id ) {
			wp_delete_user( $this->customer_id );
		}

		// Reset user.
		wp_set_current_user( 0 );

		// Reset action counters.
		if ( isset( $wp_actions[ $this->abilities_init_action ] ) ) {
			unset( $wp_actions[ $this->abilities_init_action ] );
		}

		parent::tear_down();
	}

	/**
	 * Test that abilities are registered.
	 *
	 * @group abilities-api
	 */
	public function test_abilities_are_registered() {
		$list_ability   = wp_get_ability( 'woocommerce/list-my-orders' );
		$get_ability    = wp_get_ability( 'woocommerce/get-my-order' );
		$update_ability = wp_get_ability( 'woocommerce/update-my-order' );

		$this->assertNotNull( $list_ability, 'list-my-orders ability should be registered' );
		$this->assertNotNull( $get_ability, 'get-my-order ability should be registered' );
		$this->assertNotNull( $update_ability, 'update-my-order ability should be registered' );
	}

	/**
	 * Test list-my-orders returns customer orders.
	 *
	 * @group abilities-api
	 */
	public function test_list_my_orders_returns_customer_orders() {
		// Set current user to customer.
		wp_set_current_user( $this->customer_id );

		$ability = wp_get_ability( 'woocommerce/list-my-orders' );
		$result  = $ability->execute( array() );

		$this->assertTrue( $result['success'], 'list-my-orders should succeed' );
		$this->assertIsArray( $result['orders'], 'orders should be an array' );
		$this->assertGreaterThan( 0, count( $result['orders'] ), 'should return at least one order' );
		$this->assertEquals( $this->order_id, $result['orders'][0]['id'], 'should return test order' );
	}

	/**
	 * Test get-my-order returns order details.
	 *
	 * @group abilities-api
	 */
	public function test_get_my_order_returns_details() {
		// Set current user to customer.
		wp_set_current_user( $this->customer_id );

		$ability = wp_get_ability( 'woocommerce/get-my-order' );
		$result  = $ability->execute( array( 'order_id' => $this->order_id ) );

		$this->assertTrue( $result['success'], 'get-my-order should succeed' );
		$this->assertIsArray( $result['order'], 'order should be an array' );
		$this->assertEquals( $this->order_id, $result['order']['id'], 'should return correct order ID' );
		$this->assertArrayHasKey( 'line_items', $result['order'], 'should include line items' );
	}

	/**
	 * Test get-my-order blocks access to other customer orders.
	 *
	 * @group abilities-api
	 */
	public function test_get_my_order_blocks_other_customer_orders() {
		// Create another customer.
		$other_customer_id = $this->factory->user->create( array( 'role' => 'customer' ) );

		// Set current user to other customer.
		wp_set_current_user( $other_customer_id );

		$ability = wp_get_ability( 'woocommerce/get-my-order' );
		$result  = $ability->execute( array( 'order_id' => $this->order_id ) );

		$this->assertFalse( $result['success'], 'get-my-order should fail for other customer' );
		$this->assertStringContainsString( 'permission', $result['message'], 'error should mention permission' );

		// Cleanup.
		wp_delete_user( $other_customer_id );
	}

	/**
	 * Test update-my-order can add notes.
	 *
	 * @group abilities-api
	 */
	public function test_update_my_order_adds_note() {
		// Set current user to customer.
		wp_set_current_user( $this->customer_id );

		$ability = wp_get_ability( 'woocommerce/update-my-order' );
		$result  = $ability->execute(
			array(
				'order_id' => $this->order_id,
				'action'   => 'add_note',
				'note'     => 'Please leave at side door',
			)
		);

		$this->assertTrue( $result['success'], 'update-my-order should succeed' );
		$this->assertStringContainsString( 'added', strtolower( $result['message'] ), 'message should confirm note added' );

		// Verify note was added.
		$order = wc_get_order( $this->order_id );
		$notes = $order->get_customer_order_notes();
		$this->assertGreaterThan( 0, count( $notes ), 'order should have customer notes' );
	}

	/**
	 * Test update-my-order can cancel pending orders.
	 *
	 * @group abilities-api
	 */
	public function test_update_my_order_cancels_pending_order() {
		// Set order to pending status.
		$order = wc_get_order( $this->order_id );
		$order->set_status( 'pending' );
		$order->save();

		// Set current user to customer.
		wp_set_current_user( $this->customer_id );

		$ability = wp_get_ability( 'woocommerce/update-my-order' );
		$result  = $ability->execute(
			array(
				'order_id' => $this->order_id,
				'action'   => 'cancel',
			)
		);

		$this->assertTrue( $result['success'], 'update-my-order should succeed' );
		$this->assertStringContainsString( 'cancelled', strtolower( $result['message'] ), 'message should confirm cancellation' );

		// Verify order was cancelled.
		$order = wc_get_order( $this->order_id );
		$this->assertEquals( 'cancelled', $order->get_status(), 'order status should be cancelled' );
	}

	/**
	 * Test update-my-order adds note for non-cancellable orders.
	 *
	 * @group abilities-api
	 */
	public function test_update_my_order_requests_cancellation_for_processing_order() {
		// Set order to processing status.
		$order = wc_get_order( $this->order_id );
		$order->set_status( 'processing' );
		$order->save();

		// Set current user to customer.
		wp_set_current_user( $this->customer_id );

		$ability = wp_get_ability( 'woocommerce/update-my-order' );
		$result  = $ability->execute(
			array(
				'order_id' => $this->order_id,
				'action'   => 'cancel',
			)
		);

		$this->assertTrue( $result['success'], 'update-my-order should succeed' );
		$this->assertStringContainsString( 'request', strtolower( $result['message'] ), 'message should mention request' );

		// Verify order is still processing.
		$order = wc_get_order( $this->order_id );
		$this->assertEquals( 'processing', $order->get_status(), 'order status should still be processing' );

		// Verify note was added.
		$notes = $order->get_customer_order_notes();
		$this->assertGreaterThan( 0, count( $notes ), 'order should have customer notes' );
	}
}
