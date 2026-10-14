<?php
/**
 * Tests for WC_Shipping_Method.
 *
 * @package WooCommerce\Tests\Abstracts
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Enums\ProductTaxStatus;

/**
 * Tests the tax a shipping method has the shopper charged, through a real cart.
 *
 * `WC_Shipping_Method::is_taxable()` decides this, and the expectations come from what the screens
 * promise: a method's Tax status, offered as "Taxable" or "None", the Enable taxes switch, and a
 * tax rate's own Shipping column.
 */
class WC_Abstract_Shipping_Method_Test extends WC_Unit_Test_Case {

	/**
	 * Instance id of the flat rate offered to the shopper.
	 *
	 * @var int
	 */
	private $instance_id;

	/**
	 * Id of the tax rate inserted for the current test.
	 *
	 * @var int
	 */
	private $tax_rate_id;

	/**
	 * Put a 10% rate that applies to shipping in place, and a cart that needs shipping.
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'no' );

		$this->tax_rate_id = $this->rate_applying_to_shipping( '1' );
		$this->instance_id = WC_Shipping_Zones::get_zone( 0 )->add_shipping_method( 'flat_rate' );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100 );
		$product->save();

		WC_Helper_Shipping::force_customer_us_address();
		$this->assertNotFalse( WC()->cart->add_to_cart( $product->get_id(), 1 ), 'The fixture product should reach the cart.' );
	}

	/**
	 * WC()->customer is a singleton the database rollback does not reset, so the VAT-exempt flag
	 * test_a_vat_exempt_customer_pays_no_tax_on_shipping() sets would otherwise carry into a later
	 * test and silently zero its tax. Put it back.
	 */
	public function tearDown(): void {
		WC()->customer->set_is_vat_exempt( false );
		parent::tearDown();
	}

	/**
	 * Insert a 10% rate, saying whether it applies to shipping.
	 *
	 * @param string $applies_to_shipping '1' or '0', as the Shipping column on the tax rates screen.
	 * @return int The new rate's id.
	 */
	private function rate_applying_to_shipping( string $applies_to_shipping ): int {
		return WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => '',
				'tax_rate_state'    => '',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'Tax @ 10%',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => $applies_to_shipping,
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);
	}

	/**
	 * Offer the zone's flat rate at 10, with the given Tax status.
	 *
	 * @param string $tax_status Either ProductTaxStatus::TAXABLE or ProductTaxStatus::NONE.
	 */
	private function flat_rate_with_tax_status( string $tax_status ): void {
		update_option(
			'woocommerce_flat_rate_' . $this->instance_id . '_settings',
			array(
				'title'      => 'Flat rate',
				'tax_status' => $tax_status,
				'cost'       => 10,
			)
		);

		WC_Cache_Helper::get_transient_version( 'shipping', true );
		WC()->shipping()->unregister_shipping_methods();
		WC()->session->set( 'chosen_shipping_methods', array( 'flat_rate:' . $this->instance_id ) );
	}

	/**
	 * Work the cart out and hand back what the shopper is charged on shipping.
	 *
	 * @return float
	 */
	private function shipping_tax_charged(): float {
		WC()->cart->calculate_totals();

		$offered = array();
		foreach ( WC()->shipping()->get_packages() as $package ) {
			$offered = array_merge( $offered, array_keys( $package['rates'] ) );
		}

		// Named rather than counted, because a leftover method from an earlier test would otherwise
		// be indistinguishable from the zone rate this fixture set up.
		$this->assertSame( array( 'flat_rate:' . $this->instance_id ), $offered, 'The zone flat rate should be the only thing on offer.' );
		$this->assertEquals( 10, WC()->cart->get_shipping_total(), 'The fixture shipping should cost 10, or the tax on it proves nothing.' );

		return (float) WC()->cart->get_shipping_tax();
	}

	/**
	 * @testdox A method the merchant marked Taxable is taxed.
	 */
	public function test_a_taxable_method_is_taxed(): void {
		$this->flat_rate_with_tax_status( ProductTaxStatus::TAXABLE );

		$this->assertEquals( 1.0, $this->shipping_tax_charged(), 'Ten percent of a shipping cost of 10 should reach the shopper.' );
	}

	/**
	 * @testdox A method the merchant marked None is not taxed, while the goods still are.
	 */
	public function test_a_method_marked_none_is_not_taxed(): void {
		$this->flat_rate_with_tax_status( ProductTaxStatus::NONE );

		$this->assertEquals( 0.0, $this->shipping_tax_charged(), 'Shipping marked None should carry no tax.' );
		$this->assertEquals( 10.0, WC()->cart->get_taxes_total(), 'The goods should still be taxed, or this test would pass with taxes switched off.' );
	}

	/**
	 * @testdox With taxes switched off, a taxable method is not taxed either.
	 */
	public function test_with_taxes_switched_off_nothing_is_taxed(): void {
		$this->flat_rate_with_tax_status( ProductTaxStatus::TAXABLE );
		update_option( 'woocommerce_calc_taxes', 'no' );

		$this->assertEquals( 0.0, $this->shipping_tax_charged(), 'A store not charging tax should not charge it on shipping.' );
	}

	/**
	 * The tax rates screen has a Shipping column for each rate, so a rate can apply to the goods
	 * without applying to the shipping.
	 *
	 * @testdox A rate that does not apply to shipping does not tax the shipping.
	 */
	public function test_a_rate_that_does_not_apply_to_shipping_leaves_it_untaxed(): void {
		$this->flat_rate_with_tax_status( ProductTaxStatus::TAXABLE );

		WC_Tax::_delete_tax_rate( $this->tax_rate_id );
		$this->rate_applying_to_shipping( '0' );

		$this->assertEquals( 0.0, $this->shipping_tax_charged(), 'The rate says it does not apply to shipping.' );
		$this->assertEquals( 10.0, WC()->cart->get_taxes_total(), 'But it still applies to the goods, or this test would pass with no rate at all.' );
	}

	/**
	 * `WC_Order::calculate_taxes()` already drops shipping tax for a VAT-exempt customer. The cart
	 * has to agree, or the shopper is quoted one figure and charged another.
	 *
	 * @testdox A VAT-exempt customer pays no tax on shipping.
	 */
	public function test_a_vat_exempt_customer_pays_no_tax_on_shipping(): void {
		$this->flat_rate_with_tax_status( ProductTaxStatus::TAXABLE );
		WC()->customer->set_is_vat_exempt( true );

		$this->assertEquals( 0.0, $this->shipping_tax_charged(), 'A customer exempt from VAT is exempt on the shipping too.' );
	}
}
