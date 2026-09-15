<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Mocks;

/**
 * A custom payment token type with its own display name, as an extension would define one.
 */
class PaymentTokenMock extends \WC_Payment_Token {

	/**
	 * Token type.
	 *
	 * @var string
	 */
	protected $type = 'checkout_test';

	/**
	 * Get the token's display name.
	 *
	 * @param string $deprecated Deprecated argument.
	 * @return string
	 */
	public function get_display_name( $deprecated = '' ) {
		return 'Checkout test account ending in 9876';
	}
}
