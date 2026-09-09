<?php
/**
 * Finance data provider registry.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Admin\Payments\Finance;

use WC_Payment_Gateway;

defined( 'ABSPATH' ) || exit;

/**
 * Registry of the finance data providers payment gateway extensions register.
 *
 * Extensions receive the registry from the 'woocommerce_payments_finance_providers_registration'
 * action and call register(). WooCommerce fires that action the first time a provider is looked up.
 *
 * @since 11.2.0
 */
final class FinanceDataProviderRegistry {

	/**
	 * Registered providers keyed by payment gateway id.
	 *
	 * @var array<string, FinanceDataProviderInterface>
	 */
	private array $providers = array();

	/**
	 * Whether the registration action has fired.
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Register a finance data provider for its payment gateway.
	 *
	 * @param FinanceDataProviderInterface $provider The provider.
	 * @return bool True when registered.
	 *
	 * @since 11.2.0
	 */
	public function register( FinanceDataProviderInterface $provider ): bool {
		$all_gateways = WC()->payment_gateways()->payment_gateways();

		$gateway_id = $provider->get_payment_gateway_id();

		$gateway = $all_gateways[ $gateway_id ] ?? null;
		if ( ! $gateway instanceof WC_Payment_Gateway ) {
			wc_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: %s: payment gateway id. */
					__( 'The "%s" payment gateway is not registered.', 'woocommerce' ),
					$gateway_id
				),
				'11.2.0'
			);
			return false;
		}

		// TODO: Validate that the payment gateway is permitted to provide finance data.

		if ( $this->is_registered( $gateway_id ) ) {
			wc_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: %s: payment gateway id. */
					esc_html__( 'A finance data provider is already registered for the "%s" payment gateway.', 'woocommerce' ),
					esc_html( $gateway_id )
				),
				'11.2.0'
			);
			return false;
		}

		$this->providers[ $gateway_id ] = $provider;
		return true;
	}

	/**
	 * Whether a finance data provider is registered for a payment gateway.
	 *
	 * @param string $gateway_id The payment gateway id.
	 * @return bool
	 *
	 * @since 11.2.0
	 */
	public function is_registered( string $gateway_id ): bool {
		$this->initialize();

		return isset( $this->providers[ $gateway_id ] );
	}

	/**
	 * Get the provider registered for a payment gateway.
	 *
	 * @param string $gateway_id The payment gateway id.
	 * @return FinanceDataProviderInterface|null
	 *
	 * @since 11.2.0
	 */
	public function get_provider( string $gateway_id ): ?FinanceDataProviderInterface {
		$this->initialize();

		return $this->providers[ $gateway_id ] ?? null;
	}

	/**
	 * Get all registered providers.
	 *
	 * @return array<string, FinanceDataProviderInterface> Providers keyed by payment gateway id.
	 *
	 * @since 11.2.0
	 */
	public function get_providers(): array {
		$this->initialize();

		return $this->providers;
	}

	/**
	 * Remove all registered providers and allow the registration action to fire again.
	 *
	 * @since 11.2.0
	 */
	public function unregister_all(): void {
		$this->providers   = array();
		$this->initialized = false;
	}

	/**
	 * Fire the provider registration action once.
	 */
	private function initialize(): void {
		if ( $this->initialized ) {
			return;
		}

		// Mark initialized before firing the action so re-entrant lookups do not run it again.
		$this->initialized = true;

		try {
			/**
			 * Fires when finance data providers can be registered.
			 *
			 * @param FinanceDataProviderRegistry $registry The finance data provider registry.
			 *
			 * @since 11.2.0
			 */
			do_action( 'woocommerce_payments_finance_providers_registration', $this );
		} catch ( \Throwable $e ) {
			wc_get_logger()->error(
				sprintf(
					'Finance data provider registration failed: %1$s: %2$s',
					get_class( $e ),
					$e->getMessage()
				),
				array( 'source' => 'payments-finance' )
			);

			if ( $e instanceof \Exception ) {
				wc_caught_exception( $e, __METHOD__ );
			}
		}
	}
}
