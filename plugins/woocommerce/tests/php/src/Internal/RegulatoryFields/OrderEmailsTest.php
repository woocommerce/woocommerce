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
	 * @testdox Should output a section with a heading, the label and the formatted value, for the customer and the merchant.
	 *
	 * @testWith [false, false, "<h2>Additional required information</h2><ul class=\"additional-fields\" style=\"margin-bottom: 40px;\"><li><strong>CPF/CNPJ</strong>: 529.982.247-25</li></ul>"]
	 *           [true, false, "<h2>Additional required information</h2><ul class=\"additional-fields\" style=\"margin-bottom: 40px;\"><li><strong>CPF/CNPJ</strong>: 529.982.247-25</li></ul>"]
	 *           [false, true, "\nADDITIONAL REQUIRED INFORMATION\n\nCPF/CNPJ: 529.982.247-25\n"]
	 *
	 * @param bool   $sent_to_admin Whether the email goes to the merchant.
	 * @param bool   $plain_text    Whether the email is plain text.
	 * @param string $expected      Expected output.
	 */
	public function test_outputs_own_section( bool $sent_to_admin, bool $plain_text, string $expected ): void {
		$order = new WC_Order();
		$order->update_meta_data( '_wc_regulatory_br_tax_id', '52998224725' );

		ob_start();
		$this->sut->handle_woocommerce_email_customer_details( $order, $sent_to_admin, $plain_text );

		$this->assertSame( $expected, ob_get_clean() );
	}

	/**
	 * @testdox Should output nothing, not even the heading, for an order without a value.
	 */
	public function test_outputs_nothing_without_value(): void {
		ob_start();
		$this->sut->handle_woocommerce_email_customer_details( new WC_Order() );

		$this->assertSame( '', ob_get_clean() );
	}

	/**
	 * @testdox Should place the section after the addresses and above the Additional information section.
	 */
	public function test_section_sits_between_addresses_and_additional_information(): void {
		$this->sut->register();

		$this->assertSame( 25, has_action( 'woocommerce_email_customer_details', array( $this->sut, 'handle_woocommerce_email_customer_details' ) ) );
	}
}
