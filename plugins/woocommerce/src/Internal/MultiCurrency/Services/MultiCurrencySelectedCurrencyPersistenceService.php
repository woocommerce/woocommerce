<?php
/**
 * MultiCurrencySelectedCurrencyPersistenceService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

/**
 * Persists the selected native multi-currency code using WooPayments keys.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencySelectedCurrencyPersistenceService {

	private const CURRENCY_STORAGE_KEY = 'wcpay_currency';

	/**
	 * State builder.
	 *
	 * @var MultiCurrencyStateBuilder
	 */
	private MultiCurrencyStateBuilder $state_builder;

	/**
	 * Constructor.
	 *
	 * @param MultiCurrencyStateBuilder $state_builder State builder.
	 *
	 * @since 11.0.0
	 */
	public function __construct( MultiCurrencyStateBuilder $state_builder ) {
		$this->state_builder = $state_builder;
	}

	/**
	 * Persist the selected currency when it is enabled.
	 *
	 * @param string $currency_code  Three-letter currency code.
	 * @param bool   $persist_change Whether the explicit change should persist across requests.
	 * @return bool True when the selected currency was persisted.
	 *
	 * @since 11.0.0
	 */
	public function update_selected_currency( string $currency_code, bool $persist_change = true ): bool {
		$currency_code = $this->normalize_currency_code( $currency_code );
		if ( '' === $currency_code || ! $this->is_enabled_currency( $currency_code ) ) {
			return false;
		}

		$user_id = get_current_user_id();
		if ( $user_id ) {
			update_user_meta( $user_id, self::CURRENCY_STORAGE_KEY, $currency_code );
		} elseif ( ! $this->set_session_currency( $currency_code, $persist_change ) ) {
			return false;
		}

		$this->recalculate_cart_or_schedule();

		return true;
	}

	/**
	 * Persist a new customer's selected currency from the guest session.
	 *
	 * @param int $customer_id Customer ID.
	 * @return bool True when customer meta was updated.
	 *
	 * @since 11.0.0
	 */
	public function set_new_customer_currency_meta( int $customer_id ): bool {
		if ( 0 >= $customer_id || ! function_exists( 'WC' ) || ! WC()->session ) {
			return false;
		}

		$currency_code = WC()->session->get( self::CURRENCY_STORAGE_KEY );
		if ( ! is_string( $currency_code ) ) {
			return false;
		}

		$currency_code = $this->normalize_currency_code( $currency_code );
		if ( '' === $currency_code || ! $this->is_enabled_currency( $currency_code ) ) {
			return false;
		}

		update_user_meta( $customer_id, self::CURRENCY_STORAGE_KEY, $currency_code );

		return true;
	}

	/**
	 * Tell whether more than one currency is enabled.
	 *
	 * @return bool True when a customer-facing selector is useful.
	 *
	 * @since 11.0.0
	 */
	public function has_additional_currencies_enabled(): bool {
		return $this->state_builder->build()->has_additional_currencies_enabled();
	}

	/**
	 * Get enabled currency option data for account forms.
	 *
	 * @return array<int,array{code:string,symbol:string}>
	 *
	 * @since 11.0.0
	 */
	public function get_enabled_currency_options(): array {
		$options = array();

		foreach ( $this->state_builder->build()->get_enabled_currencies() as $currency ) {
			$options[] = array(
				'code'   => $currency->get_code(),
				'symbol' => $currency->get_symbol(),
			);
		}

		return $options;
	}

	/**
	 * Get the selected currency code.
	 *
	 * @return string Selected currency code.
	 *
	 * @since 11.0.0
	 */
	public function get_selected_currency_code(): string {
		return $this->state_builder->build()->get_selected_currency()->get_code();
	}

	/**
	 * Recalculate cart totals.
	 *
	 * @internal
	 */
	public function recalculate_cart(): void {
		if ( function_exists( 'WC' ) && WC()->cart && method_exists( WC()->cart, 'calculate_totals' ) ) {
			WC()->cart->calculate_totals();
		}
	}

	/**
	 * Persist the selected guest currency to session.
	 *
	 * @param string $currency_code  Currency code.
	 * @param bool   $persist_change Whether to request a persistent customer session cookie.
	 * @return bool True when the session value was stored.
	 */
	private function set_session_currency( string $currency_code, bool $persist_change ): bool {
		if ( ! function_exists( 'WC' ) ) {
			return false;
		}

		if ( ! WC()->session && method_exists( WC(), 'initialize_session' ) ) {
			WC()->initialize_session();
		}

		if ( ! WC()->session || ! method_exists( WC()->session, 'set' ) ) {
			return false;
		}

		WC()->session->set( self::CURRENCY_STORAGE_KEY, $currency_code );

		if ( $persist_change && method_exists( WC()->session, 'set_customer_session_cookie' ) ) {
			WC()->session->set_customer_session_cookie( true );
		}

		return true;
	}

	/**
	 * Recalculate cart totals now or after WordPress finishes loading.
	 */
	private function recalculate_cart_or_schedule(): void {
		if ( did_action( 'wp_loaded' ) ) {
			$this->recalculate_cart();
			return;
		}

		if ( ! has_action( 'wp_loaded', array( $this, 'recalculate_cart' ) ) ) {
			add_action( 'wp_loaded', array( $this, 'recalculate_cart' ) );
		}
	}

	/**
	 * Tell whether a currency code is currently enabled.
	 *
	 * @param string $currency_code Currency code.
	 * @return bool True when enabled.
	 */
	private function is_enabled_currency( string $currency_code ): bool {
		return isset( $this->state_builder->build()->get_enabled_currencies()[ $currency_code ] );
	}

	/**
	 * Normalize a currency code.
	 *
	 * @param string $currency_code Currency code.
	 * @return string Normalized code.
	 */
	private function normalize_currency_code( string $currency_code ): string {
		return strtoupper( trim( $currency_code ) );
	}
}
