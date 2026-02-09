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

		// Disable taxes before adding products to cart.
		update_option( 'woocommerce_calc_taxes', 'no' );

		// Clear any existing session data before each test.
		WC()->session->set( 'fraud_protection_collected_data', null );
	}

	/**
	 * Helper method to collect data and retrieve event from session.
	 *
	 * Events only contain: event_type, timestamp, event_data.
	 * For full data (session, customer, order, addresses), use get_collected_data().
	 *
	 * @param string|null $event_type Optional event type.
	 * @param array       $event_data Optional event data.
	 * @return array The collected event data from session.
	 */
	private function collect_and_get_event( ?string $event_type = null, array $event_data = array() ): array {
		$this->sut->collect( $event_type, $event_data );
		$stored_data = WC()->session->get( 'fraud_protection_collected_data' );
		return $stored_data[0] ?? array();
	}

	/**
	 * Helper method to collect data and retrieve full response via get_collected_data().
	 *
	 * Returns: wc_version, session, customer, shipping_address, billing_address, collected_events.
	 *
	 * @param string|null $event_type Optional event type.
	 * @param array       $event_data Optional event data.
	 * @return array The full collected data response.
	 */
	private function collect_and_get_data( ?string $event_type = null, array $event_data = array() ): array {
		$this->sut->collect( $event_type, $event_data );
		return $this->sut->get_collected_data();
	}

	/**
	 * @testdox collect() stores properly structured event with 3 top-level keys.
	 */
	public function test_collect_stores_properly_structured_event(): void {
		$event = $this->collect_and_get_event();

		$this->assertIsArray( $event );
		$this->assertArrayHasKey( 'event_type', $event );
		$this->assertArrayHasKey( 'timestamp', $event );
		$this->assertArrayHasKey( 'event_data', $event );
		$this->assertCount( 3, $event );
	}

	/**
	 * @testdox get_collected_data() returns properly structured response with 7 top-level keys.
	 */
	public function test_get_collected_data_returns_properly_structured_response(): void {
		$result = $this->collect_and_get_data();

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'wc_version', $result );
		$this->assertArrayHasKey( 'session', $result );
		$this->assertArrayHasKey( 'customer', $result );
		$this->assertArrayHasKey( 'order', $result );
		$this->assertArrayHasKey( 'shipping_address', $result );
		$this->assertArrayHasKey( 'billing_address', $result );
		$this->assertArrayHasKey( 'collected_events', $result );
		$this->assertCount( 7, $result );
	}

	/**
	 * Test that collect() accepts event_type and event_data parameters.
	 */
	public function test_collect_accepts_event_type_and_event_data_parameters(): void {
		$event_type = 'checkout_started';
		$event_data = array(
			'page'   => 'checkout',
			'source' => 'test',
		);

		$event = $this->collect_and_get_event( $event_type, $event_data );

		$this->assertEquals( $event_type, $event['event_type'] );
		$this->assertEquals( $event_data, $event['event_data'] );
	}

	/**
	 * @testdox collect() degrades gracefully when session is unavailable.
	 */
	public function test_graceful_degradation_when_session_unavailable(): void {
		// This test verifies that collect() doesn't throw exceptions even if session is unavailable.
		// We can't easily simulate session being unavailable in unit tests without mocking,
		// but we can verify that calling collect() stores valid event structure.
		$event = $this->collect_and_get_event();

		$this->assertIsArray( $event );
		$this->assertCount( 3, $event );
	}

	/**
	 * Test wc_version field is included in get_collected_data response.
	 */
	public function test_wc_version_is_included(): void {
		$this->sut->collect();
		$result = $this->sut->get_collected_data();

		$this->assertEquals( WC()->version, $result['wc_version'] );
	}

	/**
	 * Test timestamp format is UTC (gmdate format).
	 */
	public function test_timestamp_format_is_utc(): void {
		$event = $this->collect_and_get_event();

		$this->assertArrayHasKey( 'timestamp', $event );
		$this->assertNotEmpty( $event['timestamp'] );

		// Verify timestamp is in Y-m-d H:i:s format.
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $event['timestamp'] );

		// Verify timestamp is recent (within last 10 seconds).
		$timestamp       = strtotime( $event['timestamp'] );
		$current_time    = time();
		$time_difference = abs( $current_time - $timestamp );
		$this->assertLessThanOrEqual( 10, $time_difference, 'Timestamp should be recent (within 10 seconds)' );
	}

	/**
	 * Test that collect() uses default values when parameters not provided.
	 */
	public function test_collect_uses_default_values_when_parameters_not_provided(): void {
		$event = $this->collect_and_get_event();

		$this->assertNull( $event['event_type'] );
		$this->assertEquals( array(), $event['event_data'] );
	}

	/**
	 * @testdox Nested sections are initialized as arrays.
	 */
	public function test_nested_sections_initialized_as_arrays(): void {
		$result = $this->collect_and_get_data();

		$this->assertIsArray( $result['session'] );
		$this->assertIsArray( $result['customer'] );
		$this->assertIsArray( $result['order'] );
		$this->assertIsArray( $result['shipping_address'] );
		$this->assertIsArray( $result['billing_address'] );
		$this->assertIsArray( $result['collected_events'] );

		$this->assertCount( 1, $result['collected_events'] );
	}

	/**
	 * Test session data includes all 6 required fields.
	 */
	public function test_session_data_includes_all_required_fields(): void {
		$this->sut->collect();
		$result = $this->sut->get_collected_data();

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
	public function test_session_id_retrieved_from_session_clearance_manager(): void {
		$this->sut->collect();
		$result = $this->sut->get_collected_data();

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
	public function test_email_collection_for_logged_in_user(): void {
		// Create a test user and log them in.
		$user_id = $this->factory->user->create(
			array(
				'user_email' => 'testuser@example.com',
			)
		);
		wp_set_current_user( $user_id );

		$this->sut->collect();
		$result = $this->sut->get_collected_data();

		$this->assertArrayHasKey( 'email', $result['session'] );
		$this->assertEquals( 'testuser@example.com', $result['session']['email'] );
	}

	/**
	 * Test email collection from WC_Customer when user not logged in.
	 */
	public function test_email_collection_from_wc_customer(): void {
		// Ensure no user is logged in.
		wp_set_current_user( 0 );

		// Set customer billing email.
		if ( isset( WC()->customer ) ) {
			WC()->customer->set_billing_email( 'customer@example.com' );
		}

		$this->sut->collect();
		$result = $this->sut->get_collected_data();

		$this->assertArrayHasKey( 'email', $result['session'] );
		// Email should be from customer object if available.
		if ( isset( WC()->customer ) ) {
			$this->assertEquals( 'customer@example.com', $result['session']['email'] );
		}
	}

	/**
	 * Test customer data includes all 4 required fields.
	 */
	public function test_customer_data_includes_all_required_fields(): void {
		$this->sut->collect();
		$result = $this->sut->get_collected_data();

		$this->assertIsArray( $result['customer'] );
		$this->assertArrayHasKey( 'first_name', $result['customer'] );
		$this->assertArrayHasKey( 'last_name', $result['customer'] );
		$this->assertArrayHasKey( 'billing_email', $result['customer'] );
		$this->assertArrayHasKey( 'lifetime_order_count', $result['customer'] );
	}

	/**
	 * Test customer name collection from WC_Customer.
	 */
	public function test_customer_name_collection_from_wc_customer(): void {
		if ( isset( WC()->customer ) ) {
			WC()->customer->set_billing_first_name( 'John' );
			WC()->customer->set_billing_last_name( 'Doe' );
		}

		$this->sut->collect();
		$result = $this->sut->get_collected_data();

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
	public function test_customer_data_fallback_to_session(): void {
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

		$this->sut->collect();
		$result = $this->sut->get_collected_data();

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
	 * Test lifetime_order_count field exists and uses WC_Customer::get_order_count().
	 */
	public function test_lifetime_order_count_for_registered_customer(): void {
		// Create a test user.
		$user_id = $this->factory->user->create(
			array(
				'user_email' => 'customer@example.com',
			)
		);
		wp_set_current_user( $user_id );

		// Initialize customer with logged-in user.
		WC()->customer = new \WC_Customer( $user_id, true );

		// Set customer billing data.
		WC()->customer->set_billing_first_name( 'John' );
		WC()->customer->set_billing_last_name( 'Doe' );
		WC()->customer->set_billing_email( 'customer@example.com' );

		$this->sut->collect();
		$result = $this->sut->get_collected_data();

		// Verify lifetime_order_count field exists and returns a valid integer.
		// In test environment, the method returns 0 because the cache is not automatically
		// populated by order lifecycle hooks. In production, WooCommerce maintains this cache.
		$this->assertArrayHasKey( 'lifetime_order_count', $result['customer'] );
		$this->assertIsInt( $result['customer']['lifetime_order_count'] );
		$this->assertGreaterThanOrEqual( 0, $result['customer']['lifetime_order_count'] );
	}

	/**
	 * Test graceful degradation when customer data unavailable.
	 */
	public function test_graceful_degradation_when_customer_data_unavailable(): void {
		// Ensure no user is logged in.
		wp_set_current_user( 0 );

		// Clear customer data.
		if ( isset( WC()->customer ) ) {
			WC()->customer->set_billing_first_name( '' );
			WC()->customer->set_billing_last_name( '' );
			WC()->customer->set_billing_email( '' );
		}

		$this->sut->collect();
		$result = $this->sut->get_collected_data();

		// Should return customer section with fields, even if empty/null.
		$this->assertIsArray( $result['customer'] );
		$this->assertArrayHasKey( 'first_name', $result['customer'] );
		$this->assertArrayHasKey( 'last_name', $result['customer'] );
		$this->assertArrayHasKey( 'billing_email', $result['customer'] );
		$this->assertArrayHasKey( 'lifetime_order_count', $result['customer'] );
	}

	/**
	 * @testdox Order data includes all required fields with proper structure when order_id is provided.
	 */
	public function test_order_data_includes_all_required_fields(): void {
		$product = \WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		$order = wc_create_order();
		$order->save();

		$this->sut->collect();
		$result = $this->sut->get_collected_data( $order->get_id() );

		$this->assertIsArray( $result['order'] );
		$this->assertArrayHasKey( 'order_id', $result['order'] );
		$this->assertArrayHasKey( 'customer_id', $result['order'] );
		$this->assertArrayHasKey( 'total', $result['order'] );
		$this->assertArrayHasKey( 'items_total', $result['order'] );
		$this->assertArrayHasKey( 'shipping_total', $result['order'] );
		$this->assertArrayHasKey( 'tax_total', $result['order'] );
		$this->assertArrayHasKey( 'shipping_tax_rate', $result['order'] );
		$this->assertArrayHasKey( 'discount_total', $result['order'] );
		$this->assertArrayHasKey( 'currency', $result['order'] );
		$this->assertArrayHasKey( 'cart_hash', $result['order'] );
		$this->assertArrayHasKey( 'items', $result['order'] );
		$this->assertIsArray( $result['order']['items'] );
	}

	/**
	 * @testdox Order totals are collected from cart when order_id is provided.
	 */
	public function test_order_totals_collected_from_cart(): void {
		WC()->cart->empty_cart();

		$product = \WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 50.00 );
		$product->save();

		WC()->cart->add_to_cart( $product->get_id(), 2 );
		WC()->cart->calculate_totals();

		$order = wc_create_order();
		$order->save();

		$this->sut->collect();
		$result = $this->sut->get_collected_data( $order->get_id() );

		$this->assertArrayHasKey( 'items_total', $result['order'] );
		$this->assertArrayHasKey( 'total', $result['order'] );
		$this->assertEquals( 100.00, $result['order']['items_total'] );
	}

	/**
	 * @testdox Shipping tax rate is calculated correctly when order_id is provided.
	 */
	public function test_shipping_tax_rate_calculation(): void {
		$product = \WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		$order = wc_create_order();
		$order->save();

		$this->sut->collect();
		$result = $this->sut->get_collected_data( $order->get_id() );

		$this->assertArrayHasKey( 'shipping_tax_rate', $result['order'] );
		if ( 0 === (float) $result['order']['shipping_total'] ) {
			$this->assertNull( $result['order']['shipping_tax_rate'] );
		}
	}

	/**
	 * @testdox Cart item data includes all 12 required fields when order_id is provided.
	 */
	public function test_cart_item_includes_all_required_fields(): void {
		WC()->cart->empty_cart();

		$product = \WC_Helper_Product::create_simple_product();
		$product->set_name( 'Test Product' );
		$product->set_description( 'Test product description' );
		$product->set_sku( 'TEST-SKU-123' );
		$product->set_regular_price( 25.00 );
		$product->save();

		WC()->cart->add_to_cart( $product->get_id(), 2 );

		$order = wc_create_order();
		$order->save();

		$this->sut->collect();
		$result = $this->sut->get_collected_data( $order->get_id() );

		$this->assertArrayHasKey( 'items', $result['order'] );
		$this->assertIsArray( $result['order']['items'] );
		$this->assertCount( 1, $result['order']['items'] );

		$item = $result['order']['items'][0];
		$this->assertArrayHasKey( 'name', $item );
		$this->assertArrayHasKey( 'description', $item );
		$this->assertArrayHasKey( 'category', $item );
		$this->assertArrayHasKey( 'sku', $item );
		$this->assertArrayHasKey( 'quantity', $item );
		$this->assertArrayHasKey( 'unit_price', $item );
		$this->assertArrayHasKey( 'unit_tax_amount', $item );
		$this->assertArrayHasKey( 'unit_discount_amount', $item );
		$this->assertArrayHasKey( 'product_type', $item );
		$this->assertArrayHasKey( 'is_virtual', $item );
		$this->assertArrayHasKey( 'is_downloadable', $item );
		$this->assertArrayHasKey( 'attributes', $item );

		$this->assertEquals( 'Test Product', $item['name'] );
		$this->assertEquals( 'Test product description', $item['description'] );
		$this->assertEquals( 'TEST-SKU-123', $item['sku'] );
		$this->assertEquals( 2, $item['quantity'] );
		$this->assertEquals( 'simple', $item['product_type'] );
	}

	/**
	 * Test billing address includes all required fields.
	 */
	public function test_billing_address_includes_all_required_fields(): void {
		// Set billing address data.
		if ( isset( WC()->customer ) ) {
			WC()->customer->set_billing_address_1( '123 Main St' );
			WC()->customer->set_billing_address_2( 'Apt 4B' );
			WC()->customer->set_billing_city( 'New York' );
			WC()->customer->set_billing_state( 'NY' );
			WC()->customer->set_billing_country( 'US' );
			WC()->customer->set_billing_postcode( '10001' );
		}

		$this->sut->collect();
		$result = $this->sut->get_collected_data();

		$this->assertIsArray( $result['billing_address'] );
		$this->assertArrayHasKey( 'address_1', $result['billing_address'] );
		$this->assertArrayHasKey( 'address_2', $result['billing_address'] );
		$this->assertArrayHasKey( 'city', $result['billing_address'] );
		$this->assertArrayHasKey( 'state', $result['billing_address'] );
		$this->assertArrayHasKey( 'country', $result['billing_address'] );
		$this->assertArrayHasKey( 'postcode', $result['billing_address'] );

		// Verify values.
		if ( isset( WC()->customer ) ) {
			$this->assertEquals( '123 Main St', $result['billing_address']['address_1'] );
			$this->assertEquals( 'Apt 4B', $result['billing_address']['address_2'] );
			$this->assertEquals( 'New York', $result['billing_address']['city'] );
			$this->assertEquals( 'NY', $result['billing_address']['state'] );
			$this->assertEquals( 'US', $result['billing_address']['country'] );
			$this->assertEquals( '10001', $result['billing_address']['postcode'] );
		}
	}

	/**
	 * Test shipping address includes all required fields.
	 */
	public function test_shipping_address_includes_all_required_fields(): void {
		// Set shipping address data.
		if ( isset( WC()->customer ) ) {
			WC()->customer->set_shipping_address_1( '456 Oak Ave' );
			WC()->customer->set_shipping_address_2( 'Suite 100' );
			WC()->customer->set_shipping_city( 'Los Angeles' );
			WC()->customer->set_shipping_state( 'CA' );
			WC()->customer->set_shipping_country( 'US' );
			WC()->customer->set_shipping_postcode( '90001' );
		}

		$this->sut->collect();
		$result = $this->sut->get_collected_data();

		$this->assertIsArray( $result['shipping_address'] );
		$this->assertArrayHasKey( 'address_1', $result['shipping_address'] );
		$this->assertArrayHasKey( 'address_2', $result['shipping_address'] );
		$this->assertArrayHasKey( 'city', $result['shipping_address'] );
		$this->assertArrayHasKey( 'state', $result['shipping_address'] );
		$this->assertArrayHasKey( 'country', $result['shipping_address'] );
		$this->assertArrayHasKey( 'postcode', $result['shipping_address'] );

		// Verify values.
		if ( isset( WC()->customer ) ) {
			$this->assertEquals( '456 Oak Ave', $result['shipping_address']['address_1'] );
			$this->assertEquals( 'Suite 100', $result['shipping_address']['address_2'] );
			$this->assertEquals( 'Los Angeles', $result['shipping_address']['city'] );
			$this->assertEquals( 'CA', $result['shipping_address']['state'] );
			$this->assertEquals( 'US', $result['shipping_address']['country'] );
			$this->assertEquals( '90001', $result['shipping_address']['postcode'] );
		}
	}

	/**
	 * @testdox Order data degrades gracefully when cart is empty and order_id is provided.
	 */
	public function test_graceful_degradation_when_cart_is_empty(): void {
		WC()->cart->empty_cart();

		$order = wc_create_order();
		$order->save();

		$this->sut->collect();
		$result = $this->sut->get_collected_data( $order->get_id() );

		$this->assertIsArray( $result['order'] );
		$this->assertArrayHasKey( 'items', $result['order'] );
		$this->assertIsArray( $result['order']['items'] );
		$this->assertEmpty( $result['order']['items'] );

		$this->assertEquals( 0, $result['order']['items_total'] );
		$this->assertEquals( 0, $result['order']['total'] );
	}

	/**
	 * @testdox customer_id is set to 'guest' for guest users when order_id is provided.
	 */
	public function test_customer_id_for_guest_users(): void {
		wp_set_current_user( 0 );

		WC()->customer = new \WC_Customer( 0, true );

		$product = \WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		$order = wc_create_order();
		$order->save();

		$this->sut->collect();
		$result = $this->sut->get_collected_data( $order->get_id() );

		$this->assertArrayHasKey( 'customer_id', $result['order'] );
		$this->assertEquals( 'guest', $result['order']['customer_id'] );
	}

	/**
	 * @testdox customer_id is set to user ID for logged-in users when order_id is provided.
	 */
	public function test_customer_id_for_logged_in_users(): void {
		$user_id = $this->factory->user->create(
			array(
				'user_email' => 'logged-in-user@example.com',
			)
		);
		wp_set_current_user( $user_id );

		WC()->customer = new \WC_Customer( $user_id, true );

		$product = \WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		$order = wc_create_order();
		$order->save();

		$this->sut->collect();
		$result = $this->sut->get_collected_data( $order->get_id() );

		$this->assertArrayHasKey( 'customer_id', $result['order'] );
		$this->assertEquals( $user_id, $result['order']['customer_id'] );
	}

	/**
	 * @testdox get_collected_data() output includes all 7 top-level sections with data.
	 */
	public function test_complete_collect_output_includes_all_sections(): void {
		$user_id = $this->factory->user->create(
			array(
				'user_email' => 'complete-test@example.com',
			)
		);
		wp_set_current_user( $user_id );

		if ( isset( WC()->customer ) ) {
			WC()->customer->set_billing_first_name( 'Test' );
			WC()->customer->set_billing_last_name( 'User' );
			WC()->customer->set_billing_email( 'complete-test@example.com' );
			WC()->customer->set_billing_address_1( '123 Test St' );
			WC()->customer->set_billing_city( 'Test City' );
			WC()->customer->set_billing_country( 'US' );
		}

		$product = \WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		$order = wc_create_order();
		$order->save();

		$this->sut->collect( 'checkout_started', array( 'test' => 'data' ) );
		$result = $this->sut->get_collected_data( $order->get_id() );

		$this->assertArrayHasKey( 'wc_version', $result );
		$this->assertArrayHasKey( 'session', $result );
		$this->assertArrayHasKey( 'customer', $result );
		$this->assertArrayHasKey( 'order', $result );
		$this->assertArrayHasKey( 'shipping_address', $result );
		$this->assertArrayHasKey( 'billing_address', $result );
		$this->assertArrayHasKey( 'collected_events', $result );

		$this->assertIsString( $result['wc_version'] );
		$this->assertIsArray( $result['session'] );
		$this->assertIsArray( $result['customer'] );
		$this->assertIsArray( $result['order'] );
		$this->assertIsArray( $result['shipping_address'] );
		$this->assertIsArray( $result['billing_address'] );
		$this->assertIsArray( $result['collected_events'] );

		$this->assertCount( 1, $result['collected_events'] );
		$event = $result['collected_events'][0];
		$this->assertEquals( 'checkout_started', $event['event_type'] );
		$this->assertIsString( $event['timestamp'] );
		$this->assertEquals( array( 'test' => 'data' ), $event['event_data'] );
	}

	/**
	 * @testdox End-to-end data collection with full cart scenario works correctly.
	 */
	public function test_end_to_end_data_collection_with_full_cart(): void {
		WC()->cart->empty_cart();

		$user_id = $this->factory->user->create(
			array(
				'user_email' => 'e2e-test@example.com',
			)
		);
		wp_set_current_user( $user_id );

		$existing_order = wc_create_order();
		$existing_order->set_customer_id( $user_id );
		$existing_order->set_status( 'completed' );
		$existing_order->save();

		if ( isset( WC()->customer ) ) {
			WC()->customer = new \WC_Customer( $user_id, true );
			WC()->customer->set_billing_first_name( 'John' );
			WC()->customer->set_billing_last_name( 'Doe' );
			WC()->customer->set_billing_email( 'e2e-test@example.com' );
			WC()->customer->set_billing_address_1( '123 Test St' );
			WC()->customer->set_billing_address_2( 'Apt 1' );
			WC()->customer->set_billing_city( 'Test City' );
			WC()->customer->set_billing_state( 'CA' );
			WC()->customer->set_billing_country( 'US' );
			WC()->customer->set_billing_postcode( '90210' );
			WC()->customer->set_shipping_address_1( '456 Ship St' );
			WC()->customer->set_shipping_city( 'Ship City' );
			WC()->customer->set_shipping_state( 'NY' );
			WC()->customer->set_shipping_country( 'US' );
			WC()->customer->set_shipping_postcode( '10001' );
		}

		$product1 = \WC_Helper_Product::create_simple_product();
		$product1->set_name( 'Product 1' );
		$product1->set_regular_price( 100.00 );
		$product1->save();

		$product2 = \WC_Helper_Product::create_simple_product();
		$product2->set_name( 'Product 2' );
		$product2->set_regular_price( 50.00 );
		$product2->save();

		WC()->cart->add_to_cart( $product1->get_id(), 2 );
		WC()->cart->add_to_cart( $product2->get_id(), 1 );
		WC()->cart->calculate_totals();

		$new_order = wc_create_order();
		$new_order->save();

		$this->sut->collect( 'payment_attempt', array( 'gateway' => 'stripe' ) );
		$result = $this->sut->get_collected_data( $new_order->get_id() );

		$this->assertArrayHasKey( 'wc_version', $result );
		$this->assertArrayHasKey( 'collected_events', $result );
		$this->assertCount( 1, $result['collected_events'] );

		$event = $result['collected_events'][0];

		$this->assertEquals( 'payment_attempt', $event['event_type'] );
		$this->assertNotEmpty( $event['timestamp'] );

		$this->assertNotEmpty( $result['session']['session_id'] );
		$this->assertEquals( 'e2e-test@example.com', $result['session']['email'] );

		$this->assertEquals( 'John', $result['customer']['first_name'] );
		$this->assertEquals( 'Doe', $result['customer']['last_name'] );
		$this->assertIsInt( $result['customer']['lifetime_order_count'] );
		$this->assertGreaterThanOrEqual( 0, $result['customer']['lifetime_order_count'] );

		$this->assertGreaterThan( 0, $result['order']['total'] );
		$this->assertCount( 2, $result['order']['items'] );

		$this->assertEquals( '123 Test St', $result['billing_address']['address_1'] );
		$this->assertEquals( 'Test City', $result['billing_address']['city'] );

		$this->assertEquals( '456 Ship St', $result['shipping_address']['address_1'] );
		$this->assertEquals( 'Ship City', $result['shipping_address']['city'] );

		$this->assertEquals( array( 'gateway' => 'stripe' ), $event['event_data'] );
	}

	/**
	 * @testdox Graceful degradation across all sections when data is minimal.
	 */
	public function test_graceful_degradation_across_all_sections(): void {
		wp_set_current_user( 0 );

		WC()->customer = new \WC_Customer( 0, true );

		WC()->cart->empty_cart();

		if ( isset( WC()->customer ) ) {
			WC()->customer->set_billing_first_name( '' );
			WC()->customer->set_billing_last_name( '' );
			WC()->customer->set_billing_email( '' );
		}

		$order = wc_create_order();
		$order->save();

		$this->sut->collect();
		$result = $this->sut->get_collected_data( $order->get_id() );

		$this->assertIsArray( $result );
		$this->assertCount( 7, $result );

		$this->assertIsArray( $result['session'] );
		$this->assertIsArray( $result['customer'] );
		$this->assertIsArray( $result['order'] );
		$this->assertIsArray( $result['shipping_address'] );
		$this->assertIsArray( $result['billing_address'] );
		$this->assertIsArray( $result['collected_events'] );

		$this->assertCount( 1, $result['collected_events'] );

		$this->assertEquals( 'guest', $result['order']['customer_id'] );
		$this->assertEquals( 0, $result['customer']['lifetime_order_count'] );
		$this->assertEmpty( $result['order']['items'] );
	}

	/**
	 * @testdox Data collection requires manual triggering (no automatic hooks).
	 */
	public function test_manual_triggering_only(): void {
		// This test verifies that SessionDataCollector doesn't automatically
		// hook into WooCommerce events. It should only collect data when
		// collect() is explicitly called.

		$product = \WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		$order = wc_create_order();
		$order->save();

		$this->sut->collect();
		$result = $this->sut->get_collected_data( $order->get_id() );

		$this->assertIsArray( $result );
		$this->assertCount( 1, $result['order']['items'] );

		// No automatic data collection should have occurred.
		// This is a design verification test - the class should not register hooks.
	}

	/**
	 * Test collect stores event data in session.
	 *
	 * @testdox collect() stores event data in WooCommerce session under 'fraud_protection_collected_data' key.
	 */
	public function test_collect_stores_event_data_in_session(): void {
		// Collect data with a specific event type.
		$this->sut->collect( 'cart_page_loaded', array( 'source' => 'test' ) );

		// Verify data was stored in session.
		$stored_data = WC()->session->get( 'fraud_protection_collected_data' );

		$this->assertIsArray( $stored_data );
		$this->assertCount( 1, $stored_data );
		$this->assertEquals( 'cart_page_loaded', $stored_data[0]['event_type'] );
		$this->assertEquals( array( 'source' => 'test' ), $stored_data[0]['event_data'] );
	}

	/**
	 * Test multiple collect calls append data to session.
	 *
	 * @testdox Multiple collect() calls append data to session array, preserving event history.
	 */
	public function test_multiple_collect_calls_append_data_to_session(): void {
		// First collect call.
		$this->sut->collect( 'cart_page_loaded', array() );

		// Second collect call.
		$this->sut->collect( 'checkout_page_loaded', array() );

		// Third collect call.
		$this->sut->collect( 'order_placed', array( 'order_id' => 123 ) );

		// Verify all three events are stored.
		$stored_data = WC()->session->get( 'fraud_protection_collected_data' );

		$this->assertIsArray( $stored_data );
		$this->assertCount( 3, $stored_data );
		$this->assertEquals( 'cart_page_loaded', $stored_data[0]['event_type'] );
		$this->assertEquals( 'checkout_page_loaded', $stored_data[1]['event_type'] );
		$this->assertEquals( 'order_placed', $stored_data[2]['event_type'] );
		$this->assertEquals( 123, $stored_data[2]['event_data']['order_id'] );
	}

	/**
	 * Test get_collected_data returns structure with empty collected_events when no data collected.
	 *
	 * @testdox get_collected_data() returns structure with empty collected_events when no data has been collected.
	 */
	public function test_get_collected_data_returns_empty_collected_events_when_no_data_collected(): void {
		$result = $this->sut->get_collected_data();

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'collected_events', $result );
		$this->assertEmpty( $result['collected_events'] );
	}

	/**
	 * Test get_collected_data returns structure with empty collected_events when session unavailable.
	 *
	 * @testdox get_collected_data() returns structure with empty collected_events when session is unavailable.
	 */
	public function test_get_collected_data_returns_empty_collected_events_when_session_unavailable(): void {
		// Store original session.
		$original_session = WC()->session;

		// Set session to null to simulate unavailability.
		WC()->session = null;

		$result = $this->sut->get_collected_data();

		// Restore original session.
		WC()->session = $original_session;

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'collected_events', $result );
		$this->assertEmpty( $result['collected_events'] );
	}

	/**
	 * @testdox get_collected_data() returns empty order array when no order_id is provided.
	 */
	public function test_get_collected_data_returns_empty_order_when_no_order_id(): void {
		$product = \WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		$this->sut->collect();
		$result = $this->sut->get_collected_data();

		$this->assertArrayHasKey( 'order', $result );
		$this->assertIsArray( $result['order'] );
		$this->assertEmpty( $result['order'] );
	}

	/**
	 * @testdox get_collected_data() returns collected_events array after collect() is called.
	 */
	public function test_get_collected_data_returns_data_after_collect(): void {
		// Collect some data.
		$this->sut->collect( 'cart_page_loaded', array( 'source' => 'test' ) );
		$this->sut->collect( 'checkout_started', array( 'gateway' => 'stripe' ) );

		// Get collected data using the new method.
		$result = $this->sut->get_collected_data();

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'collected_events', $result );
		$this->assertCount( 2, $result['collected_events'] );
		$this->assertEquals( 'cart_page_loaded', $result['collected_events'][0]['event_type'] );
		$this->assertEquals( array( 'source' => 'test' ), $result['collected_events'][0]['event_data'] );
		$this->assertEquals( 'checkout_started', $result['collected_events'][1]['event_type'] );
		$this->assertEquals( array( 'gateway' => 'stripe' ), $result['collected_events'][1]['event_data'] );
	}
}
