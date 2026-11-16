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
	 * Reset the mocks and the event queue after each test.
	 */
	public function tear_down(): void {
		global $wc_get_order_mock_return, $wc_is_order_received_page;

		unset( $GLOBALS['wc_cart_mock'] );
		$wc_get_order_mock_return  = false;
		$wc_is_order_received_page = false;
		set_query_var( 'order-received', null );

		$reflection = new \ReflectionClass( WC_Analytics_Tracking::class );
		$property   = $reflection->getProperty( 'event_queue' );
		$property->setAccessible( true );
		$property->setValue( null, array() );

		parent::tear_down();
	}

	/**
	 * Test that the cart and checkout events carry the cart line count.
	 */
	public function test_cart_checkout_event_properties_carry_cart_line_count(): void {
		$GLOBALS['wc_cart_mock'] = $this->fractional_cart();

		$properties = ( new Universal() )->get_cart_checkout_event_properties();

		$this->assertSame( 2, $properties['products_count'] );
	}

	/**
	 * Test that the order confirmation event carries the order line count.
	 */
	public function test_order_confirmation_view_carries_order_line_count(): void {
		global $wc_get_order_mock_return, $wc_is_order_received_page;

		$wc_get_order_mock_return  = $this->fractional_order();
		$wc_is_order_received_page = true;
		set_query_var( 'order-received', 123 );

		( new Universal() )->capture_order_confirmation_view();

		$queue = WC_Analytics_Tracking::get_event_queue();
		$this->assertNotEmpty( $queue );
		$event = end( $queue );
		$this->assertSame( 'order_confirmation_view', $event['eventName'] );
		$this->assertSame( 2, $event['props']['products_count'] );
	}

	/**
	 * Price values and the pp prop they should produce; null means no pp prop.
	 *
	 * @return array
	 */
	public static function price_provider(): array {
		return array(
			'decimal price'     => array( '19.99', '19.99' ),
			'free product'      => array( '0', '0' ),
			'float from filter' => array( 19.99, '19.99' ),
			'int from filter'   => array( 10, '10' ),
			'no price'          => array( '', null ),
			'filtered label'    => array( 'Call for price', null ),
		);
	}

	/**
	 * Test that pp is a numeric string, or left out when the product has no numeric price.
	 *
	 * @dataProvider price_provider
	 *
	 * @param string|float|int $price    What WC_Product::get_price() returns.
	 * @param string|null      $expected The expected pp, or null when pp should be absent.
	 */
	#[DataProvider( 'price_provider' )]
	public function test_product_details_price( $price, $expected ): void {
		$product = new class( $price ) {
			private $price;

			public function __construct( $price ) {
				$this->price = $price;
			}

			public function get_id() {
				return 13;
			}

			public function get_title() {
				return 'Cotton fabric';
			}

			public function get_price() {
				return $this->price;
			}

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

	/**
	 * A cart holding 1.5 yards of fabric and one pack of buttons.
	 *
	 * @return object
	 */
	private function fractional_cart() {
		return new class() {
			public function get_cart() {
				return array(
					'a' => array( 'data' => null, 'quantity' => 1.5 ),
					'b' => array( 'data' => null, 'quantity' => 1 ),
				);
			}

			public function get_coupons() {
				return array();
			}

			public function get_total( $context = 'view' ) {
				return 0;
			}

			public function get_subtotal() {
				return 0;
			}

			public function get_shipping_total() {
				return 0;
			}

			public function get_taxes_total() {
				return 0;
			}

			public function get_discount_total() {
				return 0;
			}
		};
	}

	/**
	 * An order holding 1.5 yards of fabric and one pack of buttons.
	 *
	 * @return \WC_Order
	 */
	private function fractional_order() {
		$order = new \WC_Order();
		$order->set_items(
			array(
				array( 'data' => null, 'quantity' => 1.5 ),
				array( 'data' => null, 'quantity' => 1 ),
			)
		);
		return $order;
	}
}
