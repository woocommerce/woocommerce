<?php
/**
 * MultiCurrencyLocalizationService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyLocalizationInterface;

/**
 * Localization service for the native multi-currency runtime.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyLocalizationService implements MultiCurrencyLocalizationInterface {

	const CURRENCY_FORMAT_TRANSIENT = 'wcpay_currency_format';
	const LOCALE_INFO_TRANSIENT     = 'wcpay_locale_info';

	/**
	 * Currency formatting map.
	 *
	 * @var array<string,array<string,array<string,mixed>>>
	 */
	private array $currency_format = array();

	/**
	 * Country locale information.
	 *
	 * @var array<string,array<string,mixed>>
	 */
	private array $locale_info = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->load_locale_data();
	}

	/**
	 * Get a currency format.
	 *
	 * @param string $currency_code Currency code.
	 * @return array<string,mixed>
	 */
	public function get_currency_format( $currency_code ): array {
		$currency_code   = strtoupper( (string) $currency_code );
		$currency_format = array(
			'currency_pos' => 'left',
			'thousand_sep' => ',',
			'decimal_sep'  => '.',
			'num_decimals' => 2,
		);
		$locale          = get_user_locale();

		$currency_options = $this->currency_format[ $currency_code ] ?? null;
		if ( is_array( $currency_options ) ) {
			$currency_format = $currency_options[ $locale ] ?? $currency_options['default'] ?? $currency_format;
		}

		/**
		 * Filter to edit formatting for a specific currency.
		 *
		 * @since 11.0.0
		 *
		 * @param array<string,mixed> $currency_format The currency format settings.
		 * @param string              $locale          The user's locale.
		 */
		return apply_filters( 'wcpay_' . strtolower( $currency_code ) . '_format', $currency_format, $locale );
	}

	/**
	 * Get locale data for a country.
	 *
	 * @param string $country Country code.
	 * @return array<string,mixed>
	 */
	public function get_country_locale_data( $country ): array {
		return $this->locale_info[ strtoupper( (string) $country ) ] ?? array();
	}

	/**
	 * Load locale and currency formatting data.
	 */
	private function load_locale_data(): void {
		$transient_currency_format_data = get_transient( self::CURRENCY_FORMAT_TRANSIENT );
		$transient_locale_info_data     = get_transient( self::LOCALE_INFO_TRANSIENT );

		if ( is_array( $transient_currency_format_data ) && is_array( $transient_locale_info_data ) ) {
			$this->currency_format = $transient_currency_format_data;
			$this->locale_info     = $transient_locale_info_data;
			return;
		}

		$locale_info_path  = WC()->plugin_path() . '/i18n/locale-info.php';
		$locale_info       = file_exists( $locale_info_path ) ? include $locale_info_path : array();
		$this->locale_info = is_array( $locale_info ) ? $locale_info : array();

		foreach ( $this->locale_info as $country_data ) {
			if ( empty( $country_data['currency_code'] ) || empty( $country_data['locales'] ) || ! is_array( $country_data['locales'] ) ) {
				continue;
			}

			$currency_code = strtoupper( (string) $country_data['currency_code'] );
			foreach ( $country_data['locales'] as $locale => $locale_data ) {
				if ( empty( $locale_data ) || ! is_array( $locale_data ) ) {
					continue;
				}

				$this->currency_format[ $currency_code ][ $locale ] = array(
					'currency_pos' => $locale_data['currency_pos'] ?? 'left',
					'thousand_sep' => $locale_data['thousand_sep'] ?? ',',
					'decimal_sep'  => $locale_data['decimal_sep'] ?? '.',
					'num_decimals' => $country_data['num_decimals'] ?? 2,
				);
			}
		}

		if ( ! empty( $this->locale_info ) ) {
			set_transient( self::CURRENCY_FORMAT_TRANSIENT, $this->currency_format, DAY_IN_SECONDS );
			set_transient( self::LOCALE_INFO_TRANSIENT, $this->locale_info, DAY_IN_SECONDS );
		}
	}
}
