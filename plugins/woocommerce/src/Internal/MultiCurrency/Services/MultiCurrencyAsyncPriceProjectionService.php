<?php
/**
 * MultiCurrencyAsyncPriceProjectionService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

/**
 * Projects async price renderer metadata and markup without registering hooks.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyAsyncPriceProjectionService {

	private const SCRIPT_HANDLE        = 'wcpay-multi-currency-async-renderer';
	private const SCRIPT_PATH          = 'dist/multi-currency-async-renderer';
	private const STYLE_PATH           = 'dist/multi-currency-async-renderer.css';
	private const LOCALIZED_OBJECT     = 'wcpayAsyncPriceConfig';
	private const SESSION_CACHE_KEY    = 'wcpay_mc_async_config';
	private const SESSION_CACHE_TTL_MS = 300000;
	private const TIMEOUT_MS           = 10000;
	private const MAX_CACHE_SIZE       = 500;

	/**
	 * Project activation blockers for the async price renderer.
	 *
	 * @param bool $cache_optimized_mode Whether cache-optimized mode is active.
	 * @param bool $is_admin             Whether the current request is admin.
	 * @param bool $doing_cron           Whether the current request is cron.
	 * @param bool $is_admin_api_request Whether the current request is an admin API request.
	 * @param bool $has_active_session   Whether a WooCommerce session is already active.
	 * @return string[]
	 *
	 * @since 11.0.0
	 */
	public static function get_activation_blockers(
		bool $cache_optimized_mode,
		bool $is_admin,
		bool $doing_cron,
		bool $is_admin_api_request,
		bool $has_active_session
	): array {
		$blockers = array();

		if ( ! $cache_optimized_mode ) {
			$blockers[] = 'cache_optimized_mode_inactive';
		}

		if ( $is_admin ) {
			$blockers[] = 'admin_context';
		}

		if ( $doing_cron ) {
			$blockers[] = 'cron_context';
		}

		if ( $is_admin_api_request ) {
			$blockers[] = 'admin_api_request';
		}

		if ( $has_active_session ) {
			$blockers[] = 'active_session';
		}

		return $blockers;
	}

	/**
	 * Project whether async price rendering should activate.
	 *
	 * @param bool $cache_optimized_mode Whether cache-optimized mode is active.
	 * @param bool $is_admin             Whether the current request is admin.
	 * @param bool $doing_cron           Whether the current request is cron.
	 * @param bool $is_admin_api_request Whether the current request is an admin API request.
	 * @param bool $has_active_session   Whether a WooCommerce session is already active.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_activate(
		bool $cache_optimized_mode,
		bool $is_admin,
		bool $doing_cron,
		bool $is_admin_api_request,
		bool $has_active_session
	): bool {
		return array() === self::get_activation_blockers(
			$cache_optimized_mode,
			$is_admin,
			$doing_cron,
			$is_admin_api_request,
			$has_active_session
		);
	}

	/**
	 * Project the async price renderer hook/action manifest.
	 *
	 * @param bool $cache_optimized_mode Whether cache-optimized mode is active.
	 * @param bool $is_admin             Whether the current request is admin.
	 * @param bool $doing_cron           Whether the current request is cron.
	 * @param bool $is_admin_api_request Whether the current request is an admin API request.
	 * @param bool $has_active_session   Whether a WooCommerce session is already active.
	 * @return array<string,mixed>
	 *
	 * @since 11.0.0
	 */
	public static function get_hook_manifest(
		bool $cache_optimized_mode,
		bool $is_admin,
		bool $doing_cron,
		bool $is_admin_api_request,
		bool $has_active_session
	): array {
		$blockers = self::get_activation_blockers(
			$cache_optimized_mode,
			$is_admin,
			$doing_cron,
			$is_admin_api_request,
			$has_active_session
		);

		if ( array() !== $blockers ) {
			return array(
				'filters'  => array(),
				'actions'  => array(),
				'blockers' => $blockers,
			);
		}

		return array(
			'filters'  => array(
				'wc_price'                       => self::hook_entry( 'wrap_price_with_skeleton', 999, 5 ),
				'woocommerce_format_sale_price'  => self::hook_entry( 'annotate_sale_price_screen_reader_text', 999, 3 ),
				'woocommerce_format_price_range' => self::hook_entry( 'annotate_price_range_screen_reader_text', 999, 3 ),
			),
			'actions'  => array(
				'wp_enqueue_scripts' => self::hook_entry( 'enqueue_async_renderer', 10, 1 ),
			),
			'blockers' => array(),
		);
	}

	/**
	 * Wrap a formatted price with async renderer skeleton markup.
	 *
	 * @param string           $price_html        Formatted price HTML.
	 * @param int|float|string $unformatted_price Raw unformatted price.
	 * @param string           $price_type        Async price type.
	 * @return string
	 *
	 * @since 11.0.0
	 */
	public static function wrap_price_with_skeleton(
		string $price_html,
		$unformatted_price,
		string $price_type = 'product'
	): string {
		return sprintf(
			'<span class="woocommerce-Price-amount amount wcpay-async-price" data-wcpay-price="%s" data-wcpay-price-type="%s"><bdi class="wcpay-price-skeleton"></bdi><span class="screen-reader-text wcpay-price-placeholder">%s</span></span>',
			esc_attr( (string) $unformatted_price ),
			esc_attr( $price_type ),
			wp_kses_post( $price_html )
		);
	}

	/**
	 * Annotate sale price screen-reader spans with raw prices.
	 *
	 * @param string           $price_html    Sale price HTML.
	 * @param int|float|string $regular_price Regular price.
	 * @param int|float|string $sale_price    Sale price.
	 * @return string
	 *
	 * @since 11.0.0
	 */
	public static function annotate_sale_price_screen_reader_text(
		string $price_html,
		$regular_price,
		$sale_price
	): string {
		if ( ! is_numeric( $regular_price ) || ! is_numeric( $sale_price ) ) {
			return $price_html;
		}

		$count  = 0;
		$result = preg_replace_callback(
			'/<span class="screen-reader-text">/',
			static function () use ( $regular_price, $sale_price, &$count ): string {
				++$count;

				if ( 1 === $count ) {
					return sprintf(
						'<span class="screen-reader-text" data-wcpay-sr-type="sale_original" data-wcpay-sr-price="%s">',
						esc_attr( (string) $regular_price )
					);
				}

				return sprintf(
					'<span class="screen-reader-text" data-wcpay-sr-type="sale_current" data-wcpay-sr-price="%s">',
					esc_attr( (string) $sale_price )
				);
			},
			$price_html,
			2
		);

		return is_string( $result ) ? $result : $price_html;
	}

	/**
	 * Annotate price range screen-reader spans with raw prices.
	 *
	 * @param string           $price_html Price range HTML.
	 * @param int|float|string $from       Minimum price.
	 * @param int|float|string $to         Maximum price.
	 * @return string
	 *
	 * @since 11.0.0
	 */
	public static function annotate_price_range_screen_reader_text(
		string $price_html,
		$from,
		$to
	): string {
		if ( ! is_numeric( $from ) || ! is_numeric( $to ) ) {
			return $price_html;
		}

		$result = preg_replace(
			'/<span class="screen-reader-text">/',
			sprintf(
				'<span class="screen-reader-text" data-wcpay-sr-type="range" data-wcpay-sr-price-from="%s" data-wcpay-sr-price-to="%s">',
				esc_attr( (string) $from ),
				esc_attr( (string) $to )
			),
			$price_html,
			1
		);

		return is_string( $result ) ? $result : $price_html;
	}

	/**
	 * Build default-currency config for localized async fallback rendering.
	 *
	 * @param string $symbol             Currency symbol.
	 * @param int    $decimals           Decimal count.
	 * @param string $decimal_separator  Decimal separator.
	 * @param string $thousand_separator Thousand separator.
	 * @param string $symbol_position    Symbol position.
	 * @return array<string,mixed>
	 *
	 * @since 11.0.0
	 */
	public static function get_default_currency_config(
		string $symbol,
		int $decimals,
		string $decimal_separator,
		string $thousand_separator,
		string $symbol_position
	): array {
		return array(
			'symbol'       => html_entity_decode( $symbol, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
			'decimals'     => $decimals,
			'decimal_sep'  => $decimal_separator,
			'thousand_sep' => $thousand_separator,
			'symbol_pos'   => $symbol_position,
		);
	}

	/**
	 * Project async renderer asset registration and localized config metadata.
	 *
	 * @param string              $api_url          Public config REST URL.
	 * @param array<string,mixed> $default_currency Default-currency fallback config.
	 * @param string              $style_url        Stylesheet URL.
	 * @param string              $style_version    Stylesheet version.
	 * @return array<string,mixed>
	 *
	 * @since 11.0.0
	 */
	public static function get_asset_manifest(
		string $api_url,
		array $default_currency,
		string $style_url = '',
		string $style_version = ''
	): array {
		return array(
			'script' => array(
				'handle'           => self::SCRIPT_HANDLE,
				'path'             => self::SCRIPT_PATH,
				'localized_object' => self::LOCALIZED_OBJECT,
				'config'           => array(
					'apiUrl'          => $api_url,
					'defaultCurrency' => $default_currency,
					'srText'          => self::get_screen_reader_text_templates(),
				),
			),
			'style'  => array(
				'handle'  => self::SCRIPT_HANDLE,
				'path'    => self::STYLE_PATH,
				'url'     => $style_url,
				'version' => $style_version,
			),
			'client' => array(
				'session_cache_key'    => self::SESSION_CACHE_KEY,
				'session_cache_ttl_ms' => self::SESSION_CACHE_TTL_MS,
				'timeout_ms'           => self::TIMEOUT_MS,
				'max_cache_size'       => self::MAX_CACHE_SIZE,
			),
		);
	}

	/**
	 * Build hook metadata.
	 *
	 * @param string $callback      Callback marker.
	 * @param int    $priority      Hook priority.
	 * @param int    $accepted_args Accepted argument count.
	 * @return array<string,mixed>
	 */
	private static function hook_entry( string $callback, int $priority, int $accepted_args ): array {
		return array(
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
	}

	/**
	 * Get screen-reader text templates localized like WooCommerce core prices.
	 *
	 * @return array<string,string>
	 */
	private static function get_screen_reader_text_templates(): array {
		return array(
			'sale_original' => self::get_sale_original_screen_reader_text(),
			'sale_current'  => self::get_sale_current_screen_reader_text(),
			'range'         => self::get_range_screen_reader_text(),
		);
	}

	/**
	 * Get the original sale price screen-reader template.
	 *
	 * @return string
	 */
	private static function get_sale_original_screen_reader_text(): string {
		/* translators: %s: formatted price. */
		$text = __( 'Original price was: %s.', 'woocommerce' );

		return $text;
	}

	/**
	 * Get the current sale price screen-reader template.
	 *
	 * @return string
	 */
	private static function get_sale_current_screen_reader_text(): string {
		/* translators: %s: formatted price. */
		$text = __( 'Current price is: %s.', 'woocommerce' );

		return $text;
	}

	/**
	 * Get the price range screen-reader template.
	 *
	 * @return string
	 */
	private static function get_range_screen_reader_text(): string {
		/* translators: %1$s: minimum price, %2$s: maximum price. */
		$text = __( 'Price range: %1$s through %2$s', 'woocommerce' );

		return $text;
	}
}
