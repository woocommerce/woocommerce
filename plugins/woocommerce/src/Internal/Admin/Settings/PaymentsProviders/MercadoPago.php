<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;

use Automattic\WooCommerce\Internal\Admin\Settings\Utils;
use Automattic\WooCommerce\Internal\Logging\SafeGlobalFunctionProxy;
use WC_Payment_Gateway;

defined( 'ABSPATH' ) || exit;

/**
 * MercadoPago payment gateway provider class.
 *
 * This class handles all the custom logic for the MercadoPago payment gateway provider.
 */
class MercadoPago extends PaymentGateway {

	/**
	 * Check if the payment gateway needs setup.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway needs setup, false otherwise.
	 */
	public function needs_setup( WC_Payment_Gateway $payment_gateway ): bool {
		$is_onboarded = $this->is_mercado_pago_onboarded( $payment_gateway );
		if ( ! is_null( $is_onboarded ) ) {
			return ! $is_onboarded;
		}

		return parent::needs_setup( $payment_gateway );
	}

	/**
	 * Try to determine if the payment gateway is in test mode.
	 *
	 * This is a best-effort attempt, as there is no standard way to determine this.
	 * Trust the true value, but don't consider a false value as definitive.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway is in test mode, false otherwise.
	 */
	public function is_in_test_mode( WC_Payment_Gateway $payment_gateway ): bool {
		return $this->is_mercado_pago_in_sandbox_mode( $payment_gateway ) ?? parent::is_in_test_mode( $payment_gateway );
	}

	/**
	 * Try to determine if the payment gateway is in dev mode.
	 *
	 * This is a best-effort attempt, as there is no standard way to determine this.
	 * Trust the true value, but don't consider a false value as definitive.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway is in dev mode, false otherwise.
	 */
	public function is_in_dev_mode( WC_Payment_Gateway $payment_gateway ): bool {
		return $this->is_mercado_pago_in_sandbox_mode( $payment_gateway ) ?? parent::is_in_dev_mode( $payment_gateway );
	}

	/**
	 * Check if the payment gateway has a payments processor account connected.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway account is connected, false otherwise.
	 *              If the payment gateway does not provide the information, it will return true.
	 */
	public function is_account_connected( WC_Payment_Gateway $payment_gateway ): bool {
		return $this->is_mercado_pago_onboarded( $payment_gateway ) ?? parent::is_account_connected( $payment_gateway );
	}

	/**
	 * Check if the payment gateway has completed the onboarding process.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway has completed the onboarding process, false otherwise.
	 *              If the payment gateway does not provide the information,
	 *              it will infer it from having a connected account.
	 */
	public function is_onboarding_completed( WC_Payment_Gateway $payment_gateway ): bool {
		return $this->is_mercado_pago_onboarded( $payment_gateway ) ?? parent::is_onboarding_completed( $payment_gateway );
	}

	/**
	 * Try to determine if the payment gateway is in test mode onboarding (aka sandbox or test-drive).
	 *
	 * This is a best-effort attempt, as there is no standard way to determine this.
	 * Trust the true value, but don't consider a false value as definitive.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway is in test mode onboarding, false otherwise.
	 */
	public function is_in_test_mode_onboarding( WC_Payment_Gateway $payment_gateway ): bool {
		return $this->is_mercado_pago_in_sandbox_mode( $payment_gateway ) ?? parent::is_in_test_mode_onboarding( $payment_gateway );
	}

	/**
	 * Check if the MercadoPago payment gateway is in sandbox mode.
	 *
	 * For MercadoPago, there are two different environments: sandbox and production.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return ?bool True if the payment gateway is in sandbox mode, false otherwise.
	 *               Null if the environment could not be determined.
	 */
	private function is_mercado_pago_in_sandbox_mode( WC_Payment_Gateway $payment_gateway ): ?bool {
		global $mercadopago;

		try {
			// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			if ( class_exists( '\MercadoPago\Woocommerce\WoocommerceMercadoPago' ) &&
				class_exists( '\MercadoPago\Woocommerce\Configs\Store' ) &&
				$mercadopago instanceof \MercadoPago\Woocommerce\WoocommerceMercadoPago &&
				! is_null( $mercadopago->storeConfig ) &&
				$mercadopago->storeConfig instanceof \MercadoPago\Woocommerce\Configs\Store &&
				is_callable( array( $mercadopago->storeConfig, 'isTestMode' ) )
			) {
				return wc_string_to_bool( $mercadopago->storeConfig->isTestMode() );

			}
		} catch ( \Throwable $e ) {
			// Do nothing but log so we can investigate.
			SafeGlobalFunctionProxy::wc_get_logger()->debug(
				'Failed to determine if gateway is in sandbox mode: ' . $e->getMessage(),
				array(
					'gateway'   => $payment_gateway->id,
					'source'    => 'settings-payments',
					'exception' => $e,
				)
			);
		}

		// Let the caller know that we couldn't determine the environment.
		return null;
	}

	/**
	 * Check if the MercadoPago payment gateway is onboarded.
	 *
	 * For MercadoPago, there are two different environments: sandbox/test and production/sale.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return ?bool True if the payment gateway is onboarded, false otherwise.
	 *               Null if we failed to determine the onboarding status.
	 */
	private function is_mercado_pago_onboarded( WC_Payment_Gateway $payment_gateway ): ?bool {
		global $mercadopago;

		try {
			// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			if ( class_exists( '\MercadoPago\Woocommerce\WoocommerceMercadoPago' ) &&
				class_exists( '\MercadoPago\Woocommerce\Configs\Seller' ) &&
				$mercadopago instanceof \MercadoPago\Woocommerce\WoocommerceMercadoPago &&
				! is_null( $mercadopago->sellerConfig ) &&
				$mercadopago->sellerConfig instanceof \MercadoPago\Woocommerce\Configs\Seller &&
				is_callable( array( $mercadopago->sellerConfig, 'getCredentialsPublicKey' ) ) &&
				is_callable( array( $mercadopago->sellerConfig, 'getCredentialsAccessToken' ) )
			) {
				return ! empty( $mercadopago->sellerConfig->getCredentialsPublicKey() ) &&
						! empty( $mercadopago->sellerConfig->getCredentialsAccessToken() );

			}
		} catch ( \Throwable $e ) {
			// Do nothing but log so we can investigate.
			SafeGlobalFunctionProxy::wc_get_logger()->debug(
				'Failed to determine if gateway is onboarded: ' . $e->getMessage(),
				array(
					'gateway'   => $payment_gateway->id,
					'source'    => 'settings-payments',
					'exception' => $e,
				)
			);
		}

		// Let the caller know that we couldn't determine the onboarding status.
		return null;
	}
}
