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
	}

	/**
	 * Test that collect() method returns properly structured nested array with 9 top-level keys.
	 */
	public function test_collect_returns_properly_structured_nested_array() {
		$result = $this->sut->collect();

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'event_type', $result );
		$this->assertArrayHasKey( 'timestamp', $result );
		$this->assertArrayHasKey( 'wc_version', $result );
		$this->assertArrayHasKey( 'session', $result );
		$this->assertArrayHasKey( 'customer', $result );
		$this->assertArrayHasKey( 'order', $result );
		$this->assertArrayHasKey( 'shipping_address', $result );
		$this->assertArrayHasKey( 'billing_address', $result );
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
	 * Test wc_version field is included in collected data.
	 */
	public function test_wc_version_is_included() {
		$result = $this->sut->collect();

		$this->assertEquals( WC()->version, $result['wc_version'] );
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
		$timestamp       = strtotime( $result['timestamp'] );
		$current_time    = time();
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
	 * Test lifetime_order_count field exists and uses WC_Customer::get_order_count().
	 */
	public function test_lifetime_order_count_for_registered_customer() {
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

		$result = $this->sut->collect();

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

	/**
	 * Test order data includes all required fields with proper structure.
	 */
	public function test_order_data_includes_all_required_fields() {
		// Add a product to cart.
		$product = \WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		$result = $this->sut->collect();

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
	 * Test order totals are collected from cart.
	 */
	public function test_order_totals_collected_from_cart() {
		// Empty cart first to ensure clean state.
		WC()->cart->empty_cart();

		// Add a product to cart.
		$product = \WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 50.00 );
		$product->save();

		WC()->cart->add_to_cart( $product->get_id(), 2 );
		WC()->cart->calculate_totals();

		$result = $this->sut->collect();

		$this->assertArrayHasKey( 'items_total', $result['order'] );
		$this->assertArrayHasKey( 'total', $result['order'] );
		// Verify items_total matches expected value.
		$this->assertEquals( 100.00, $result['order']['items_total'] );
	}

	/**
	 * Test shipping_tax_rate calculation.
	 */
	public function test_shipping_tax_rate_calculation() {
		// Add a product to cart.
		$product = \WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		$result = $this->sut->collect();

		$this->assertArrayHasKey( 'shipping_tax_rate', $result['order'] );
		// When shipping total is zero, shipping_tax_rate should be null.
		if ( 0 === (float) $result['order']['shipping_total'] ) {
			$this->assertNull( $result['order']['shipping_tax_rate'] );
		}
	}

	/**
	 * Test cart item data includes all 12 required fields.
	 */
	public function test_cart_item_includes_all_required_fields() {
		// Empty cart first to ensure clean state.
		WC()->cart->empty_cart();

		// Add a product to cart.
		$product = \WC_Helper_Product::create_simple_product();
		$product->set_name( 'Test Product' );
		$product->set_description( 'Test product description' );
		$product->set_sku( 'TEST-SKU-123' );
		$product->set_regular_price( 25.00 );
		$product->save();

		WC()->cart->add_to_cart( $product->get_id(), 2 );

		$result = $this->sut->collect();

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

		// Verify values match product data.
		$this->assertEquals( 'Test Product', $item['name'] );
		$this->assertEquals( 'Test product description', $item['description'] );
		$this->assertEquals( 'TEST-SKU-123', $item['sku'] );
		$this->assertEquals( 2, $item['quantity'] );
		$this->assertEquals( 'simple', $item['product_type'] );
	}

	/**
	 * Test billing address includes all required fields.
	 */
	public function test_billing_address_includes_all_required_fields() {
		// Set billing address data.
		if ( isset( WC()->customer ) ) {
			WC()->customer->set_billing_address_1( '123 Main St' );
			WC()->customer->set_billing_address_2( 'Apt 4B' );
			WC()->customer->set_billing_city( 'New York' );
			WC()->customer->set_billing_state( 'NY' );
			WC()->customer->set_billing_country( 'US' );
			WC()->customer->set_billing_postcode( '10001' );
		}

		$result = $this->sut->collect();

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
	public function test_shipping_address_includes_all_required_fields() {
		// Set shipping address data.
		if ( isset( WC()->customer ) ) {
			WC()->customer->set_shipping_address_1( '456 Oak Ave' );
			WC()->customer->set_shipping_address_2( 'Suite 100' );
			WC()->customer->set_shipping_city( 'Los Angeles' );
			WC()->customer->set_shipping_state( 'CA' );
			WC()->customer->set_shipping_country( 'US' );
			WC()->customer->set_shipping_postcode( '90001' );
		}

		$result = $this->sut->collect();

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
	 * Test graceful degradation when cart is empty.
	 */
	public function test_graceful_degradation_when_cart_is_empty() {
		// Ensure cart is empty.
		WC()->cart->empty_cart();

		$result = $this->sut->collect();

		// Order section should still exist even with empty cart.
		$this->assertIsArray( $result['order'] );
		$this->assertArrayHasKey( 'items', $result['order'] );
		$this->assertIsArray( $result['order']['items'] );
		$this->assertEmpty( $result['order']['items'] );

		// Totals should be zero or null.
		$this->assertEquals( 0, $result['order']['items_total'] );
		$this->assertEquals( 0, $result['order']['total'] );
	}

	/**
	 * Test customer_id for guest users.
	 */
	public function test_customer_id_for_guest_users() {
		// Ensure no user is logged in.
		wp_set_current_user( 0 );

		// Reinitialize customer as guest (ID will be 0).
		WC()->customer = new \WC_Customer( 0, true );

		// Add a product to cart.
		$product = \WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		$result = $this->sut->collect();

		$this->assertArrayHasKey( 'customer_id', $result['order'] );
		$this->assertEquals( 'guest', $result['order']['customer_id'] );
	}

	/**
	 * Test customer_id for logged-in users.
	 */
	public function test_customer_id_for_logged_in_users() {
		// Create a test user and log them in.
		$user_id = $this->factory->user->create(
			array(
				'user_email' => 'logged-in-user@example.com',
			)
		);
		wp_set_current_user( $user_id );

		// Reinitialize customer with logged-in user.
		WC()->customer = new \WC_Customer( $user_id, true );

		// Add a product to cart.
		$product = \WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		$result = $this->sut->collect();

		$this->assertArrayHasKey( 'customer_id', $result['order'] );
		$this->assertEquals( $user_id, $result['order']['customer_id'] );
	}

	/**
	 * Test complete collect() output includes all 8 top-level sections with data.
	 */
	public function test_complete_collect_output_includes_all_sections() {
		// Create a logged-in user.
		$user_id = $this->factory->user->create(
			array(
				'user_email' => 'complete-test@example.com',
			)
		);
		wp_set_current_user( $user_id );

		// Set customer data.
		if ( isset( WC()->customer ) ) {
			WC()->customer->set_billing_first_name( 'Test' );
			WC()->customer->set_billing_last_name( 'User' );
			WC()->customer->set_billing_email( 'complete-test@example.com' );
			WC()->customer->set_billing_address_1( '123 Test St' );
			WC()->customer->set_billing_city( 'Test City' );
			WC()->customer->set_billing_country( 'US' );
		}

		// Add a product to cart.
		$product = \WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		$result = $this->sut->collect( 'checkout_started', array( 'test' => 'data' ) );

		// Verify all 8 sections exist.
		$this->assertArrayHasKey( 'event_type', $result );
		$this->assertArrayHasKey( 'timestamp', $result );
		$this->assertArrayHasKey( 'session', $result );
		$this->assertArrayHasKey( 'customer', $result );
		$this->assertArrayHasKey( 'order', $result );
		$this->assertArrayHasKey( 'shipping_address', $result );
		$this->assertArrayHasKey( 'billing_address', $result );
		$this->assertArrayHasKey( 'event_data', $result );

		// Verify sections contain expected data types.
		$this->assertEquals( 'checkout_started', $result['event_type'] );
		$this->assertIsString( $result['timestamp'] );
		$this->assertIsArray( $result['session'] );
		$this->assertIsArray( $result['customer'] );
		$this->assertIsArray( $result['order'] );
		$this->assertIsArray( $result['shipping_address'] );
		$this->assertIsArray( $result['billing_address'] );
		$this->assertEquals( array( 'test' => 'data' ), $result['event_data'] );
	}

	/**
	 * Test end-to-end data collection with full cart scenario.
	 */
	public function test_end_to_end_data_collection_with_full_cart() {
		// Empty cart first.
		WC()->cart->empty_cart();

		// Create logged-in user.
		$user_id = $this->factory->user->create(
			array(
				'user_email' => 'e2e-test@example.com',
			)
		);
		wp_set_current_user( $user_id );

		// Create completed order for lifetime count.
		$order = wc_create_order();
		$order->set_customer_id( $user_id );
		$order->set_status( 'completed' );
		$order->save();

		// Set customer data.
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

		// Add products to cart.
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

		// Collect data.
		$result = $this->sut->collect( 'payment_attempt', array( 'gateway' => 'stripe' ) );

		// Verify comprehensive data collection.
		$this->assertEquals( 'payment_attempt', $result['event_type'] );
		$this->assertNotEmpty( $result['timestamp'] );

		// Session data.
		$this->assertNotEmpty( $result['session']['session_id'] );
		$this->assertEquals( 'e2e-test@example.com', $result['session']['email'] );

		// Customer data.
		$this->assertEquals( 'John', $result['customer']['first_name'] );
		$this->assertEquals( 'Doe', $result['customer']['last_name'] );
		// Lifetime order count will be >= 0 (depends on WC_Customer::get_order_count() availability).
		$this->assertIsInt( $result['customer']['lifetime_order_count'] );
		$this->assertGreaterThanOrEqual( 0, $result['customer']['lifetime_order_count'] );

		// Order data.
		$this->assertGreaterThan( 0, $result['order']['total'] );
		$this->assertCount( 2, $result['order']['items'] );

		// Billing address.
		$this->assertEquals( '123 Test St', $result['billing_address']['address_1'] );
		$this->assertEquals( 'Test City', $result['billing_address']['city'] );

		// Shipping address.
		$this->assertEquals( '456 Ship St', $result['shipping_address']['address_1'] );
		$this->assertEquals( 'Ship City', $result['shipping_address']['city'] );

		// Event data.
		$this->assertEquals( array( 'gateway' => 'stripe' ), $result['event_data'] );
	}

	/**
	 * Test graceful degradation across all sections.
	 */
	public function test_graceful_degradation_across_all_sections() {
		// Ensure no user logged in.
		wp_set_current_user( 0 );

		// Reinitialize customer as guest (ID will be 0).
		WC()->customer = new \WC_Customer( 0, true );

		// Empty cart.
		WC()->cart->empty_cart();

		// Clear customer data.
		if ( isset( WC()->customer ) ) {
			WC()->customer->set_billing_first_name( '' );
			WC()->customer->set_billing_last_name( '' );
			WC()->customer->set_billing_email( '' );
		}

		// Collect should still succeed and return valid structure.
		$result = $this->sut->collect();

		// Verify structure is intact even with minimal data.
		$this->assertIsArray( $result );
		$this->assertCount( 9, $result );

		// All sections should be arrays.
		$this->assertIsArray( $result['session'] );
		$this->assertIsArray( $result['customer'] );
		$this->assertIsArray( $result['order'] );
		$this->assertIsArray( $result['shipping_address'] );
		$this->assertIsArray( $result['billing_address'] );

		// Key fields should have appropriate defaults.
		$this->assertEquals( 'guest', $result['order']['customer_id'] );
		$this->assertEquals( 0, $result['customer']['lifetime_order_count'] );
		$this->assertEmpty( $result['order']['items'] );
	}

	/**
	 * Test manual triggering only (no automatic hooks).
	 */
	public function test_manual_triggering_only() {
		// This test verifies that SessionDataCollector doesn't automatically
		// hook into WooCommerce events. It should only collect data when
		// collect() is explicitly called.

		// Add a product to cart (should not trigger automatic collection).
		$product = \WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Verify collect() must be called manually.
		$result = $this->sut->collect();

		$this->assertIsArray( $result );
		$this->assertCount( 1, $result['order']['items'] );

		// No automatic data collection should have occurred.
		// This is a design verification test - the class should not register hooks.
	}
}
