<?php
/**
 * Class WC_Gateway_Paypal_Transact_Account_Manager file.
 *
 * @package WooCommerce\Gateways
 *
 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\TransactAccountManager instead. This class will be removed in 11.0.0.
 */

declare(strict_types=1);

use Automattic\WooCommerce\Gateways\PayPal\TransactAccountManager as PayPalTransactAccountManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles Transact account management.
 *
 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\TransactAccountManager instead. This class will be removed in 11.0.0.
 */
final class WC_Gateway_Paypal_Transact_Account_Manager {
	/**
	 * The delegated TransactAccountManager instance.
	 *
	 * @var PayPalTransactAccountManager
	 */
	private $transact_account_manager;

	/**
	 * Constructor.
	 *
	 * @param WC_Gateway_Paypal $gateway Paypal gateway object.
	 */
	public function __construct( WC_Gateway_Paypal $gateway ) {
		$this->transact_account_manager = new PayPalTransactAccountManager( $gateway );
	}

	/**
	 * Onboard the merchant with the Transact platform.
	 *
	 * @return void
	 *
	 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\TransactAccountManager::do_onboarding() instead. This method will be removed in 11.0.0.
	 */
	public function do_onboarding() {
		wc_deprecated_function(
			__METHOD__,
			'10.5.0',
			PayPalTransactAccountManager::class . '::do_onboarding()'
		);

		$this->transact_account_manager->do_onboarding();
	}

	/**
	 * Get the Transact account (merchant or provider) data. Performs a fetch if the account
	 * is not in cache or expired.
	 *
	 * @param string $account_type The type of account to get (merchant or provider).
	 * @return array|null Returns null if the transact account cannot be retrieved.
	 *
	 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\TransactAccountManager::get_transact_account_data() instead. This method will be removed in 11.0.0.
	 */
	public function get_transact_account_data( $account_type ) {
		wc_deprecated_function(
			__METHOD__,
			'10.5.0',
			PayPalTransactAccountManager::class . '::get_transact_account_data()'
		);

		return $this->transact_account_manager->get_transact_account_data( $account_type );
	}
}
