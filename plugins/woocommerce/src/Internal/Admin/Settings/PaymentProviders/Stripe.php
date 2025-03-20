<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings\PaymentProviders;

use WC_Payment_Gateway;

defined( 'ABSPATH' ) || exit;

/**
 * Stripe payment gateway provider class.
 *
 * This class handles all the custom logic for the Stripe payment gateway provider.
 */
class Stripe extends PaymentGateway {

	/**
	 * Check if the payment gateway has a payments processor account connected.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway account is connected, false otherwise.
	 *              If the payment gateway does not provide the information, it will return true.
	 */
	public function is_account_connected( WC_Payment_Gateway $payment_gateway ): bool {
		if ( class_exists( '\WC_Stripe' ) && is_callable( '\WC_Stripe::get_instance' ) ) {
			$stripe = \WC_Stripe::get_instance();
			if ( isset( $stripe->account ) &&
				class_exists( '\WC_Stripe_Account' ) &&
				defined( '\WC_Stripe_Account::STATUS_NO_ACCOUNT' ) &&
				$stripe->account instanceof \WC_Stripe_Account &&
				is_callable( array( $stripe->account, 'get_account_status' ) ) ) {

				return \WC_Stripe_Account::STATUS_NO_ACCOUNT !== $stripe->account->get_account_status();
			}
		}

		return parent::is_account_connected( $payment_gateway );
	}
}
