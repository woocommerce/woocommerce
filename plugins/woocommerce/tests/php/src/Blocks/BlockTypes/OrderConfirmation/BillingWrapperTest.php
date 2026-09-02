<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\OrderConfirmation;

use Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation\BillingWrapper as BillingWrapperBlock;
use WC_Order;
use WC_Order_Item_Product;
use WC_Order_Item_Shipping;
use WC_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the order-confirmation billing wrapper.
 */
final class BillingWrapperTest extends WC_Unit_Test_Case {
	/**
	 * @testdox Billing wrapper headings and content require an address and permission across fulfillment topologies.
	 * @dataProvider billing_wrapper_cases
	 *
	 * @param string       $topology Order fulfillment topology.
	 * @param string|false $permission View permission.
	 * @param bool         $has_address Whether the order has a billing address.
	 * @param bool         $expected_visible Whether wrapper content should render.
	 */
	public function test_billing_wrapper_topology( string $topology, $permission, bool $has_address, bool $expected_visible ): void {
		list( $order, $product ) = $this->create_order( $topology, $has_address );
		$content                 = '<h2>Billing address</h2><p>Billing wrapper marker</p>';

		try {
			$rendered = $this->render( $order, $permission, $content );

			if ( $expected_visible ) {
				$this->assertSame( $content, $rendered, 'An authorized order with billing data should preserve the wrapper heading and content.' );
				$this->assertStringContainsString( 'Billing address', $rendered );
				$this->assertStringContainsString( 'Billing wrapper marker', $rendered );
			} else {
				$this->assertSame( '', $rendered, 'The billing wrapper should be empty without permission or an address.' );
			}
		} finally {
			$order->delete( true );
			$product->delete( true );
		}
	}

	/**
	 * Named billing-wrapper cases.
	 *
	 * @return array<string, array{string, string|false, bool, bool}>
	 */
	public static function billing_wrapper_cases(): array {
		return array(
			'physical free-shipping order' => array( 'physical', 'full', true, true ),
			'local pickup order'           => array( 'local-pickup', 'full', true, true ),
			'virtual downloadable order'   => array( 'virtual', 'full', true, true ),
			'no view permission'           => array( 'physical', false, true, false ),
			'no billing address'           => array( 'physical', 'full', false, false ),
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
		$proxy = new class() extends BillingWrapperBlock {
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
	 * @param bool   $has_address Whether to add billing data.
	 * @return array{WC_Order, WC_Product}
	 */
	private function create_order( string $topology, bool $has_address ): array {
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
			$order->set_billing_first_name( 'Billing' );
			$order->set_billing_last_name( 'Marker' );
			$order->set_billing_address_1( '500 Billing Avenue' );
			$order->set_billing_city( 'San Francisco' );
			$order->set_billing_state( 'CA' );
			$order->set_billing_postcode( '94105' );
			$order->set_billing_country( 'US' );
		}

		if ( 'virtual' !== $topology ) {
			$shipping_item = new WC_Order_Item_Shipping();
			$shipping_item->set_method_title( 'local-pickup' === $topology ? 'Local pickup' : 'Free shipping' );
			$shipping_item->set_method_id( 'local-pickup' === $topology ? 'local_pickup' : 'free_shipping' );
			$order->add_item( $shipping_item );
		}

		$order->save();

		return array( $order, $product );
	}
}
