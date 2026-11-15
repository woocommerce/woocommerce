<?php
/**
 * Fake custom payment token helper.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types = 1 );

/**
 * A custom payment token type with its own display name and no card details, as a payment extension would define one.
 *
 * Map the type to this class for the duration of a test:
 *
 *     add_filter( 'woocommerce_payment_token_class', fn( $class_name, $type ) => FakeCustomPaymentToken::TYPE === $type ? FakeCustomPaymentToken::class : $class_name, 10, 2 );
 */
class FakeCustomPaymentToken extends WC_Payment_Token {

	/**
	 * The token type this class represents.
	 */
	public const TYPE = 'fake_custom';

	/**
	 * The display name every token of this type returns.
	 */
	public const DISPLAY_NAME = 'Test bank account ending in 9876';

	/**
	 * Token type.
	 *
	 * @var string
	 */
	protected $type = self::TYPE;

	/**
	 * Get the token's display name.
	 *
	 * @param string $deprecated Deprecated argument.
	 * @return string
	 */
	public function get_display_name( $deprecated = '' ) {
		return self::DISPLAY_NAME;
	}
}
