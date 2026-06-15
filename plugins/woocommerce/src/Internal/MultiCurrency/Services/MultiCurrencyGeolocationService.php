<?php
/**
 * MultiCurrencyGeolocationService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyLocalizationInterface;

/**
 * Projects customer geolocation for native multi-currency shadow work.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyGeolocationService {

	/**
	 * Localization service.
	 *
	 * @var MultiCurrencyLocalizationInterface
	 */
	private MultiCurrencyLocalizationInterface $localization_service;

	/**
	 * Country resolver.
	 *
	 * @var callable|null
	 */
	private $country_resolver;

	/**
	 * Constructor.
	 *
	 * @param MultiCurrencyLocalizationInterface $localization_service Localization service.
	 * @param callable|null                      $country_resolver     Optional country resolver for tests.
	 */
	public function __construct( MultiCurrencyLocalizationInterface $localization_service, ?callable $country_resolver = null ) {
		$this->localization_service = $localization_service;
		$this->country_resolver     = $country_resolver;
	}

	/**
	 * Get the customer's currency based on their projected location.
	 *
	 * @return string|null
	 */
	public function get_currency_by_customer_location(): ?string {
		$country     = $this->get_country_by_customer_location();
		$locale_data = $this->localization_service->get_country_locale_data( $country );

		return isset( $locale_data['currency_code'] ) ? strtoupper( (string) $locale_data['currency_code'] ) : null;
	}

	/**
	 * Get the customer's country based on projected geolocation.
	 *
	 * @return string
	 */
	public function get_country_by_customer_location(): string {
		$country = $this->geolocate_customer();

		if ( null !== $country ) {
			$allowed_country_codes = WC()->countries->get_allowed_countries();
			if ( ! array_key_exists( $country, $allowed_country_codes ) ) {
				$country = null;
			}
		}

		if ( null === $country ) {
			$default_location = get_option( 'woocommerce_default_country', '' );
			/**
			 * Filters the fallback customer location used for multi-currency geolocation.
			 *
			 * @param string $default_location Default customer location.
			 *
			 * @since 11.0.0
			 */
			$default_location = apply_filters( 'woocommerce_customer_default_location', $default_location );
			$location         = wc_format_country_state_string( $default_location );
			$country          = (string) ( $location['country'] ?? '' );
		}

		return strtoupper( $country );
	}

	/**
	 * Guess the customer's country based on request data.
	 *
	 * @return string|null
	 */
	private function geolocate_customer(): ?string {
		$user_agent = wc_get_user_agent();
		if (
			false !== stripos( $user_agent, 'bot' )
			|| false !== stripos( $user_agent, 'spider' )
			|| false !== stripos( $user_agent, 'crawl' )
		) {
			return null;
		}

		$country = is_callable( $this->country_resolver )
			? call_user_func( $this->country_resolver )
			: $this->geolocate_ip_country();

		return is_string( $country ) && '' !== $country ? strtoupper( $country ) : null;
	}

	/**
	 * Get the country code from WooCommerce IP geolocation.
	 *
	 * @return string|null
	 */
	private function geolocate_ip_country(): ?string {
		$geolocation = \WC_Geolocation::geolocate_ip( '', true, true );
		$country     = $geolocation['country'] ?? null;

		return is_string( $country ) && '' !== $country ? strtoupper( $country ) : null;
	}
}
