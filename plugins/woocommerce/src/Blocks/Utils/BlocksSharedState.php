<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\Utils;

use InvalidArgumentException;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\Hydration;

/**
 * Manages the registration of interactivity config and state that is commonly shared by WooCommerce blocks.
 * Initialization only happens on the first call to load_store_config.
 *
 * This is a private API and may change in future versions.
 */
class BlocksSharedState {

	/**
	 * The consent statement for using private APIs of this class.
	 *
	 * @var string
	 */
	private static string $consent_statement = 'I acknowledge that using private APIs means my theme or plugin will inevitably break in the next version of WooCommerce';

	/**
	 * The namespace for the config.
	 *
	 * @var string
	 */
	private static string $settings_namespace = 'woocommerce';

	/**
	 * Whether the core config has been registered.
	 *
	 * @var bool
	 */
	private static bool $core_config_registered = false;

	/**
	 * Cart state.
	 *
	 * @var array|null
	 */
	private static ?array $blocks_shared_cart_state = null;

	/**
	 * Prevent caching on certain pages.
	 *
	 * @return void
	 */
	private static function prevent_cache(): void {
		\WC_Cache_Helper::set_nocache_constants();
		nocache_headers();
	}

	/**
	 * Check that the consent statement was passed.
	 *
	 * @param string $consent_statement The consent statement string.
	 * @return true
	 * @throws InvalidArgumentException If the statement does not match.
	 */
	private static function check_consent( string $consent_statement ): bool {
		if ( $consent_statement !== self::$consent_statement ) {
			throw new InvalidArgumentException( 'This method cannot be called without consenting the API may change.' );
		}

		return true;
	}

	/**
	 * Load store config (currency, locale, core data) into interactivity config.
	 *
	 * @param string $consent_statement The consent statement string.
	 * @return void
	 * @throws InvalidArgumentException If consent statement doesn't match.
	 */
	public static function load_store_config( string $consent_statement ): void {
		self::check_consent( $consent_statement );

		if ( self::$core_config_registered ) {
			return;
		}

		self::$core_config_registered = true;

		wp_interactivity_config( self::$settings_namespace, self::get_currency_data() );
		wp_interactivity_config( self::$settings_namespace, self::get_locale_data() );
		wp_interactivity_config( self::$settings_namespace, self::get_core_data() );
	}

	/**
	 * Load cart state into interactivity state.
	 *
	 * @param string $consent_statement The consent statement string.
	 * @return void
	 * @throws InvalidArgumentException If consent statement doesn't match.
	 */
	public static function load_cart_state( string $consent_statement ): void {
		self::check_consent( $consent_statement );

		if ( null === self::$blocks_shared_cart_state ) {
			$cart_exists       = isset( WC()->cart );
			$cart_has_contents = $cart_exists && ! WC()->cart->is_empty();
			if ( $cart_exists ) {
				$cart_response                  = Package::container()->get( Hydration::class )->get_rest_api_response_data( '/wc/store/v1/cart' );
				self::$blocks_shared_cart_state = $cart_response['body'] ?? array();
			} else {
				self::$blocks_shared_cart_state = array();
			}

			if ( $cart_has_contents ) {
				self::prevent_cache();
			}

			wp_interactivity_state(
				'woocommerce',
				array(
					'cart'     => self::$blocks_shared_cart_state,
					'nonce'    => wp_create_nonce( 'wc_store_api' ),
					'noticeId' => '',
					'restUrl'  => get_rest_url(),
				)
			);
		}
	}

	/**
	 * Get core data to include in settings.
	 *
	 * @return array
	 */
	private static function get_core_data(): array {
		return array(
			'isBlockTheme' => wp_is_block_theme(),
		);
	}

	/**
	 * Get currency data to include in settings.
	 *
	 * @return array
	 */
	private static function get_currency_data(): array {
		$currency = get_woocommerce_currency();

		return array(
			'currency' => array(
				'code'              => $currency,
				'precision'         => wc_get_price_decimals(),
				'symbol'            => html_entity_decode( get_woocommerce_currency_symbol( $currency ) ),
				'symbolPosition'    => get_option( 'woocommerce_currency_pos' ),
				'decimalSeparator'  => wc_get_price_decimal_separator(),
				'thousandSeparator' => wc_get_price_thousand_separator(),
				'priceFormat'       => html_entity_decode( get_woocommerce_price_format() ),
			),
		);
	}

	/**
	 * Get locale data to include in settings.
	 *
	 * @return array
	 */
	private static function get_locale_data(): array {
		global $wp_locale;

		return array(
			'locale' => array(
				'siteLocale'    => get_locale(),
				'userLocale'    => get_user_locale(),
				'weekdaysShort' => array_values( $wp_locale->weekday_abbrev ),
			),
		);
	}

	/**
	 * Load placeholder image into interactivity config.
	 *
	 * @param string $consent_statement The consent statement string.
	 * @return void
	 * @throws InvalidArgumentException If consent statement doesn't match.
	 */
	public static function load_placeholder_image( string $consent_statement ): void {
		self::check_consent( $consent_statement );

		wp_interactivity_config(
			self::$settings_namespace,
			array( 'placeholderImgSrc' => wc_placeholder_img_src() )
		);
	}
}
