<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\TaxLocationPolicy;
use WC_Unit_Test_Case;

/**
 * Tests for the POS tax-location policy.
 *
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\TaxLocationPolicy
 */
class TaxLocationPolicyTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var TaxLocationPolicy
	 */
	private $sut;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new TaxLocationPolicy();
		$this->sut->register();

		update_option( 'woocommerce_default_country', 'US:CA' );
		update_option( 'woocommerce_store_postcode', '94016' );
		update_option( 'woocommerce_store_city', 'San Francisco' );
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		remove_filter( 'woocommerce_customer_taxable_address', array( $this->sut, 'maybe_use_store_base_address' ) );
		Context::set_test_override( null );
		parent::tearDown();
	}

	/**
	 * @testdox The taxable address is the store base in POS context and the customer's outside it.
	 */
	public function test_taxable_address_is_store_base_only_in_pos_context(): void {
		$customer_address = array( 'DE', '', '10115', 'Berlin' );

		Context::set_test_override( false );
		$this->assertSame(
			$customer_address,
			apply_filters( 'woocommerce_customer_taxable_address', $customer_address ) // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		);

		Context::set_test_override( true );
		$this->assertSame(
			array( 'US', 'CA', '94016', 'San Francisco' ),
			apply_filters( 'woocommerce_customer_taxable_address', $customer_address ) // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		);
	}
}
