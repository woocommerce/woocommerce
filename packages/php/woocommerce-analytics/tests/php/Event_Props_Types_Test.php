<?php
/**
 * Tests that event props fit the types the analytics pipeline stores them as.
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic\Woocommerce_Analytics;

use PHPUnit\Framework\Attributes\DataProvider;
use WorDBless\BaseTestCase;

/**
 * Tests for products_count and pp.
 */
class Event_Props_Types_Test extends BaseTestCase {

	/**
	 * Clear the cart mock after each test.
	 */
	public function tear_down(): void {
		unset( $GLOBALS['wc_cart_mock'] );
		parent::tear_down();
	}

	/**
	 * Test that products_count counts cart lines, not their (possibly fractional) quantities.
	 */
	public function test_cart_items_count_counts_products_not_quantities(): void {
		$GLOBALS['wc_cart_mock'] = new class() {
			/**
			 * Cart contents: 1.5 yards of fabric and one pack of buttons.
			 *
			 * @return array
			 */
			public function get_cart() {
				return array(
					'a' => array( 'quantity' => 1.5 ),
					'b' => array( 'quantity' => 1 ),
				);
			}
		};

		$this->assertSame( 2, ( new Universal() )->get_cart_items_count() );
	}

	/**
	 * Test that the order confirmation products_count counts order lines, not their quantities.
	 */
	public function test_order_items_count_counts_products_not_quantities(): void {
		$order = new \WC_Order();
		$order->set_items( array( 'fabric', 'buttons' ) );

		$this->assertSame( 2, ( new Universal() )->get_order_items_count( $order ) );
	}

	/**
	 * Price values and the pp prop they should produce; null means no pp prop.
	 *
	 * @return array
	 */
	public static function price_provider(): array {
		return array(
			'decimal price'  => array( '19.99', 19.99 ),
			'whole price'    => array( '10', 10.0 ),
			'free product'   => array( '0', 0.0 ),
			'no price'       => array( '', null ),
			'filtered label' => array( 'Call for price', null ),
		);
	}

	/**
	 * Test that pp is a number, or left out when the product has no numeric price.
	 *
	 * @dataProvider price_provider
	 *
	 * @param string     $price    What WC_Product::get_price() returns.
	 * @param float|null $expected The expected pp, or null when pp should be absent.
	 */
	#[DataProvider( 'price_provider' )]
	public function test_product_details_price( $price, $expected ): void {
		$product = new class( $price ) {
			/**
			 * Product price.
			 *
			 * @var string
			 */
			private $price;

			/**
			 * Constructor.
			 *
			 * @param string $price Product price.
			 */
			public function __construct( $price ) {
				$this->price = $price;
			}

			/**
			 * Get ID.
			 *
			 * @return int
			 */
			public function get_id() {
				return 13;
			}

			/**
			 * Get title.
			 *
			 * @return string
			 */
			public function get_title() {
				return 'Cotton fabric';
			}

			/**
			 * Get price.
			 *
			 * @return string
			 */
			public function get_price() {
				return $this->price;
			}

			/**
			 * Get type.
			 *
			 * @return string
			 */
			public function get_type() {
				return 'simple';
			}
		};

		$details = ( new Universal() )->get_product_details( $product );

		if ( null === $expected ) {
			$this->assertArrayNotHasKey( 'pp', $details );
		} else {
			$this->assertSame( $expected, $details['pp'] );
		}
	}
}
