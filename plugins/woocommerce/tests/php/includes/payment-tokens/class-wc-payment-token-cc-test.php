<?php
declare( strict_types = 1 );

/**
 * Class WC_Payment_Token_CC_Test file.
 *
 * @package WooCommerce\Tests\Payment_Tokens
 */

/**
 * Tests for the WC_Payment_Token_CC class.
 */
class WC_Payment_Token_CC_Test extends WC_Unit_Test_Case {

	/**
	 * @testdox Should report each missing or malformed card field with its current value.
	 *
	 * @testWith [{}, []]
	 *           [{"token": ""}, {"token": ""}]
	 *           [{"last4": ""}, {"last4": ""}]
	 *           [{"expiry_year": ""}, {"expiry_year": ""}]
	 *           [{"expiry_year": "30"}, {"expiry_year": "30"}]
	 *           [{"expiry_month": null}, {"expiry_month": ""}]
	 *           [{"expiry_month": "888"}, {"expiry_month": "888"}]
	 *           [{"card_type": ""}, {"card_type": ""}]
	 *           [{"token": "", "last4": "", "expiry_year": "30", "expiry_month": null, "card_type": ""}, {"token": "", "last4": "", "expiry_year": "30", "expiry_month": "", "card_type": ""}]
	 *
	 * @param array $props           Props to override on an otherwise valid token. A null value leaves that prop unset.
	 * @param array $expected_fields Expected invalid fields.
	 */
	public function test_get_invalid_token_fields( array $props, array $expected_fields ): void {
		$props = array_merge(
			array(
				'token'        => 'tok_123',
				'last4'        => '4242',
				'expiry_year'  => '2030',
				'expiry_month' => '08',
				'card_type'    => 'visa',
			),
			$props
		);

		// set_expiry_month() pads '' to '00', so a missing month can only come from never calling the setter.
		$token = new WC_Payment_Token_CC();
		$token->set_props(
			array_filter(
				$props,
				static function ( $value ) {
					return null !== $value;
				}
			)
		);

		$this->assertSame( $expected_fields, $token->get_invalid_token_fields(), 'Unexpected invalid fields' );
		$this->assertSame( array() === $expected_fields, $token->validate(), 'validate() should fail when invalid fields are reported' );
	}
}
