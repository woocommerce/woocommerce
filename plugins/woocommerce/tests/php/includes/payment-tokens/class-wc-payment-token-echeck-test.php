<?php
declare( strict_types = 1 );

/**
 * Class WC_Payment_Token_ECheck_Test file.
 *
 * @package WooCommerce\Tests\Payment_Tokens
 */

/**
 * Tests for the WC_Payment_Token_ECheck class.
 */
class WC_Payment_Token_ECheck_Test extends WC_Unit_Test_Case {

	/**
	 * @testdox Should report each missing eCheck field with its current value.
	 *
	 * @testWith [{}, []]
	 *           [{"last4": ""}, {"last4": ""}]
	 *           [{"token": "", "last4": ""}, {"token": "", "last4": ""}]
	 *
	 * @param array $props           Props to override on an otherwise valid token.
	 * @param array $expected_fields Expected invalid fields.
	 */
	public function test_get_invalid_token_fields( array $props, array $expected_fields ): void {
		$token = new WC_Payment_Token_ECheck();
		$token->set_props(
			array_merge(
				array(
					'token' => 'tok_123',
					'last4' => '6789',
				),
				$props
			)
		);

		$this->assertSame( $expected_fields, $token->get_invalid_token_fields(), 'Unexpected invalid fields' );
		$this->assertSame( array() === $expected_fields, $token->validate(), 'validate() should fail  when invalid fields are reported' );
	}
}
