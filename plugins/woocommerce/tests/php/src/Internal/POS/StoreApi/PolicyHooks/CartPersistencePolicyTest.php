<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CartPersistencePolicy;
use WC_Unit_Test_Case;

/**
 * Tests for the POS persistent-cart opt-out.
 *
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CartPersistencePolicy
 */
class CartPersistencePolicyTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var CartPersistencePolicy
	 */
	private $sut;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new CartPersistencePolicy();
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		remove_filter( 'woocommerce_persistent_cart_enabled', array( $this->sut, 'maybe_disable_persistent_cart' ) );
		Context::set_test_override( null );
		parent::tearDown();
	}

	/**
	 * @testdox register() attaches the filter unconditionally; context is decided per call.
	 */
	public function test_register_attaches_filter_unconditionally(): void {
		Context::set_test_override( false );

		$this->sut->register();

		$this->assertNotFalse( has_filter( 'woocommerce_persistent_cart_enabled', array( $this->sut, 'maybe_disable_persistent_cart' ) ) );
	}

	/**
	 * The operator's saved web cart must not merge into a customer
	 * transaction, and a transaction must not overwrite the operator's saved
	 * cart — both paths honour this filter.
	 *
	 * @testdox The persistent cart is disabled in POS context and untouched outside it.
	 */
	public function test_persistent_cart_disabled_only_in_pos_context(): void {
		$this->sut->register();

		Context::set_test_override( true );
		$this->assertFalse( apply_filters( 'woocommerce_persistent_cart_enabled', true ) ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment

		Context::set_test_override( false );
		$this->assertTrue( apply_filters( 'woocommerce_persistent_cart_enabled', true ) ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		$this->assertFalse( apply_filters( 'woocommerce_persistent_cart_enabled', false ), 'A pre-existing false must pass through.' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
	}
}
