<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\RegulatoryFields;

use Automattic\WooCommerce\Internal\RegulatoryFields\ClassicCheckout;
use Automattic\WooCommerce\Internal\RegulatoryFields\RegulatoryFields;
use WC_Helper_Product;
use WC_Order;
use WC_Unit_Test_Case;
use WP_Error;

/**
 * Tests for the ClassicCheckout class.
 */
class ClassicCheckoutTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ClassicCheckout
	 */
	private $sut;

	/**
	 * Set up test fixtures. The cart holds a virtual product, so the billing country decides which fields apply.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new ClassicCheckout();
		$this->sut->init( new RegulatoryFields() );

		$product = WC_Helper_Product::create_simple_product( true, array( 'virtual' => true ) );
		WC()->cart->add_to_cart( $product->get_id() );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		try {
			WC()->cart->empty_cart();
			WC()->customer->set_billing_country( '' );
			unset( $_POST['regulatory_br_tax_id'], $_POST['post_data'] );
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Should output a titled section with the CPF/CNPJ input, in the same column layout as the address forms, when the cart is billed to Brazil.
	 */
	public function test_outputs_field_for_brazil(): void {
		WC()->customer->set_billing_country( 'BR' );

		ob_start();
		$this->sut->handle_woocommerce_checkout_after_customer_details();
		$html = ob_get_clean();

		$this->assertStringStartsWith( '<div class="woocommerce-regulatory-fields"><div class="col2-set"><div class="col-1"><h3>Additional required information</h3>', $html );
		$this->assertStringContainsString( 'name="regulatory_br_tax_id"', $html );
		$this->assertStringContainsString( 'CPF/CNPJ', $html );
	}

	/**
	 * @testdox Should output an empty wrapper when no fields apply, so a later refresh has an element to replace.
	 */
	public function test_outputs_empty_wrapper_for_other_countries(): void {
		WC()->customer->set_billing_country( 'US' );

		ob_start();
		$this->sut->handle_woocommerce_checkout_after_customer_details();
		$html = ob_get_clean();

		$this->assertSame( '<div class="woocommerce-regulatory-fields"></div>', $html );
	}

	/**
	 * @testdox Should return the fields as a fragment that keeps the value already typed in the form.
	 */
	public function test_fragment_keeps_typed_value(): void {
		WC()->customer->set_billing_country( 'BR' );
		$_POST['post_data'] = 'billing_country=BR&regulatory_br_tax_id=529.982';

		$fragments = $this->sut->handle_woocommerce_update_order_review_fragments( array() );

		$this->assertArrayHasKey( '.woocommerce-regulatory-fields', $fragments );
		$this->assertStringContainsString( 'value="529.982"', $fragments['.woocommerce-regulatory-fields'] );
	}

	/**
	 * @testdox Should add the submitted value to the posted checkout data.
	 */
	public function test_posted_data_includes_submitted_value(): void {
		$_POST['regulatory_br_tax_id'] = ' 529.982.247-25 ';

		$data = $this->sut->handle_woocommerce_checkout_posted_data( array( 'billing_country' => 'BR' ) );

		$this->assertArrayHasKey( 'regulatory_fields', $data );
		$this->assertSame( '529.982.247-25', $data['regulatory_fields']['br_tax_id'] );
	}

	/**
	 * @testdox Should add a checkout error pointing at the input when the value is missing or not valid for Brazil.
	 *
	 * @testWith [""]
	 *           ["529.982.247-26"]
	 *
	 * @param string $submitted Submitted value.
	 */
	public function test_validation_rejects_bad_value_for_brazil( string $submitted ): void {
		WC()->customer->set_billing_country( 'BR' );
		$errors = new WP_Error();

		$this->sut->handle_woocommerce_after_checkout_validation( array( 'regulatory_fields' => array( 'br_tax_id' => $submitted ) ), $errors );

		$this->assertSame( array( 'regulatory_br_tax_id' ), $errors->get_error_codes() );
		$this->assertSame( array( 'id' => 'regulatory_br_tax_id' ), $errors->get_error_data( 'regulatory_br_tax_id' ) );
	}

	/**
	 * @testdox Should add no checkout error for a valid value in Brazil, or for any value where no fields apply.
	 *
	 * @testWith ["BR", "529.982.247-25"]
	 *           ["US", ""]
	 *           ["US", "not a number"]
	 *
	 * @param string $billing_country Billing country.
	 * @param string $submitted       Submitted value.
	 */
	public function test_validation_accepts( string $billing_country, string $submitted ): void {
		WC()->customer->set_billing_country( $billing_country );
		$errors = new WP_Error();

		$this->sut->handle_woocommerce_after_checkout_validation( array( 'regulatory_fields' => array( 'br_tax_id' => $submitted ) ), $errors );

		$this->assertFalse( $errors->has_errors() );
	}

	/**
	 * @testdox Should store the normalized value on the new order for Brazil and nothing where no fields apply.
	 *
	 * @testWith ["BR", "52998224725"]
	 *           ["US", ""]
	 *
	 * @param string $billing_country Billing country.
	 * @param string $expected        Expected stored value.
	 */
	public function test_create_order_stores_value( string $billing_country, string $expected ): void {
		WC()->customer->set_billing_country( $billing_country );
		$order = new WC_Order();

		$this->sut->handle_woocommerce_checkout_create_order( $order, array( 'regulatory_fields' => array( 'br_tax_id' => '529.982.247-25' ) ) );

		$this->assertSame( $expected, $order->get_meta( '_wc_regulatory_br_tax_id' ) );
	}
}
