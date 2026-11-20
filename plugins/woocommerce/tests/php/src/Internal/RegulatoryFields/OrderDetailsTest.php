<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\RegulatoryFields;

use Automattic\WooCommerce\Internal\RegulatoryFields\OrderDetails;
use Automattic\WooCommerce\Internal\RegulatoryFields\RegulatoryFields;
use WC_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the OrderDetails class.
 */
class OrderDetailsTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var OrderDetails
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new OrderDetails();
		$this->sut->init( new RegulatoryFields() );
	}

	/**
	 * @testdox Should output a section with a heading, the label and the formatted value after the customer details.
	 */
	public function test_outputs_own_section_after_customer_details(): void {
		$order = new WC_Order();
		$order->update_meta_data( '_wc_regulatory_br_tax_id', '52998224725' );

		ob_start();
		$this->sut->handle_woocommerce_order_details_after_customer_details( $order );

		$this->assertSame(
			'<section class="wc-block-order-confirmation-regulatory-fields"><h2 class="wp-block-heading">Additional required information</h2><dl class="wc-block-components-additional-fields-list"><dt>CPF/CNPJ</dt><dd>529.982.247-25</dd></dl></section>',
			ob_get_clean()
		);
	}

	/**
	 * @testdox Should output nothing, not even the heading, for an order without a value.
	 */
	public function test_outputs_nothing_without_value(): void {
		$this->assertSame( '', $this->sut->get_section_html( new WC_Order() ) );
	}

	/**
	 * @testdox Should place the section above the Additional information section on the shortcode order pages.
	 */
	public function test_section_comes_before_additional_information(): void {
		$this->sut->register();

		$this->assertSame( 5, has_action( 'woocommerce_order_details_after_customer_details', array( $this->sut, 'handle_woocommerce_order_details_after_customer_details' ) ) );
	}
}
