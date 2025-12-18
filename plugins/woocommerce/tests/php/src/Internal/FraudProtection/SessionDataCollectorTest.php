<?php
/**
 * SessionDataCollectorTest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\FraudProtection\SessionDataCollector;
use Automattic\WooCommerce\Internal\FraudProtection\SessionClearanceManager;

/**
 * Tests for SessionDataCollector.
 *
 * @covers \Automattic\WooCommerce\Internal\FraudProtection\SessionDataCollector
 */
class SessionDataCollectorTest extends \WC_Unit_Test_Case {

	/**
	 * The system under test.
	 *
	 * @var SessionDataCollector
	 */
	private $sut;

	/**
	 * SessionClearanceManager instance.
	 *
	 * @var SessionClearanceManager
	 */
	private $session_clearance_manager;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Ensure WooCommerce cart and session are available.
		if ( ! did_action( 'woocommerce_load_cart_from_session' ) && function_exists( 'wc_load_cart' ) ) {
			wc_load_cart();
		}

		$this->session_clearance_manager = new SessionClearanceManager();
		$this->sut                       = new SessionDataCollector();
		$this->sut->init( $this->session_clearance_manager );
	}

	/**
	 * Test that collect() method returns properly structured nested array with 9 top-level keys.
	 */
	public function test_collect_returns_properly_structured_nested_array() {
		$result = $this->sut->collect();

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'event_type', $result );
		$this->assertArrayHasKey( 'timestamp', $result );
		$this->assertArrayHasKey( 'session', $result );
		$this->assertArrayHasKey( 'customer', $result );
		$this->assertArrayHasKey( 'order', $result );
		$this->assertArrayHasKey( 'shipping_address', $result );
		$this->assertArrayHasKey( 'billing_address', $result );
		$this->assertArrayHasKey( 'payment', $result );
		$this->assertArrayHasKey( 'event_data', $result );
		$this->assertCount( 9, $result );
	}

	/**
	 * Test that collect() accepts event_type and event_data parameters.
	 */
	public function test_collect_accepts_event_type_and_event_data_parameters() {
		$event_type = 'checkout_started';
		$event_data = array(
			'page'   => 'checkout',
			'source' => 'test',
		);

		$result = $this->sut->collect( $event_type, $event_data );

		$this->assertEquals( $event_type, $result['event_type'] );
		$this->assertEquals( $event_data, $result['event_data'] );
	}

	/**
	 * Test graceful degradation when session is unavailable.
	 */
	public function test_graceful_degradation_when_session_unavailable() {
		// This test verifies that collect() doesn't throw exceptions even if session is unavailable.
		// We can't easily simulate session being unavailable in unit tests without mocking,
		// but we can verify that calling collect() returns a valid structure.
		$result = $this->sut->collect();

		$this->assertIsArray( $result );
		$this->assertCount( 9, $result );
		// All sections should be initialized even if session unavailable.
		$this->assertIsArray( $result['session'] );
		$this->assertIsArray( $result['customer'] );
		$this->assertIsArray( $result['order'] );
	}

	/**
	 * Test timestamp format is UTC (gmdate format).
	 */
	public function test_timestamp_format_is_utc() {
		$result = $this->sut->collect();

		$this->assertArrayHasKey( 'timestamp', $result );
		$this->assertNotEmpty( $result['timestamp'] );

		// Verify timestamp is in Y-m-d H:i:s format.
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $result['timestamp'] );

		// Verify timestamp is recent (within last 10 seconds).
		$timestamp      = strtotime( $result['timestamp'] );
		$current_time   = time();
		$time_difference = abs( $current_time - $timestamp );
		$this->assertLessThanOrEqual( 10, $time_difference, 'Timestamp should be recent (within 10 seconds)' );
	}

	/**
	 * Test that collect() uses default values when parameters not provided.
	 */
	public function test_collect_uses_default_values_when_parameters_not_provided() {
		$result = $this->sut->collect();

		$this->assertNull( $result['event_type'] );
		$this->assertEquals( array(), $result['event_data'] );
	}

	/**
	 * Test that nested sections are initialized as arrays.
	 */
	public function test_nested_sections_initialized_as_arrays() {
		$result = $this->sut->collect();

		$this->assertIsArray( $result['session'] );
		$this->assertIsArray( $result['customer'] );
		$this->assertIsArray( $result['order'] );
		$this->assertIsArray( $result['shipping_address'] );
		$this->assertIsArray( $result['billing_address'] );
		$this->assertIsArray( $result['payment'] );
	}

	/**
	 * Test session data includes all 6 required fields.
	 */
	public function test_session_data_includes_all_required_fields() {
		$result = $this->sut->collect();

		$this->assertIsArray( $result['session'] );
		$this->assertArrayHasKey( 'session_id', $result['session'] );
		$this->assertArrayHasKey( 'ip_address', $result['session'] );
		$this->assertArrayHasKey( 'email', $result['session'] );
		$this->assertArrayHasKey( 'ja3_hash', $result['session'] );
		$this->assertArrayHasKey( 'user_agent', $result['session'] );
		$this->assertArrayHasKey( 'is_user_session', $result['session'] );
	}

	/**
	 * Test session_id is retrieved from SessionClearanceManager.
	 */
	public function test_session_id_retrieved_from_session_clearance_manager() {
		$result = $this->sut->collect();

		$this->assertArrayHasKey( 'session_id', $result['session'] );
		// Session ID should be a string when session is available.
		if ( isset( WC()->session ) ) {
			$this->assertIsString( $result['session']['session_id'] );
			$this->assertNotEmpty( $result['session']['session_id'] );
		}
	}

	/**
	 * Test email collection fallback chain for logged-in user.
	 */
	public function test_email_collection_for_logged_in_user() {
		// Create a test user and log them in.
		$user_id = $this->factory->user->create(
			array(
				'user_email' => 'testuser@example.com',
			)
		);
		wp_set_current_user( $user_id );

		$result = $this->sut->collect();

		$this->assertArrayHasKey( 'email', $result['session'] );
		$this->assertEquals( 'testuser@example.com', $result['session']['email'] );
	}

	/**
	 * Test email collection from WC_Customer when user not logged in.
	 */
	public function test_email_collection_from_wc_customer() {
		// Ensure no user is logged in.
		wp_set_current_user( 0 );

		// Set customer billing email.
		if ( isset( WC()->customer ) ) {
			WC()->customer->set_billing_email( 'customer@example.com' );
		}

		$result = $this->sut->collect();

		$this->assertArrayHasKey( 'email', $result['session'] );
		// Email should be from customer object if available.
		if ( isset( WC()->customer ) ) {
			$this->assertEquals( 'customer@example.com', $result['session']['email'] );
		}
	}

	/**
	 * Test customer data includes all 4 required fields.
	 */
	public function test_customer_data_includes_all_required_fields() {
		$result = $this->sut->collect();

		$this->assertIsArray( $result['customer'] );
		$this->assertArrayHasKey( 'first_name', $result['customer'] );
		$this->assertArrayHasKey( 'last_name', $result['customer'] );
		$this->assertArrayHasKey( 'billing_email', $result['customer'] );
		$this->assertArrayHasKey( 'lifetime_order_count', $result['customer'] );
	}

	/**
	 * Test customer name collection from WC_Customer.
	 */
	public function test_customer_name_collection_from_wc_customer() {
		if ( isset( WC()->customer ) ) {
			WC()->customer->set_billing_first_name( 'John' );
			WC()->customer->set_billing_last_name( 'Doe' );
		}

		$result = $this->sut->collect();

		$this->assertArrayHasKey( 'first_name', $result['customer'] );
		$this->assertArrayHasKey( 'last_name', $result['customer'] );

		if ( isset( WC()->customer ) ) {
			$this->assertEquals( 'John', $result['customer']['first_name'] );
			$this->assertEquals( 'Doe', $result['customer']['last_name'] );
		}
	}

	/**
	 * Test customer data fallback to session when WC_Customer not available.
	 */
	public function test_customer_data_fallback_to_session() {
		// Ensure no user is logged in.
		wp_set_current_user( 0 );

		// Set customer data in session.
		if ( isset( WC()->session ) ) {
			WC()->session->set(
				'customer',
				array(
					'first_name' => 'Jane',
					'last_name'  => 'Smith',
					'email'      => 'jane.smith@example.com',
				)
			);
		}

		// Nullify WC_Customer to force fallback to session.
		$original_customer = WC()->customer;
		WC()->customer     = null;

		$result = $this->sut->collect();

		// Restore original customer.
		WC()->customer = $original_customer;

		// Verify session data was used.
		if ( isset( WC()->session ) ) {
			$this->assertEquals( 'Jane', $result['customer']['first_name'] );
			$this->assertEquals( 'Smith', $result['customer']['last_name'] );
			$this->assertEquals( 'jane.smith@example.com', $result['customer']['billing_email'] );
		}
	}

	/**
	 * Test lifetime_order_count calculation for registered customer.
	 */
	public function test_lifetime_order_count_for_registered_customer() {
		// Create a test user.
		$user_id = $this->factory->user->create(
			array(
				'user_email' => 'customer@example.com',
			)
		);
		wp_set_current_user( $user_id );

		// Create completed orders for the user.
		$order1 = wc_create_order();
		$order1->set_customer_id( $user_id );
		$order1->set_status( 'completed' );
		$order1->save();

		$order2 = wc_create_order();
		$order2->set_customer_id( $user_id );
		$order2->set_status( 'completed' );
		$order2->save();

		// Create a pending order (should not be counted).
		$order3 = wc_create_order();
		$order3->set_customer_id( $user_id );
		$order3->set_status( 'pending' );
		$order3->save();

		$result = $this->sut->collect();

		$this->assertArrayHasKey( 'lifetime_order_count', $result['customer'] );
		$this->assertEquals( 2, $result['customer']['lifetime_order_count'] );
	}

	/**
	 * Test graceful degradation when customer data unavailable.
	 */
	public function test_graceful_degradation_when_customer_data_unavailable() {
		// Ensure no user is logged in.
		wp_set_current_user( 0 );

		// Clear customer data.
		if ( isset( WC()->customer ) ) {
			WC()->customer->set_billing_first_name( '' );
			WC()->customer->set_billing_last_name( '' );
			WC()->customer->set_billing_email( '' );
		}

		$result = $this->sut->collect();

		// Should return customer section with fields, even if empty/null.
		$this->assertIsArray( $result['customer'] );
		$this->assertArrayHasKey( 'first_name', $result['customer'] );
		$this->assertArrayHasKey( 'last_name', $result['customer'] );
		$this->assertArrayHasKey( 'billing_email', $result['customer'] );
		$this->assertArrayHasKey( 'lifetime_order_count', $result['customer'] );
	}
}
