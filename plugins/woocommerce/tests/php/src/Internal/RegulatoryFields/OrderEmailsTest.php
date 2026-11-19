<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\RegulatoryFields;

use Automattic\WooCommerce\Internal\RegulatoryFields\OrderEmails;
use Automattic\WooCommerce\Internal\RegulatoryFields\RegulatoryFields;
use WC_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the OrderEmails class.
 */
class OrderEmailsTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var OrderEmails
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new OrderEmails();
		$this->sut->init( new RegulatoryFields() );
	}

	/**
	 * @testdox Should output the label and formatted value below the billing address, for the customer and the merchant.
	 *
	 * @testWith ["billing", false, false, "<br><strong>CPF/CNPJ</strong>: 529.982.247-25"]
	 *           ["billing", true, false, "<br><strong>CPF/CNPJ</strong>: 529.982.247-25"]
	 *           ["billing", false, true, "CPF/CNPJ: 529.982.247-25\n"]
	 *           ["shipping", false, false, ""]
	 *
	 * @param string $address_type  Address being shown.
	 * @param bool   $sent_to_admin Whether the email goes to the merchant.
	 * @param bool   $plain_text    Whether the email is plain text.
	 * @param string $expected      Expected output.
	 */
	public function test_outputs_value_below_billing_address( string $address_type, bool $sent_to_admin, bool $plain_text, string $expected ): void {
		$order = new WC_Order();
		$order->update_meta_data( '_wc_regulatory_br_tax_id', '52998224725' );

		ob_start();
		$this->sut->handle_woocommerce_email_customer_address_section( $address_type, $order, $sent_to_admin, $plain_text );

		$this->assertSame( $expected, ob_get_clean() );
	}
}
