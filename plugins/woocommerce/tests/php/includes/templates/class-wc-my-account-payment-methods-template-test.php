<?php
/**
 * Tests for the My Account payment methods template.
 */

declare( strict_types = 1 );

/**
 * My Account payment methods template test.
 */
class WC_My_Account_Payment_Methods_Template_Test extends WC_Unit_Test_Case {

	/**
	 * @testdox Should render a saved method of a custom token type that provides no card brand, with its actions.
	 */
	public function test_renders_custom_token_without_brand(): void {
		add_filter(
			'woocommerce_payment_token_class',
			static function ( $class_name, $type ) {
				return FakeCustomPaymentToken::TYPE === $type ? FakeCustomPaymentToken::class : $class_name;
			},
			10,
			2
		);

		$user_id = self::factory()->user->create();
		wp_set_current_user( $user_id );

		$token = new FakeCustomPaymentToken();
		$token->set_token( 'brandless-token' );
		$token->set_gateway_id( WC_Gateway_BACS::ID );
		$token->set_user_id( $user_id );
		$token->save();

		$html = wc_get_template_html( 'myaccount/payment-methods.php' );

		$this->assertStringContainsString( 'payment-method-method', $html, 'The method column should render for a brand-less token.' );
		$this->assertStringContainsString( 'class="button delete"', $html, 'The Delete action should render for a brand-less token.' );
		$this->assertStringNotContainsString( 'Undefined array key', $html, 'The template should not emit PHP notices for a brand-less token.' );
	}
}
