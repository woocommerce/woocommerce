<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\ShippingPolicy;
use WC_Unit_Test_Case;

/**
 * Tests for the POS shipping policy.
 *
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\ShippingPolicy
 */
class ShippingPolicyTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ShippingPolicy
	 */
	private $sut;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new ShippingPolicy();
		$this->sut->register();
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		remove_filter( 'woocommerce_cart_needs_shipping', array( $this->sut, 'maybe_disable_shipping' ) );
		Context::set_test_override( null );
		parent::tearDown();
	}

	/**
	 * @testdox The cart needs no shipping in POS context; web behaviour is untouched.
	 */
	public function test_shipping_disabled_only_in_pos_context(): void {
		Context::set_test_override( true );
		$this->assertFalse( apply_filters( 'woocommerce_cart_needs_shipping', true ) ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment

		Context::set_test_override( false );
		$this->assertTrue( apply_filters( 'woocommerce_cart_needs_shipping', true ) ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		$this->assertFalse( apply_filters( 'woocommerce_cart_needs_shipping', false ), 'A pre-existing false must pass through.' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
	}
}
