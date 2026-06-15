<?php
/**
 * MultiCurrencyState class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency;

/**
 * Immutable multi-currency state snapshot for native shadow calculations.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyState {

	/**
	 * Available currencies keyed by uppercase code.
	 *
	 * @var array<string,MultiCurrencyCurrency>
	 */
	private array $available_currencies;

	/**
	 * Enabled currencies keyed by uppercase code.
	 *
	 * @var array<string,MultiCurrencyCurrency>
	 */
	private array $enabled_currencies;

	/**
	 * Default currency.
	 *
	 * @var MultiCurrencyCurrency
	 */
	private MultiCurrencyCurrency $default_currency;

	/**
	 * Selected currency.
	 *
	 * @var MultiCurrencyCurrency
	 */
	private MultiCurrencyCurrency $selected_currency;

	/**
	 * Customer-used currency codes.
	 *
	 * @var string[]
	 */
	private array $customer_currencies;

	/**
	 * Constructor.
	 *
	 * @param array<string,MultiCurrencyCurrency> $available_currencies Available currencies.
	 * @param array<string,MultiCurrencyCurrency> $enabled_currencies   Enabled currencies.
	 * @param MultiCurrencyCurrency               $default_currency     Default currency.
	 * @param MultiCurrencyCurrency               $selected_currency    Selected currency.
	 * @param string[]                            $customer_currencies Customer-used currency codes.
	 */
	public function __construct(
		array $available_currencies,
		array $enabled_currencies,
		MultiCurrencyCurrency $default_currency,
		MultiCurrencyCurrency $selected_currency,
		array $customer_currencies = array()
	) {
		$this->available_currencies = $available_currencies;
		$this->enabled_currencies   = $enabled_currencies;
		$this->default_currency     = $default_currency;
		$this->selected_currency    = $selected_currency;
		$this->customer_currencies  = array_values( array_unique( array_map( 'strtoupper', $customer_currencies ) ) );
	}

	/**
	 * Get available currencies.
	 *
	 * @return array<string,MultiCurrencyCurrency>
	 */
	public function get_available_currencies(): array {
		return $this->available_currencies;
	}

	/**
	 * Get enabled currencies.
	 *
	 * @return array<string,MultiCurrencyCurrency>
	 */
	public function get_enabled_currencies(): array {
		return $this->enabled_currencies;
	}

	/**
	 * Get default currency.
	 *
	 * @return MultiCurrencyCurrency
	 */
	public function get_default_currency(): MultiCurrencyCurrency {
		return $this->default_currency;
	}

	/**
	 * Get selected currency.
	 *
	 * @return MultiCurrencyCurrency
	 */
	public function get_selected_currency(): MultiCurrencyCurrency {
		return $this->selected_currency;
	}

	/**
	 * Get customer-used currency codes.
	 *
	 * @return string[]
	 */
	public function get_customer_currencies(): array {
		return $this->customer_currencies;
	}

	/**
	 * Tell whether any non-default currency is enabled.
	 *
	 * @return bool
	 */
	public function has_additional_currencies_enabled(): bool {
		return count( $this->enabled_currencies ) > 1;
	}
}
