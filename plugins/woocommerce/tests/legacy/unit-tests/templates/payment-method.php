<?php
/**
 * Tests for the checkout/payment-method.php template.
 *
 * @package WooCommerce\Tests\Templates
 */

declare( strict_types = 1 );

/**
 * Class WC_Tests_Template_Payment_Method
 *
 * Regression coverage for woo#53551 / RSMAPGJ-432: the order-pay page rendered
 * the payment method list inside a content area that runs `wpautop()`, which
 * turned the blank line between the radio input and the <label> into an empty
 * <p></p> tag and wrapped the label text in an additional, useless paragraph.
 *
 * The template should not introduce blank lines or stray newlines between the
 * radio input and its label, so applying `wpautop()` to its rendered output
 * produces no empty <p> tags inside the <li>.
 */
class WC_Tests_Template_Payment_Method extends WC_Unit_Test_Case {

	/**
	 * Render the payment-method.php template for a given gateway and return the
	 * resulting HTML.
	 *
	 * @param WC_Payment_Gateway $gateway Gateway to render the markup for.
	 * @return string
	 */
	private function render_template( WC_Payment_Gateway $gateway ) {
		ob_start();
		wc_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) );
		return ob_get_clean();
	}

	/**
	 * Build a minimal mock gateway with neither fields nor a description, which
	 * is the simplest configuration that hits the markup at the top of the
	 * template (the radio input + label only, no payment_box <div>).
	 *
	 * @return WC_Payment_Gateway
	 */
	private function make_minimal_gateway() {
		$gateway              = new WC_Mock_Payment_Gateway();
		$gateway->id          = 'wc_tests_minimal_gateway';
		$gateway->chosen      = false;
		$gateway->title       = 'Minimal Test Gateway';
		$gateway->description = '';
		$gateway->has_fields  = false;
		return $gateway;
	}

	/**
	 * Regression test for woo#53551: ensure rendering the template through
	 * `wpautop()` (the same filter applied by the_content on the order-pay
	 * page) does not yield empty <p></p> tags inside the rendered <li>.
	 */
	public function test_template_output_does_not_produce_empty_paragraphs_via_wpautop() {
		$gateway = $this->make_minimal_gateway();
		$html    = $this->render_template( $gateway );

		// The template output itself must not contain an empty paragraph.
		$this->assertDoesNotMatchRegularExpression(
			'#<p[^>]*>\s*</p>#i',
			$html,
			'Raw template output should not contain empty <p> tags.'
		);

		// And running wpautop over it (mirroring the_content on order-pay)
		// must not introduce one either. This is the exact symptom reported.
		$autop = wpautop( $html );
		$this->assertDoesNotMatchRegularExpression(
			'#<p[^>]*>\s*</p>#i',
			$autop,
			'wpautop() applied to the template output should not create empty <p> tags.'
		);
	}

	/**
	 * The radio input and the label must remain on the same logical line (no
	 * intervening blank line). A blank line between them is what wpautop()
	 * historically converted into an empty paragraph on the order-pay page.
	 */
	public function test_no_blank_line_between_radio_input_and_label() {
		$gateway = $this->make_minimal_gateway();
		$html    = $this->render_template( $gateway );

		// Match the </input>-equivalent self-closing radio followed by any
		// whitespace up to the opening of the <label>. There must be no
		// double-newline (blank line) in that gap.
		if ( preg_match( '#/>\s*<label\b#s', $html, $matches ) ) {
			$this->assertStringNotContainsString( "\n\n", $matches[0], 'No blank line should separate the radio input and the label.' );
		} else {
			$this->fail( 'Expected to find the radio input followed by a <label> in the rendered template output.' );
		}
	}
}
