<?php
/**
 * MultiCurrencyFrontendProjectionService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Exceptions\InvalidCurrencyException;
use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyLocalizationInterface;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyCurrency;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyState;

/**
 * Projects frontend multi-currency surfaces without registering filters.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyFrontendProjectionService {

	const OPTION_PREFIX             = 'wcpay_multi_currency';
	const CURRENCY_STORAGE_KEY      = 'wcpay_currency';
	const RENDERING_MODE_SPEED      = 'speed';
	const RENDERING_MODE_CACHE      = 'cache';
	const CACHE_FEATURE_FLAG_OPTION = '_wcpay_feature_mc_cache_optimized';
	const APPLY_CHARM_PRODUCTS_HOOK = 'wcpay_multi_currency_apply_charm_only_to_products';

	/**
	 * State builder.
	 *
	 * @var MultiCurrencyStateBuilder
	 */
	private MultiCurrencyStateBuilder $state_builder;

	/**
	 * Localization service.
	 *
	 * @var MultiCurrencyLocalizationInterface
	 */
	private MultiCurrencyLocalizationInterface $localization_service;

	/**
	 * Geolocation service.
	 *
	 * @var MultiCurrencyGeolocationService
	 */
	private MultiCurrencyGeolocationService $geolocation_service;

	/**
	 * Constructor.
	 *
	 * @param MultiCurrencyStateBuilder          $state_builder        State builder.
	 * @param MultiCurrencyLocalizationInterface $localization_service Localization service.
	 * @param MultiCurrencyGeolocationService    $geolocation_service  Geolocation service.
	 */
	public function __construct(
		MultiCurrencyStateBuilder $state_builder,
		MultiCurrencyLocalizationInterface $localization_service,
		MultiCurrencyGeolocationService $geolocation_service
	) {
		$this->state_builder        = $state_builder;
		$this->localization_service = $localization_service;
		$this->geolocation_service  = $geolocation_service;
	}

	/**
	 * Project the WooCommerce currency code.
	 *
	 * @param string|null $order_currency Optional order currency override.
	 * @return string
	 */
	public function get_woocommerce_currency( ?string $order_currency = null ): string {
		return $this->get_currency_code( $this->state_builder->build(), $order_currency );
	}

	/**
	 * Project the number of price decimals.
	 *
	 * @param int         $decimals       Original decimal count.
	 * @param string|null $order_currency Optional order currency override.
	 * @return int
	 */
	public function get_price_decimals( int $decimals, ?string $order_currency = null ): int {
		$state         = $this->state_builder->build();
		$currency_code = $this->get_currency_code( $state, $order_currency );

		if ( $currency_code !== $state->get_default_currency()->get_code() ) {
			return absint( $this->localization_service->get_currency_format( $currency_code )['num_decimals'] );
		}

		return $decimals;
	}

	/**
	 * Project the price decimal separator.
	 *
	 * @param string      $separator      Original separator.
	 * @param string|null $order_currency Optional order currency override.
	 * @return string
	 */
	public function get_price_decimal_separator( string $separator, ?string $order_currency = null ): string {
		$state         = $this->state_builder->build();
		$currency_code = $this->get_currency_code( $state, $order_currency );

		if ( $currency_code !== $state->get_default_currency()->get_code() ) {
			return (string) $this->localization_service->get_currency_format( $currency_code )['decimal_sep'];
		}

		return $separator;
	}

	/**
	 * Project the price thousand separator.
	 *
	 * @param string      $separator      Original separator.
	 * @param string|null $order_currency Optional order currency override.
	 * @return string
	 */
	public function get_price_thousand_separator( string $separator, ?string $order_currency = null ): string {
		$state         = $this->state_builder->build();
		$currency_code = $this->get_currency_code( $state, $order_currency );

		if ( $currency_code !== $state->get_default_currency()->get_code() ) {
			return (string) $this->localization_service->get_currency_format( $currency_code )['thousand_sep'];
		}

		return $separator;
	}

	/**
	 * Project the WooCommerce price format.
	 *
	 * @param string      $format         Original price format.
	 * @param string|null $order_currency Optional order currency override.
	 * @return string
	 */
	public function get_woocommerce_price_format( string $format, ?string $order_currency = null ): string {
		$state         = $this->state_builder->build();
		$currency_code = $this->get_currency_code( $state, $order_currency );

		if ( $currency_code === $state->get_default_currency()->get_code() ) {
			return $format;
		}

		return $this->currency_position_to_price_format(
			(string) $this->localization_service->get_currency_format( $currency_code )['currency_pos']
		);
	}

	/**
	 * Project the WooCommerce currency position option.
	 *
	 * @param string      $position       Original currency position.
	 * @param string|null $order_currency Optional order currency override.
	 * @return string
	 */
	public function get_woocommerce_currency_pos( string $position, ?string $order_currency = null ): string {
		$state         = $this->state_builder->build();
		$currency_code = $this->get_currency_code( $state, $order_currency );

		if ( $currency_code !== $state->get_default_currency()->get_code() ) {
			return (string) $this->localization_service->get_currency_format( $currency_code )['currency_pos'];
		}

		return $position;
	}

	/**
	 * Project a cart hash varied by selected currency and rate.
	 *
	 * @param string $hash Original cart hash.
	 * @return string
	 */
	public function add_currency_to_cart_hash( string $hash ): string {
		$currency = $this->state_builder->build()->get_selected_currency();

		return md5( $hash . $currency->get_code() . $currency->get_rate() );
	}

	/**
	 * Project store currencies with WooPayments-compatible keys.
	 *
	 * @return array<string,mixed>
	 */
	public function get_store_currencies(): array {
		$state = $this->state_builder->build();

		return array(
			'available' => $state->get_available_currencies(),
			'enabled'   => $state->get_enabled_currencies(),
			'default'   => $state->get_default_currency(),
		);
	}

	/**
	 * Project the store/default currency decimal count.
	 *
	 * @return int
	 */
	public function get_store_currency_decimals(): int {
		$default_code = $this->state_builder->build()->get_default_currency()->get_code();

		return absint( $this->localization_service->get_currency_format( $default_code )['num_decimals'] );
	}

	/**
	 * Project stored settings for a single currency.
	 *
	 * @param string $currency_code Currency code.
	 * @return array<string,mixed>
	 * @throws InvalidCurrencyException When the currency is not available.
	 */
	public function get_single_currency_settings( string $currency_code ): array {
		$state         = $this->state_builder->build();
		$currency_code = strtoupper( $currency_code );

		if ( ! array_key_exists( $currency_code, $state->get_available_currencies() ) ) {
			throw new InvalidCurrencyException( esc_html( 'Invalid currency passed to get_single_currency_settings: ' . $currency_code ), 500 );
		}

		$currency_id = strtolower( $currency_code );

		return array(
			'exchange_rate_type' => get_option( self::OPTION_PREFIX . '_exchange_rate_' . $currency_id, 'automatic' ),
			'manual_rate'        => get_option( self::OPTION_PREFIX . '_manual_rate_' . $currency_id, null ),
			'price_rounding'     => get_option( self::OPTION_PREFIX . '_price_rounding_' . $currency_id, null ),
			'price_charm'        => get_option( self::OPTION_PREFIX . '_price_charm_' . $currency_id, null ),
		);
	}

	/**
	 * Project store-level multi-currency settings.
	 *
	 * @return array<string,mixed>
	 */
	public function get_settings(): array {
		return array(
			self::OPTION_PREFIX . '_enable_auto_currency' => $this->is_using_auto_currency_switching(),
			self::OPTION_PREFIX . '_enable_storefront_switcher' => $this->is_using_storefront_switcher(),
			self::OPTION_PREFIX . '_rendering_mode'       => $this->get_rendering_mode(),
			'is_cache_optimized_feature_enabled'          => $this->is_cache_optimized_feature_enabled(),
			'site_theme'                                  => wp_get_theme()->get( 'Name' ),
			'date_format'                                 => esc_attr( get_option( 'date_format', 'F j, Y' ) ),
			'time_format'                                 => esc_attr( get_option( 'time_format', 'g:i a' ) ),
			'store_url'                                   => esc_attr( $this->get_store_page_uri() ),
		);
	}

	/**
	 * Project the public async price renderer config.
	 *
	 * @return array<string,mixed>
	 */
	public function get_public_config(): array {
		$state              = $this->state_builder->build();
		$enabled_currencies = $state->get_enabled_currencies();
		$default_currency   = $state->get_default_currency();
		$default_code       = $default_currency->get_code();
		$selected_code      = $this->get_public_config_selected_currency_code( $enabled_currencies, $default_code );
		$currencies_data    = array();

		foreach ( $enabled_currencies as $currency ) {
			$currencies_data[ $currency->get_code() ] = $this->get_public_config_currency_data( $currency, $default_code );
		}

		/**
		 * Filters whether charm pricing applies only to product prices.
		 *
		 * @param bool $apply_charm_only_to_products Whether charm pricing is product-only.
		 *
		 * @since 11.0.0
		 */
		$charm_only_products = (bool) apply_filters( self::APPLY_CHARM_PRODUCTS_HOOK, true );

		return array(
			'default_currency'    => $default_code,
			'selected_currency'   => $selected_code,
			'charm_only_products' => $charm_only_products,
			'currencies'          => $currencies_data,
		);
	}

	/**
	 * Tell whether cache-optimized rendering is active.
	 *
	 * @return bool
	 */
	public function is_cache_optimized_mode(): bool {
		return $this->is_cache_optimized_feature_enabled()
			&& self::RENDERING_MODE_CACHE === $this->get_rendering_mode();
	}

	/**
	 * Get the selected currency or order override code.
	 *
	 * @param MultiCurrencyState $state          Multi-currency state.
	 * @param string|null        $order_currency Optional order currency override.
	 * @return string
	 */
	private function get_currency_code( MultiCurrencyState $state, ?string $order_currency = null ): string {
		return is_string( $order_currency ) && '' !== $order_currency
			? strtoupper( $order_currency )
			: $state->get_selected_currency()->get_code();
	}

	/**
	 * Convert a currency position value to a WooCommerce price format.
	 *
	 * @param string $currency_pos Currency position.
	 * @return string
	 */
	private function currency_position_to_price_format( string $currency_pos ): string {
		switch ( $currency_pos ) {
			case 'right':
				return '%2$s%1$s';
			case 'left_space':
				return '%1$s&nbsp;%2$s';
			case 'right_space':
				return '%2$s&nbsp;%1$s';
			case 'left':
			default:
				return '%1$s%2$s';
		}
	}

	/**
	 * Get the selected currency code for the public config without writes.
	 *
	 * @param array<string,MultiCurrencyCurrency> $enabled_currencies Enabled currencies.
	 * @param string                              $default_code       Default currency code.
	 * @return string
	 */
	private function get_public_config_selected_currency_code( array $enabled_currencies, string $default_code ): string {
		if ( $this->has_active_session() ) {
			$stored = WC()->session->get( self::CURRENCY_STORAGE_KEY );
			$stored = is_string( $stored ) ? strtoupper( $stored ) : null;

			if ( $stored && isset( $enabled_currencies[ $stored ] ) ) {
				return $stored;
			}
		} elseif ( $this->is_using_auto_currency_switching() ) {
			$geo_currency = $this->geolocation_service->get_currency_by_customer_location();
			$geo_currency = is_string( $geo_currency ) ? strtoupper( $geo_currency ) : null;

			if ( $geo_currency && isset( $enabled_currencies[ $geo_currency ] ) ) {
				return $geo_currency;
			}
		}

		return $default_code;
	}

	/**
	 * Tell whether a cookie-backed WooCommerce session is already active.
	 *
	 * @return bool
	 */
	private function has_active_session(): bool {
		$session = WC()->session;

		return is_object( $session )
			&& method_exists( $session, 'has_session' )
			&& $session->has_session();
	}

	/**
	 * Build public config data for a currency.
	 *
	 * @param MultiCurrencyCurrency $currency     Currency.
	 * @param string                $default_code Default currency code.
	 * @return array<string,mixed>
	 */
	private function get_public_config_currency_data( MultiCurrencyCurrency $currency, string $default_code ): array {
		$currency_code = $currency->get_code();

		if ( $currency_code === $default_code ) {
			$decimals     = wc_get_price_decimals();
			$decimal_sep  = wc_get_price_decimal_separator();
			$thousand_sep = wc_get_price_thousand_separator();
			$symbol_pos   = get_option( 'woocommerce_currency_pos' );
		} else {
			$format       = $this->localization_service->get_currency_format( $currency_code );
			$decimals     = absint( $format['num_decimals'] );
			$decimal_sep  = $format['decimal_sep'];
			$thousand_sep = $format['thousand_sep'];
			$symbol_pos   = $format['currency_pos'];
		}

		return array(
			'code'         => $currency_code,
			'symbol'       => get_woocommerce_currency_symbol( $currency_code ),
			'rate'         => $currency->get_rate(),
			'decimals'     => $decimals,
			'decimal_sep'  => $decimal_sep,
			'thousand_sep' => $thousand_sep,
			'symbol_pos'   => $symbol_pos,
			'rounding'     => (float) $currency->get_rounding(),
			'charm'        => (float) $currency->get_charm(),
		);
	}

	/**
	 * Tell whether automatic currency switching is enabled.
	 *
	 * @return bool
	 */
	private function is_using_auto_currency_switching(): bool {
		return 'yes' === get_option( self::OPTION_PREFIX . '_enable_auto_currency', 'no' );
	}

	/**
	 * Tell whether the storefront switcher setting is enabled.
	 *
	 * @return bool
	 */
	private function is_using_storefront_switcher(): bool {
		return 'yes' === get_option( self::OPTION_PREFIX . '_enable_storefront_switcher', 'no' );
	}

	/**
	 * Get the rendering mode option.
	 *
	 * @return string
	 */
	private function get_rendering_mode(): string {
		return (string) get_option( self::OPTION_PREFIX . '_rendering_mode', self::RENDERING_MODE_SPEED );
	}

	/**
	 * Tell whether the cache-optimized feature flag is enabled.
	 *
	 * @return bool
	 */
	private function is_cache_optimized_feature_enabled(): bool {
		return '1' === get_option( self::CACHE_FEATURE_FLAG_OPTION, '0' );
	}

	/**
	 * Get the shop page URI as a string.
	 *
	 * @return string
	 */
	private function get_store_page_uri(): string {
		$store_url = get_page_uri( wc_get_page_id( 'shop' ) );

		return is_string( $store_url ) ? $store_url : '';
	}
}
