<?php
/**
 * WooCommerce Analytics Tracking for tracking frontend events
 *
 * This class is designed to work without WooCommerce dependencies,
 * enabling it to run at the MU-plugin stage without loading WooCommerce to optimize performance.
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic\Woocommerce_Analytics;

use Automattic\Jetpack\Device_Detection;
use Automattic\Jetpack\Device_Detection\User_Agent_Info;
use Automattic\Jetpack\IP\Utils as IP_Utils;
use WP_Error;

/**
 * WooCommerce Analytics Tracking class
 */
class WC_Analytics_Tracking {
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
	 * Batch pixel queue for batched requests.
	 *
	 * @var array
	 */
	private static $pixel_batch_queue = array();

	/**
	 * Whether the shutdown hook has been registered.
	 *
	 * @var bool
	 */
	private static $shutdown_hook_registered = false;

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
	 * @return bool|WP_Error True on emit or deliberate skip (no consent, bot UA,
	 *                       or cookie-less context); WP_Error if pixel firing failed.
	 */
	public static function record_event( $event_name, $event_properties = array() ) {
		// Check consent before recording any event.
		if ( ! Consent_Manager::has_analytics_consent() ) {
			return true; // Skip recording.
		}

		// Skip recording if the request is coming from a bot.
		if ( User_Agent_Info::is_bot() ) {
			return true;
		}

		// Skip events that arrive without a stable visitor id (e.g. no tk_ai cookie); see get_visitor_id().
		if ( empty( self::get_visitor_id() ) ) {
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
		if ( Features::is_clickhouse_enabled() || ( isset( $properties['ch'] ) && 1 === (int) $properties['ch'] ) ) {
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
		$pixel_url = Pixel_Builder::build_tracks_url( $properties );

		if ( is_wp_error( $pixel_url ) ) {
			return $pixel_url;
		}

		return self::record_pixel_url( $pixel_url );
	}

	/**
	 * Record a ClickHouse event.
	 *
	 * @param array $properties The event properties.
	 * @return bool|WP_Error True for success or WP_Error if the event pixel could not be fired.
	 */
	private static function record_ch_event( $properties ) {
		$pixel_url = Pixel_Builder::build_ch_url( $properties );

		if ( is_wp_error( $pixel_url ) ) {
			return $pixel_url;
		}

		return self::record_pixel_url( $pixel_url );
	}

	/**
	 * Record a pixel URL using batching.
	 *
	 * @param string $pixel_url The pixel URL to record.
	 * @return bool|WP_Error True for success or WP_Error if the event pixel could not be fired.
	 */
	private static function record_pixel_url( $pixel_url ) {
		if ( empty( $pixel_url ) ) {
			return new WP_Error( 'invalid_pixel', 'cannot generate tracks pixel for given input', 400 );
		}

		// Check if batching is supported.
		$can_batch = ( class_exists( 'WpOrg\Requests\Requests' ) && method_exists( 'WpOrg\Requests\Requests', 'request_multiple' ) )
			|| ( class_exists( 'Requests' ) && method_exists( 'Requests', 'request_multiple' ) );

		if ( $can_batch ) {
			// Queue the pixel and send on shutdown.
			self::queue_pixel_for_batch( $pixel_url );
		} else {
			// Send immediately as batching is not supported.
			Pixel_Builder::send_pixel( $pixel_url );
		}

		return true;
	}

	/**
	 * Queue a pixel URL for batch sending.
	 *
	 * @param string $pixel The pixel URL to queue.
	 */
	private static function queue_pixel_for_batch( $pixel ) {
		self::$pixel_batch_queue[] = $pixel;

		// Register shutdown hook once.
		if ( ! self::$shutdown_hook_registered ) {
			add_action( 'shutdown', array( __CLASS__, 'send_batched_pixels' ), 20 );
			self::$shutdown_hook_registered = true;
		}
	}

	/**
	 * Send all queued pixels using batched non-blocking requests.
	 * This runs on the shutdown hook to batch all requests together.
	 *
	 * Uses Pixel_Builder for the actual sending via Requests library.
	 */
	public static function send_batched_pixels() {
		if ( empty( self::$pixel_batch_queue ) ) {
			return;
		}

		// Delegate to Pixel_Builder for batched sending.
		Pixel_Builder::send_pixels_batched( self::$pixel_batch_queue );

		// Clear the queue.
		self::$pixel_batch_queue = array();
	}

	/**
	 * Request-scoped — not for page output, see `get_page_common_properties()`.
	 *
	 * Includes the session cookie and `get_server_details()`, so this is only safe
	 * for events the server fires itself on an uncached request, which includes the
	 * proxy tracking endpoint.
	 *
	 * @return array The common properties.
	 */
	public static function get_common_properties() {
		return array_merge(
			self::get_session_properties(),
			self::get_page_common_properties(),
			self::get_server_details()
		);
	}

	/**
	 * Get the visitor's session properties from the session cookie.
	 *
	 * Request-derived, so these are for the server-fired path only. The cookie is
	 * written and read by the client's own SessionManager, which supplies these
	 * properties directly on events it sends.
	 *
	 * @since 0.16.7
	 *
	 * @return array The session properties.
	 */
	private static function get_session_properties() {
		$session_details = self::get_session_details();

		return array(
			'session_id'   => $session_details['session_id'] ?? null,
			'landing_page' => $session_details['landing_page'] ?? null,
			'is_engaged'   => $session_details['is_engaged'] ?? null,
		);
	}

	/**
	 * Get the common properties that are safe to embed in cacheable page HTML.
	 *
	 * Request headers and cookies are not part of the CDN cache key, so a property
	 * derived from one is attributed to every later visitor of the cached page.
	 * Anything request-derived belongs in `get_session_properties()` or
	 * `get_server_details()`, which only reach the server-fired path.
	 *
	 * Two exceptions, neither of them licence to add a third: `device` is
	 * User-Agent derived and a known gap, tracked for a client-side follow-up;
	 * `ui`, `is_guest` and `store_admin` are safe only because caches bypass
	 * logged-in requests.
	 *
	 * @since 0.16.7
	 *
	 * @return array The common properties.
	 */
	public static function get_page_common_properties() {
		$blog_user_id = self::get_blog_user_id();
		$blog_details = self::get_blog_details();

		return array(
			'ui'             => $blog_user_id,
			'blog_id'        => $blog_details['blog_id'] ?? null,
			'store_id'       => $blog_details['store_id'] ?? null,
			'url'            => $blog_details['url'] ?? null,
			'woo_version'    => $blog_details['wc_version'] ?? null,
			'wp_version'     => get_bloginfo( 'version' ),
			'store_admin'    => count( array_intersect( array( 'administrator', 'shop_manager' ), wp_get_current_user()->roles ) ) > 0 ? 1 : 0,
			'device'         => self::get_device_type(),
			'store_currency' => $blog_details['store_currency'] ?? null,
			'timezone'       => wp_timezone_string(),
			'is_guest'       => ( $blog_user_id === null || $blog_user_id === 0 ) ? 1 : 0,
		);
	}

	/**
	 * Get all properties for the event including filtered and identity properties.
	 *
	 * @param string $event_name Event name.
	 * @param array  $event_properties Event specific properties.
	 * @return array
	 */
	public static function get_properties( $event_name, $event_properties ) {
		$common_properties = self::get_common_properties();

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
				'_ts' => Pixel_Builder::build_timestamp(),
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
			$all_properties[ $key ] = wp_json_encode( $value, JSON_UNESCAPED_SLASHES );
		}

		return $all_properties;
	}

	/**
	 * Get the current user id.
	 *
	 * @return int The user ID, or 0 if not logged in.
	 */
	private static function get_blog_user_id() {
		// Ensure cookie constants are defined.
		if ( ! defined( 'LOGGED_IN_COOKIE' ) ) {
			if ( function_exists( 'wp_cookie_constants' ) ) {
				wp_cookie_constants();
			} else {
				require_once ABSPATH . WPINC . '/default-constants.php';
				wp_cookie_constants();
			}
		}

		if ( function_exists( 'get_current_user_id' ) && get_current_user_id() ) {
			return get_current_user_id();
		}

		// Manually validate the logged_in cookie
		if ( ! function_exists( 'wp_validate_auth_cookie' ) ) {
			require_once ABSPATH . WPINC . '/pluggable.php';
		}

		$user_id = wp_validate_auth_cookie( '', 'logged_in' );

		return $user_id ? (int) $user_id : 0;
	}

	/**
	 * Gather details from the request to the server.
	 *
	 * This method is now standalone and doesn't rely on WC_Tracks parent class.
	 *
	 * @return array Server details.
	 */
	public static function get_server_details() {
		// Sanitization helper - use wc_clean if available, otherwise sanitize_text_field.
		$clean = function_exists( 'wc_clean' ) ? 'wc_clean' : 'sanitize_text_field';

		$data = array(
			'_via_ua' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? $clean( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			'_via_ip' => self::get_user_ip_address(),
			'_lg'     => isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ), 0, 5 ) : '',
			'_dr'     => isset( $_SERVER['HTTP_REFERER'] ) ? $clean( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		);

		// Build the document location URL.
		$uri         = isset( $_SERVER['REQUEST_URI'] ) ? $clean( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$host        = isset( $_SERVER['HTTP_HOST'] ) ? $clean( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$data['_dl'] = isset( $_SERVER['REQUEST_SCHEME'] ) ? $clean( wp_unslash( $_SERVER['REQUEST_SCHEME'] ) ) . '://' . $host . $uri : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		// Add _via_ref (referrer) for backward compatibility.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$data['_via_ref'] = isset( $_SERVER['HTTP_REFERER'] ) ? $clean( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';

		return $data;
	}

	/**
	 * Get the blog details.
	 *
	 * This method is now standalone and doesn't rely on WC_Tracks parent class.
	 * It still works with WooCommerce when available for additional details.
	 *
	 * @return array The blog details.
	 */
	public static function get_blog_details() {
		// Try to get cached blog details.
		$blog_details = get_transient( 'wc_analytics_blog_details' );

		if ( false !== $blog_details ) {
			return $blog_details;
		}

		// Get Jetpack blog ID if available.
		$jetpack_blog_id = null;
		if ( class_exists( 'Jetpack_Options' ) ) {
			$jetpack_blog_id = \Jetpack_Options::get_option( 'id' );
		}

		// Get WooCommerce version if available.
		// Check WC_VERSION constant first (most reliable), then fall back to option.
		if ( defined( 'WC_VERSION' ) ) {
			$wc_version = WC_VERSION;
		} else {
			$wc_version = get_option( 'woocommerce_version', '' );
		}

		// Get store ID from known option name.
		$store_id = get_option( 'woocommerce_store_id', null );

		// Get store currency - use WC function if available, otherwise fall back to option.
		$store_currency = function_exists( 'get_woocommerce_currency' )
		? get_woocommerce_currency()
		: get_option( 'woocommerce_currency', 'USD' );

		$blog_details = array(
			'url'            => home_url(),
			'blog_lang'      => get_locale(),
			'blog_id'        => $jetpack_blog_id,
			'store_id'       => $store_id,
			'wc_version'     => $wc_version,
			'store_currency' => $store_currency,
		);

		// Cache for 1 day.
		set_transient( 'wc_analytics_blog_details', $blog_details, DAY_IN_SECONDS );

		return $blog_details;
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
	 * Get the existing stable visitor id: the `tk_ai` cookie, or an IP-based hash when
	 * proxy tracking is enabled. Returns null otherwise so the caller skips the event.
	 *
	 * We never mint a new id here: attributing an event to a brand-new id creates a
	 * throwaway one-event "visitor" (mostly cookie-less crawlers) that inflates session
	 * counts. Real browsers already have a `tk_ai` cookie by the time an event fires.
	 *
	 * @return string|null Stable visitor id, or null when none is available.
	 */
	private static function get_visitor_id() {
		// Return cached result if available.
		if ( null !== self::$cached_visitor_id ) {
			return self::$cached_visitor_id;
		}

		// Prefer the tk_ai cookie if present.
		if ( ! empty( $_COOKIE['tk_ai'] ) ) {
			self::$cached_visitor_id = sanitize_text_field( wp_unslash( $_COOKIE['tk_ai'] ) );
			return self::$cached_visitor_id;
		}

		// Cron and WP-CLI have no real visitor; never attribute background activity to one.
		if ( ( defined( 'DOING_CRON' ) && DOING_CRON )
			|| ( defined( 'WP_CLI' ) && WP_CLI )
		) {
			return null;
		}

		// Proxy tracking provides a stable id from daily_salt + domain + ip + user_agent.
		if ( Features::is_proxy_tracking_enabled() ) {
			self::$cached_visitor_id = self::get_ip_based_visitor_id();
			return self::$cached_visitor_id;
		}

		// No stable id arrived with the request. Do not mint one (see method doc).
		return null;
	}

	/**
	 * Get the user's IP address.
	 *
	 * Resolution defaults to `REMOTE_ADDR`. A proxy header is only consulted when the site
	 * has declared one trustworthy through Jetpack's `trusted_ip_header` site option, which
	 * is determined by observation rather than guesswork. Request headers are forgeable, so
	 * trusting them unconditionally lets any client choose the address we report — and in
	 * proxy tracking mode that address feeds the visitor id hash.
	 *
	 * Only when that resolution produced nothing usable does a forwarded value come into play,
	 * and then only on the further condition that `REMOTE_ADDR` — the address we actually
	 * terminated the connection on, not whatever `get_ip()` resolved — is present, parseable
	 * and non-routable. Both halves are required. The second half is the proof that a proxy on
	 * the local network is in front of us, since an external client cannot fake it; the first
	 * half keeps that recovery from overwriting a good answer, because a site that configured
	 * `trusted_ip_header` correctly has already produced the visitor address and its
	 * `REMOTE_ADDR` is private precisely because the proxy it declared is doing its job. An
	 * absent or malformed `REMOTE_ADDR` proves nothing either way, so it fails closed. See
	 * `get_proxy_written_ip_address()` for why only the last `X-Forwarded-For` entry is read.
	 * `get_ip()`'s result is not usable as the proxy proof: when `trusted_ip_header` is
	 * configured it is selected out of a client-supplied list, so an external attacker can
	 * drive it non-public too.
	 *
	 * @since 0.16.8 Stopped trusting `Cf-Connecting-IP`, `X-Forwarded-For` and `Client-IP`
	 *               unconditionally.
	 * @since 0.16.8 Recover the visitor address from the last `X-Forwarded-For` entry when
	 *               nothing resolved and `REMOTE_ADDR` is non-routable, instead of losing it
	 *               entirely.
	 *
	 * @return string The user's IP address. An empty string when no public address resolved.
	 */
	private static function get_user_ip_address() {
		// Return cached IP if available.
		if ( null !== self::$cached_ip ) {
			return self::$cached_ip;
		}

		$ip = IP_Utils::get_ip();
		$ip = is_string( $ip ) ? $ip : '';

		$remote_addr = isset( $_SERVER['REMOTE_ADDR'] )
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- IP_Utils::clean_ip() validates.
			? IP_Utils::clean_ip( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
			: false;

		// The forwarded value only fills a gap; it never replaces an answer. So this runs when
		// resolution produced nothing usable AND REMOTE_ADDR — the address we terminated on,
		// never the resolved one, which a configured trusted_ip_header may have selected out
		// of a client-supplied list — is present, parseable and non-routable, which is what
		// proves a local proxy is in front of us. A site whose trusted_ip_header already gave
		// us the visitor address keeps it, even though its REMOTE_ADDR is private; an absent
		// or malformed REMOTE_ADDR is no evidence of a proxy at all, so it fails closed.
		if ( ! IP_Utils::ip_is_public( $ip )
			&& is_string( $remote_addr )
			&& ! IP_Utils::ip_is_public( $remote_addr ) ) {
			$proxy_written = self::get_proxy_written_ip_address();

			if ( '' !== $proxy_written ) {
				$ip = $proxy_written;
			}
		}

		/**
		 * Filters the visitor IP address used for analytics.
		 *
		 * For sites behind a proxy this package cannot identify on its own. The returned value
		 * must be the visitor's own address; it is still required to be public and globally
		 * routable, so passing a request header through verbatim reintroduces the spoofing this
		 * resolution exists to prevent.
		 *
		 * @since 0.16.8
		 *
		 * @param string $ip Resolved visitor IP, or an empty string when none was determined.
		 */
		$ip = apply_filters( 'woocommerce_analytics_visitor_ip', is_string( $ip ) ? $ip : '' );

		self::$cached_ip = ( is_string( $ip ) && IP_Utils::ip_is_public( $ip ) ) ? $ip : '';

		return self::$cached_ip;
	}

	/**
	 * The address the nearest proxy recorded, from the last `X-Forwarded-For` entry.
	 *
	 * Only the last entry is usable. Proxies append the address they observed, so everything
	 * to its left — including anything the client sent as its own `X-Forwarded-For` — is
	 * client-controlled. Scanning leftwards for the first public address would walk straight
	 * back into forged input, so a private last entry (chained internal proxies) correctly
	 * yields nothing.
	 *
	 * Only called once resolution has produced nothing usable and `REMOTE_ADDR` has been shown
	 * to be present, parseable and non-routable. `Cf-Connecting-IP`
	 * and `X-Real-IP` are deliberately not consulted: an internal proxy forwards client
	 * headers verbatim unless configured otherwise, so those stay client-controlled.
	 *
	 * This still assumes the proxy in front of us is the one that wrote `X-Forwarded-For`,
	 * and that assumption can be wrong. An nginx configured with only
	 * `proxy_set_header X-Real-IP $remote_addr;`, for instance, leaves `X-Forwarded-For`
	 * untouched and forwards whatever the client sent, verbatim — so the last entry is
	 * attacker-chosen even though `REMOTE_ADDR` is private, and nothing observable from PHP
	 * tells that configuration apart from one where a proxy really did write the header.
	 * This is not a regression, though: the code this replaced trusted the first
	 * `X-Forwarded-For` entry unconditionally on the same misconfigured setup, which handed
	 * the attacker an even easier win. A site in that situation needs to populate the
	 * `trusted_ip_header` site option — Jetpack Brute Force Protection does this by asking
	 * WordPress.com which header is actually trustworthy — or to supply a value of its own
	 * through the `woocommerce_analytics_visitor_ip` filter.
	 *
	 * @since 0.16.8
	 *
	 * @return string The proxy-written address, or an empty string when there is none.
	 */
	private static function get_proxy_written_ip_address() {
		if ( empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			return '';
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- IP_Utils::clean_ip() validates below.
		$forwarded = explode( ',', wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		$written   = IP_Utils::clean_ip( end( $forwarded ) );

		return is_string( $written ) ? $written : '';
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

	/**
	 * Get the device type for the current request.
	 *
	 * Uses Jetpack Device Detection to distinguish between mobile phones, tablets, and desktop devices.
	 *
	 * @return string 'mobile' for phones, 'tablet' for tablets, 'desktop' otherwise.
	 */
	private static function get_device_type() {
		if ( Device_Detection::is_phone() ) {
			return 'mobile';
		}

		if ( Device_Detection::is_tablet() ) {
			return 'tablet';
		}

		return 'desktop';
	}
}
