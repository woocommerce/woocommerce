<?php
/**
 * WooCommerce Analytics Tracking for tracking frontend events
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic\Woocommerce_Analytics;

use Automattic\Jetpack\Connection\Manager as Jetpack_Connection;
use WC_Tracks;
use WC_Tracks_Client;
use WC_Tracks_Event;
use WP_Error;

/**
 * WooCommerce Analytics Tracking class
 */
class WC_Analytics_Tracking extends WC_Tracks {
	/**
	 * Event prefix.
	 *
	 * @var string
	 */
	const PREFIX = 'woocommerceanalytics_';

	/**
	 * Allowed ClickHouse events.
	 *
	 * @var array
	 */
	const ALLOWED_CH_EVENTS = array(
		'woocommerceanalytics_session_started',
		'woocommerceanalytics_session_engagement',
		'woocommerceanalytics_product_view',
		'woocommerceanalytics_cart_view',
		'woocommerceanalytics_add_to_cart',
		'woocommerceanalytics_remove_from_cart',
		'woocommerceanalytics_checkout_view',
		'woocommerceanalytics_product_checkout',
		'woocommerceanalytics_product_purchase',
		'woocommerceanalytics_order_confirmation_view',
		'woocommerceanalytics_search',
		'woocommerceanalytics_page_view',
	);

	/**
	 * Record an event in Tracks and ClickHouse (If enabled).
	 *
	 * @param string $event_name The name of the event.
	 * @param array  $event_properties Custom properties to send with the event.
	 *
	 * @return bool|WP_Error True for success or WP_Error if the event pixel could not be fired.
	 */
	public static function record_event( $event_name, $event_properties = array() ) {
		$prefixed_event_name = self::PREFIX . $event_name;
		$properties          = self::get_properties( $prefixed_event_name, $event_properties );

		// Record Tracks event.
		$tracks_error  = null;
		$tracks_result = self::record_tracks_event( $properties );
		if ( is_wp_error( $tracks_result ) ) {
			$tracks_error = $tracks_result;
		}

		// Record ClickHouse event, if applicable.
		$ch_error = null;
		if ( self::should_send_to_clickhouse( $prefixed_event_name ) ) {
			$properties['ch'] = 1;
			$ch_result        = self::record_ch_event( $properties );
			if ( is_wp_error( $ch_result ) ) {
				$ch_error = $ch_result;
			}
		}

		// If both failed, return the Tracks error (primary), else the CH error, else true.
		if ( $tracks_error ) {
			return $tracks_error;
		}
		if ( $ch_error ) {
			return $ch_error;
		}

		return true;
	}

	/**
	 * Record an event in Tracks.
	 *
	 * @param array $properties Properties to send with the event.
	 * @return bool|WP_Error True for success or WP_Error if the event pixel could not be fired.
	 */
	private static function record_tracks_event( $properties = array() ) {
		$event_obj = new WC_Tracks_Event( $properties );
		if ( is_wp_error( $event_obj->error ) ) {
			return $event_obj->error;
		}

		return WC_Tracks_Client::record_event( $event_obj ); // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid @phan-suppress-current-line PhanTypeMismatchArgumentProbablyReal
	}

	/**
	 * Record a ClickHouse event.
	 *
	 * @param array $properties The event properties.
	 * @return bool|WP_Error True for success or WP_Error if the event pixel could not be fired.
	 */
	private static function record_ch_event( $properties ) {
		$event_obj = new WC_Analytics_Ch_Event( $properties );
		if ( is_wp_error( $event_obj->error ) ) {
			return $event_obj->error;
		}
		return WC_Tracks_Client::record_event( $event_obj ); // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid @phan-suppress-current-line PhanTypeMismatchArgumentProbablyReal
	}

	/**
	 * Get all properties for the event including filtered and identity properties.
	 *
	 * @param string $event_name Event name.
	 * @param array  $event_properties Event specific properties.
	 * @return array
	 */
	public static function get_properties( $event_name, $event_properties ) {
		$blog_user_id    = self::get_blog_user_id();
		$server_details  = self::get_server_details();
		$blog_details    = self::get_blog_details( 0 );
		$session_details = self::get_session_details();

		$common_properties = array_merge(
			array(
				'session_id'     => $session_details['session_id'] ?? null,
				'landing_page'   => $session_details['landing_page'] ?? null,
				'is_engaged'     => $session_details['is_engaged'] ?? null,
				'ui'             => $blog_user_id,
				'blog_id'        => $blog_details['blog_id'],
				'store_id'       => $blog_details['store_id'],
				'url'            => $blog_details['url'],
				'woo_version'    => $blog_details['wc_version'],
				'wp_version'     => get_bloginfo( 'version' ),
				'store_admin'    => count( array_intersect( array( 'administrator', 'shop_manager' ), wp_get_current_user()->roles ) ) > 0 ? 1 : 0,
				'device'         => wp_is_mobile() ? 'mobile' : 'desktop',
				'store_currency' => get_woocommerce_currency(),
				'timezone'       => wp_timezone_string(),
				'is_guest'       => $blog_user_id === null,
			),
			$server_details
		);

		/**
		 * Allow defining custom event properties in WooCommerce Analytics.
		 *
		 * @module woocommerce-analytics
		 *
		 * @since 12.5
		 *
		 * @param array $all_props Array of event props to be filtered.
		 */
		$properties = apply_filters( 'jetpack_woocommerce_analytics_event_props', array_merge( $common_properties, $event_properties ), $event_name );

		$required_properties = $event_name
			? array(
				'_en' => $event_name,
				'_ts' => WC_Tracks_Client::build_timestamp(),
				'_ut' => 'anon',
				'_ui' => self::get_visitor_id(),
			)
			: array();

		return array_merge( $properties, $required_properties );
	}

	/**
	 * Get the current user id. Returned as a string in the format "blog_id:user_id".
	 *
	 * @return string|null
	 */
	private static function get_blog_user_id() {
		if ( is_user_logged_in() ) {
			$blogid = Jetpack_Connection::get_site_id();
			$userid = get_current_user_id();
			return $blogid . ':' . $userid;
		}
		return null;
	}

	/**
	 * Gather details from the request to the server.
	 *
	 * @return array Server details.
	 */
	public static function get_server_details() {
		$data = parent::get_server_details();
		return array_merge(
			$data,
			array(
				 // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				'_via_ref' => isset( $_SERVER['HTTP_REFERER'] ) ? wc_clean( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '',
				'_via_ip'  => self::get_user_ip_address(),
				// Get the first language defined from the request headers.
				'_lg'      => isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ), 0, 5 ) : '',
			)
		);
	}

	/**
	 * Get the session details as an array
	 *
	 * @return array
	 */
	private static function get_session_details() {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON is decoded and validated below. We don't need to sanitize the cookie value because we're not outputting it but decoding it as JSON. Sanitization might break the JSON.
		$raw_cookie = isset( $_COOKIE['woocommerceanalytics_session'] ) ? wp_unslash( $_COOKIE['woocommerceanalytics_session'] ) : '';

		if ( ! $raw_cookie ) {
			return array();
		}

		$decoded = json_decode( rawurldecode( $raw_cookie ), true );
		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * Get the visitor id from the cookie.
	 *
	 * @return string|null
	 */
	private static function get_visitor_id() {
		if ( isset( $_COOKIE['tk_ai'] ) ) {
			return sanitize_text_field( wp_unslash( $_COOKIE['tk_ai'] ) );
		}
		return null;
	}

	/**
	 * Check if the event should be sent to ClickHouse
	 *
	 * @param string $event The event name.
	 * @return bool True if it should be sent to ClickHouse
	 */
	private static function should_send_to_clickhouse( $event ) {
		return self::is_clickhouse_enabled() &&
			in_array( $event, self::ALLOWED_CH_EVENTS, true );
	}

	/**
	 * Check if ClickHouse is enabled.
	 *
	 * @return bool
	 */
	private static function is_clickhouse_enabled() {
		/**
		 * Filter to enable/disable ClickHouse event tracking.
		 *
		 * @module woocommerce-analytics
		 *
		 * @since 0.5.0
		 *
		 * @param bool $enabled Whether ClickHouse event tracking is enabled.
		 */
		return apply_filters( 'woocommerce_analytics_clickhouse_enabled', false );
	}

	/**
	 * Get the user's IP address.
	 *
	 * @return string The user's IP address. An empty string if no valid IP address is found.
	 */
	private static function get_user_ip_address() {
		$ip_headers = array(
			'HTTP_CF_CONNECTING_IP', // Cloudflare specific header.
			'HTTP_X_FORWARDED_FOR',
			'REMOTE_ADDR',
			'HTTP_CLIENT_IP',
		);

		foreach ( $ip_headers as $header ) {
			if ( isset( $_SERVER[ $header ] ) ) {
				$ip_list = explode( ',', wp_unslash( $_SERVER[ $header ] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				foreach ( $ip_list as $ip_candidate ) {
					$ip_candidate = trim( $ip_candidate );
					if ( filter_var(
						$ip_candidate,
						FILTER_VALIDATE_IP,
						array( FILTER_FLAG_NO_RES_RANGE, FILTER_FLAG_IPV6 )
					) ) {
						return $ip_candidate;
					}
				}
			}
		}

		return '';
	}
}
