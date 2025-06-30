<?php
/**
 * WCAdminHelper
 *
 * Helper class for generic WCAdmin functions.
 */

namespace Automattic\WooCommerce\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Class WCAdminHelper
 */
class WCAdminHelper {
	/**
	 * WC Admin timestamp option name.
	 */
	const WC_ADMIN_TIMESTAMP_OPTION = 'woocommerce_admin_install_timestamp';

	const WC_ADMIN_STORE_AGE_RANGES = array(
		'week-1'    => array(
			'start' => 0,
			'end'   => WEEK_IN_SECONDS,
		),
		'week-1-4'  => array(
			'start' => WEEK_IN_SECONDS,
			'end'   => WEEK_IN_SECONDS * 4,
		),
		'month-1-3' => array(
			'start' => MONTH_IN_SECONDS,
			'end'   => MONTH_IN_SECONDS * 3,
		),
		'month-3-6' => array(
			'start' => MONTH_IN_SECONDS * 3,
			'end'   => MONTH_IN_SECONDS * 6,
		),
		'month-6+'  => array(
			'start' => MONTH_IN_SECONDS * 6,
		),
	);

	/**
	 * Get the number of seconds that the store has been active.
	 *
	 * @return number Number of seconds.
	 */
	public static function get_wcadmin_active_for_in_seconds() {
		$install_timestamp = get_option( self::WC_ADMIN_TIMESTAMP_OPTION );

		if ( ! is_numeric( $install_timestamp ) ) {
			$install_timestamp = time();
			update_option( self::WC_ADMIN_TIMESTAMP_OPTION, $install_timestamp );
		}

		return time() - $install_timestamp;
	}


	/**
	 * Test how long WooCommerce Admin has been active.
	 *
	 * @param int $seconds Time in seconds to check.
	 * @return bool Whether or not WooCommerce admin has been active for $seconds.
	 */
	public static function is_wc_admin_active_for( $seconds ) {
		$wc_admin_active_for = self::get_wcadmin_active_for_in_seconds();

		return ( $wc_admin_active_for >= $seconds );
	}

	/**
	 * Test if WooCommerce Admin has been active within a pre-defined range.
	 *
	 * @param string $range range available in WC_ADMIN_STORE_AGE_RANGES.
	 * @param int    $custom_start custom start in range.
	 * @throws \InvalidArgumentException Throws exception when invalid $range is passed in.
	 * @return bool Whether or not WooCommerce admin has been active within the range.
	 */
	public static function is_wc_admin_active_in_date_range( $range, $custom_start = null ) {
		if ( ! array_key_exists( $range, self::WC_ADMIN_STORE_AGE_RANGES ) ) {
			throw new \InvalidArgumentException(
				sprintf(
					'"%s" range is not supported, use one of: %s',
					$range,
					implode( ', ', array_keys( self::WC_ADMIN_STORE_AGE_RANGES ) )
				)
			);
		}
		$wc_admin_active_for = self::get_wcadmin_active_for_in_seconds();

		$range_data = self::WC_ADMIN_STORE_AGE_RANGES[ $range ];
		$start      = null !== $custom_start ? $custom_start : $range_data['start'];
		if ( $range_data && $wc_admin_active_for >= $start ) {
			return isset( $range_data['end'] ) ? $wc_admin_active_for < $range_data['end'] : true;
		}
		return false;
	}

	/**
	 * Test if the site is fresh. A fresh site must meet the following requirements.
	 *
	 * - The current user was registered less than 1 month ago.
	 * - fresh_site option must be 1
	 *
	 * @return bool
	 */
	public static function is_site_fresh() {
		$fresh_site = get_option( 'fresh_site' );
		if ( '1' !== $fresh_site ) {
			return false;
		}

		$current_userdata = get_userdata( get_current_user_id() );
		// Return false if we can't get user meta data for some reason.
		if ( ! $current_userdata ) {
			return false;
		}

		$date      = new \DateTime( $current_userdata->user_registered );
		$month_ago = new \DateTime( '-1 month' );

		return $date > $month_ago;
	}

	/**
	 * Check if the current page is a store page.
	 *
	 * This should only be called when WP has has set up the query, typically during or after the parse_query or template_redirect action hooks.
	 *
	 * @return bool
	 */
	public static function is_current_page_store_page() {
		// WC store pages.
		$store_pages = array(
			'shop'        => wc_get_page_id( 'shop' ),
			'cart'        => wc_get_page_id( 'cart' ),
			'checkout'    => wc_get_page_id( 'checkout' ),
			'terms'       => wc_terms_and_conditions_page_id(),
			'coming_soon' => wc_get_page_id( 'coming_soon' ),
		);

		/**
		 * Filter the store pages array to check if a URL is a store page.
		 *
		 * @since 8.8.0
		 * @param array $store_pages The store pages array. The keys are the page slugs and the values are the page IDs.
		 */
		$store_pages = apply_filters( 'woocommerce_store_pages', $store_pages );

		foreach ( $store_pages as $page_slug => $page_id ) {
			if ( $page_id > 0 && is_page( $page_id ) ) {
				return true;
			}
		}

		// Product archive page.
		if ( is_post_type_archive( 'product' ) ) {
			return true;
		}

		// Product page.
		if ( is_singular( 'product' ) ) {
			return true;
		}

		// Product taxonomy page (e.g. Product Category, Product Tag, etc.).
		if ( is_product_taxonomy() ) {
			return true;
		}

		global $wp;
		$url = self::get_url_from_wp( $wp );

		/**
		 * Filter if a URL is a store page.
		 *
		 * @since 9.3.0
		 * @param bool   $is_store_page Whether or not the URL is a store page.
		 * @param string $url           URL to check.
		 */
		$is_store_page = apply_filters( 'woocommerce_is_extension_store_page', false, $url );

		return filter_var( $is_store_page, FILTER_VALIDATE_BOOL );
	}

	/**
	 * Test if a URL is a store page.
	 *
	 * @param string $url URL to check. If not provided, the current URL will be used.
	 * @return bool Whether or not the URL is a store page.
	 * @deprecated 9.8.0 Use is_current_page_store_page instead.
	 */
	public static function is_store_page( $url = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		_deprecated_function( __METHOD__, '9.8.0', 'is_current_page_store_page' );
		return self::is_current_page_store_page();
	}

	/**
	 * Get normalized URL path.
	 * 1. Only keep the path and query string (if any).
	 * 2. Remove wp home path from the URL path if WP is installed in a subdirectory.
	 * 3. Remove leading and trailing slashes.
	 *
	 * For example:
	 *
	 * - https://example.com/wordpress/shop/uncategorized/test/?add-to-cart=123 => shop/uncategorized/test/?add-to-cart=123
	 *
	 * @param string $url URL to normalize.
	 */
	private static function get_normalized_url_path( $url ) {
		$query           = wp_parse_url( $url, PHP_URL_QUERY );
		$path            = wp_parse_url( $url, PHP_URL_PATH ) . ( $query ? '?' . $query : '' );
		$home_path       = wp_parse_url( site_url(), PHP_URL_PATH ) ?? '';
		$normalized_path = trim( substr( $path, strlen( $home_path ) ), '/' );
		return $normalized_path;
	}

	/**
	 * Builds the relative URL from the WP instance.
	 *
	 * @internal
	 * @link https://wordpress.stackexchange.com/a/274572
	 * @param \WP $wp WordPress environment instance.
	 */
	private static function get_url_from_wp( \WP $wp ) {
		// Initialize query vars if they haven't been set.
		if ( empty( $wp->query_vars ) || empty( $wp->request ) ) {
			$wp->parse_request();
		}

		return home_url( add_query_arg( $wp->query_vars, $wp->request ) );
	}
}
