<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Tax;

use Automattic\WooCommerce\Enums\ProductTaxStatus;
use Automattic\WooCommerce\Enums\ShippingTaxClass;
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
	 *           [10.0, {"": 1, "zero-rate": 1, "non-taxable": 1}, {"": 3.34, "zero-rate": 3.33, "non-taxable": 3.33}]
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
	 * @testdox Shipping tax at checkout follows the shipping tax class setting.
	 *
	 * @dataProvider provide_baskets
	 *
	 * @param string $shipping_tax_class Shipping tax class option.
	 * @param array  $lines              Lines of tax class, tax status, price and whether the product is virtual.
	 * @param int[]  $free_lines         Lines a 100% coupon makes free.
	 * @param float  $expected           Expected shipping tax.
	 */
	public function test_cart_shipping_tax( string $shipping_tax_class, array $lines, array $free_lines, float $expected ): void {
		update_option( 'woocommerce_shipping_tax_class', $shipping_tax_class );
		$products = $this->create_products( $lines );

		$this->fill_cart( $products );
		if ( $free_lines ) {
			WC()->cart->apply_coupon( $this->create_coupon( $products, $free_lines ) );
		}
		WC()->cart->calculate_totals();

		$this->assertEqualsWithDelta( $expected, (float) WC()->cart->get_shipping_tax(), 0.001 );
	}

	/**
	 * @testdox Shipping tax on an order recalculation follows the shipping tax class setting.
	 *
	 * @dataProvider provide_baskets
	 *
	 * @param string $shipping_tax_class Shipping tax class option.
	 * @param array  $lines              Lines of tax class, tax status, price and whether the product is virtual.
	 * @param int[]  $free_lines         Lines a 100% coupon makes free.
	 * @param float  $expected           Expected shipping tax.
	 */
	public function test_order_shipping_tax( string $shipping_tax_class, array $lines, array $free_lines, float $expected ): void {
		update_option( 'woocommerce_shipping_tax_class', $shipping_tax_class );
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
		$split    = ShippingTaxClass::SPLIT_BY_ITEM_VALUE;
		$standard = array( '', ProductTaxStatus::TAXABLE, 10, false );
		$zero     = array( 'zero-rate', ProductTaxStatus::TAXABLE, 90, false );

		return array(
			'based on cart items'      => array( ShippingTaxClass::INHERIT, array( $standard, $zero ), array(), 2.0 ),
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
		update_option( 'woocommerce_shipping_tax_class', ShippingTaxClass::SPLIT_BY_ITEM_VALUE );

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
	 * @testdox Each package is split by its own items, and the order created from the cart keeps that split.
	 */
	public function test_split_per_package(): void {
		update_option( 'woocommerce_shipping_tax_class', ShippingTaxClass::SPLIT_BY_ITEM_VALUE );
		$products = $this->create_products(
			array(
				array( 'zero-rate', ProductTaxStatus::TAXABLE, 90, false ),
				array( '', ProductTaxStatus::TAXABLE, 10, false ),
				array( 'zero-rate', ProductTaxStatus::TAXABLE, 40, false ),
			)
		);
		$first_id = $products[0]->get_id();
		add_filter(
			'woocommerce_cart_shipping_packages',
			function ( $packages ) use ( $first_id ) {
				$first = $packages[0];
				$rest  = $packages[0];

				$first['contents'] = wp_list_filter( $packages[0]['contents'], array( 'product_id' => $first_id ) );
				$rest['contents']  = array_diff_key( $packages[0]['contents'], $first['contents'] );

				return array( $first, $rest );
			}
		);

		$this->fill_cart( $products, array( 'flat_rate', 'flat_rate' ) );
		WC()->cart->calculate_totals();

		// The zero rate package pays no tax, and 10 of the other package's 50 is standard rate: 20% of 2.00.
		$this->assertEqualsWithDelta( 0.4, (float) WC()->cart->get_shipping_tax(), 0.001 );
		$this->assertEqualsWithDelta( 0.4, (float) $this->create_order_from_cart()->get_shipping_tax(), 0.001 );
	}

	/**
	 * @testdox With shipping costs entered with tax, the order created from the cart keeps the cart's shipping tax.
	 */
	public function test_split_with_shipping_costs_including_tax(): void {
		update_option( 'woocommerce_shipping_tax_class', ShippingTaxClass::SPLIT_BY_ITEM_VALUE );
		add_filter( 'woocommerce_shipping_prices_include_tax', '__return_true' );

		$this->fill_cart( $this->create_products( array( array( '', ProductTaxStatus::TAXABLE, 50, false ), array( '', ProductTaxStatus::NONE, 50, false ) ) ) );
		WC()->cart->calculate_totals();
		$order = $this->create_order_from_cart();

		// Half of the 10.00 includes 20% tax.
		$this->assertEqualsWithDelta( 0.83, (float) WC()->cart->get_shipping_tax(), 0.001 );
		$this->assertEqualsWithDelta( 0.83, (float) $order->get_shipping_tax(), 0.001 );
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
		update_option( 'woocommerce_shipping_tax_class', ShippingTaxClass::SPLIT_BY_ITEM_VALUE );
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
