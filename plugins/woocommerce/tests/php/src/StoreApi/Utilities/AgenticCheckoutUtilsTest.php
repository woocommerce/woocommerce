<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\StoreApi\Utilities;

use Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\AgenticCheckoutSession;
use Automattic\WooCommerce\StoreApi\Utilities\AgenticCheckoutUtils;
use Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Enums\SessionKey;
use Automattic\WooCommerce\Internal\Agentic\Enums\Specs\CheckoutSessionStatus;

/**
 * Tests for AgenticCheckoutUtils class.
 */
class AgenticCheckoutUtilsTest extends \WC_Unit_Test_Case {
	/**
	 * Setup cart and session data.
	 */
	public function setUp(): void {
		parent::setUp();

		// Reset cart FIRST before anything else.
		wc_empty_cart();

		// Clear all session data early to ensure clean state.
		if ( WC()->session ) {
			WC()->session->destroy_session();
		}
	}

	/**
	 * Tear down test.
	 */
	public function tearDown(): void {
		parent::tearDown();

		// Clear session data.
		WC()->session->set( SessionKey::AGENTIC_CHECKOUT_PAYMENT_IN_PROGRESS, null );
	}

	/**
	 * Test that calculate_status returns IN_PROGRESS when payment is in progress.
	 */
	public function test_calculate_status_returns_in_progress_when_payment_in_progress() {
		$checkout_session = new AgenticCheckoutSession( WC()->cart );
		$cart             = $checkout_session->get_cart();

		// Add a product to the cart.
		$product = \WC_Helper_Product::create_simple_product();
		$cart->add_to_cart( $product->get_id(), 1 );

		// Set the payment in progress flag.
		WC()->session->set( SessionKey::AGENTIC_CHECKOUT_PAYMENT_IN_PROGRESS, true );

		// Calculate status.
		$status = AgenticCheckoutUtils::calculate_status( $checkout_session );

		// Assert that status is IN_PROGRESS.
		$this->assertEquals( CheckoutSessionStatus::IN_PROGRESS, $status );
	}

	/**
	 * Test that calculate_status returns COMPLETED when order is completed.
	 */
	public function test_calculate_status_returns_completed_when_order_completed() {
		$checkout_session = new AgenticCheckoutSession( WC()->cart );
		$cart             = $checkout_session->get_cart();

		// Set completed order ID.
		WC()->session->set( SessionKey::AGENTIC_CHECKOUT_COMPLETED_ORDER_ID, 123 );

		// Calculate status.
		$status = AgenticCheckoutUtils::calculate_status( $checkout_session );

		// Assert that status is COMPLETED (takes precedence over IN_PROGRESS).
		$this->assertEquals( CheckoutSessionStatus::COMPLETED, $status );
	}

	/**
	 * Test that IN_PROGRESS status has correct priority even though cart is otherwise ready.
	 */
	public function test_in_progress_status_priority() {
		$checkout_session = new AgenticCheckoutSession( WC()->cart );
		$cart             = $checkout_session->get_cart();

		// Add a product to the cart.
		$product = \WC_Helper_Product::create_simple_product();
		$cart->add_to_cart( $product->get_id(), 1 );

		// Set up shipping.
		WC()->customer->set_shipping_country( 'US' );
		WC()->customer->set_shipping_state( 'CA' );
		WC()->customer->set_shipping_postcode( '90210' );
		WC()->customer->set_shipping_city( 'Los Angeles' );
		WC()->customer->set_shipping_address_1( '123 Main St' );

		// Set payment in progress flag.
		WC()->session->set( SessionKey::AGENTIC_CHECKOUT_PAYMENT_IN_PROGRESS, true );

		// Calculate status - should be IN_PROGRESS even though cart is otherwise ready.
		$status = AgenticCheckoutUtils::calculate_status( $checkout_session );

		// Assert that status is IN_PROGRESS.
		$this->assertEquals( CheckoutSessionStatus::IN_PROGRESS, $status );
	}

	/**
	 * Test that calculate_status returns READY_FOR_PAYMENT after IN_PROGRESS flag is cleared.
	 */
	public function test_calculate_status_ready_after_in_progress_cleared() {
		// Set up cart and session.
		$checkout_session = new AgenticCheckoutSession( WC()->cart );
		$cart             = $checkout_session->get_cart();
		$cart->empty_cart();

		// Add a product to the cart.
		$product = \WC_Helper_Product::create_simple_product();
		$cart->add_to_cart( $product->get_id(), 1 );

		WC()->customer->set_shipping_country( 'US' );
		WC()->customer->set_shipping_state( 'CA' );
		WC()->customer->set_shipping_postcode( '90210' );
		WC()->customer->set_shipping_city( 'Los Angeles' );
		WC()->customer->set_shipping_address_1( '123 Main St' );
		WC()->customer->save();

		// Set chosen shipping method.
		WC()->session->set( SessionKey::CHOSEN_SHIPPING_METHODS, array( 'flat_rate' ) );

		// First, set IN_PROGRESS flag.
		WC()->session->set( SessionKey::AGENTIC_CHECKOUT_PAYMENT_IN_PROGRESS, true );
		$status = AgenticCheckoutUtils::calculate_status( $checkout_session );
		$this->assertEquals( CheckoutSessionStatus::IN_PROGRESS, $status );

		// Clear the IN_PROGRESS flag.
		WC()->session->set( SessionKey::AGENTIC_CHECKOUT_PAYMENT_IN_PROGRESS, false );

		// Recalculate status - should be READY_FOR_PAYMENT now.
		$status = AgenticCheckoutUtils::calculate_status( $checkout_session );

		// Assert that status is READY_FOR_PAYMENT after clearing IN_PROGRESS.
		$this->assertNotEquals( CheckoutSessionStatus::IN_PROGRESS, $status );
	}

	/**
	 * Test authorization with valid OpenAI token.
	 */
	public function test_is_authorized_with_valid_token() {
		// Enable the feature.
		update_option( 'woocommerce_feature_agentic_checkout_enabled', 'yes' );

		// Set up registry with OpenAI token (hashed).
		$test_token = 'test_bearer_token_12345';
		update_option(
			'woocommerce_agentic_agent_registry',
			array(
				'openai' => array(
					'bearer_token' => wp_hash_password( $test_token ),
				),
			),
			false
		);

		// Create mock request with Authorization header (plaintext).
		$request = new \WP_REST_Request();
		$request->set_header( 'Authorization', 'Bearer ' . $test_token );

		// Test authorization.
		$result = AgenticCheckoutUtils::is_authorized( $request );

		// Assert authorization succeeds.
		$this->assertTrue( $result );

		// Assert provider ID is stored in session.
		$this->assertEquals( 'openai', WC()->session->get( SessionKey::AGENTIC_CHECKOUT_PROVIDER_ID ) );
	}

	/**
	 * Test authorization with invalid token.
	 */
	public function test_is_authorized_with_invalid_token() {
		// Enable the feature.
		update_option( 'woocommerce_feature_agentic_checkout_enabled', 'yes' );

		// Set up registry with OpenAI token (hashed).
		update_option(
			'woocommerce_agentic_agent_registry',
			array(
				'openai' => array(
					'bearer_token' => wp_hash_password( 'correct_token' ),
				),
			),
			false
		);

		// Create mock request with wrong token.
		$request = new \WP_REST_Request();
		$request->set_header( 'Authorization', 'Bearer wrong_token' );

		// Test authorization.
		$result = AgenticCheckoutUtils::is_authorized( $request );

		// Assert authorization fails with ACP error format.
		$this->assertWPError( $result );
		$this->assertEquals( 'invalid_request', $result->get_error_code() );
		$this->assertEquals( 400, $result->get_error_data()['status'] );
		$this->assertEquals( 'invalid_request', $result->get_error_data()['type'] );
		$this->assertEquals( 'authentication_failed', $result->get_error_data()['code'] );
	}

	/**
	 * Test authorization with missing Authorization header.
	 */
	public function test_is_authorized_with_missing_header() {
		// Enable the feature.
		update_option( 'woocommerce_feature_agentic_checkout_enabled', 'yes' );

		// Create mock request without Authorization header.
		$request = new \WP_REST_Request();

		// Test authorization.
		$result = AgenticCheckoutUtils::is_authorized( $request );

		// Assert authorization fails with ACP error format.
		$this->assertWPError( $result );
		$this->assertEquals( 'invalid_request', $result->get_error_code() );
		$this->assertEquals( 400, $result->get_error_data()['status'] );
		$this->assertEquals( 'invalid_request', $result->get_error_data()['type'] );
		$this->assertEquals( 'invalid_authorization_format', $result->get_error_data()['code'] );
	}

	/**
	 * Test authorization with malformed Bearer token format.
	 */
	public function test_is_authorized_with_malformed_header() {
		// Enable the feature.
		update_option( 'woocommerce_feature_agentic_checkout_enabled', 'yes' );

		// Test various malformed formats.
		$malformed_headers = array(
			'token_without_bearer',
			'Basic token123',
			'Bearertoken123', // Missing space.
			'Bearer',         // No token.
		);

		foreach ( $malformed_headers as $header ) {
			$request = new \WP_REST_Request();
			$request->set_header( 'Authorization', $header );

			$result = AgenticCheckoutUtils::is_authorized( $request );

			$this->assertWPError( $result, "Failed for header: $header" );
			$this->assertEquals( 'invalid_request', $result->get_error_code() );
			$this->assertEquals( 400, $result->get_error_data()['status'] );
			$this->assertEquals( 'invalid_request', $result->get_error_data()['type'] );
			$this->assertEquals( 'invalid_authorization_format', $result->get_error_data()['code'] );
		}
	}

	/**
	 * Test authorization with empty provider tokens.
	 */
	public function test_is_authorized_with_empty_provider_tokens() {
		// Enable the feature.
		update_option( 'woocommerce_feature_agentic_checkout_enabled', 'yes' );

		// Set up registry with empty token.
		update_option(
			'woocommerce_agentic_agent_registry',
			array(
				'openai' => array(
					'bearer_token' => '',
				),
			),
			false
		);

		// Create mock request with token.
		$request = new \WP_REST_Request();
		$request->set_header( 'Authorization', 'Bearer some_token' );

		// Test authorization.
		$result = AgenticCheckoutUtils::is_authorized( $request );

		// Assert authorization fails with ACP error format (empty tokens are skipped).
		$this->assertWPError( $result );
		$this->assertEquals( 'invalid_request', $result->get_error_code() );
		$this->assertEquals( 400, $result->get_error_data()['status'] );
		$this->assertEquals( 'invalid_request', $result->get_error_data()['type'] );
		$this->assertEquals( 'authentication_failed', $result->get_error_data()['code'] );
	}

	/**
	 * Test authorization with multiple providers.
	 */
	public function test_is_authorized_with_multiple_providers() {
		// Enable the feature.
		update_option( 'woocommerce_feature_agentic_checkout_enabled', 'yes' );

		// Set up registry with multiple providers (hashed tokens).
		$token_a = 'provider_a_token';
		$token_b = 'provider_b_token';
		update_option(
			'woocommerce_agentic_agent_registry',
			array(
				'general'    => array(
					'enable_products_default' => 'yes',
				),
				'provider_a' => array(
					'bearer_token' => wp_hash_password( $token_a ),
				),
				'provider_b' => array(
					'bearer_token' => wp_hash_password( $token_b ),
				),
			),
			false
		);

		// Test with provider A token (plaintext).
		$request = new \WP_REST_Request();
		$request->set_header( 'Authorization', 'Bearer ' . $token_a );
		$result = AgenticCheckoutUtils::is_authorized( $request );
		$this->assertTrue( $result );
		$this->assertEquals( 'provider_a', WC()->session->get( SessionKey::AGENTIC_CHECKOUT_PROVIDER_ID ) );

		// Test with provider B token (plaintext).
		$request = new \WP_REST_Request();
		$request->set_header( 'Authorization', 'Bearer ' . $token_b );
		$result = AgenticCheckoutUtils::is_authorized( $request );
		$this->assertTrue( $result );
		$this->assertEquals( 'provider_b', WC()->session->get( SessionKey::AGENTIC_CHECKOUT_PROVIDER_ID ) );
	}

	/**
	 * Test authorization with case-insensitive Bearer keyword.
	 */
	public function test_is_authorized_with_case_insensitive_bearer() {
		// Enable the feature.
		update_option( 'woocommerce_feature_agentic_checkout_enabled', 'yes' );

		// Set up registry (hashed token).
		$test_token = 'test_token';
		update_option(
			'woocommerce_agentic_agent_registry',
			array(
				'openai' => array(
					'bearer_token' => wp_hash_password( $test_token ),
				),
			),
			false
		);

		// Test with different casings of "Bearer" (plaintext token).
		$casings = array( 'Bearer', 'bearer', 'BEARER', 'BeArEr' );
		foreach ( $casings as $casing ) {
			$request = new \WP_REST_Request();
			$request->set_header( 'Authorization', $casing . ' ' . $test_token );
			$result = AgenticCheckoutUtils::is_authorized( $request );
			$this->assertTrue( $result, "Failed for casing: $casing" );
		}
	}
}
