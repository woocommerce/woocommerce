<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\StoreApi\Utilities;

use Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\AgenticCheckoutSession;
use Automattic\WooCommerce\StoreApi\Utilities\AgenticCheckoutUtils;
use Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Enums\SessionKey;
use Automattic\WooCommerce\Internal\Agentic\Enums\Specs\CheckoutSessionStatus;
use Automattic\WooCommerce\Tests\Internal\Admin\Agentic\AgenticTestHelpers;

/**
 * Tests for AgenticCheckoutUtils class.
 */
class AgenticCheckoutUtilsTest extends \WC_Unit_Test_Case {
	use AgenticTestHelpers;

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

		// Reset Jetpack auth state.
		$this->reset_jetpack_auth_state();
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
	 * Test validate_jetpack_request returns error when Jetpack authentication is not present.
	 */
	public function test_validate_jetpack_request_returns_error_without_authentication() {
		// Ensure no Jetpack authentication is set.
		$this->mock_jetpack_auth_failure();

		// Test validation.
		$result = AgenticCheckoutUtils::validate_jetpack_request();

		// Assert authorization fails with 401 error.
		$this->assertWPError( $result );
		$this->assertEquals( 'rest_forbidden', $result->get_error_code() );
		$this->assertEquals( 401, $result->get_error_data()['status'] );
		$this->assertStringContainsString( 'Jetpack blog token', $result->get_error_message() );
	}

	/**
	 * Test validate_jetpack_request returns true with valid blog token authentication.
	 */
	public function test_validate_jetpack_request_returns_true_with_valid_blog_token() {
		// Mock successful Jetpack blog token authentication.
		$this->mock_jetpack_blog_token_auth();

		// Test validation.
		$result = AgenticCheckoutUtils::validate_jetpack_request();

		// Assert authorization succeeds.
		$this->assertTrue( $result );
	}

	/**
	 * Test validate_jetpack_request error message is user-friendly.
	 */
	public function test_validate_jetpack_request_error_message() {
		// Ensure no Jetpack authentication is set.
		$this->mock_jetpack_auth_failure();

		// Test validation.
		$result = AgenticCheckoutUtils::validate_jetpack_request();

		// Assert the error message is helpful for debugging.
		$this->assertWPError( $result );
		$this->assertEquals(
			'This endpoint requires Jetpack blog token authentication.',
			$result->get_error_message()
		);
	}
}
