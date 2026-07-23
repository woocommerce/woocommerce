<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\Utils;

use InvalidArgumentException;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\Hydration;
use Automattic\WooCommerce\Utilities\HydrationUtil;

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
	 * The namespace for interactivity config and state.
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
	 * Whether WooCommerce should hydrate server-rendered output with per-user data.
	 *
	 * Thin wrapper around the neutral {@see HydrationUtil::should_hydrate()} so block
	 * render callbacks keep a Blocks-layer entry point while the decision and the
	 * `woocommerce_should_hydrate` filter live in core.
	 *
	 * @since 11.1.0
	 *
	 * @param string $store_namespace Optional. Block or IAPI store namespace making the decision.
	 * @return bool
	 */
	public static function should_hydrate( string $store_namespace = '' ): bool {
		return HydrationUtil::should_hydrate( $store_namespace );
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

			$should_hydrate = self::should_hydrate( self::$settings_namespace );

			if ( $cart_exists && $should_hydrate ) {
				$cart_response                  = Package::container()->get( Hydration::class )->get_rest_api_response_data( '/wc/store/v1/cart' );
				self::$blocks_shared_cart_state = $cart_response['body'] ?? self::get_empty_cart_schema();
			} else {
				self::$blocks_shared_cart_state = self::get_empty_cart_schema();
			}

			if ( $cart_has_contents && $should_hydrate ) {
				self::prevent_cache();
			}

			wp_interactivity_config(
				self::$settings_namespace,
				array( 'nonOptimisticProperties' => self::get_non_optimistic_properties() )
			);

			wp_interactivity_state(
				self::$settings_namespace,
				array(
					'cart'     => self::$blocks_shared_cart_state,
					'noticeId' => '',
					'restUrl'  => get_rest_url(),
				)
			);
		}
	}

	/**
	 * Build an empty cart payload matching the Store API cart response schema.
	 *
	 * @return array
	 */
	private static function get_empty_cart_schema(): array {
		$currency = self::get_currency_data()['currency'];

		// Mirror the prefix/suffix logic in the Store API CurrencyFormatter so the
		// empty payload matches a live cart response for every symbol position.
		$symbol          = $currency['symbol'];
		$currency_prefix = '';
		$currency_suffix = '';
		switch ( $currency['symbolPosition'] ) {
			case 'left_space':
				$currency_prefix = $symbol . ' ';
				break;
			case 'right':
				$currency_suffix = $symbol;
				break;
			case 'right_space':
				$currency_suffix = ' ' . $symbol;
				break;
			case 'left':
			default:
				$currency_prefix = $symbol;
				break;
		}

		$empty_totals = array(
			'total_items'                 => '0',
			'total_items_tax'             => '0',
			'total_fees'                  => '0',
			'total_fees_tax'              => '0',
			'total_discount'              => '0',
			'total_discount_tax'          => '0',
			'total_shipping'              => '0',
			'total_shipping_tax'          => '0',
			'total_price'                 => '0',
			'total_tax'                   => '0',
			'tax_lines'                   => array(),
			'currency_code'               => $currency['code'],
			'currency_symbol'             => $currency['symbol'],
			'currency_minor_unit'         => $currency['precision'],
			'currency_decimal_separator'  => $currency['decimalSeparator'],
			'currency_thousand_separator' => $currency['thousandSeparator'],
			'currency_prefix'             => $currency_prefix,
			'currency_suffix'             => $currency_suffix,
		);

		// Match AbstractAddressSchema: empty-string fields rather than an empty object.
		$empty_shipping_address = array(
			'first_name' => '',
			'last_name'  => '',
			'company'    => '',
			'address_1'  => '',
			'address_2'  => '',
			'city'       => '',
			'state'      => '',
			'postcode'   => '',
			'country'    => '',
			'phone'      => '',
		);
		$empty_billing_address  = array(
			'first_name' => '',
			'last_name'  => '',
			'company'    => '',
			'address_1'  => '',
			'address_2'  => '',
			'city'       => '',
			'state'      => '',
			'postcode'   => '',
			'country'    => '',
			'email'      => '',
			'phone'      => '',
		);

		return array(
			'items'                   => array(),
			'coupons'                 => array(),
			'fees'                    => array(),
			'totals'                  => (object) $empty_totals,
			'shipping_address'        => (object) $empty_shipping_address,
			'billing_address'         => (object) $empty_billing_address,
			'needs_payment'           => false,
			'needs_shipping'          => false,
			'payment_requirements'    => array(),
			'has_calculated_shipping' => false,
			'shipping_rates'          => array(),
			'items_count'             => 0,
			'items_weight'            => 0,
			'cross_sells'             => array(),
			'errors'                  => array(),
			'payment_methods'         => array(),
			'extensions'              => (object) array(),
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
	 * Get cart properties that cannot use optimistic UI on the frontend.
	 *
	 * Detects whether third-party code has registered callbacks on filters that
	 * modify cart property values. When callbacks are present, the corresponding
	 * property must use the server-computed value instead of a client-side
	 * optimistic computation.
	 *
	 * `@return` string[] List of cart property paths (dot-delimited) that cannot be optimistic.
	 *
	 * @return string[] List of cart property paths (dot-delimited) that cannot be optimistic.
	 */
	private static function get_non_optimistic_properties(): array {
		$properties = array();

		if ( has_filter( 'woocommerce_cart_contents_count' ) ) {
			$properties[] = 'cart.items_count';
		}

		return $properties;
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

	/**
	 * Get cart errors formatted as notices for the store-notices interactivity store.
	 *
	 * Returns errors from the hydrated cart state in the format expected by
	 * the store-notices store context.
	 *
	 * @param string $consent_statement The consent statement string.
	 * @return array Array of notices with id, notice, type, and dismissible keys.
	 * @throws InvalidArgumentException If consent statement doesn't match.
	 */
	public static function get_cart_error_notices( string $consent_statement ): array {
		self::check_consent( $consent_statement );

		// Ensure cart state is loaded so this method works independently.
		if ( null === self::$blocks_shared_cart_state ) {
			self::load_cart_state( $consent_statement );
		}

		$errors  = self::$blocks_shared_cart_state['errors'] ?? array();
		$notices = array();

		foreach ( $errors as $error ) {
			$notices[] = array(
				'id'          => wp_unique_id( 'store-notice-' ),
				'notice'      => $error['message'] ?? '',
				'type'        => 'error',
				'dismissible' => true,
			);
		}

		return $notices;
	}
}
