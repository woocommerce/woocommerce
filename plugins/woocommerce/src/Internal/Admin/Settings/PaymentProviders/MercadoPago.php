<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings\PaymentProviders;

use Automattic\WooCommerce\Internal\Admin\Settings\Utils;
use WC_Payment_Gateway;

defined( 'ABSPATH' ) || exit;

/**
 * MercadoPago payment gateway provider class.
 *
 * This class handles all the custom logic for the MercadoPago payment gateway provider.
 */
class MercadoPago extends PaymentGateway {

	/**
	 * Get the provider title of the payment gateway.
	 *
	 * This is the intended gateway title to use throughout the WC admin. It should be short.
	 *
	 * Note: We don't allow HTML tags in the title. All HTML tags will be stripped, including their contents.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return string The provider title of the payment gateway.
	 */
	public function get_title( WC_Payment_Gateway $payment_gateway ): string {
		$title = $payment_gateway->get_method_title();
		switch ( $payment_gateway->id ) {
			case 'woo-mercado-pago-basic':
				$title = $title . ' (' . esc_html__( 'Installments without cards', 'woocommerce' ) . ')';
				break;
			case 'woo-mercado-pago-custom':
				$title = $title . ' (' . esc_html__( 'Credit and debit cards', 'woocommerce' ) . ')';
				break;
			case 'woo-mercado-pago-ticket':
				$title = $title . ' (' . esc_html__( 'Invoice', 'woocommerce' ) . ')';
				break;
			default:
				break;
		}

		$title = wp_strip_all_tags( html_entity_decode( $title ), true );

		// Truncate the title.
		return Utils::truncate_with_words( $title, 75 );
	}

	/**
	 * Check if the payment gateway needs setup.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway needs setup, false otherwise.
	 */
	public function needs_setup( WC_Payment_Gateway $payment_gateway ): bool {
		$is_onboarded = $this->is_mercado_pago_onboarded();
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
		$is_in_sandbox_mode = $this->is_mercado_pago_in_sandbox_mode();
		if ( ! is_null( $is_in_sandbox_mode ) ) {
			return $is_in_sandbox_mode;
		}

		return parent::is_in_test_mode( $payment_gateway );
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
		$is_in_sandbox_mode = $this->is_mercado_pago_in_sandbox_mode();
		if ( ! is_null( $is_in_sandbox_mode ) ) {
			return $is_in_sandbox_mode;
		}

		return parent::is_in_dev_mode( $payment_gateway );
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
		$is_onboarded = $this->is_mercado_pago_onboarded();
		if ( ! is_null( $is_onboarded ) ) {
			return $is_onboarded;
		}

		return parent::is_account_connected( $payment_gateway );
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
		$is_onboarded = $this->is_mercado_pago_onboarded();
		if ( ! is_null( $is_onboarded ) ) {
			return $is_onboarded;
		}

		return parent::is_onboarding_completed( $payment_gateway );
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
		$is_in_sandbox_mode = $this->is_mercado_pago_in_sandbox_mode();
		if ( ! is_null( $is_in_sandbox_mode ) ) {
			return $is_in_sandbox_mode;
		}

		return parent::is_in_test_mode( $payment_gateway );
	}

	/**
	 * Check if the MercadoPago payment gateway is in sandbox mode.
	 *
	 * For MercadoPago, there are two different environments: sandbox and production.
	 *
	 * @return ?bool True if the payment gateway is in sandbox mode, false otherwise.
	 *               Null if the environment could not be determined.
	 */
	private function is_mercado_pago_in_sandbox_mode(): ?bool {
		global $mercadopago;

		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		if ( class_exists( '\MercadoPago\Woocommerce\WoocommerceMercadoPago' ) &&
			class_exists( '\MercadoPago\Woocommerce\Configs\Store' ) &&
			$mercadopago instanceof \MercadoPago\Woocommerce\WoocommerceMercadoPago &&
			! is_null( $mercadopago->storeConfig ) &&
			$mercadopago->storeConfig instanceof \MercadoPago\Woocommerce\Configs\Store &&
			is_callable( array( $mercadopago->storeConfig, 'isTestMode' ) )
		) {
			return $mercadopago->storeConfig->isTestMode();

		}

		// Let the caller know that we couldn't determine the environment.
		return null;
	}

	/**
	 * Check if the MercadoPago payment gateway is onboarded.
	 *
	 * For MercadoPago, there are two different environments: sandbox/test and production/sale.
	 *
	 * @return ?bool True if the payment gateway is onboarded, false otherwise.
	 *               Null if we failed to determine the onboarding status.
	 */
	private function is_mercado_pago_onboarded(): ?bool {
		global $mercadopago;

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

		// Let the caller know that we couldn't determine the onboarding status.
		return null;
	}
}
