<?php declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\OrderConfirmation;

use Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation\ShippingAddress as ShippingAddressBlock;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\Blocks\Package;

/**
 * Test ShippingAddress block class.
 */
final class ShippingAddress extends \WP_UnitTestCase {
	/**
	 * Field id registered for the duration of a test.
	 *
	 * @var string
	 */
	private $field_id = 'plugin-namespace/shipping-confirmation-field';

	/**
	 * Tear down test fixtures.
	 */
	public function tear_down() {
		__internal_woocommerce_blocks_deregister_checkout_field( $this->field_id );
		parent::tear_down();
	}

	/**
	 * @testdox Additional address field values with show_in_order_confirmation set to false are hidden from the block.
	 */
	public function test_hides_field_when_show_in_order_confirmation_is_false(): void {
		woocommerce_register_additional_checkout_field(
			array(
				'id'                         => $this->field_id,
				'label'                      => 'Hidden on confirmation',
				'location'                   => 'address',
				'show_in_order_confirmation' => false,
			)
		);

		$order = $this->create_order_with_field_value( 'secret value' );

		$this->assertStringNotContainsString( 'secret value', $this->render( $order ) );
	}

	/**
	 * @testdox Additional address field values with show_in_order_confirmation set to true (the default) are shown in the block.
	 */
	public function test_shows_field_when_show_in_order_confirmation_is_true(): void {
		woocommerce_register_additional_checkout_field(
			array(
				'id'       => $this->field_id,
				'label'    => 'Shown on confirmation',
				'location' => 'address',
			)
		);

		$order = $this->create_order_with_field_value( 'visible value' );

		$this->assertStringContainsString( 'visible value', $this->render( $order ) );
	}

	/**
	 * @testdox Shipping details render only for authorized orders that require a shipping address.
	 * @dataProvider shipping_address_topology_cases
	 *
	 * @param string       $topology Order fulfillment topology.
	 * @param string|false $permission View permission.
	 * @param bool         $expected_visible Whether shipping content should render.
	 */
	public function test_shipping_address_topology( string $topology, $permission, bool $expected_visible ): void {
		$original_shipping_option = get_option( 'woocommerce_calc_shipping', null );
		list( $order, $product )  = $this->create_topology_order( $topology );

		try {
			update_option( 'woocommerce_calc_shipping', 'yes' );
			$content = $this->render( $order, $permission );
			if ( 'physical' === $topology ) {
				$this->assertTrue( $order->needs_shipping_address(), 'A free-shipping order should require a shipping address.' );
			} else {
				$this->assertFalse( $order->needs_shipping_address(), 'Pickup and virtual orders should not require a shipping address.' );
			}

			if ( $expected_visible ) {
				$this->assertStringContainsString( 'Shipping Marker', $content, 'Shipping details should be non-empty for a shipped order.' );
			} else {
				$this->assertSame( '', $content, 'Shipping details should be empty for this topology or permission.' );
			}
		} finally {
			$order->delete( true );
			$product->delete( true );
			if ( null === $original_shipping_option ) {
				delete_option( 'woocommerce_calc_shipping' );
			} else {
				update_option( 'woocommerce_calc_shipping', $original_shipping_option );
			}
		}
	}

	/**
	 * Named shipping-address topology cases.
	 *
	 * @return array<string, array{string, string|false, bool}>
	 */
	public static function shipping_address_topology_cases(): array {
		return array(
			'physical free-shipping order' => array( 'physical', 'full', true ),
			'local pickup order'           => array( 'local-pickup', 'full', false ),
			'virtual downloadable order'   => array( 'virtual', 'full', false ),
			'no view permission'           => array( 'physical', false, false ),
		);
	}

	/**
	 * Create an order placed via the Store API, with a shipping address and a value persisted for the shipping group of the test field.
	 *
	 * @param string $value Field value.
	 * @return \WC_Order
	 */
	private function create_order_with_field_value( string $value ): \WC_Order {
		$order = \WC_Helper_Order::create_order();
		$order->set_created_via( 'store-api' );
		$order->set_shipping_first_name( 'Jeroen' );
		$order->set_shipping_last_name( 'Sormani' );
		$order->set_shipping_address_1( 'WooAddress' );
		$order->set_shipping_city( 'WooCity' );
		$order->set_shipping_state( 'NY' );
		$order->set_shipping_postcode( '12345' );
		$order->set_shipping_country( 'US' );
		Package::container()->get( CheckoutFields::class )->persist_field_for_order( $this->field_id, $value, $order, 'shipping', false );
		$order->save();

		return $order;
	}

	/**
	 * Render the shipping address block content for the given order with full view permissions.
	 *
	 * @param \WC_Order    $order Order object.
	 * @param string|false $permission View permission.
	 * @return string
	 */
	private function render( \WC_Order $order, $permission = 'full' ): string {
		$proxy = new class() extends ShippingAddressBlock {
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function __construct() {
			}
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function render_content_proxy( $order, $permission ) {
				return $this->render_content( $order, $permission );
			}
		};

		return $proxy->render_content_proxy( $order, $permission );
	}

	/**
	 * Create an order with a persisted shipping address and fulfillment topology.
	 *
	 * @param string $topology Order fulfillment topology.
	 * @return array{\WC_Order, \WC_Product}
	 */
	private function create_topology_order( string $topology ): array {
		$product = \WC_Helper_Product::create_simple_product(
			true,
			array(
				'downloadable' => 'virtual' === $topology,
				'virtual'      => 'virtual' === $topology,
			)
		);
		$product->set_virtual( 'virtual' === $topology );
		$product->save();

		$order = wc_create_order( array( 'customer_id' => 0 ) );
		$item  = new \WC_Order_Item_Product();
		$item->set_product( $product );
		$item->set_quantity( 1 );
		$order->add_item( $item );
		$order->set_shipping_first_name( 'Shipping' );
		$order->set_shipping_last_name( 'Marker' );
		$order->set_shipping_address_1( '500 Shipping Avenue' );
		$order->set_shipping_city( 'San Francisco' );
		$order->set_shipping_state( 'CA' );
		$order->set_shipping_postcode( '94105' );
		$order->set_shipping_country( 'US' );

		if ( 'virtual' !== $topology ) {
			$shipping_item = new \WC_Order_Item_Shipping();
			$shipping_item->set_method_title( 'local-pickup' === $topology ? 'Local pickup' : 'Free shipping' );
			$shipping_item->set_method_id( 'local-pickup' === $topology ? 'local_pickup' : 'free_shipping' );
			$order->add_item( $shipping_item );
		}

		$order->save();

		return array( $order, $product );
	}
}
