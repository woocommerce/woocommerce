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
	 * @testdox Should output the label and formatted value below the billing address only.
	 *
	 * @testWith ["billing", "<dl class=\"wc-block-components-additional-fields-list\"><dt>CPF/CNPJ</dt><dd>529.982.247-25</dd></dl>"]
	 *           ["shipping", ""]
	 *
	 * @param string $address_type Address being shown.
	 * @param string $expected     Expected output.
	 */
	public function test_outputs_value_below_billing_address( string $address_type, string $expected ): void {
		$order = new WC_Order();
		$order->update_meta_data( '_wc_regulatory_br_tax_id', '52998224725' );

		ob_start();
		$this->sut->handle_woocommerce_order_details_after_customer_address( $address_type, $order );

		$this->assertSame( $expected, ob_get_clean() );
	}

	/**
	 * @testdox Should output nothing for an order without a value.
	 */
	public function test_outputs_nothing_without_value(): void {
		$this->assertSame( '', $this->sut->get_html( new WC_Order() ) );
	}
}
