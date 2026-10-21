<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Shipping;

use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Shipping\ShippingController;

/**
 * Tests for what a collected order shows the shopper on the order received page and emails.
 *
 * `ShippingController::show_local_pickup_details()` filters `woocommerce_order_shipping_to_display`
 * so a pickup order says where to collect from and what to do on arrival, in place of a delivery
 * line. Only an end-to-end test exercised this before; these pin it at the unit level.
 */
class ShowLocalPickupDetailsTest extends \WC_Unit_Test_Case {

	/**
	 * The controller under test.
	 *
	 * @var ShippingController
	 */
	private $controller;

	/**
	 * Build the controller from the container, as the plugin does.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->controller = new ShippingController(
			Package::container()->get( Api::class ),
			Package::container()->get( AssetDataRegistry::class )
		);
	}

	/**
	 * Build an order carrying one shipping line of the given method, with the given meta.
	 *
	 * @param string $method_id Shipping method id, e.g. 'pickup_location' or 'flat_rate'.
	 * @param array  $meta      Meta keys and values to set on the shipping line.
	 * @return \WC_Order
	 */
	private function order_shipped_by( string $method_id, array $meta = array() ): \WC_Order {
		$order = new \WC_Order();

		$item = new \WC_Order_Item_Shipping();
		$item->set_method_title( ucfirst( $method_id ) );
		$item->set_method_id( $method_id );
		$item->set_total( '0' );
		foreach ( $meta as $key => $value ) {
			$item->add_meta_data( $key, $value, true );
		}
		$order->add_item( $item );
		$order->save();

		return $order;
	}

	/**
	 * A collected order replaces the delivery line with where to collect from, the branch address,
	 * and the merchant's arrival note.
	 *
	 * @testdox A pickup order shows where to collect from, its address and its details.
	 */
	public function test_a_pickup_order_shows_where_to_collect_from(): void {
		$order = $this->order_shipped_by(
			'pickup_location',
			array(
				'pickup_location' => 'Downtown Store',
				'pickup_address'  => '12 High Street,London,W1',
				'pickup_details'  => 'Ring the bell on arrival.',
			)
		);

		$shown = $this->controller->show_local_pickup_details( 'Flat rate', $order );

		$this->assertStringContainsString( 'Collection from', $shown, 'A collected order should say so.' );
		$this->assertStringContainsString( 'Downtown Store', $shown, 'It should name the branch.' );
		$this->assertStringContainsString( 'Ring the bell on arrival.', $shown, 'And carry the arrival note.' );
		$this->assertStringContainsString( '12 High Street, London, W1', $shown, 'And the branch address, with its commas spaced.' );
		$this->assertStringNotContainsString( 'Flat rate', $shown, 'The delivery line it replaces should be gone.' );
	}

	/**
	 * When the merchant charges for pickup, the cost reaches the shopper on the line too.
	 *
	 * @testdox A priced pickup order shows the pickup cost.
	 */
	public function test_a_priced_pickup_order_shows_the_cost(): void {
		update_option( 'woocommerce_tax_display_cart', 'excl' );

		$order = new \WC_Order();
		$item  = new \WC_Order_Item_Shipping();
		$item->set_method_title( 'Pickup' );
		$item->set_method_id( 'pickup_location' );
		$item->set_total( '5' );
		$item->add_meta_data( 'pickup_location', 'Downtown Store', true );
		$order->add_item( $item );
		$order->save();

		$shown = $this->controller->show_local_pickup_details( 'Flat rate', $order );

		$this->assertStringContainsString( 'Pickup cost:', $shown, 'A charged pickup should show its cost.' );
		$this->assertStringContainsString(
			wc_price( 5, array( 'currency' => $order->get_currency() ) ),
			$shown,
			'And the formatted amount, not only the label, so a zero or wrong total is caught.'
		);
	}

	/**
	 * A delivered order is left exactly as the rest of the store rendered it.
	 *
	 * @testdox A delivered order is left as it was.
	 */
	public function test_a_delivered_order_is_left_as_it_was(): void {
		$order = $this->order_shipped_by( 'flat_rate' );

		$this->assertSame(
			'Flat rate: £5.00',
			$this->controller->show_local_pickup_details( 'Flat rate: £5.00', $order ),
			'A delivered order should keep the shipping line it was given.'
		);
	}

	/**
	 * An order placed before collection meta existed, or whose pickup location was later removed,
	 * carries a pickup method with no meta to read. The details fall back to the line the store
	 * already rendered rather than erroring or showing an empty "Collection from".
	 *
	 * @testdox A pickup order with no collection meta falls back to the original line.
	 */
	public function test_a_pickup_order_with_no_collection_meta_falls_back(): void {
		$order = $this->order_shipped_by( 'pickup_location' );

		$this->assertSame(
			'Flat rate',
			$this->controller->show_local_pickup_details( 'Flat rate', $order ),
			'With no location, address or details to show, the original line should be kept.'
		);
	}

	/**
	 * The filter can run with something that is not an order, and leaves it untouched.
	 *
	 * @testdox Something that is not an order is left untouched.
	 */
	public function test_a_non_order_is_left_untouched(): void {
		$this->assertSame(
			'unchanged',
			$this->controller->show_local_pickup_details( 'unchanged', null ),
			'With no order to read, the value should pass through.'
		);
	}
}
