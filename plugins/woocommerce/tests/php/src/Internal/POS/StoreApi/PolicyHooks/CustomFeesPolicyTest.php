<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\CustomFeesStore;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CustomFeesPolicy;
use WC_Unit_Test_Case;

/**
 * Tests for the POS custom fees policy.
 *
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CustomFeesPolicy
 */
class CustomFeesPolicyTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var CustomFeesPolicy
	 */
	private $sut;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new CustomFeesPolicy();
		$this->sut->register();
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		remove_action( 'woocommerce_cart_calculate_fees', array( $this->sut, 'apply_custom_fees' ) );
		WC()->session->set( 'pos_custom_fees', null );
		WC()->cart->fees_api()->remove_all_fees();
		Context::set_test_override( null );
		parent::tearDown();
	}

	/**
	 * @testdox Stored fees are applied on cart calculation in POS context only.
	 */
	public function test_stored_fees_applied_only_in_pos_context(): void {
		( new CustomFeesStore( WC()->session ) )->add( 'Gift wrap', 5.0 );

		Context::set_test_override( false );
		WC()->cart->fees_api()->remove_all_fees();
		do_action( 'woocommerce_cart_calculate_fees', WC()->cart ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		$this->assertCount( 0, WC()->cart->fees_api()->get_fees(), 'Web calculations must not pick up POS fees.' );

		Context::set_test_override( true );
		do_action( 'woocommerce_cart_calculate_fees', WC()->cart ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		$this->assertCount( 1, WC()->cart->fees_api()->get_fees() );
	}

	/**
	 * The policy runs inside every totals calculation; the content-derived fee
	 * id makes re-application idempotent rather than duplicative.
	 *
	 * @testdox Repeated calculations do not duplicate fees.
	 */
	public function test_repeated_calculation_does_not_duplicate_fees(): void {
		Context::set_test_override( true );
		( new CustomFeesStore( WC()->session ) )->add( 'Gift wrap', 5.0 );

		WC()->cart->fees_api()->remove_all_fees();
		do_action( 'woocommerce_cart_calculate_fees', WC()->cart ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'woocommerce_cart_calculate_fees', WC()->cart ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment

		$this->assertCount( 1, WC()->cart->fees_api()->get_fees() );
	}
}
