<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\StoreApi\Utilities;

use Automattic\WooCommerce\StoreApi\Utilities\AgenticCheckoutUtils;
use Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Enums\SessionKey;
use Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Enums\Specs\CheckoutSessionStatus;

/**
 * Tests for AgenticCheckoutUtils class.
 */
class AgenticCheckoutUtilsTest extends \WC_Unit_Test_Case {
	/**
	 * Test that calculate_status returns IN_PROGRESS when payment is in progress.
	 */
	public function test_calculate_status_returns_in_progress_when_payment_in_progress() {
		// Set up cart and session.
		$cart = WC()->cart;
		$cart->empty_cart();

		// Add a product to the cart.
		$product = \WC_Helper_Product::create_simple_product();
		$cart->add_to_cart( $product->get_id(), 1 );

		// Set the payment in progress flag.
		WC()->session->set( SessionKey::AGENTIC_CHECKOUT_PAYMENT_IN_PROGRESS, true );

		// Calculate status.
		$status = AgenticCheckoutUtils::calculate_status( $cart );

		// Assert that status is IN_PROGRESS.
		$this->assertEquals( CheckoutSessionStatus::IN_PROGRESS, $status );

		// Clean up.
		WC()->session->set( SessionKey::AGENTIC_CHECKOUT_PAYMENT_IN_PROGRESS, null );
		wp_delete_post( $product->get_id(), true );
	}

	/**
	 * Test that calculate_status returns COMPLETED when order is completed.
	 */
	public function test_calculate_status_returns_completed_when_order_completed() {
		// Set up cart and session.
		$cart = WC()->cart;
		$cart->empty_cart();

		// Set completed order ID.
		WC()->session->set( SessionKey::AGENTIC_CHECKOUT_COMPLETED_ORDER_ID, 123 );

		// Calculate status.
		$status = AgenticCheckoutUtils::calculate_status( $cart );

		// Assert that status is COMPLETED (takes precedence over IN_PROGRESS).
		$this->assertEquals( CheckoutSessionStatus::COMPLETED, $status );

		// Clean up.
		WC()->session->set( SessionKey::AGENTIC_CHECKOUT_COMPLETED_ORDER_ID, null );
	}

	/**
	 * Test that IN_PROGRESS status has correct priority even though cart is otherwise ready.
	 */
	public function test_in_progress_status_priority() {
		// Set up cart and session.
		$cart = WC()->cart;
		$cart->empty_cart();

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
		$status = AgenticCheckoutUtils::calculate_status( $cart );

		// Assert that status is IN_PROGRESS.
		$this->assertEquals( CheckoutSessionStatus::IN_PROGRESS, $status );

		// Clear the IN_PROGRESS flag.
		WC()->session->set( SessionKey::AGENTIC_CHECKOUT_PAYMENT_IN_PROGRESS, false );

		// Clean up.
		wp_delete_post( $product->get_id(), true );
	}

	/**
	 * Test that calculate_status returns READY_FOR_PAYMENT after IN_PROGRESS flag is cleared.
	 */
	public function test_calculate_status_ready_after_in_progress_cleared() {
		// Set up cart and session.
		$cart = WC()->cart;
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
		$status = AgenticCheckoutUtils::calculate_status( $cart );
		$this->assertEquals( CheckoutSessionStatus::IN_PROGRESS, $status );

		// Clear the IN_PROGRESS flag.
		WC()->session->set( SessionKey::AGENTIC_CHECKOUT_PAYMENT_IN_PROGRESS, false );

		// Recalculate status - should be READY_FOR_PAYMENT now.
		$status = AgenticCheckoutUtils::calculate_status( $cart );

		// Assert that status is READY_FOR_PAYMENT after clearing IN_PROGRESS.
		$this->assertNotEquals( CheckoutSessionStatus::IN_PROGRESS, $status );

		// Clean up.
		wp_delete_post( $product->get_id(), true );
	}
}
