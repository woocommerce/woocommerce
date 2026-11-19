<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\RegulatoryFields;

use Automattic\WooCommerce\Internal\RegulatoryFields\RegulatoryFields;
use WC_Helper_Product;
use WC_Helper_Shipping;
use WC_Order;
use WC_Order_Item_Shipping;
use WC_Unit_Test_Case;
use WP_Error;

/**
 * Tests for the RegulatoryFields class.
 */
class RegulatoryFieldsTest extends WC_Unit_Test_Case {

	private const VALID_CPF = '52998224725';

	/**
	 * The System Under Test.
	 *
	 * @var RegulatoryFields
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new RegulatoryFields();
		update_option( 'woocommerce_default_country', 'US:CA' );
		WC_Helper_Shipping::create_simple_flat_rate();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		try {
			WC()->cart->empty_cart();
			WC_Helper_Shipping::delete_simple_flat_rate();
			WC()->session->set( 'chosen_shipping_methods', null );
			WC()->customer->set_billing_country( '' );
			WC()->customer->set_shipping_country( '' );
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Should return the CPF/CNPJ field for Brazil and no fields for a country without rules.
	 */
	public function test_get_fields_for_country(): void {
		$brazil = $this->sut->get_fields_for_country( 'BR' );

		$this->assertArrayHasKey( 'br_tax_id', $brazil );
		$this->assertTrue( $brazil['br_tax_id']['required'] );
		$this->assertSame( array(), $this->sut->get_fields_for_country( 'US' ) );
	}

	/**
	 * @testdox Should use the shipping country for a cart that ships, whatever the billing country.
	 *
	 * @testWith ["BR", "US", "BR"]
	 *           ["US", "BR", "US"]
	 *           ["", "BR", "BR"]
	 *
	 * @param string $shipping_country Customer shipping country.
	 * @param string $billing_country  Customer billing country.
	 * @param string $expected         Expected country.
	 */
	public function test_get_cart_country_for_shipped_cart( string $shipping_country, string $billing_country, string $expected ): void {
		$this->add_product_to_cart( false );
		WC()->customer->set_shipping_country( $shipping_country );
		WC()->customer->set_billing_country( $billing_country );
		WC()->session->set( 'chosen_shipping_methods', array( 'flat_rate:1' ) );

		$this->assertSame( $expected, $this->sut->get_cart_country() );
	}

	/**
	 * @testdox Should use the billing country for a cart with nothing to ship.
	 */
	public function test_get_cart_country_for_virtual_cart(): void {
		$this->add_product_to_cart( true );
		WC()->customer->set_shipping_country( 'US' );
		WC()->customer->set_billing_country( 'BR' );

		$this->assertSame( 'BR', $this->sut->get_cart_country() );
	}

	/**
	 * @testdox Should use the chosen pickup location's country, or the store country when the pickup method has no address.
	 *
	 * @testWith ["pickup_location:1", "BR"]
	 *           ["pickup_location:0", "PT"]
	 *           ["local_pickup:1", "US"]
	 *
	 * @param string $chosen_rate Chosen shipping rate ID.
	 * @param string $expected    Expected country.
	 */
	public function test_get_cart_country_for_local_pickup( string $chosen_rate, string $expected ): void {
		$this->save_pickup_locations();
		$this->add_product_to_cart( false );
		WC()->customer->set_shipping_country( 'AR' );
		WC()->customer->set_billing_country( 'AR' );
		WC()->session->set( 'chosen_shipping_methods', array( $chosen_rate ) );

		$this->assertSame( $expected, $this->sut->get_cart_country() );
	}

	/**
	 * @testdox Should use an order's shipping country when it has a shipping line that is not local pickup.
	 */
	public function test_get_order_country_for_shipped_order(): void {
		$order = $this->create_order( 'US', 'BR' );
		$this->add_shipping_line( $order, 'flat_rate' );

		$this->assertSame( 'BR', $this->sut->get_order_country( $order ) );
	}

	/**
	 * @testdox Should use an order's billing country when it has no shipping line.
	 */
	public function test_get_order_country_for_order_without_shipping(): void {
		$order = $this->create_order( 'BR', 'US' );

		$this->assertSame( 'BR', $this->sut->get_order_country( $order ) );
	}

	/**
	 * @testdox Should ignore items of another type that a filter adds to an order's shipping lines.
	 */
	public function test_get_order_country_ignores_non_shipping_items(): void {
		$order = $this->create_order( 'BR', 'US' );
		add_filter( 'woocommerce_order_get_items', fn( $items ) => array_merge( $items, array( new \WC_Order_Item_Fee() ) ) );

		$this->assertSame( 'BR', $this->sut->get_order_country( $order ) );
	}

	/**
	 * @testdox Should use the pickup address stored on a local pickup order, or the store country without one.
	 *
	 * @testWith ["BR", "BR"]
	 *           ["", "US"]
	 *
	 * @param string $pickup_country Country stored on the shipping line, or empty for none.
	 * @param string $expected       Expected country.
	 */
	public function test_get_order_country_for_local_pickup_order( string $pickup_country, string $expected ): void {
		$order = $this->create_order( 'AR', 'AR' );
		$this->add_shipping_line( $order, 'pickup_location', '' === $pickup_country ? null : array( 'country' => $pickup_country ) );

		$this->assertSame( $expected, $this->sut->get_order_country( $order ) );
	}

	/**
	 * @testdox Should return the normalized value and no error for a valid value.
	 */
	public function test_get_valid_values_normalizes_a_valid_value(): void {
		$errors = new WP_Error();

		$values = $this->sut->get_valid_values( $this->sut->get_fields_for_country( 'BR' ), array( 'br_tax_id' => '529.982.247-25' ), $errors );

		$this->assertSame( array( 'br_tax_id' => self::VALID_CPF ), $values );
		$this->assertFalse( $errors->has_errors() );
	}

	/**
	 * @testdox Should report an error under the field ID when a required value is missing, not valid, or not a string.
	 *
	 * @dataProvider provider_rejected_values
	 *
	 * @param mixed $submitted Submitted values.
	 */
	public function test_get_valid_values_rejects( $submitted ): void {
		$errors = new WP_Error();

		$values = $this->sut->get_valid_values( $this->sut->get_fields_for_country( 'BR' ), $submitted, $errors );

		$this->assertSame( array(), $values );
		$this->assertSame( array( 'br_tax_id' ), $errors->get_error_codes() );
	}

	/**
	 * Submitted values that must be rejected.
	 *
	 * @return array<string, array<mixed>>
	 */
	public function provider_rejected_values(): array {
		return array(
			'missing key'        => array( array() ),
			'empty string'       => array( array( 'br_tax_id' => '' ) ),
			'only punctuation'   => array( array( 'br_tax_id' => ' .-/ ' ) ),
			'wrong check digits' => array( array( 'br_tax_id' => '529.982.247-26' ) ),
			'array value'        => array( array( 'br_tax_id' => array( self::VALID_CPF ) ) ),
			'values not array'   => array( self::VALID_CPF ),
		);
	}

	/**
	 * @testdox Should ignore submitted values when no fields apply.
	 */
	public function test_get_valid_values_without_fields(): void {
		$errors = new WP_Error();

		$values = $this->sut->get_valid_values( array(), array( 'br_tax_id' => self::VALID_CPF ), $errors );

		$this->assertSame( array(), $values );
		$this->assertFalse( $errors->has_errors() );
	}

	/**
	 * @testdox Should store a value on an order and return it formatted for display.
	 */
	public function test_set_order_values_stores_value(): void {
		$order = $this->create_order( 'BR', 'BR' );

		$this->sut->set_order_values( $order, array( 'br_tax_id' => self::VALID_CPF ) );
		$order->save();
		$order = wc_get_order( $order->get_id() );

		$this->assertSame( self::VALID_CPF, $order->get_meta( '_wc_regulatory_br_tax_id' ) );
		$this->assertSame( array( 'br_tax_id' => self::VALID_CPF ), $this->sut->get_order_values( $order ) );
		$display = $this->sut->get_order_display_values( $order );
		$this->assertSame( 'CPF/CNPJ', $display['br_tax_id']['label'] );
		$this->assertSame( '529.982.247-25', $display['br_tax_id']['value'] );
	}

	/**
	 * @testdox Should clear a stored value when the new values do not include the field.
	 */
	public function test_set_order_values_clears_value_left_out(): void {
		$order = $this->create_order( 'BR', 'BR' );
		$this->sut->set_order_values( $order, array( 'br_tax_id' => self::VALID_CPF ) );

		$this->sut->set_order_values( $order, array() );

		$this->assertSame( array(), $this->sut->get_order_values( $order ) );
		$this->assertSame( array(), $this->sut->get_order_display_values( $order ) );
	}

	/**
	 * Add one product to the cart.
	 *
	 * @param bool $is_virtual Whether the product is virtual, so the cart needs no shipping.
	 */
	private function add_product_to_cart( bool $is_virtual ): void {
		$product = WC_Helper_Product::create_simple_product( true, array( 'virtual' => $is_virtual ) );
		WC()->cart->add_to_cart( $product->get_id() );
	}

	/**
	 * Create an unsaved order.
	 *
	 * @param string $billing_country  Billing country.
	 * @param string $shipping_country Shipping country.
	 * @return WC_Order
	 */
	private function create_order( string $billing_country, string $shipping_country ): WC_Order {
		$order = new WC_Order();
		$order->set_billing_country( $billing_country );
		$order->set_shipping_country( $shipping_country );

		return $order;
	}

	/**
	 * Add a shipping line to an order.
	 *
	 * @param WC_Order                   $order          Order.
	 * @param string                     $method_id      Shipping method ID.
	 * @param array<string, string>|null $pickup_address Pickup location address to store on the line.
	 */
	private function add_shipping_line( WC_Order $order, string $method_id, ?array $pickup_address = null ): void {
		$shipping_line = new WC_Order_Item_Shipping();
		$shipping_line->set_method_id( $method_id );

		if ( null !== $pickup_address ) {
			$shipping_line->add_meta_data( '_pickup_location_address', $pickup_address );
		}

		$order->add_item( $shipping_line );
	}

	/**
	 * Save two pickup locations: Lisbon at index 0 and São Paulo at index 1.
	 */
	private function save_pickup_locations(): void {
		update_option(
			'pickup_location_pickup_locations',
			array(
				array(
					'name'    => 'Lisbon',
					'address' => array( 'country' => 'PT' ),
					'details' => '',
					'enabled' => true,
				),
				array(
					'name'    => 'São Paulo',
					'address' => array( 'country' => 'BR' ),
					'details' => '',
					'enabled' => true,
				),
			)
		);
	}
}
