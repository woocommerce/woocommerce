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
		require_once WC_ABSPATH . 'includes/admin/wc-meta-box-functions.php';
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
	 * @testdox Should output a column with an Edit link, "Not provided" and an empty input for an order billed to Brazil that has no value yet.
	 */
	public function test_outputs_column_without_value_for_brazil_order(): void {
		$order = new WC_Order();
		$order->set_billing_country( 'BR' );

		$html = $this->render_section( $order );

		$this->assertStringStartsWith( '<div class="order_data_column order_data_column_regulatory"><h3>Additional required information <a href="#" class="edit_address">Edit</a></h3>', $html );
		$this->assertStringContainsString( '<div class="address"><p class="none_set"><strong>CPF/CNPJ:</strong> Not provided.</p></div>', $html );
		$this->assertMatchesRegularExpression( '/<div class="edit_address">.*name="' . self::META_KEY . '"[^>]*value=""/s', $html );
	}

	/**
	 * @testdox Should show a stored value formatted, as text and in the input, even when the field no longer applies to the order.
	 */
	public function test_shows_stored_value_when_field_no_longer_applies(): void {
		$order = new WC_Order();
		$order->set_billing_country( 'US' );
		$order->update_meta_data( self::META_KEY, '52998224725' );

		$html = $this->render_section( $order );

		$this->assertStringContainsString( '<div class="address"><p><strong>CPF/CNPJ:</strong> 529.982.247-25</p></div>', $html );
		$this->assertMatchesRegularExpression( '/name="' . self::META_KEY . '"[^>]*value="529\.982\.247-25"/', $html );
	}

	/**
	 * @testdox Should output nothing, not even the column, for an order the fields do not apply to and that has no value.
	 */
	public function test_outputs_nothing_for_other_orders(): void {
		$order = new WC_Order();
		$order->set_billing_country( 'US' );

		$this->assertSame( '', $this->render_section( $order ) );
	}

	/**
	 * @testdox Should hand the field to the order screen's save routine only while an order is being saved, so it is never listed with the billing details.
	 */
	public function test_billing_fields_include_field_only_while_saving(): void {
		$order = new WC_Order();
		$order->set_billing_country( 'BR' );
		$sut = $this->sut;

		$this->assertSame( array(), $sut->handle_woocommerce_admin_billing_fields( array(), $order ), 'Not listed when the screen is shown' );

		$fields_while_saving = null;
		add_action(
			'woocommerce_process_shop_order_meta',
			function () use ( $sut, $order, &$fields_while_saving ) {
				$fields_while_saving = $sut->handle_woocommerce_admin_billing_fields( array(), $order );
			}
		);
		remove_all_actions( 'woocommerce_process_shop_order_meta', 40 );
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Documented in WC_Admin_Meta_Boxes::save_meta_boxes().
		do_action( 'woocommerce_process_shop_order_meta', 0, $order );

		$this->assertArrayHasKey( self::META_KEY, $fields_while_saving );
		$this->assertSame( array( $sut, 'update_order_value' ), $fields_while_saving[ self::META_KEY ]['update_callback'] );
	}

	/**
	 * Render the column for an order.
	 *
	 * @param WC_Order $order Order.
	 * @return string
	 */
	private function render_section( WC_Order $order ): string {
		ob_start();
		$this->sut->render_column( $order );

		return (string) ob_get_clean();
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
