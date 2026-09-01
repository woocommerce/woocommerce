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
	 * Property names a client is authoritative for on the proxy path.
	 *
	 * The server's own values for these describe the /track request itself — its
	 * URL, its referrer, its Accept-Language header — not the page the event
	 * happened on. The client's values are the correct ones.
	 *
	 * `_via_ref` stays server-owned despite coming from the same header as `_dr`:
	 * it records the referrer of the request that fired the pixel, which on the
	 * proxy path is the /track POST, and is not used for page attribution.
	 *
	 * @since 0.16.8
	 *
	 * @var string[]
	 */
	const CLIENT_OVERRIDABLE_PROPERTIES = array( '_lg', '_dl', '_dr' );

	/**
	 * Identity and envelope property names a client may never set.
	 *
	 * Each is also protected today by something else: `_ui`, `_ut`, `_en` and
	 * `_ts` by `$required_properties` merging last in `get_properties()`,
	 * `browser_type` by `Pixel_Builder::validate_and_sanitize()` assigning it
	 * unconditionally. Listed explicitly so that neither merge ordering nor a
	 * downstream overwrite is the only thing standing between a client and the
	 * visitor id.
	 *
	 * @since 0.16.8
	 *
	 * @var string[]
	 */
	const RESERVED_IDENTITY_PROPERTIES = array( '_ui', '_ut', '_en', '_ts', 'browser_type' );

	/**
	 * Maximum number of events a single client request may record.
	 *
	 * Each event becomes an outbound pixel request, so an unbounded batch turns
	 * the unauthenticated endpoint into an amplifier. The client's own batch size
	 * is 10 (see `api-client.ts`); the headroom is for retries coalescing.
	 *
	 * @since 0.16.8
	 *
	 * @var int
	 */
	const MAX_CLIENT_EVENTS_PER_REQUEST = 50;

	/**
	 * Maximum number of properties a client may set on one event.
	 *
	 * @since 0.16.8
	 *
	 * @var int
	 */
	const MAX_CLIENT_PROPERTIES_PER_EVENT = 50;

	/**
	 * Maximum length of a single client-supplied property value.
	 *
	 * Set for the same reason `Woo_Analytics_Trait::cap_page_string()` bounds
	 * caller-influenced strings on the page-output path: values reach the pixel
	 * URL, which is rejected outright once it grows too long. The two are
	 * independent — a change to one does not imply a change to the other.
	 *
	 * @since 0.16.8
	 *
	 * @var int
	 */
	const MAX_CLIENT_PROPERTY_LENGTH = 200;

	/**
	 * Maximum length of a client-supplied event or property name.
	 *
	 * `Pixel_Builder` checks a name's characters but not its length.
	 *
	 * @since 0.16.8
	 *
	 * @var int
	 */
	const MAX_CLIENT_NAME_LENGTH = 100;

	/**
	 * Path suffix of the proxy tracking endpoint.
	 *
	 * Duplicated in the MU-plugin speed module template, which cannot use this
	 * copy: it tests the request shape before loading the autoloader, so no
	 * package class exists yet at that point. Change both together.
	 *
	 * @since 0.16.8
	 *
	 * @var string
	 */
	const PROXY_REQUEST_PATH = 'woocommerce-analytics/v1/track';

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
	 * Memoized reserved property names for the current request.
	 *
	 * @var string[]|null
	 */
	private static $reserved_property_names = null;

	/**
	 * Record an event in Tracks and ClickHouse (If enabled).
	 *
	 * @since 0.16.8 Added the `$is_client_supplied` parameter.
	 *
	 * @param string $event_name The name of the event.
	 * @param array  $event_properties Custom properties to send with the event.
	 * @param bool   $is_client_supplied Whether $event_properties came from an untrusted
	 *                                   client. Reserved property names are stripped and
	 *                                   the rest are capped when true. Defaults to false
	 *                                   for server-side callers.
	 *
	 * @return bool|WP_Error True on emit or deliberate skip (no consent, bot UA,
	 *                       or cookie-less context); WP_Error if pixel firing failed.
	 */
	public static function record_event( $event_name, $event_properties = array(), $is_client_supplied = false ) {
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

		// The guard covers MU-plugin copies predating record_client_event(); see is_proxy_tracking_request().
		$is_client_supplied = $is_client_supplied || self::is_proxy_tracking_request();

		if ( $is_client_supplied ) {
			if ( ! self::is_valid_client_name( $event_name ) ) {
				return true;
			}

			$event_properties = self::sanitize_client_properties( $event_properties );
		}

		$prefixed_event_name = self::PREFIX . $event_name;
		$properties          = self::get_properties( $prefixed_event_name, $event_properties, $is_client_supplied );

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
	 * Record an event whose properties came from an untrusted client.
	 *
	 * The entry point for the tracking proxy: the REST controller and the
	 * MU-plugin speed module both come through here. It exists as a distinct
	 * method rather than a sanitizer callers must remember to invoke, so that a
	 * future entry point choosing the wrong one is visible at the call site.
	 *
	 * @since 0.16.8
	 *
	 * @param string $event_name The name of the event.
	 * @param array  $event_properties Client-supplied properties.
	 *
	 * @return bool|WP_Error True on emit or deliberate skip; WP_Error if pixel firing failed.
	 */
	public static function record_client_event( $event_name, $event_properties = array() ) {
		return self::record_event( $event_name, $event_properties, true );
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
	 * @param bool   $is_client_supplied Whether $event_properties came from an untrusted client.
	 * @return array
	 */
	public static function get_properties( $event_name, $event_properties, $is_client_supplied = false ) {
		$common_properties = self::get_common_properties();

		/**
		 * Allow defining custom event properties in WooCommerce Analytics.
		 *
		 * On the proxy path the incoming array contains client-controlled values:
		 * a callback setting a server-authoritative property must assign
		 * unconditionally rather than defer to what is already there, because a
		 * client can supply that property name itself. `$is_client_supplied` says
		 * when that applies.
		 *
		 * @module woocommerce-analytics
		 *
		 * @since 12.5
		 * @since 0.16.8 Added the `$is_client_supplied` parameter.
		 *
		 * @param array  $all_props Array of event props to be filtered.
		 * @param string $event_name Event name.
		 * @param bool   $is_client_supplied Whether the props came from an untrusted client.
		 */
		$properties = apply_filters(
			'jetpack_woocommerce_analytics_event_props',
			array_merge( $common_properties, $event_properties ),
			$event_name,
			$is_client_supplied
		);

		if ( $is_client_supplied ) {
			// A callback that defers to an existing value hands a reserved property
			// back to the client, which supplied it. Re-assert the server's own.
			$properties = array_merge(
				$properties,
				array_intersect_key( $common_properties, array_flip( self::get_reserved_property_names() ) )
			);
		}

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
	 * Get the property names a client may not set.
	 *
	 * Derived from `get_common_properties()` — everything `get_session_properties()`,
	 * `get_page_common_properties()` and `get_server_details()` compute — rather
	 * than restated as a literal, so a property added to any of the three is
	 * protected with no edit to this method. The pinned list in
	 * `WC_Analytics_Tracking_Reserved_Props_Test` still fails on the addition, on
	 * purpose: protection is automatic, granting an exemption is not.
	 *
	 * Memoized: a batch of events would otherwise recompute the common properties
	 * once per event.
	 *
	 * @since 0.16.8
	 *
	 * @return string[] Reserved property names.
	 */
	public static function get_reserved_property_names() {
		if ( null !== self::$reserved_property_names ) {
			return self::$reserved_property_names;
		}

		$server_owned = array_diff(
			array_keys( self::get_common_properties() ),
			self::CLIENT_OVERRIDABLE_PROPERTIES
		);

		self::$reserved_property_names = array_values(
			array_unique( array_merge( $server_owned, self::RESERVED_IDENTITY_PROPERTIES ) )
		);

		return self::$reserved_property_names;
	}

	/**
	 * Remove server-owned properties from a client-supplied property array.
	 *
	 * Stripping is silent and the event still records. Rejecting the event would
	 * turn the endpoint into an oracle for probing the reserved list, and
	 * analytics should not fail loudly on a malformed client.
	 *
	 * Reserved names a callback on `jetpack_woocommerce_analytics_event_props`
	 * introduces are re-asserted after the filter runs; see `get_properties()`.
	 * Names the filter invents are still the client's to win — see the filter's
	 * docblock for the contract callbacks are expected to follow.
	 *
	 * @since 0.16.8
	 *
	 * @param array $event_properties Client-supplied properties. Any non-array value
	 *                                is tolerated — the REST body is attacker-shaped —
	 *                                and yields an empty array.
	 * @return array Properties with reserved names removed; empty array for empty or
	 *               non-array input.
	 */
	public static function strip_reserved_properties( $event_properties ) {
		if ( ! is_array( $event_properties ) || empty( $event_properties ) ) {
			return array();
		}

		return array_diff_key(
			$event_properties,
			array_flip( self::get_reserved_property_names() )
		);
	}

	/**
	 * Strip and bound a client-supplied property array.
	 *
	 * The single place the untrusted-input rules are applied, so that the REST
	 * controller, the MU-plugin speed module and the stale-template safety net
	 * cannot drift apart on what "sanitized" means.
	 *
	 * Capping is silent and lossy for the same reason stripping is: an
	 * unauthenticated endpoint that reports which values it rejected is an oracle,
	 * and analytics should not fail loudly on a malformed client. Truncated values
	 * keep an ellipsis so they stay distinguishable downstream from a value that
	 * genuinely ended at the limit, matching `Woo_Analytics_Trait::cap_page_string()`.
	 *
	 * @since 0.16.8
	 *
	 * @param array $event_properties Client-supplied properties.
	 * @return array Sanitized properties.
	 */
	public static function sanitize_client_properties( $event_properties ) {
		$event_properties = self::strip_reserved_properties( $event_properties );

		if ( count( $event_properties ) > self::MAX_CLIENT_PROPERTIES_PER_EVENT ) {
			$event_properties = array_slice( $event_properties, 0, self::MAX_CLIENT_PROPERTIES_PER_EVENT, true );
		}

		foreach ( $event_properties as $key => $value ) {
			// Dropped, not truncated: two long names could truncate to the same key.
			if ( ! self::is_valid_client_name( $key ) || ! Pixel_Builder::prop_name_is_valid( $key ) ) {
				unset( $event_properties[ $key ] );
				continue;
			}

			// Arrays are flattened later by get_properties(); bound their members too.
			if ( is_array( $value ) ) {
				$event_properties[ $key ] = array_map( array( __CLASS__, 'cap_client_value' ), $value );
				continue;
			}

			$event_properties[ $key ] = self::cap_client_value( $value );
		}

		return $event_properties;
	}

	/**
	 * Whether a client-supplied event or property name is usable.
	 *
	 * Without the type check an array name reaches `PREFIX . $event_name` and
	 * writes a PHP warning to the log, unauthenticated.
	 *
	 * @since 0.16.8
	 *
	 * @param mixed $name Client-supplied name.
	 * @return bool True when the name is a non-empty string within the length bound.
	 */
	private static function is_valid_client_name( $name ) {
		return is_string( $name )
			&& '' !== $name
			&& mb_strlen( $name ) <= self::MAX_CLIENT_NAME_LENGTH;
	}

	/**
	 * Bound one client-supplied value on its way to the pixel URL.
	 *
	 * Nested arrays are collapsed to an empty string rather than capped: the
	 * flattening in `get_properties()` calls `implode()` on array members, which
	 * emits an "Array to string conversion" warning for a nested one. Letting an
	 * unauthenticated caller write warnings into the error log is the actual
	 * problem; the value itself is meaningless either way.
	 *
	 * @since 0.16.8
	 *
	 * @param mixed $value Client-supplied value.
	 * @return mixed Bounded value.
	 */
	private static function cap_client_value( $value ) {
		if ( is_array( $value ) || is_object( $value ) ) {
			return '';
		}

		if ( ! is_string( $value ) ) {
			return $value;
		}

		if ( mb_strlen( $value ) <= self::MAX_CLIENT_PROPERTY_LENGTH ) {
			return $value;
		}

		return mb_substr( $value, 0, self::MAX_CLIENT_PROPERTY_LENGTH - 1 ) . '…';
	}

	/**
	 * Whether the current request is a POST to the proxy tracking endpoint.
	 *
	 * A safety net, not the boundary. The boundary is `record_client_event()`,
	 * which does not depend on URL shape and so survives subdirectory installs,
	 * rewrites and proxying. This exists only because MU-plugin copies installed
	 * before `record_client_event()` existed call `record_event()` directly, and
	 * rewriting an installed copy depends on a chain of preconditions — admin
	 * traffic, an expired transient, a writable mu-plugins directory — that is
	 * not guaranteed to hold.
	 *
	 * Remove once a package-version floor guarantees no such copy survives.
	 *
	 * Kept in step with `WooCommerceAnalyticsProxySpeed::is_proxy_request()` in
	 * the MU-plugin template, including the character restriction on the path.
	 *
	 * @since 0.16.8
	 *
	 * @return bool True when the request shape matches the proxy endpoint.
	 */
	public static function is_proxy_tracking_request() {
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Compared against a fixed string below; unsanitized on purpose to stay byte-for-byte in step with the MU-plugin template's read.
		$method = strtoupper( wp_unslash( $_SERVER['REQUEST_METHOD'] ) );
		if ( 'POST' !== $method ) {
			return false;
		}

		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Parsed and character-restricted below; sanitizing first would mangle the path.
		$raw_uri = wp_unslash( $_SERVER['REQUEST_URI'] );
		if ( ! is_string( $raw_uri ) || '' === $raw_uri ) {
			return false;
		}

		$path = wp_parse_url( $raw_uri, PHP_URL_PATH );
		if ( ! is_string( $path ) ) {
			return false;
		}

		// Reject anything outside expected URL path characters, matching the template.
		if ( preg_match( '/[^A-Za-z0-9\-._~\/]/', $path ) ) {
			return false;
		}

		$normalized_path = rtrim( $path, '/' );
		$proxy_suffix    = '/' . ltrim( self::PROXY_REQUEST_PATH, '/' );

		if ( strlen( $normalized_path ) < strlen( $proxy_suffix ) ) {
			return false;
		}

		return substr( $normalized_path, -strlen( $proxy_suffix ) ) === $proxy_suffix;
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
