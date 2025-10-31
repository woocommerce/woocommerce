<?php
/**
 * WooCommerce Analytics Tracking for tracking frontend events
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic\Woocommerce_Analytics;

use Automattic\Jetpack\Connection\Manager as Jetpack_Connection;
use Automattic\Jetpack\Device_Detection\User_Agent_Info;
use WC_Site_Tracking;
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
	 * Option name for storing daily salt data.
	 *
	 * @var string
	 */
	const DAILY_SALT_OPTION = 'woocommerce_analytics_daily_salt';

	/**
	 * Event queue.
	 *
	 * @var array
	 */
	protected static $event_queue = array();

	/**
	 * Cached user IP address for the current request.
	 *
	 * @var string|null
	 */
	private static $cached_ip = null;

	/**
	 * Cached visitor ID for the current request.
	 *
	 * @var string|null
	 */
	private static $cached_visitor_id = null;

	/**
	 * Record an event in Tracks and ClickHouse (If enabled).
	 *
	 * @param string $event_name The name of the event.
	 * @param array  $event_properties Custom properties to send with the event.
	 *
	 * @return bool|WP_Error True for success or WP_Error if the event pixel could not be fired.
	 */
	public static function record_event( $event_name, $event_properties = array() ) {
		// Check consent before recording any event
		if ( ! Consent_Manager::has_analytics_consent() ) {
			return true; // Skip recording.
		}

		// Skip recording if the request is coming from a bot.
		if ( User_Agent_Info::is_bot() ) {
			return true;
		}

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
		if ( Features::is_clickhouse_enabled() ) {
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
	 * Queue an event in the event queue which will be processed on the page load in client-side analytics.
	 *
	 * @param string $event_name The name of the event.
	 * @param array  $properties The event properties.
	 */
	public static function add_event_to_queue( $event_name, $properties = array() ) {
		self::$event_queue[] = array(
			'eventName' => $event_name,
			'props'     => $properties,
		);
	}

	/**
	 * Get the event queue.
	 *
	 * @return array The event queue.
	 */
	public static function get_event_queue() {
		return self::$event_queue;
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
				'blog_id'        => $blog_details['blog_id'] ?? null,
				'store_id'       => $blog_details['store_id'] ?? null,
				'url'            => $blog_details['url'] ?? null,
				'woo_version'    => $blog_details['wc_version'] ?? null,
				'wp_version'     => get_bloginfo( 'version' ),
				'store_admin'    => count( array_intersect( array( 'administrator', 'shop_manager' ), wp_get_current_user()->roles ) ) > 0 ? 1 : 0,
				'device'         => wp_is_mobile() ? 'mobile' : 'desktop',
				'store_currency' => get_woocommerce_currency(),
				'timezone'       => wp_timezone_string(),
				'is_guest'       => ( $blog_user_id === null ) ? 1 : 0,
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

		$all_properties = array_merge( $properties, $required_properties );

		// Convert array values to a comma-separated string and URL-encode them to ensure compatibility with JavaScript's encodeURIComponent() for pixel URL transmission.
		foreach ( $all_properties as $key => $value ) {
			if ( ! is_array( $value ) ) {
				continue;
			}

			if ( empty( $value ) ) {
				$all_properties[ $key ] = '';
				continue;
			}

			$is_indexed_array = array_keys( $value ) === range( 0, count( $value ) - 1 );
			if ( $is_indexed_array ) {
				$value_string           = implode( ',', $value );
				$all_properties[ $key ] = rawurlencode( $value_string );
				continue;
			}

			// Serialize non-indexed arrays to JSON strings.
			$all_properties[ $key ] = wp_json_encode( $value );
		}

		return $all_properties;
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
		$data = array();

		if ( method_exists( parent::class, 'get_server_details' ) ) {
			$data = parent::get_server_details();
		} elseif ( method_exists( WC_Site_Tracking::class, 'get_server_details' ) ) {
			// WC < 6.8
			$data = WC_Site_Tracking::get_server_details(); // @phan-suppress-current-line PhanUndeclaredStaticMethod -- method is available in WC < 6.8
		}

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
	 * Get the blog details.
	 *
	 * @param int $blog_id The blog ID.
	 * @return array The blog details.
	 */
	public static function get_blog_details( $blog_id ) {
		if ( method_exists( parent::class, 'get_blog_details' ) ) {
			return parent::get_blog_details( $blog_id );
		} elseif ( method_exists( WC_Site_Tracking::class, 'get_blog_details' ) ) {
			// WC < 6.8
			return WC_Site_Tracking::get_blog_details( $blog_id ); // @phan-suppress-current-line PhanUndeclaredStaticMethod -- method is available in WC < 6.8
		}
		return array();
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
	 * Get the visitor id from the cookie or IP address (if proxy tracking is enabled).
	 *
	 * @return string|null
	 */
	private static function get_visitor_id() {
		// Return cached result if available.
		if ( null !== self::$cached_visitor_id ) {
			return self::$cached_visitor_id;
		}

		// Prefer tk_ai cookie if present.
		if ( ! empty( $_COOKIE['tk_ai'] ) ) {
			self::$cached_visitor_id = sanitize_text_field( wp_unslash( $_COOKIE['tk_ai'] ) );
			return self::$cached_visitor_id;
		}

		// Fallback to IP-based visitor ID if proxy tracking is enabled.
		if ( Features::is_proxy_tracking_enabled() ) {
			self::$cached_visitor_id = self::get_ip_based_visitor_id();
			return self::$cached_visitor_id;
		}

		// Generate a new anonId and try to save it in the browser's cookies.
		// Note that base64-encoding an 18 character string generates a 24-character anon id.
		$binary = '';
		for ( $i = 0; $i < 18; ++$i ) {
			$binary .= chr( wp_rand( 0, 255 ) );
		}

		self::$cached_visitor_id = base64_encode( $binary ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode


		if ( ! headers_sent()
			&& ! ( defined( 'REST_REQUEST' ) && REST_REQUEST )
			&& ! ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST )
		) {
			setcookie(
				'tk_ai',
				self::$cached_visitor_id,
				array(
					'expires'  => time() + ( 365 * 24 * 60 * 60 ), // 1 year
					'path'     => '/',
					'domain'   => COOKIE_DOMAIN,
					'secure'   => is_ssl(),
					'httponly' => true,
					'samesite' => 'Strict',
				)
			);
		}
		return self::$cached_visitor_id;
	}

	/**
	 * Get the user's IP address.
	 *
	 * @return string The user's IP address. An empty string if no valid IP address is found.
	 */
	private static function get_user_ip_address() {
		// Return cached IP if available
		if ( null !== self::$cached_ip ) {
			return self::$cached_ip;
		}

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
						// Cache the resolved IP
						self::$cached_ip = $ip_candidate;
						return self::$cached_ip;
					}
				}
			}
		}

		// Cache empty result
		self::$cached_ip = '';
		return self::$cached_ip;
	}

	/**
	 * Get IP-based visitor ID for proxy tracking mode.
	 *
	 * @return string|null
	 */
	private static function get_ip_based_visitor_id() {
		$ip = self::get_user_ip_address();
		if ( empty( $ip ) ) {
			return null;
		}

		$salt       = self::get_daily_salt();
		$url_parts  = wp_parse_url( home_url() );
		$domain     = $url_parts['host'] ?? '';
		$user_agent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );

		// Create hash from: daily_salt + domain + ip + user_agent
		$hash_input = $salt . $domain . $ip . $user_agent;

		return substr( hash( 'sha256', $hash_input ), 0, 16 );
	}

	/**
	 * Get or generate daily salt for visitor ID hashing.
	 * Creates a new salt value each day (UTC) for privacy protection.
	 *
	 * @return string The daily salt.
	 */
	private static function get_daily_salt() {
		$today = gmdate( 'Y-m-d' ); // UTC date

		$salt_data = get_option( self::DAILY_SALT_OPTION );

		// Check if salt exists and is still valid for today
		if (
			is_array( $salt_data )
			&& isset( $salt_data['date'] )
			&& isset( $salt_data['salt'] )
			&& $salt_data['date'] === $today
		) {
			return $salt_data['salt'];
		}

		// Generate new salt for today
		$new_salt = wp_generate_password( 32, false );

		// Store salt with date (no expiration time needed)
		$salt_data = array(
			'date' => $today,
			'salt' => $new_salt,
		);

		update_option( self::DAILY_SALT_OPTION, $salt_data );
		return $new_salt;
	}
}
