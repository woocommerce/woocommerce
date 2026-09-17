<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\OrderConfirmation;

use Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation\ShippingWrapper as ShippingWrapperBlock;
use WC_Order;
use WC_Order_Item_Product;
use WC_Order_Item_Shipping;
use WC_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the order-confirmation shipping wrapper.
 */
final class ShippingWrapperTest extends WC_Unit_Test_Case {
	/**
	 * @testdox Shipping wrapper headings and content render only for an authorized shipped order.
	 * @dataProvider shipping_wrapper_cases
	 *
	 * @param string       $topology Order fulfillment topology.
	 * @param string|false $permission View permission.
	 * @param bool         $has_address Whether the order has a shipping address.
	 * @param bool         $expected_visible Whether wrapper content should render.
	 */
	public function test_shipping_wrapper_topology( string $topology, $permission, bool $has_address, bool $expected_visible ): void {
		$content = '<h2>Shipping address</h2><p>Shipping wrapper marker</p>';

		try {
			// Inside the try: create_order() makes the Checkout block the store
			// checkout partway through, which has core register Local Pickup, so a
			// throw after that point must still reach the finally below.
			$order = $this->create_order( $topology, $has_address );
			update_option( 'woocommerce_calc_shipping', 'yes' );
			$rendered = $this->render( $order, $permission, $content );

			if ( 'physical' === $topology ) {
				$this->assertTrue( $order->needs_shipping_address(), 'A free-shipping order should require a shipping address.' );
			} else {
				$this->assertFalse( $order->needs_shipping_address(), 'Pickup and virtual orders should not require a shipping address.' );
			}

			if ( $expected_visible ) {
				$this->assertSame( $content, $rendered, 'An authorized shipped order should preserve the wrapper heading and content.' );
			} else {
				$this->assertSame( '', $rendered, 'The shipping wrapper should be empty for pickup, virtual, missing-address, or unauthorized orders.' );
			}
		} finally {
			if ( 'pickup-location' === $topology ) {
				// Drop the memo so the next test reloads the ambient method list.
				WC()->shipping()->unregister_shipping_methods();
			}
		}
	}

	/**
	 * Named shipping-wrapper cases.
	 *
	 * @return array<string, array{string, string|false, bool, bool}>
	 */
	public static function shipping_wrapper_cases(): array {
		return array(
			'physical free-shipping order' => array( 'physical', 'full', true, true ),
			'classic local pickup order'   => array( 'local-pickup', 'full', true, false ),
			'blocks pickup location order' => array( 'pickup-location', 'full', true, false ),
			'virtual downloadable order'   => array( 'virtual', 'full', true, false ),
			'no view permission'           => array( 'physical', false, true, false ),
			'no shipping address'          => array( 'physical', 'full', false, false ),
		);
	}

	/**
	 * Render wrapper content through a public proxy.
	 *
	 * @param WC_Order     $order Order object.
	 * @param string|false $permission View permission.
	 * @param string       $content Wrapper content.
	 * @return string
	 */
	private function render( WC_Order $order, $permission, string $content ): string {
		$proxy = new class() extends ShippingWrapperBlock {
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function __construct() {
			}
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function render_content_proxy( $order, $permission, $content ) {
				return $this->render_content( $order, $permission, array(), $content );
			}
		};

		return $proxy->render_content_proxy( $order, $permission, $content );
	}

	/**
	 * Create a persisted order for one fulfillment topology.
	 *
	 * @param string $topology Order fulfillment topology.
	 * @param bool   $has_address Whether to add shipping data.
	 * @return WC_Order
	 */
	private function create_order( string $topology, bool $has_address ): WC_Order {
		$product = \WC_Helper_Product::create_simple_product(
			true,
			array(
				'downloadable' => 'virtual' === $topology,
				'virtual'      => 'virtual' === $topology,
			)
		);
		$order   = wc_create_order( array( 'customer_id' => 0 ) );
		$item    = new WC_Order_Item_Product();
		$item->set_product( $product );
		$item->set_quantity( 1 );
		$order->add_item( $item );

		if ( $has_address ) {
			$order->set_shipping_first_name( 'Shipping' );
			$order->set_shipping_last_name( 'Marker' );
			$order->set_shipping_address_1( '500 Shipping Avenue' );
			$order->set_shipping_city( 'San Francisco' );
			$order->set_shipping_state( 'CA' );
			$order->set_shipping_postcode( '94105' );
			$order->set_shipping_country( 'US' );
		}

		if ( 'virtual' !== $topology ) {
			$shipping_item = new WC_Order_Item_Shipping();
			// `local_pickup` is the classic pickup method, `pickup_location` the Blocks one.
			// `needs_shipping_address()` hides the address for both, but it finds
			// `pickup_location` only by asking the registered shipping methods which of them
			// support `local-pickup`. Core registers that method when the Checkout block is
			// the store checkout, so make it the store checkout and let core register it.
			// Registering PickupLocation here would supply the very thing this asserts.
			$pickup_methods = array(
				'local-pickup'    => array( 'Local pickup', 'local_pickup' ),
				'pickup-location' => array( 'Pickup Location', 'pickup_location' ),
			);

			if ( 'pickup-location' === $topology ) {
				$checkout_page_id = self::factory()->post->create(
					array(
						'post_type'    => 'page',
						'post_status'  => 'publish',
						'post_content' => '<!-- wp:woocommerce/checkout --><div class="wp-block-woocommerce-checkout"></div><!-- /wp:woocommerce/checkout -->',
					)
				);
				update_option( 'woocommerce_checkout_page_id', $checkout_page_id );

				// `ShippingController::register_local_pickup()` runs on
				// `woocommerce_load_shipping_methods` and asks
				// `is_checkout_block_default()` at that moment. The method list is already
				// populated by now, so it has to be reloaded after the option changes.
				WC()->shipping()->load_shipping_methods();

				$shipping_methods = WC()->shipping()->get_shipping_methods();
				$this->assertArrayHasKey(
					'pickup_location',
					$shipping_methods,
					'Local Pickup should be registered by core once the Checkout block is the store checkout.'
				);
			}

			list( $method_title, $method_id ) = $pickup_methods[ $topology ] ?? array( 'Free shipping', 'free_shipping' );

			$shipping_item->set_method_title( $method_title );
			$shipping_item->set_method_id( $method_id );
			$order->add_item( $shipping_item );
		}

		$order->save();

		return $order;
	}
}
