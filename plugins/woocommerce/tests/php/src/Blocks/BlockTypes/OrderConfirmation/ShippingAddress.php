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
	 * @testdox The shipping phone reads as entered after wptexturize() runs over the block output.
	 */
	public function test_shipping_phone_survives_wptexturize(): void {
		$order = \WC_Helper_Order::create_order();
		$order->set_shipping_address_1( 'WooAddress' );
		$order->set_shipping_city( 'WooCity' );
		$order->set_shipping_postcode( '12345' );
		$order->set_shipping_country( 'US' );
		$order->set_shipping_phone( '555--0200' );
		$order->save();

		$text = html_entity_decode( wp_strip_all_tags( wptexturize( $this->render( $order ) ) ), ENT_QUOTES | ENT_HTML5 );

		$this->assertStringContainsString( '555--0200', $text, 'The phone should not be texturized into an en dash.' );
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
	 * @param \WC_Order $order Order object.
	 * @return string
	 */
	private function render( \WC_Order $order ): string {
		$proxy = new class() extends ShippingAddressBlock {
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function __construct() {
			}
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function render_content_proxy( $order, $permission ) {
				return $this->render_content( $order, $permission );
			}
		};

		return $proxy->render_content_proxy( $order, 'full' );
	}
}
