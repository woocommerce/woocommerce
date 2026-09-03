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
	 * The server's own values for these describe the /track request, not the page
	 * the event happened on. `_via_ref` is excluded despite sharing a header with
	 * `_dr`: it records what fired the pixel, and is not used for page attribution.
	 *
	 * @since 0.18.0
	 *
	 * @var string[]
	 */
	const CLIENT_OVERRIDABLE_PROPERTIES = array( '_lg', '_dl', '_dr' );

	/**
	 * Identity and envelope property names a client may never set.
	 *
	 * Each is already protected by merge ordering in `get_properties()` or by
	 * `Pixel_Builder::validate_and_sanitize()`. Listed anyway so that neither is
	 * the only thing standing between a client and the visitor id.
	 *
	 * @since 0.18.0
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
	 * @since 0.18.0
	 *
	 * @var int
	 */
	const MAX_CLIENT_EVENTS_PER_REQUEST = 50;

	/**
	 * Maximum number of properties a client may set on one event.
	 *
	 * @since 0.18.0
	 *
	 * @var int
	 */
	const MAX_CLIENT_PROPERTIES_PER_EVENT = 50;

	/**
	 * Maximum length of a single property value bound for the pixel URL.
	 *
	 * Not the event's size limit — MAX_CLIENT_PAYLOAD_LENGTH is, and it applies
	 * whatever this is set to. This only stops one value spending the whole
	 * budget, so it is set well above real values rather than close to them: an
	 * ad-click landing URL carrying `gclid` and `fbclid` runs past 200
	 * characters, and truncating it destroys the campaign attribution the event
	 * exists to record.
	 *
	 * Deliberately unlike `Woo_Analytics_Trait::cap_page_string()`, which bounds
	 * breadcrumb titles and search terms. Those are short by nature; URLs are not.
	 *
	 * @since 0.18.0
	 *
	 * @var int
	 */
	const MAX_CLIENT_PROPERTY_LENGTH = 1000;

	/**
	 * Maximum length of a client-supplied event or property name.
	 *
	 * `Pixel_Builder` checks a name's characters but not its length.
	 *
	 * @since 0.18.0
	 *
	 * @var int
	 */
	const MAX_CLIENT_NAME_LENGTH = 100;

	/**
	 * Maximum number of members in a client-supplied array value.
	 *
	 * Not a size bound: `fit_client_array()` already drops members until the value
	 * fits the payload budget, whatever the count. This caps the work that costs,
	 * since fitting re-measures the whole array on every pop, and an array of
	 * hundreds of thousands of one-character members would otherwise be measured
	 * that many times.
	 *
	 * @since 0.18.0
	 *
	 * @var int
	 */
	const MAX_CLIENT_ARRAY_MEMBERS = 50;

	/**
	 * Maximum total length of one event's client-supplied properties.
	 *
	 * The other caps bound each axis separately and multiply: at their limits one
	 * event still built a 512KB pixel URL. This bounds the product.
	 *
	 * Counted after percent-encoding, in bytes, unlike the per-value cap which
	 * counts characters. A `%` costs three bytes in the URL and a CJK character
	 * nine — three UTF-8 bytes, each percent-encoded — so counting characters
	 * here would under-count by up to 9x and let the budget pass a payload that
	 * still blows past MAX_PIXEL_URL_LENGTH.
	 *
	 * @since 0.18.0
	 *
	 * @var int
	 */
	const MAX_CLIENT_PAYLOAD_LENGTH = 4096;

	/**
	 * Maximum length of a pixel URL this package will fire.
	 *
	 * The backstop for every other cap. It is the only bound that sees the final
	 * URL, so it is also the only one covering properties a
	 * `jetpack_woocommerce_analytics_event_props` callback added after the
	 * client-side caps ran.
	 *
	 * @since 0.18.0
	 *
	 * @var int
	 */
	const MAX_PIXEL_URL_LENGTH = 8192;

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
	 * @since 0.18.0 Added the `$is_client_supplied` parameter.
	 *
	 * @param string $event_name The name of the event.
	 * @param array  $event_properties Custom properties to send with the event.
	 * @param bool   $is_client_supplied Whether $event_properties came from an untrusted
	 *                                   client. Reserved property names are stripped and the
	 *                                   rest are bounded when true. Defaults to false for
	 *                                   server-side callers.
	 *
	 * @return bool|WP_Error True on emit or deliberate skip (no consent, bot UA, or
	 *                       cookie-less context); WP_Error for an unusable client
	 *                       event name, or if the pixel could not be built or fired.
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

		if ( $is_client_supplied ) {
			// An error rather than a silent skip: the caller sent a name that cannot
			// be recorded, and reporting success for an event that produced no pixel
			// is what makes the loss invisible. Telling a client about its own
			// malformed input leaks nothing.
			if ( ! self::is_valid_client_name( $event_name ) ) {
				return new WP_Error( 'invalid_event_name', 'the event name is empty, too long, or not a string', 400 );
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
	 * The entry point for the tracking proxy: the REST controller and the MU-plugin
	 * speed module both come through here. A distinct method rather than a sanitizer
	 * callers must remember to invoke, so a wrong choice is visible at the call site.
	 *
	 * @since 0.18.0
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

		if ( strlen( $pixel_url ) > self::MAX_PIXEL_URL_LENGTH ) {
			// The proxy endpoint reports this error back to its caller, but no
			// first-party call site checks the return value, so for those events the
			// log line is the only signal that one was dropped.
			$error_message = sprintf(
				'WooCommerce Analytics: dropped a %d byte pixel, over the %d byte limit.',
				strlen( $pixel_url ),
				self::MAX_PIXEL_URL_LENGTH
			);
			if ( function_exists( 'wc_get_logger' ) ) {
				wc_get_logger()->warning( $error_message, array( 'source' => 'woocommerce-analytics' ) );
			} else {
				// Fallback for MU-plugin stage when WooCommerce logger is not available.
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( $error_message );
			}

			return new WP_Error( 'pixel_too_long', 'tracks pixel URL exceeds the maximum length', 400 );
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

		// Capped here rather than only on the proxy path: the cookie is client-writable
		// on every request, and first-party events never meet the client caps, so an
		// oversized cookie would push their pixel past MAX_PIXEL_URL_LENGTH.
		return array(
			'session_id'   => self::cap_property_value( $session_details['session_id'] ?? null ),
			'landing_page' => self::cap_json_list_value( $session_details['landing_page'] ?? null ),
			'is_engaged'   => self::cap_property_value( $session_details['is_engaged'] ?? null ),
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
	 * @since 0.18.0 Added the `$is_client_supplied` parameter.
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
		 * On the proxy path (`$is_client_supplied`) a reserved name a callback returns
		 * is discarded, because the server re-asserts its own value below. Names a
		 * callback introduces are not reserved and are kept.
		 *
		 * @module woocommerce-analytics
		 *
		 * @since 12.5
		 * @since 0.18.0 Added the `$is_client_supplied` parameter.
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

		foreach ( $all_properties as $key => $value ) {
			$all_properties[ $key ] = self::flatten_property_value( $value );
		}

		return $all_properties;
	}

	/**
	 * Get the property names a client may not set.
	 *
	 * Derived from `get_common_properties()` rather than restated as a literal, so a
	 * newly added common property is protected with no edit here. The pinned list in
	 * `WC_Analytics_Tracking_Reserved_Props_Test` still fails on the addition, on
	 * purpose: protection is automatic, granting an exemption is not. Memoized because
	 * a batch would otherwise recompute the common properties once per event.
	 *
	 * @since 0.18.0
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
	 * Stripping is silent and the event still records: rejecting it would turn the
	 * endpoint into an oracle for probing the reserved list.
	 *
	 * @since 0.18.0
	 *
	 * @param array $event_properties Client-supplied properties. A non-array is
	 *                                tolerated, since the REST body is attacker-shaped.
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
	 * @since 0.18.0
	 *
	 * @param array $event_properties Client-supplied properties.
	 * @return array Sanitized properties.
	 */
	public static function sanitize_client_properties( $event_properties ) {
		$event_properties = self::strip_reserved_properties( $event_properties );

		if ( count( $event_properties ) > self::MAX_CLIENT_PROPERTIES_PER_EVENT ) {
			$event_properties = array_slice( $event_properties, 0, self::MAX_CLIENT_PROPERTIES_PER_EVENT, true );
		}

		$values = array();
		$costs  = array();

		foreach ( $event_properties as $key => $value ) {
			// Dropped, not truncated: two long names could truncate to the same key.
			if ( ! self::is_valid_client_name( $key ) || ! Pixel_Builder::prop_name_is_valid( $key ) ) {
				continue;
			}

			// Arrays are flattened later by get_properties(); bound their members too.
			if ( is_array( $value ) ) {
				$value = array_map(
					array( __CLASS__, 'cap_property_value' ),
					array_slice( $value, 0, self::MAX_CLIENT_ARRAY_MEMBERS, true )
				);
			} else {
				$value = self::cap_property_value( $value );
			}

			$values[ $key ] = $value;
			$costs[ $key ]  = strlen( $key ) + self::measure_client_value( $value );
		}

		// Cheapest first, so one long value costs its own tail rather than every
		// property that happens to follow it. A product name at the value cap can
		// still outweigh the whole budget once percent-encoded, and in source order
		// it would take `pi`, `pp` and `pt` down with it.
		asort( $costs );

		$budget = self::MAX_CLIENT_PAYLOAD_LENGTH;
		$kept   = array();

		foreach ( $costs as $key => $cost ) {
			$value = $values[ $key ];

			// Trimmed to fit rather than dropped, the same way arrays already were.
			// The value cap counts characters and the budget counts encoded bytes, so
			// a value at the cap can still be nine times its length here; dropping it
			// would lose a whole property to an encoding difference.
			if ( $cost > $budget ) {
				$room = $budget - strlen( $key );

				$value = is_array( $value )
					? self::fit_client_array( $value, $room )
					: self::fit_client_string( (string) $value, $room );

				if ( array() === $value || '' === $value ) {
					continue;
				}

				$cost = strlen( $key ) + self::measure_client_value( $value );
			}

			$budget      -= $cost;
			$kept[ $key ] = $value;
		}

		// Back into the order the caller sent, so the pixel is not reordered by cost.
		return array_replace( array_intersect_key( $values, $kept ), $kept );
	}

	/**
	 * Whether a client-supplied event or property name is usable.
	 *
	 * Without the type check an array name reaches `PREFIX . $event_name` and writes
	 * a PHP warning to the log, unauthenticated.
	 *
	 * @since 0.18.0
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
	 * Reduce one property value to the string that goes into the pixel URL.
	 *
	 * Array values are joined and encoded for compatibility with the client's
	 * `encodeURIComponent()`; an associative array becomes JSON, which carries its
	 * keys. The single definition matters: the payload budget measures a value by
	 * running it through here, and an approximation that missed the JSON branch
	 * charged nothing for those keys.
	 *
	 * @since 0.18.0
	 *
	 * @param mixed $value Property value.
	 * @return mixed The scalar it serializes to; non-array values are returned as-is.
	 */
	private static function flatten_property_value( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}

		if ( empty( $value ) ) {
			return '';
		}

		if ( array_keys( $value ) === range( 0, count( $value ) - 1 ) ) {
			return rawurlencode( implode( ',', $value ) );
		}

		return wp_json_encode( $value, JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Bytes one value contributes to the pixel URL.
	 *
	 * @since 0.18.0
	 *
	 * @param mixed $value Already-capped client value.
	 * @return int Byte count after `flatten_property_value()` and the encoding
	 *             `http_build_query()` applies on top of it.
	 */
	private static function measure_client_value( $value ) {
		// urlencode(), not rawurlencode(): http_build_query() defaults to RFC1738,
		// which writes `~` as %7E. Measuring with rawurlencode() under-counted a
		// tilde threefold, the same way counting characters under-counted a `%`.
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.urlencode_urlencode -- Deliberate: mirrors http_build_query()'s RFC1738 encoding so the budget measures the bytes the finished URL carries.
		return strlen( urlencode( (string) self::flatten_property_value( $value ) ) );
	}

	/**
	 * Trim a string value until it fits the remaining budget.
	 *
	 * The scalar counterpart of `fit_client_array()`. Binary search rather than a
	 * character-at-a-time walk because each step re-encodes the candidate.
	 *
	 * @since 0.18.0
	 *
	 * @param string $value Already-capped value.
	 * @param int    $budget Bytes still available for this value.
	 * @return string The longest prefix that fits, with an ellipsis; empty when
	 *                even one character does not.
	 */
	private static function fit_client_string( $value, $budget ) {
		if ( $budget <= 0 ) {
			return '';
		}

		$low  = 0;
		$high = mb_strlen( $value );

		while ( $low < $high ) {
			$mid = (int) ceil( ( $low + $high ) / 2 );

			if ( self::measure_client_value( self::truncate_value( $value, $mid ) ) <= $budget ) {
				$low = $mid;
			} else {
				$high = $mid - 1;
			}
		}

		return self::truncate_value( $value, $low );
	}

	/**
	 * Drop trailing members until an array value fits the remaining budget.
	 *
	 * @since 0.18.0
	 *
	 * @param array $members Already-capped members.
	 * @param int   $budget Bytes still available for this value.
	 * @return array Members that fit; empty when even one does not.
	 */
	private static function fit_client_array( $members, $budget ) {
		while ( ! empty( $members ) && self::measure_client_value( $members ) > $budget ) {
			array_pop( $members );
		}

		return $members;
	}

	/**
	 * Bound a value that carries a JSON list, without invalidating the JSON.
	 *
	 * `landing_page` is a JSON-encoded breadcrumb trail. Capping it as a plain
	 * string cuts mid-token and hands the pipeline something that no longer
	 * parses, so drop whole trailing entries instead and re-encode. The leading
	 * entries are the ones worth keeping: they are the top of the trail.
	 *
	 * Anything that is not a JSON list falls back to the plain cap.
	 *
	 * @since 0.18.0
	 *
	 * @param mixed $value Caller-influenced value.
	 * @return mixed Bounded value, still valid JSON when it arrived as JSON.
	 */
	private static function cap_json_list_value( $value ) {
		if ( ! is_string( $value ) || mb_strlen( $value ) <= self::MAX_CLIENT_PROPERTY_LENGTH ) {
			return self::cap_property_value( $value );
		}

		$decoded = json_decode( $value, true );
		if ( ! is_array( $decoded ) ) {
			return self::cap_property_value( $value );
		}

		while ( ! empty( $decoded ) ) {
			$encoded = wp_json_encode( $decoded );

			if ( is_string( $encoded ) && mb_strlen( $encoded ) <= self::MAX_CLIENT_PROPERTY_LENGTH ) {
				return $encoded;
			}

			array_pop( $decoded );
		}

		return '[]';
	}

	/**
	 * Bound one value on its way to the pixel URL.
	 *
	 * Applies to anything a caller can influence, which is not only the properties
	 * a client posts: the session cookie and the request headers behind
	 * `get_server_details()` are caller-influenced too, and an uncapped one of
	 * those pushed the finished URL past MAX_PIXEL_URL_LENGTH, costing the whole
	 * event rather than the oversized value.
	 *
	 * Arrays and objects are collapsed to an empty string rather than capped: the
	 * flattening in `get_properties()` calls `implode()` on array members, which
	 * emits an "Array to string conversion" warning for a nested one. Letting an
	 * unauthenticated caller write warnings into the error log is the actual
	 * problem; the value itself is meaningless either way.
	 *
	 * @since 0.18.0
	 *
	 * @param mixed $value Caller-influenced value.
	 * @return mixed Bounded value.
	 */
	private static function cap_property_value( $value ) {
		if ( is_array( $value ) || is_object( $value ) ) {
			return '';
		}

		if ( ! is_string( $value ) ) {
			return $value;
		}

		if ( mb_strlen( $value ) <= self::MAX_CLIENT_PROPERTY_LENGTH ) {
			return $value;
		}

		return self::truncate_value( $value, self::MAX_CLIENT_PROPERTY_LENGTH );
	}

	/**
	 * Cut a value to a character count, marking that it was cut.
	 *
	 * The ellipsis keeps a truncated value distinguishable downstream from one
	 * that genuinely ended at the limit, and costs one of the characters.
	 *
	 * @since 0.18.0
	 *
	 * @param string $value  Value to cut.
	 * @param int    $length Characters the result may occupy, ellipsis included.
	 * @return string The cut value, or an empty string when nothing fits.
	 */
	private static function truncate_value( $value, $length ) {
		if ( $length <= 0 ) {
			return '';
		}

		return mb_substr( $value, 0, $length - 1 ) . '…';
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

		// Headers are caller-supplied, and the referer lands here twice. Uncapped, one
		// long Referer pushes the finished URL past MAX_PIXEL_URL_LENGTH and costs the
		// whole event; capped, it costs the tail of one value. `_lg` is already bounded
		// above and `_via_ip` is validated by get_user_ip_address().
		foreach ( array( '_via_ua', '_dr', '_dl', '_via_ref' ) as $key ) {
			$data[ $key ] = self::cap_property_value( $data[ $key ] );
		}

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
