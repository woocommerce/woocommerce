<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Cart;

use Automattic\WooCommerce\Enums\CartBehaviorOnLogout;
use Automattic\WooCommerce\Internal\Cart\CartLogoutBehavior;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the CartLogoutBehavior class.
 */
class CartLogoutBehaviorTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var CartLogoutBehavior
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new CartLogoutBehavior();
	}

	/**
	 * @testdox Should carry the cart over to the new session when the store preserves carts on logout.
	 */
	public function test_cart_is_preserved_when_option_is_preserve(): void {
		update_option( 'woocommerce_cart_behavior_on_logout', CartBehaviorOnLogout::PRESERVE );
		$product_id = $this->add_product_to_cart();

		$this->sut->handle_wp_logout_capture();
		$this->destroy_session_like_logout_does();
		$this->sut->handle_wp_logout_restore();

		$this->assertSame(
			array( $product_id ),
			$this->get_product_ids_in_cart(),
			'The cart should still hold the product after logging out'
		);
		$this->assertNotEmpty(
			WC()->session->get( 'cart' ),
			'The new session should be seeded with the cart so the next request can read it back'
		);
	}

	/**
	 * @testdox Should leave the cart empty when the store clears carts on logout.
	 */
	public function test_cart_is_not_preserved_when_option_is_clear(): void {
		update_option( 'woocommerce_cart_behavior_on_logout', CartBehaviorOnLogout::CLEAR );
		$this->add_product_to_cart();

		$this->sut->handle_wp_logout_capture();
		$this->destroy_session_like_logout_does();
		$this->sut->handle_wp_logout_restore();

		$this->assertTrue( WC()->cart->is_empty(), 'The cart should be empty after logging out' );
		$this->assertEmpty( WC()->session->get( 'cart' ), 'The new session should not be seeded with the cart' );
	}

	/**
	 * @testdox Should preserve the cart by default, without the option being saved.
	 */
	public function test_cart_is_preserved_when_option_is_not_set(): void {
		delete_option( 'woocommerce_cart_behavior_on_logout' );
		$product_id = $this->add_product_to_cart();

		$this->sut->handle_wp_logout_capture();
		$this->destroy_session_like_logout_does();
		$this->sut->handle_wp_logout_restore();

		$this->assertSame(
			array( $product_id ),
			$this->get_product_ids_in_cart(),
			'The cart should be preserved when the store has not chosen a behavior'
		);
	}

	/**
	 * @testdox Should not seed the new session when the cart was already empty.
	 */
	public function test_empty_cart_does_not_seed_the_new_session(): void {
		update_option( 'woocommerce_cart_behavior_on_logout', CartBehaviorOnLogout::PRESERVE );

		$this->sut->handle_wp_logout_capture();
		$this->destroy_session_like_logout_does();
		$this->sut->handle_wp_logout_restore();

		$this->assertEmpty(
			WC()->session->get( 'cart' ),
			'Logging out with an empty cart should leave the new session without cart data'
		);
	}

	/**
	 * @testdox Should read the cart before, and write it after, the session handler tears the session down.
	 */
	public function test_hooks_run_around_the_session_teardown(): void {
		$this->sut->register();

		$capture_priority = has_action( 'wp_logout', array( $this->sut, 'handle_wp_logout_capture' ) );
		$restore_priority = has_action( 'wp_logout', array( $this->sut, 'handle_wp_logout_restore' ) );

		// WC_Session_Handler::destroy_session() empties the cart on wp_logout at priority 10.
		$this->assertLessThan( 10, $capture_priority, 'The cart must be captured before the session is destroyed' );
		$this->assertGreaterThan( 10, $restore_priority, 'The cart must be restored after the session is destroyed' );
	}

	/**
	 * Add a simple product to the cart.
	 *
	 * @return int The ID of the product added to the cart.
	 */
	private function add_product_to_cart(): int {
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id() );

		return $product->get_id();
	}

	/**
	 * Get the product IDs currently held in the in-memory cart.
	 *
	 * @return array<int>
	 */
	private function get_product_ids_in_cart(): array {
		return array_values( array_map( 'intval', array_column( WC()->cart->get_cart_contents(), 'product_id' ) ) );
	}

	/**
	 * Reproduce what WC_Session_Handler::destroy_session() does to the cart between the two
	 * wp_logout callbacks. The test session handler is a bare WC_Session, so it has no
	 * destroy_session() of its own to call here.
	 */
	private function destroy_session_like_logout_does(): void {
		wc_empty_cart();
		WC()->session->set( 'cart', null );
	}
}
