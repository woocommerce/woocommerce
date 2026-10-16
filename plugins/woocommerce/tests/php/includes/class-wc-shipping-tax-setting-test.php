<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\Blocks\Shipping\PickupLocation;
use Automattic\WooCommerce\Enums\ProductTaxStatus;
use Automattic\WooCommerce\Enums\TaxBasedOn;

/**
 * Shipping costs entered with tax.
 */
class WC_Shipping_Tax_Setting_Test extends WC_Unit_Test_Case {

	/**
	 * Configure a shipping tax rate.
	 */
	public function setUp(): void {
		parent::setUp();
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_tax_based_on', TaxBasedOn::BASE );
		update_option( 'woocommerce_default_country', 'US:CA' );
		update_option( 'woocommerce_shipping_tax_class', '' );
		update_option( 'woocommerce_shipping_prices_include_tax', 'yes' );
		foreach ( array( 'flat_rate', 'local_pickup', 'pickup_location' ) as $method_id ) {
			update_option(
				'woocommerce_' . $method_id . '_settings',
				array(
					'title'      => 'Shipping',
					'tax_status' => ProductTaxStatus::TAXABLE,
					'cost'       => '10',
				)
			);
		}
		WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate'          => '20',
				'tax_rate_name'     => 'Tax',
				'tax_rate_priority' => 1,
				'tax_rate_compound' => 0,
				'tax_rate_shipping' => 1,
			)
		);
	}

	/**
	 * @testdox Core merchant-entered rates keep the entered gross cost when enabled.
	 * @dataProvider core_methods
	 * @param string $method_class Shipping method class.
	 */
	public function test_core_methods_use_shipping_tax_setting( string $method_class ): void {
		$method = new $method_class();
		$method->add_rate(
			array(
				'label' => 'Shipping',
				'cost'  => 10,
			)
		);
		$rate = current( $method->rates );
		$this->assertEqualsWithDelta( 10 / 1.2, (float) $rate->get_cost(), 0.00001 );
		$this->assertEqualsWithDelta( 10, (float) $rate->get_cost() + array_sum( $rate->get_taxes() ), 0.00001 );
	}

	/**
	 * @return array Shipping method classes.
	 */
	public function core_methods(): array {
		return array(
			'flat rate'    => array( WC_Shipping_Flat_Rate::class ),
			'zone pickup'  => array( WC_Shipping_Local_Pickup::class ),
			'block pickup' => array( PickupLocation::class ),
		);
	}

	/**
	 * @testdox Existing stores keep shipping tax exclusive independently of product prices.
	 */
	public function test_missing_option_keeps_existing_costs_exclusive(): void {
		delete_option( 'woocommerce_shipping_prices_include_tax' );
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		$method = new WC_Shipping_Flat_Rate();
		$method->add_rate(
			array(
				'label' => 'Shipping',
				'cost'  => 10,
			)
		);
		$rate = current( $method->rates );
		$this->assertSame( '10', $rate->get_cost() );
		$this->assertEquals( 2, array_sum( $rate->get_taxes() ) );
	}

	/**
	 * @testdox Extensions must opt in before the setting affects their rates.
	 */
	public function test_extension_rates_require_explicit_support(): void {
		$method     = new class() extends WC_Shipping_Method {};
		$method->id = 'carrier';
		$method->add_rate(
			array(
				'label' => 'Carrier',
				'cost'  => 10,
			)
		);
		$this->assertSame( '10', current( $method->rates )->get_cost() );
		$this->assertEquals( 2, array_sum( current( $method->rates )->get_taxes() ) );

		$method->supports[] = 'costs-entered-with-tax';
		$method->add_rate(
			array(
				'label' => 'Merchant rate',
				'cost'  => 10,
			)
		);
		$this->assertEqualsWithDelta( 10 / 1.2, (float) current( $method->rates )->get_cost(), 0.00001 );
	}

	/**
	 * @testdox Existing shipping tax filters override the store setting in either direction.
	 * @testWith ["yes", false, 10.0]
	 *           ["no", true, 8.333333]
	 * @param string $setting Store setting.
	 * @param bool   $override Filter result.
	 * @param float  $expected Expected net shipping cost.
	 */
	public function test_filter_overrides_setting( string $setting, bool $override, float $expected ): void {
		update_option( 'woocommerce_shipping_prices_include_tax', $setting );
		$filter = static fn() => $override;
		add_filter( 'woocommerce_shipping_prices_include_tax', $filter );
		try {
			$method = new WC_Shipping_Flat_Rate();
			$method->add_rate(
				array(
					'label' => 'Shipping',
					'cost'  => 10,
				)
			);
			$this->assertEqualsWithDelta( $expected, (float) current( $method->rates )->get_cost(), 0.00001 );
		} finally {
			remove_filter( 'woocommerce_shipping_prices_include_tax', $filter );
		}
	}

	/**
	 * @testdox Supplied tax amounts and non-taxable rates retain their existing cost contracts.
	 */
	public function test_explicit_taxes_and_non_taxable_rates_are_unchanged(): void {
		$method = new WC_Shipping_Flat_Rate();
		$method->add_rate(
			array(
				'label' => 'Shipping',
				'cost'  => 10,
				'taxes' => array( 1 => 2 ),
			)
		);
		$this->assertSame( '10', current( $method->rates )->get_cost() );
		$this->assertEquals( 2, array_sum( current( $method->rates )->get_taxes() ) );

		$method->tax_status = ProductTaxStatus::NONE;
		$method->add_rate(
			array(
				'label' => 'Shipping',
				'cost'  => 10,
			)
		);
		$this->assertSame( '10', current( $method->rates )->get_cost() );
		$this->assertEmpty( current( $method->rates )->get_taxes() );
	}

	/**
	 * @testdox Flat rate formulas are evaluated before included tax is removed.
	 */
	public function test_flat_rate_formula_uses_inclusive_total(): void {
		update_option(
			'woocommerce_flat_rate_settings',
			array(
				'title'      => 'Shipping',
				'tax_status' => ProductTaxStatus::TAXABLE,
				'cost'       => '2 * [qty] + [fee percent="10"]',
			)
		);
		$method = new WC_Shipping_Flat_Rate();
		$method->calculate_shipping(
			array(
				'contents'      => array(
					array(
						'data'       => new WC_Product_Simple(),
						'quantity'   => 2,
						'line_total' => 60,
					),
				),
				'contents_cost' => 60,
			)
		);
		$rate = current( $method->rates );
		$this->assertEqualsWithDelta( 10, (float) $rate->get_cost() + array_sum( $rate->get_taxes() ), 0.00001 );
	}

	/**
	 * @testdox Per-item and order costs both use the opted-in shipping tax setting.
	 */
	public function test_per_item_shipping_tax(): void {
		$product = WC_Helper_Product::create_simple_product();
		$key     = WC()->cart->add_to_cart( $product->get_id() );
		$method  = new WC_Shipping_Flat_Rate();
		$method->add_rate(
			array(
				'label'    => 'Shipping',
				'cost'     => array(
					$key    => 3,
					'order' => 7,
				),
				'calc_tax' => 'per_item',
			)
		);
		$rate = current( $method->rates );
		$this->assertEqualsWithDelta( 10 / 1.2, (float) $rate->get_cost(), 0.00001 );
		$this->assertEqualsWithDelta( 10, (float) $rate->get_cost() + array_sum( $rate->get_taxes() ), 0.00001 );
	}

	/**
	 * @testdox Shipping class costs are included in the entered gross amount.
	 */
	public function test_shipping_class_cost_is_inclusive(): void {
		$term    = wp_insert_term( 'Bulky', 'product_shipping_class' );
		$product = new WC_Product_Simple();
		$product->set_shipping_class_id( $term['term_id'] );
		update_option(
			'woocommerce_flat_rate_settings',
			array(
				'title'                          => 'Shipping',
				'tax_status'                     => ProductTaxStatus::TAXABLE,
				'cost'                           => '6',
				'class_cost_' . $term['term_id'] => '4',
			)
		);
		WC()->shipping()->shipping_classes = array();
		try {
			$method = new WC_Shipping_Flat_Rate();
			$method->calculate_shipping(
				array(
					'contents'      => array(
						array(
							'data'       => $product,
							'quantity'   => 1,
							'line_total' => 60,
						),
					),
					'contents_cost' => 60,
				)
			);
			$rate = current( $method->rates );
			$this->assertEqualsWithDelta( 10, (float) $rate->get_cost() + array_sum( $rate->get_taxes() ), 0.00001 );
		} finally {
			WC()->shipping()->shipping_classes = array();
		}
	}

	/**
	 * @testdox Tax-exempt customers retain the existing inclusive-filter behavior.
	 */
	public function test_tax_exempt_customer_keeps_entered_cost_without_tax(): void {
		$was_exempt = WC()->customer->get_is_vat_exempt();
		WC()->customer->set_is_vat_exempt( true );
		try {
			$method = new WC_Shipping_Flat_Rate();
			$method->add_rate(
				array(
					'label' => 'Shipping',
					'cost'  => 10,
				)
			);
			$rate = current( $method->rates );
			$this->assertSame( '10', $rate->get_cost() );
			$this->assertEmpty( $rate->get_taxes() );
		} finally {
			WC()->customer->set_is_vat_exempt( $was_exempt );
		}
	}

	/**
	 * @testdox Calling the existing tax API directly still treats costs as exclusive.
	 */
	public function test_direct_tax_api_keeps_its_default(): void {
		$this->assertEquals( 2, array_sum( WC_Tax::calc_shipping_tax( 10, WC_Tax::get_shipping_tax_rates() ) ) );
	}

	/**
	 * @testdox Third-party subclasses of core methods must opt in explicitly.
	 */
	public function test_inheriting_flat_rate_does_not_opt_in(): void {
		$method = new class() extends WC_Shipping_Flat_Rate {};
		$method->add_rate(
			array(
				'label' => 'Carrier',
				'cost'  => 10,
			)
		);
		$this->assertSame( '10', current( $method->rates )->get_cost() );
	}

	/**
	 * @testdox Install defaults follow product prices only for a first installation.
	 * @testWith [false, "yes", "yes"]
	 *           [false, "no", "no"]
	 *           ["11.2.0", "yes", "no"]
	 * @param string|false $version Existing version, or false for a new installation.
	 * @param string       $product_setting Product tax inclusion.
	 * @param string       $expected Shipping tax inclusion.
	 */
	public function test_install_default( $version, string $product_setting, string $expected ): void {
		delete_option( 'woocommerce_shipping_prices_include_tax' );
		delete_option( 'woocommerce_version' );
		if ( $version ) {
			update_option( 'woocommerce_version', $version );
		}
		update_option( 'woocommerce_prices_include_tax', $product_setting );
		$create_options = Closure::bind(
			static function () {
				WC_Install::create_options();
			},
			null,
			WC_Install::class
		);
		$create_options();
		$this->assertSame( $expected, get_option( 'woocommerce_shipping_prices_include_tax' ) );
		update_option( 'woocommerce_shipping_prices_include_tax', 'yes' === $expected ? 'no' : 'yes' );
		$create_options();
		$this->assertSame( 'yes' === $expected ? 'no' : 'yes', get_option( 'woocommerce_shipping_prices_include_tax' ), 'Reinstalling must preserve an existing value.' );
	}

	/**
	 * @testdox Changing shipping tax inclusion invalidates cached package rates.
	 */
	public function test_option_change_invalidates_cached_rates(): void {
		update_option( 'woocommerce_shipping_debug_mode', 'no' );
		update_option( 'woocommerce_shipping_prices_include_tax', 'no' );
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'Tax inclusion' );
		$zone->add_location( 'US', 'country' );
		$zone->save();
		$instance_id = $zone->add_shipping_method( 'flat_rate' );
		update_option(
			'woocommerce_flat_rate_' . $instance_id . '_settings',
			array(
				'title'      => 'Shipping',
				'tax_status' => ProductTaxStatus::TAXABLE,
				'cost'       => '10',
			)
		);
		$shipping = new WC_Shipping();
		$package  = array(
			'contents'      => array(),
			'contents_cost' => 10,
			'destination'   => array(
				'country'  => 'US',
				'state'    => 'CA',
				'postcode' => '90210',
			),
		);
		$before   = $shipping->calculate_shipping_for_package( $package );
		$this->assertSame( '10', current( $before['rates'] )->get_cost() );
		update_option( 'woocommerce_shipping_prices_include_tax', 'yes' );
		$after = $shipping->calculate_shipping_for_package( $package );
		$this->assertEqualsWithDelta( 10 / 1.2, (float) current( $after['rates'] )->get_cost(), 0.00001 );
	}
}
