<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Tax;

use Automattic\WooCommerce\Enums\ProductTaxStatus;
use Automattic\WooCommerce\Internal\Tax\ShippingTaxSplit;
use WC_Coupon;
use WC_Helper_Shipping;
use WC_Order_Item_Fee;
use WC_Order_Item_Shipping;
use WC_Product_Simple;
use WC_Tax;
use WC_Unit_Test_Case;

/**
 * Tests for the ShippingTaxSplit class.
 *
 * Rates are 20% standard, 5% reduced and 0% zero rate, all applied to shipping, with a 10.00 flat rate.
 */
class ShippingTaxSplitTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ShippingTaxSplit
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new ShippingTaxSplit();

		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'no' );

		foreach ( array(
			''             => '20.0000',
			'reduced-rate' => '5.0000',
			'zero-rate'    => '0.0000',
		) as $tax_class => $rate ) {
			WC_Tax::_insert_tax_rate(
				array(
					'tax_rate_country'  => '',
					'tax_rate'          => $rate,
					'tax_rate_name'     => 'VAT',
					'tax_rate_priority' => '1',
					'tax_rate_shipping' => '1',
					'tax_rate_class'    => $tax_class,
				)
			);
		}

		WC_Helper_Shipping::create_simple_flat_rate();
		WC_Helper_Shipping::force_customer_us_address();
	}

	/**
	 * @testdox The cost is split in proportion to the values, with the rounding remainder on the largest part.
	 *
	 * @testWith [10.0, {"": 10, "zero-rate": 90}, {"": 1.0, "zero-rate": 9.0}]
	 *           [10.0, {"": 1, "zero-rate": 1, ":non-taxable": 1}, {"": 3.34, "zero-rate": 3.33, ":non-taxable": 3.33}]
	 *           [10.0, {"": 1, "zero-rate": 1, "reduced-rate": 1.5}, {"": 2.86, "zero-rate": 2.86, "reduced-rate": 4.28}]
	 *
	 * @param float $cost     Cost to split.
	 * @param array $values   Value per tax class.
	 * @param array $expected Expected cost per tax class.
	 */
	public function test_split_cost( float $cost, array $values, array $expected ): void {
		$this->assertSame( $expected, ShippingTaxSplit::split_cost( $cost, $values ) );
	}

	/**
	 * @testdox Shipping tax at checkout follows the split setting.
	 *
	 * @dataProvider provide_baskets
	 *
	 * @param bool  $split      Whether to split shipping tax by cart item value.
	 * @param array $lines      Lines of tax class, tax status, price and whether the product is virtual.
	 * @param int[] $free_lines Lines a 100% coupon makes free.
	 * @param float $expected   Expected shipping tax.
	 */
	public function test_cart_shipping_tax( bool $split, array $lines, array $free_lines, float $expected ): void {
		if ( $split ) {
			$this->enable_split();
		}
		$products = $this->create_products( $lines );

		$this->fill_cart( $products );
		if ( $free_lines ) {
			WC()->cart->apply_coupon( $this->create_coupon( $products, $free_lines ) );
		}
		WC()->cart->calculate_totals();

		$this->assertEqualsWithDelta( $expected, (float) WC()->cart->get_shipping_tax(), 0.001 );
	}

	/**
	 * @testdox Shipping tax on an order recalculation follows the split setting.
	 *
	 * @dataProvider provide_baskets
	 *
	 * @param bool  $split      Whether to split shipping tax by cart item value.
	 * @param array $lines      Lines of tax class, tax status, price and whether the product is virtual.
	 * @param int[] $free_lines Lines a 100% coupon makes free.
	 * @param float $expected   Expected shipping tax.
	 */
	public function test_order_shipping_tax( bool $split, array $lines, array $free_lines, float $expected ): void {
		if ( $split ) {
			$this->enable_split();
		}
		$products = $this->create_products( $lines );

		$order = $this->create_order( $products );
		if ( $free_lines ) {
			$order->apply_coupon( $this->create_coupon( $products, $free_lines ) );
		}
		$order->calculate_totals();

		$this->assertEqualsWithDelta( $expected, (float) $order->get_shipping_tax(), 0.001 );
	}

	/**
	 * Data provider for the cart and order shipping tax tests.
	 *
	 * @return array
	 */
	public function provide_baskets(): array {
		$split    = true;
		$standard = array( '', ProductTaxStatus::TAXABLE, 10, false );
		$zero     = array( 'zero-rate', ProductTaxStatus::TAXABLE, 90, false );

		return array(
			'based on cart items'      => array( false, array( $standard, $zero ), array(), 2.0 ),
			'split'                    => array( $split, array( $standard, $zero ), array(), 0.2 ),
			'item with no tax'         => array( $split, array( $standard, array( '', ProductTaxStatus::NONE, 90, false ) ), array(), 0.2 ),
			'item with shipping tax'   => array( $split, array( array( '', ProductTaxStatus::SHIPPING, 50, false ), array( 'zero-rate', ProductTaxStatus::TAXABLE, 50, false ) ), array(), 1.0 ),
			'virtual item not shipped' => array( $split, array( array( '', ProductTaxStatus::TAXABLE, 10, true ), $zero ), array(), 0.0 ),
			'free standard item'       => array( $split, array( $standard, $zero ), array( 0 ), 0.0 ),
			'every item free'          => array( $split, array( $standard, $zero ), array( 0, 1 ), 2.0 ),
		);
	}

	/**
	 * @testdox A rate added without a package is split by the cart contents.
	 */
	public function test_package_values_use_the_cart_without_a_package(): void {
		$this->enable_split();

		$this->fill_cart( $this->create_products( array( array( '', ProductTaxStatus::TAXABLE, 10, false ), array( 'zero-rate', ProductTaxStatus::NONE, 30, false ) ) ) );

		$this->assertSame(
			array(
				''                            => 10.0,
				ShippingTaxSplit::NON_TAXABLE => 30.0,
			),
			$this->sut->get_package_values( false )
		);
	}

	/**
	 * @testdox Each package is split by its own items, and the order created from the cart keeps that split, also when the other package ships free.
	 * @testWith [10]
	 *           [0]
	 *
	 * @param int $first_cost Shipping cost of the zero rate package.
	 */
	public function test_split_per_package( int $first_cost ): void {
		$this->enable_split();
		$products = $this->create_products(
			array(
				array( 'zero-rate', ProductTaxStatus::TAXABLE, 90, false ),
				array( '', ProductTaxStatus::TAXABLE, 10, false ),
				array( 'zero-rate', ProductTaxStatus::TAXABLE, 40, false ),
			)
		);
		$first_id = $products[0]->get_id();
		$this->split_packages_by_product( $first_id );
		add_filter(
			'woocommerce_shipping_method_add_rate_args',
			function ( $args ) use ( $first_id, $first_cost ) {
				if ( wp_list_filter( $args['package']['contents'] ?? array(), array( 'product_id' => $first_id ) ) ) {
					$args['cost'] = $first_cost;
				}
				return $args;
			}
		);

		$this->fill_cart( $products, array( 'flat_rate', 'flat_rate' ) );
		WC()->cart->calculate_totals();

		// The zero rate package pays no tax, and 10 of the other package's 50 is standard rate: 20% of 2.00.
		$this->assertEqualsWithDelta( 0.4, (float) WC()->cart->get_shipping_tax(), 0.001 );
		$this->assertEqualsWithDelta( 0.4, (float) $this->create_order_from_cart()->get_shipping_tax(), 0.001 );
	}

	/**
	 * @testdox An order created from the cart keeps the cart's shipping tax when an item is in no package.
	 */
	public function test_item_in_no_package_keeps_the_cart_tax(): void {
		$this->enable_split();
		$products = $this->create_products( array( array( '', ProductTaxStatus::TAXABLE, 10, false ), array( 'zero-rate', ProductTaxStatus::TAXABLE, 90, false ) ) );
		$zero_id  = $products[1]->get_id();
		add_filter(
			'woocommerce_cart_shipping_packages',
			function ( $packages ) use ( $zero_id ) {
				$packages[0]['contents'] = array_diff_key( $packages[0]['contents'], wp_list_filter( $packages[0]['contents'], array( 'product_id' => $zero_id ) ) );
				return $packages;
			}
		);

		$this->fill_cart( $products );
		WC()->cart->calculate_totals();
		$order = $this->create_order_from_cart();

		// The package has only the standard rate item, so all 10.00 of shipping is taxed at 20%.
		$this->assertEqualsWithDelta( 2.0, (float) WC()->cart->get_shipping_tax(), 0.001 );
		$this->assertEqualsWithDelta( 2.0, (float) $order->get_shipping_tax(), 0.001 );
	}

	/**
	 * @testdox An order created from the cart keeps the cart's shipping tax when a rate is rebuilt without its split.
	 *
	 * @testWith [1, 4.0]
	 *           [2, 4.0]
	 *
	 * @param int   $package_count Number of packages, each with a 10.00 rate.
	 * @param float $expected      Expected shipping tax.
	 */
	public function test_rate_rebuilt_without_split_keeps_the_cart_tax( int $package_count, float $expected ): void {
		$this->enable_split();
		$products = $this->create_products( array( array( '', ProductTaxStatus::TAXABLE, 10, false ), array( 'zero-rate', ProductTaxStatus::TAXABLE, 90, false ) ) );
		if ( 2 === $package_count ) {
			$this->split_packages_by_product( $products[0]->get_id() );
		} else {
			add_filter(
				'woocommerce_shipping_method_add_rate_args',
				function ( $args ) {
					$args['cost'] = 20;
					return $args;
				}
			);
		}
		add_filter(
			'woocommerce_package_rates',
			function ( $rates ) {
				foreach ( $rates as $rate_id => $rate ) {
					$taxes             = WC_Tax::calc_shipping_tax( $rate->get_cost(), WC_Tax::get_shipping_tax_rates() );
					$rates[ $rate_id ] = new \WC_Shipping_Rate( $rate->get_id(), $rate->get_label(), $rate->get_cost(), $taxes, $rate->get_method_id(), $rate->get_instance_id() );
				}
				return $rates;
			}
		);

		$this->fill_cart( $products, array_fill( 0, $package_count, 'flat_rate' ) );
		WC()->cart->calculate_totals();
		$order = $this->create_order_from_cart();

		// The rebuilt rates are taxed as trunk does, at 20% for the standard rate item: 20.00 of shipping in all.
		$this->assertEqualsWithDelta( $expected, (float) WC()->cart->get_shipping_tax(), 0.001 );
		$this->assertEqualsWithDelta( $expected, (float) $order->get_shipping_tax(), 0.001 );
	}

	/**
	 * @testdox A checkout draft keeps the cart's shipping tax when its items change and its shipping lines are kept.
	 */
	public function test_draft_with_changed_items_keeps_the_cart_tax(): void {
		$this->enable_split();
		$products = $this->create_products( array( array( '', ProductTaxStatus::TAXABLE, 100, false ), array( 'zero-rate', ProductTaxStatus::TAXABLE, 50, false ) ) );
		$this->split_packages_by_product( $products[0]->get_id() );
		$this->fill_cart( $products, array( 'flat_rate', 'flat_rate' ) );
		WC()->cart->calculate_totals();
		$order = $this->create_order_from_cart();

		// The Store API rebuilds a draft's line items when the cart changes, and keeps its shipping lines while their rates are unchanged.
		WC()->cart->set_quantity( (string) array_key_first( WC()->cart->get_cart() ), 2 );
		WC()->cart->calculate_totals();
		$order->remove_order_items( 'line_item' );
		WC()->checkout()->create_order_line_items( $order, WC()->cart );
		$order->calculate_totals();

		// Each package holds one class, so only the standard rate package's 10.00 is taxed, at 20%.
		$this->assertEqualsWithDelta( 2.0, (float) WC()->cart->get_shipping_tax(), 0.001 );
		$this->assertEqualsWithDelta( 2.0, (float) $order->get_shipping_tax(), 0.001 );
	}

	/**
	 * @testdox An order created from the cart keeps the cart's shipping tax when a rate's cost and taxes are changed and its split is not.
	 */
	public function test_rate_with_changed_cost_keeps_the_cart_tax(): void {
		$this->enable_split();
		$products = $this->create_products( array( array( '', ProductTaxStatus::TAXABLE, 10, false ), array( 'zero-rate', ProductTaxStatus::TAXABLE, 90, false ) ) );
		add_filter(
			'woocommerce_package_rates',
			function ( $rates ) {
				foreach ( $rates as $rate ) {
					$rate->set_cost( 5 );
					$rate->set_taxes( WC_Tax::calc_shipping_tax( 5, WC_Tax::get_shipping_tax_rates() ) );
				}
				return $rates;
			}
		);

		$this->fill_cart( $products );
		WC()->cart->calculate_totals();
		$order = $this->create_order_from_cart();

		// The changed rate is taxed as trunk does, at 20% for the standard rate item.
		$this->assertEqualsWithDelta( 1.0, (float) WC()->cart->get_shipping_tax(), 0.001 );
		$this->assertEqualsWithDelta( 1.0, (float) $order->get_shipping_tax(), 0.001 );
	}

	/**
	 * @testdox An order created from the cart keeps the cart's shipping tax when a rate is taxed again in one class and the split had a small part in another.
	 */
	public function test_rate_taxed_again_in_one_class_with_a_small_part_keeps_the_cart_tax(): void {
		$this->enable_split();
		$products = $this->create_products( array( array( '', ProductTaxStatus::TAXABLE, 99, false ), array( $this->create_tax_class( 'Five', '5.0000' ), ProductTaxStatus::TAXABLE, 1, false ) ) );
		add_filter(
			'woocommerce_package_rates',
			function ( $rates ) {
				foreach ( $rates as $rate ) {
					$rate->set_cost( 5 );
					$rate->set_taxes( WC_Tax::calc_shipping_tax( 5, WC_Tax::get_shipping_tax_rates() ) );
				}
				return $rates;
			}
		);

		$this->fill_cart( $products );
		WC()->cart->calculate_totals();
		$order = $this->create_order_from_cart();

		// 20% of 5.00. The split scaled to 5.00 would charge 0.9925, close enough to pass for the cart's tax if only rounding were allowed for.
		$this->assertEqualsWithDelta( 1.0, (float) WC()->cart->get_shipping_tax(), 0.001 );
		$this->assertEqualsWithDelta( 1.0, (float) $order->get_shipping_tax(), 0.001 );
	}

	/**
	 * @testdox An order created from the cart keeps the cart's shipping tax when a rate keeps its cost and is taxed again in one class.
	 *
	 * @testWith [1, 2.0]
	 *           [2, 4.0]
	 *
	 * @param int   $package_count Number of packages, each with a 10.00 rate.
	 * @param float $expected      Expected shipping tax.
	 */
	public function test_rate_taxed_again_in_one_class_at_the_same_cost_keeps_the_cart_tax( int $package_count, float $expected ): void {
		$this->enable_split();
		$products = $this->create_products( array( array( '', ProductTaxStatus::TAXABLE, 10, false ), array( 'zero-rate', ProductTaxStatus::TAXABLE, 90, false ) ) );
		if ( 2 === $package_count ) {
			$this->split_packages_by_product( $products[0]->get_id() );
		}
		add_filter(
			'woocommerce_package_rates',
			function ( $rates ) {
				foreach ( $rates as $rate ) {
					$rate->set_taxes( WC_Tax::calc_shipping_tax( $rate->get_cost(), WC_Tax::get_shipping_tax_rates() ) );
				}
				return $rates;
			}
		);

		$this->fill_cart( $products, array_fill( 0, $package_count, 'flat_rate' ) );
		WC()->cart->calculate_totals();
		$order = $this->create_order_from_cart();

		// 20% of each whole 10.00, as the cart charged, also on the package holding only the zero rate item.
		$this->assertEqualsWithDelta( $expected, (float) WC()->cart->get_shipping_tax(), 0.001 );
		$this->assertEqualsWithDelta( $expected, (float) $order->get_shipping_tax(), 0.001 );
	}

	/**
	 * @testdox A negative fee gets the same tax in the cart and the order when a rate keeps its cost and is taxed again in one class.
	 *
	 * @testWith [1, -0.2]
	 *           [2, -0.35]
	 *
	 * @param int   $package_count Number of packages, each with a 10.00 rate.
	 * @param float $expected      Expected fee tax.
	 */
	public function test_negative_fee_tax_with_a_rate_taxed_again_in_one_class( int $package_count, float $expected ): void {
		$this->enable_split();
		$products = $this->create_products( array( array( '', ProductTaxStatus::TAXABLE, 10, false ), array( 'zero-rate', ProductTaxStatus::TAXABLE, 90, false ) ) );
		if ( 2 === $package_count ) {
			$this->split_packages_by_product( $products[0]->get_id() );
		}
		add_filter(
			'woocommerce_package_rates',
			function ( $rates ) {
				foreach ( $rates as $rate ) {
					$rate->set_taxes( WC_Tax::calc_shipping_tax( $rate->get_cost(), WC_Tax::get_shipping_tax_rates() ) );
				}
				return $rates;
			}
		);
		add_action(
			'woocommerce_cart_calculate_fees',
			function ( $cart ) {
				$cart->add_fee( 'Discount', -10, true );
			}
		);

		$this->fill_cart( $products, array_fill( 0, $package_count, 'flat_rate' ) );
		WC()->cart->calculate_totals();
		$fees  = WC()->cart->get_fees();
		$order = $this->create_order_from_cart();
		WC()->checkout()->create_order_fee_lines( $order, WC()->cart );
		$order->calculate_totals();
		$order_fees = $order->get_fees();
		$order_fee  = reset( $order_fees );
		$this->assertInstanceOf( WC_Order_Item_Fee::class, $order_fee );

		// A rate taxed in one class is split by the items on both sides, 1.00 of its 10.00 standard rate. A package holding only
		// the standard rate item keeps its split, so with 2 packages standard rate items and shipping are 21 of the 120: 20% of -1.75.
		$this->assertEqualsWithDelta( $expected, (float) reset( $fees )->tax, 0.001 );
		$this->assertEqualsWithDelta( $expected, (float) $order_fee->get_total_tax(), 0.001 );
	}

	/**
	 * @testdox An order created from the cart keeps the cart's shipping tax when a rate's cost and split taxes are scaled.
	 *
	 * @testWith [false, 0.3]
	 *           [true, 0.25]
	 *
	 * @param bool  $including_tax Whether shipping costs are entered with tax.
	 * @param float $expected      Expected shipping tax.
	 */
	public function test_rate_with_scaled_cost_keeps_the_cart_tax( bool $including_tax, float $expected ): void {
		$this->enable_split();
		if ( $including_tax ) {
			add_filter( 'woocommerce_shipping_prices_include_tax', '__return_true' );
		}
		$products = $this->create_products( array( array( '', ProductTaxStatus::TAXABLE, 10, false ), array( 'zero-rate', ProductTaxStatus::TAXABLE, 90, false ) ) );
		add_filter(
			'woocommerce_package_rates',
			function ( $rates ) {
				foreach ( $rates as $rate ) {
					$rate->set_cost( $rate->get_cost() * 1.5 );
					$rate->set_taxes(
						array_map(
							function ( $tax ) {
								return $tax * 1.5;
							},
							$rate->get_taxes()
						)
					);
				}
				return $rates;
			}
		);

		$this->fill_cart( $products );
		WC()->cart->calculate_totals();
		$order = $this->create_order_from_cart();

		// 1.5 times the tax on the standard rate tenth of 10.00: 20% of 1.00, or the 20% included in 1.00.
		$this->assertEqualsWithDelta( $expected, (float) WC()->cart->get_shipping_tax(), 0.001 );
		$this->assertEqualsWithDelta( $expected, (float) $order->get_shipping_tax(), 0.001 );
	}

	/**
	 * @testdox Recalculating an order saves the unrounded costs it taxed on the shipping line.
	 */
	public function test_recalculate_saves_the_costs_it_taxed(): void {
		$this->enable_split();
		$order = $this->create_order( $this->create_products( array( array( '', ProductTaxStatus::TAXABLE, 10, false ), array( 'zero-rate', ProductTaxStatus::TAXABLE, 20, false ) ) ) );

		$order->calculate_totals();
		$lines = $order->get_shipping_methods();
		$costs = reset( $lines )->get_meta( ShippingTaxSplit::COSTS_META_KEY );

		// A third of the 10.00 is standard rate, and its tax was worked out on that third, not on 3.33.
		$this->assertEqualsWithDelta( 10 / 3, $costs[''], 0.000001 );
		$this->assertEqualsWithDelta( 20 / 3, $costs['zero-rate'], 0.000001 );
	}

	/**
	 * @testdox Recalculating an order split by its items saves the split on each shipping line.
	 */
	public function test_recalculate_saves_the_split_on_the_line(): void {
		$this->enable_split();
		$order = $this->create_order( $this->create_products( array( array( '', ProductTaxStatus::TAXABLE, 10, false ), array( 'zero-rate', ProductTaxStatus::TAXABLE, 90, false ) ) ) );

		$order->calculate_totals();
		$lines = $order->get_shipping_methods();
		$line  = reset( $lines );

		$this->assertEqualsWithDelta( 0.2, (float) $order->get_shipping_tax(), 0.001 );
		$this->assertSame(
			array(
				''          => 1.0,
				'zero-rate' => 9.0,
			),
			$line->get_meta( ShippingTaxSplit::COSTS_META_KEY )
		);
	}

	/**
	 * @testdox Recalculating with the split turned off removes the split saved on the shipping line.
	 */
	public function test_recalculate_with_the_split_off_removes_the_saved_split(): void {
		$this->enable_split();
		$order = $this->create_order( $this->create_products( array( array( '', ProductTaxStatus::TAXABLE, 10, false ), array( 'zero-rate', ProductTaxStatus::TAXABLE, 90, false ) ) ) );
		$order->calculate_totals();

		update_option( ShippingTaxSplit::OPTION, 'no' );
		$order->calculate_totals();
		$lines = $order->get_shipping_methods();
		$line  = reset( $lines );

		$this->assertEqualsWithDelta( 2.0, (float) $order->get_shipping_tax(), 0.001 );
		$this->assertSame( '', $line->get_meta( ShippingTaxSplit::COSTS_META_KEY ) );
		$this->assertSame( '', $line->get_meta( ShippingTaxSplit::VALUES_META_KEY ) );
	}

	/**
	 * @testdox With shipping costs entered with tax, the order created from the cart keeps the cart's shipping tax.
	 */
	public function test_split_with_shipping_costs_including_tax(): void {
		$this->enable_split();
		add_filter( 'woocommerce_shipping_prices_include_tax', '__return_true' );

		$this->fill_cart( $this->create_products( array( array( '', ProductTaxStatus::TAXABLE, 50, false ), array( '', ProductTaxStatus::NONE, 50, false ) ) ) );
		WC()->cart->calculate_totals();
		$order = $this->create_order_from_cart();

		// Half of the 10.00 includes 20% tax.
		$this->assertEqualsWithDelta( 0.83, (float) WC()->cart->get_shipping_tax(), 0.001 );
		$this->assertEqualsWithDelta( 0.83, (float) $order->get_shipping_tax(), 0.001 );
	}

	/**
	 * @testdox With shipping costs entered with tax, the order keeps the cart's shipping tax to the penny.
	 */
	public function test_split_with_shipping_costs_including_tax_rounds_as_the_cart(): void {
		$this->enable_split();
		add_filter( 'woocommerce_shipping_prices_include_tax', '__return_true' );
		add_filter(
			'woocommerce_shipping_method_add_rate_args',
			function ( $args ) {
				$args['cost'] = 10.08;
				return $args;
			}
		);
		$products = $this->create_products(
			array(
				array( $this->create_tax_class( 'High', '19.0000' ), ProductTaxStatus::TAXABLE, 50, false ),
				array( $this->create_tax_class( 'Low', '7.0000' ), ProductTaxStatus::TAXABLE, 50, false ),
			)
		);

		$this->fill_cart( $products );
		WC()->cart->calculate_totals();
		$order = $this->create_order_from_cart();

		// Each half of the 10.08 includes its tax: 0.80 at 19% and 0.33 at 7%. Rounding the net halves first gives 0.81 and 0.33.
		$this->assertEqualsWithDelta( 1.13, (float) WC()->cart->get_shipping_tax(), 0.001 );
		$this->assertEqualsWithDelta( 1.13, (float) $order->get_shipping_tax(), 0.001 );
	}

	/**
	 * @testdox Recalculating an order after its items change splits shipping by the items it has now.
	 *
	 * @testWith ["remove", 0.0]
	 *           ["quantity", 0.71]
	 *
	 * @param string $edit     Change made to the standard rate item.
	 * @param float  $expected Expected shipping tax after recalculating.
	 */
	public function test_recalculate_after_items_change( string $edit, float $expected ): void {
		$this->enable_split();
		$products = $this->create_products( array( array( '', ProductTaxStatus::TAXABLE, 10, false ), array( 'zero-rate', ProductTaxStatus::TAXABLE, 90, false ) ) );

		$this->fill_cart( $products );
		WC()->cart->calculate_totals();
		$order = $this->create_order_from_cart();
		$this->assertEqualsWithDelta( 0.2, (float) $order->get_shipping_tax(), 0.001 );

		foreach ( $order->get_items() as $item_id => $item ) {
			if ( $item->get_product_id() === $products[0]->get_id() ) {
				if ( 'remove' === $edit ) {
					$order->remove_item( $item_id );
				} else {
					$item->set_quantity( 5 );
					$item->set_subtotal( '50' );
					$item->set_total( '50' );
				}
			}
		}
		$order->calculate_totals();

		// With 5 standard rate items, 50 of the 140 is standard rate: 20% of 3.57.
		$this->assertEqualsWithDelta( $expected, (float) $order->get_shipping_tax(), 0.001 );
	}

	/**
	 * @testdox A negative fee counts shipping in the classes its tax was split across.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $in_cart Whether to check the cart or an order.
	 */
	public function test_negative_fee_tax( bool $in_cart ): void {
		$this->enable_split();
		$products = $this->create_products( array( array( '', ProductTaxStatus::TAXABLE, 10, false ), array( 'zero-rate', ProductTaxStatus::TAXABLE, 90, false ) ) );

		if ( $in_cart ) {
			add_action(
				'woocommerce_cart_calculate_fees',
				function ( $cart ) {
					$cart->add_fee( 'Discount', -10, true );
				}
			);
			$this->fill_cart( $products );
			WC()->cart->calculate_totals();
			$fees    = WC()->cart->get_fees();
			$fee_tax = reset( $fees )->tax;
		} else {
			$order = $this->create_order( $products );
			$fee   = new WC_Order_Item_Fee();
			$fee->set_total( '-10' );
			$order->add_item( $fee );
			$order->calculate_totals();
			$fee_tax = $fee->get_total_tax();
		}

		// Standard rate items and their 1.00 of shipping are 11 of the 110 total: 20% of -1.00.
		$this->assertEqualsWithDelta( -0.2, (float) $fee_tax, 0.001 );
	}

	/**
	 * @testdox A negative fee gets the same tax in the cart and the order when the rate was taxed per item and has no split of its own.
	 */
	public function test_negative_fee_tax_with_a_rate_taxed_per_item(): void {
		$this->enable_split();
		add_filter(
			'woocommerce_shipping_method_add_rate_args',
			function ( $args ) {
				return array_merge( $args, array( 'calc_tax' => 'per_item' ) );
			}
		);
		add_action(
			'woocommerce_cart_calculate_fees',
			function ( $cart ) {
				$cart->add_fee( 'Discount', -10, true );
			}
		);

		$this->fill_cart( $this->create_products( array( array( '', ProductTaxStatus::TAXABLE, 10, false ), array( 'zero-rate', ProductTaxStatus::TAXABLE, 90, false ) ) ) );
		WC()->cart->calculate_totals();
		$fees  = WC()->cart->get_fees();
		$order = $this->create_order_from_cart();
		WC()->checkout()->create_order_fee_lines( $order, WC()->cart );
		$order->calculate_totals();
		$order_fees = $order->get_fees();
		$order_fee  = reset( $order_fees );
		$this->assertInstanceOf( WC_Order_Item_Fee::class, $order_fee );

		// Shipping is split by the items on both sides: standard rate items and their 1.00 of shipping are 11 of the 110 total.
		$this->assertEqualsWithDelta( -0.2, (float) reset( $fees )->tax, 0.001 );
		$this->assertEqualsWithDelta( -0.2, (float) $order_fee->get_total_tax(), 0.001 );
	}

	/**
	 * @testdox A negative fee on a second cart, such as a recurring cart, counts shipping by that cart's items, not the main cart's.
	 */
	public function test_negative_fee_tax_on_a_second_cart(): void {
		$this->enable_split();
		add_filter(
			'woocommerce_shipping_method_add_rate_args',
			function ( $args ) {
				return array_merge( $args, array( 'calc_tax' => 'per_item' ) );
			}
		);
		$this->fill_cart( $this->create_products( array( array( '', ProductTaxStatus::TAXABLE, 100, false ) ) ) );

		$second_cart = new \WC_Cart();
		foreach ( $this->create_products( array( array( '', ProductTaxStatus::TAXABLE, 10, false ), array( 'zero-rate', ProductTaxStatus::TAXABLE, 90, false ) ) ) as $product ) {
			$second_cart->add_to_cart( $product->get_id() );
		}
		add_action(
			'woocommerce_cart_calculate_fees',
			function ( $cart ) use ( $second_cart ) {
				if ( $cart === $second_cart ) {
					$cart->add_fee( 'Discount', -10, true );
				}
			}
		);
		$second_cart->calculate_totals();
		$fees = $second_cart->get_fees();

		// The second cart's standard rate items and their 1.00 of shipping are 11 of the 110 total: 20% of -1.00.
		// Split by the main cart's items, all 10.00 of shipping would be standard rate, giving 20% of -1.82.
		$this->assertEqualsWithDelta( -0.2, (float) reset( $fees )->tax, 0.001 );
	}

	/**
	 * @testdox Items in a taxable class slugged non-taxable are still taxed on their share of shipping.
	 */
	public function test_class_slugged_non_taxable_is_taxed(): void {
		$this->enable_split();
		$slug = $this->create_tax_class( 'Non-taxable' );

		$this->fill_cart( $this->create_products( array( array( $slug, ProductTaxStatus::TAXABLE, 10, false ), array( '', ProductTaxStatus::TAXABLE, 10, false ) ) ) );
		WC()->cart->calculate_totals();

		// Both halves of the 10.00 are taxed at 20%.
		$this->assertEqualsWithDelta( 2.0, (float) WC()->cart->get_shipping_tax(), 0.001 );
		$this->assertEqualsWithDelta( 2.0, (float) $this->create_order_from_cart()->get_shipping_tax(), 0.001 );
	}

	/**
	 * @testdox A negative fee is taxed on the share of a taxable class slugged non-taxable.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $in_cart Whether to check the cart or an order.
	 */
	public function test_negative_fee_tax_with_a_class_slugged_non_taxable( bool $in_cart ): void {
		$this->enable_split();
		$products = $this->create_products( array( array( $this->create_tax_class( 'Non-taxable' ), ProductTaxStatus::TAXABLE, 10, false ), array( 'zero-rate', ProductTaxStatus::TAXABLE, 90, false ) ) );

		if ( $in_cart ) {
			add_action(
				'woocommerce_cart_calculate_fees',
				function ( $cart ) {
					$cart->add_fee( 'Discount', -10, true );
				}
			);
			$this->fill_cart( $products );
			WC()->cart->calculate_totals();
			$fees    = WC()->cart->get_fees();
			$fee_tax = reset( $fees )->tax;
		} else {
			$order = $this->create_order( $products );
			$fee   = new WC_Order_Item_Fee();
			$fee->set_total( '-10' );
			$order->add_item( $fee );
			$order->calculate_totals();
			$fee_tax = $fee->get_total_tax();
		}

		// The class's items and their 1.00 of shipping are 11 of the 110 total: 20% of -1.00.
		$this->assertEqualsWithDelta( -0.2, (float) $fee_tax, 0.001 );
	}

	/**
	 * @testdox A fixed shipping tax class wins over the split setting.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $in_cart Whether to check the cart or an order.
	 */
	public function test_fixed_shipping_tax_class_wins_over_the_split( bool $in_cart ): void {
		$this->enable_split();
		update_option( 'woocommerce_shipping_tax_class', $this->create_tax_class( 'Shipping' ) );
		$products = $this->create_products( array( array( 'zero-rate', ProductTaxStatus::TAXABLE, 100, false ) ) );

		if ( $in_cart ) {
			$this->fill_cart( $products );
			WC()->cart->calculate_totals();
			$shipping_tax = WC()->cart->get_shipping_tax();
		} else {
			$order = $this->create_order( $products );
			$order->calculate_totals();
			$shipping_tax = $order->get_shipping_tax();
		}

		// All 10.00 of shipping in the class at 20%, where a split by the zero rate items would give none.
		$this->assertEqualsWithDelta( 2.0, (float) $shipping_tax, 0.001 );
	}

	/**
	 * Turn on the split, with the shipping tax class left based on cart items.
	 */
	private function enable_split(): void {
		update_option( 'woocommerce_shipping_tax_class', 'inherit' );
		update_option( 'woocommerce_split_shipping_tax_by_item_value', 'yes' );
	}

	/**
	 * Ship one product in a package of its own and the rest in a second package.
	 *
	 * @param int $product_id Product to ship on its own.
	 */
	private function split_packages_by_product( int $product_id ): void {
		add_filter(
			'woocommerce_cart_shipping_packages',
			function ( $packages ) use ( $product_id ) {
				$first = $packages[0];
				$rest  = $packages[0];

				$first['contents'] = wp_list_filter( $packages[0]['contents'], array( 'product_id' => $product_id ) );
				$rest['contents']  = array_diff_key( $packages[0]['contents'], $first['contents'] );

				return array( $first, $rest );
			}
		);
	}

	/**
	 * Create a tax class with one rate, applied to shipping.
	 *
	 * @param string $name Class name.
	 * @param string $rate Tax rate.
	 * @return string The class slug.
	 */
	private function create_tax_class( string $name, string $rate = '20.0000' ): string {
		$tax_class = WC_Tax::create_tax_class( $name );
		$this->assertIsArray( $tax_class );
		WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => '',
				'tax_rate'          => $rate,
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => '1',
				'tax_rate_shipping' => '1',
				'tax_rate_class'    => $tax_class['slug'],
			)
		);

		return $tax_class['slug'];
	}

	/**
	 * Create one simple product per basket line.
	 *
	 * @param array $lines Lines of tax class, tax status, price and whether the product is virtual.
	 * @return WC_Product_Simple[]
	 */
	private function create_products( array $lines ): array {
		$products = array();

		foreach ( $lines as list( $tax_class, $tax_status, $price, $is_virtual ) ) {
			$product = new WC_Product_Simple();
			$product->set_props(
				array(
					'tax_class'     => $tax_class,
					'tax_status'    => $tax_status,
					'regular_price' => (string) $price,
					'virtual'       => $is_virtual,
				)
			);
			$product->save();
			$products[] = $product;
		}

		return $products;
	}

	/**
	 * Add the products to the cart and choose the flat rate.
	 *
	 * @param WC_Product_Simple[] $products         Products.
	 * @param string[]            $shipping_methods Chosen method per package.
	 */
	private function fill_cart( array $products, array $shipping_methods = array( 'flat_rate' ) ): void {
		foreach ( $products as $product ) {
			WC()->cart->add_to_cart( $product->get_id() );
		}
		WC()->session->set( 'chosen_shipping_methods', $shipping_methods );
	}

	/**
	 * Create an order of the products with a 10.00 flat rate shipping line.
	 *
	 * @param WC_Product_Simple[] $products Products.
	 * @return \WC_Order
	 */
	private function create_order( array $products ): \WC_Order {
		$order = wc_create_order();
		foreach ( $products as $product ) {
			$order->add_product( $product );
		}
		$shipping = new WC_Order_Item_Shipping();
		$shipping->set_method_id( 'flat_rate' );
		$shipping->set_total( '10' );
		$order->add_item( $shipping );

		return $order;
	}

	/**
	 * Create an order from the cart the way the Store API does, which recalculates the order's taxes.
	 *
	 * @return \WC_Order
	 */
	private function create_order_from_cart(): \WC_Order {
		$order = wc_create_order();
		WC()->checkout()->create_order_line_items( $order, WC()->cart );
		WC()->checkout()->create_order_shipping_lines( $order, WC()->session->get( 'chosen_shipping_methods' ), WC()->shipping()->get_packages() );
		$order->calculate_totals();

		return $order;
	}

	/**
	 * Create a 100% coupon for some of the products.
	 *
	 * @param WC_Product_Simple[] $products   Products.
	 * @param int[]               $free_lines Indexes of the products to make free.
	 * @return string Coupon code.
	 */
	private function create_coupon( array $products, array $free_lines ): string {
		$coupon = new WC_Coupon();
		$coupon->set_props(
			array(
				'code'          => 'free-lines',
				'discount_type' => 'percent',
				'amount'        => 100,
				'product_ids'   => array_map(
					function ( int $line ) use ( $products ): int {
						return $products[ $line ]->get_id();
					},
					$free_lines
				),
			)
		);
		$coupon->save();

		return $coupon->get_code();
	}
}
