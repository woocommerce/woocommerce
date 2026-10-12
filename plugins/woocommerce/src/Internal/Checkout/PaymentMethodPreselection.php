<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Checkout;

/**
 * Tells the checkout whether a payment method should be preselected for the shopper.
 *
 * Some countries require the shopper to actively choose a payment method, so merchants
 * can turn preselection off. The option is on by default, matching the historic behavior.
 *
 * @since 11.3.0
 */
class PaymentMethodPreselection {

	/**
	 * Name of the option that stores the merchant's choice ('yes' or 'no').
	 */
	public const OPTION_NAME = 'woocommerce_checkout_preselect_payment_method';

	/**
	 * Whether the checkout should preselect a payment method.
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		return wc_string_to_bool( get_option( self::OPTION_NAME, 'yes' ) );
	}
}
