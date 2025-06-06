<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\Utils;

use InvalidArgumentException;

/**
 * Manages the registration of interactivity config and state that is commonly shared by WooCommerce blocks.
 * Initialization only happens on the first call to initialize_shared_config.
 * Intended to be used as a singleton.
 */
trait BlocksSharedState {

	/**
	 * The consent statement for using private APIs of this class.
	 *
	 * @var string
	 */
	private static $consent_statement = 'I acknowledge that using private APIs means my theme or plugin will inevitably break in the next version of WooCommerce';

	/**
	 * The namespace for the config.
	 *
	 * @var string
	 */
	private static $settings_namespace = 'woocommerce';

	/**
	 * Whether the core config has been registered.
	 *
	 * @var boolean
	 */
	private static $core_config_registered = false;

	/**
	 * Cart state.
	 *
	 * @var mixed
	 */
	private static $blocks_shared_cart_state;

	/**
	 * Prevent caching on certain pages
	 */
	private static function prevent_cache() {
		\WC_Cache_Helper::set_nocache_constants();
		nocache_headers();
	}


	/**
	 * Check that the consent statement was passed.
	 *
	 * @param string $consent_statement - The consent statement string.
	 * @return true
	 * @throws \InvalidArgumentException - If the statement does not match the class consent statement string.
	 */
	private static function check_consent( $consent_statement ) {
		if ( $consent_statement !== self::$consent_statement ) {
			throw new InvalidArgumentException( 'This method cannot be called without consenting the API may change.' );
		}

		return true;
	}

	/**
	 * Initialize the shared core config.
	 *
	 * @param string $consent_statement - The consent statement string.
	 */
	public function initialize_shared_config( $consent_statement ) {
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
	 * Initialize interactivity state for cart that is needed by multiple blocks.
	 *
	 * @param string $consent_statement - The consent statement string.
	 * @return void
	 */
	public function register_cart_interactivity( $consent_statement ) {
		self::check_consent( $consent_statement );

		if ( null === self::$blocks_shared_cart_state ) {
			$cart_exists                    = isset( WC()->cart );
			$cart_has_contents              = $cart_exists && ! WC()->cart->is_empty();
			self::$blocks_shared_cart_state = $cart_exists
				? rest_do_request( new \WP_REST_Request( 'GET', '/wc/store/v1/cart' ) )->data
				: array();

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
	private static function get_core_data() {
		return [
			'isBlockTheme' => wp_is_block_theme(),
		];
	}

	/**
	 * Get currency data to include in settings.
	 *
	 * @return array
	 */
	private static function get_currency_data() {
		$currency = get_woocommerce_currency();

		return [
			'currency' => [
				'code'              => $currency,
				'precision'         => wc_get_price_decimals(),
				'symbol'            => html_entity_decode( get_woocommerce_currency_symbol( $currency ) ),
				'symbolPosition'    => get_option( 'woocommerce_currency_pos' ),
				'decimalSeparator'  => wc_get_price_decimal_separator(),
				'thousandSeparator' => wc_get_price_thousand_separator(),
				'priceFormat'       => html_entity_decode( get_woocommerce_price_format() ),
			],
		];
	}

	/**
	 * Get locale data to include in settings.
	 *
	 * @return array
	 */
	private static function get_locale_data() {
		global $wp_locale;

		return [
			'locale' => [
				'siteLocale'    => get_locale(),
				'userLocale'    => get_user_locale(),
				'weekdaysShort' => array_values( $wp_locale->weekday_abbrev ),
			],
		];
	}
}
