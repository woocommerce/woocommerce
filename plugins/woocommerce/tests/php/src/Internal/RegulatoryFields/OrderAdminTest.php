<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\RegulatoryFields;

use Automattic\WooCommerce\Internal\RegulatoryFields\OrderAdmin;
use Automattic\WooCommerce\Internal\RegulatoryFields\RegulatoryFields;
use WC_Admin_Meta_Boxes;
use WC_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the OrderAdmin class.
 */
class OrderAdminTest extends WC_Unit_Test_Case {

	private const META_KEY = '_wc_regulatory_br_tax_id';

	/**
	 * The System Under Test.
	 *
	 * @var OrderAdmin
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new OrderAdmin();
		$this->sut->init( new RegulatoryFields() );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		try {
			WC_Admin_Meta_Boxes::$meta_box_errors = array();
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Should add an empty CPF/CNPJ input to an order billed to Brazil that has no value yet.
	 */
	public function test_adds_empty_field_for_brazil_order(): void {
		$order = new WC_Order();
		$order->set_billing_country( 'BR' );

		$fields = $this->sut->handle_woocommerce_admin_billing_fields( array(), $order );

		$this->assertArrayHasKey( self::META_KEY, $fields );
		$this->assertSame( self::META_KEY, $fields[ self::META_KEY ]['id'] );
		$this->assertSame( 'CPF/CNPJ', $fields[ self::META_KEY ]['label'] );
		$this->assertSame( '', $fields[ self::META_KEY ]['value'] );
	}

	/**
	 * @testdox Should show a stored value formatted, even when the field no longer applies to the order.
	 */
	public function test_shows_stored_value_when_field_no_longer_applies(): void {
		$order = new WC_Order();
		$order->set_billing_country( 'US' );
		$order->update_meta_data( self::META_KEY, '52998224725' );

		$fields = $this->sut->handle_woocommerce_admin_billing_fields( array(), $order );

		$this->assertSame( '529.982.247-25', $fields[ self::META_KEY ]['value'] );
	}

	/**
	 * @testdox Should add nothing to an order the fields do not apply to and that has no value.
	 */
	public function test_adds_nothing_for_other_orders(): void {
		$order = new WC_Order();
		$order->set_billing_country( 'US' );

		$this->assertSame( array(), $this->sut->handle_woocommerce_admin_billing_fields( array(), $order ) );
	}

	/**
	 * @testdox Should store a valid value without punctuation.
	 */
	public function test_update_stores_valid_value(): void {
		$order = new WC_Order();

		$this->sut->update_order_value( self::META_KEY, '529.982.247-25', $order );

		$this->assertSame( '52998224725', $order->get_meta( self::META_KEY ) );
		$this->assertSame( array(), WC_Admin_Meta_Boxes::$meta_box_errors );
	}

	/**
	 * @testdox Should keep the stored value and report an error when the new value is not valid.
	 */
	public function test_update_rejects_invalid_value(): void {
		$order = new WC_Order();
		$order->update_meta_data( self::META_KEY, '52998224725' );

		$this->sut->update_order_value( self::META_KEY, '529.982.247-26', $order );

		$this->assertSame( '52998224725', $order->get_meta( self::META_KEY ) );
		$this->assertCount( 1, WC_Admin_Meta_Boxes::$meta_box_errors );
	}

	/**
	 * @testdox Should clear the stored value when the merchant empties the input, without an error.
	 */
	public function test_update_clears_value(): void {
		$order = new WC_Order();
		$order->update_meta_data( self::META_KEY, '52998224725' );

		$this->sut->update_order_value( self::META_KEY, '', $order );

		$this->assertSame( '', $order->get_meta( self::META_KEY ) );
		$this->assertSame( array(), WC_Admin_Meta_Boxes::$meta_box_errors );
	}
}
