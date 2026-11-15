<?php
declare( strict_types = 1 );

/**
 * Class WC_Abstract_Payment_Token_Test file.
 *
 * @package WooCommerce\Tests\Abstracts
 */

/**
 * Tests for the WC_Payment_Token abstract class.
 */
class WC_Abstract_Payment_Token_Test extends WC_Unit_Test_Case {

	/**
	 * @testdox Should report the token field as invalid only when it is empty.
	 *
	 * @testWith ["tok_123", []]
	 *           ["", {"token": ""}]
	 *
	 * @param string $raw_token       Token value to set.
	 * @param array  $expected_fields Expected invalid fields.
	 */
	public function test_get_invalid_token_fields( string $raw_token, array $expected_fields ): void {
		$token = new WC_Payment_Token_Stub();
		$token->set_token( $raw_token );

		$this->assertSame( $expected_fields, $token->get_invalid_token_fields(), 'Unexpected invalid fields' );
		$this->assertSame( array() === $expected_fields, $token->validate(), 'validate() should fail when invalid fields are reported' );
	}
}
