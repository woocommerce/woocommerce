<?php
/**
 * Pixel Builder for WooCommerce Analytics
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic\Woocommerce_Analytics;

use WP_Error;

/**
 * Pixel Builder class - handles pixel URL construction.
 */
class Pixel_Builder {

	/**
	 * Tracks pixel URL.
	 *
	 * @var string
	 */
	const TRACKS_PIXEL_URL = 'https://pixel.wp.com/t.gif';

	/**
	 * ClickHouse pixel URL.
	 *
	 * @var string
	 */
	const CH_PIXEL_URL = 'https://pixel.wp.com/w.gif';

	/**
	 * Browser type identifier for server-side tracking.
	 *
	 * @var string
	 */
	const BROWSER_TYPE = 'php-agent';

	/**
	 * Event name regex pattern.
	 * Format: prefix_eventname (e.g., woocommerceanalytics_checkout_started)
	 *
	 * @var string
	 */
	const EVENT_NAME_REGEX = '/^(([a-z0-9]+)_){1}([a-z0-9_]+)$/';

	/**
	 * Property name regex pattern.
	 * Format: lowercase letters/underscores, starting with letter or underscore.
	 *
	 * @var string
	 */
	const PROP_NAME_REGEX = '/^[a-z_][a-z0-9_]*$/';

	/**
	 * Build a timestamp representing milliseconds since 1970-01-01.
	 *
	 * @return string A string representing a timestamp.
	 */
	public static function build_timestamp() {
		$ts = round( microtime( true ) * 1000 );
		return number_format( $ts, 0, '', '' );
	}

	/**
	 * Add request timestamp and nocache parameter to pixel URL.
	 * Should be called just before the HTTP request.
	 *
	 * @param string $pixel Pixel URL.
	 * @return string Pixel URL with request timestamp and URL terminator.
	 */
	public static function add_request_timestamp_and_nocache( $pixel ) {
		return $pixel . '&_rt=' . self::build_timestamp() . '&_=_';
	}

	/**
	 * Build a Tracks pixel URL from properties.
	 *
	 * @param array $properties Event properties.
	 * @return string|WP_Error Pixel URL on success, WP_Error on failure.
	 */
	public static function build_tracks_url( $properties ) {
		$validated = self::validate_and_sanitize( $properties );

		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		return self::TRACKS_PIXEL_URL . '?' . http_build_query( $validated );
	}

	/**
	 * Build a ClickHouse pixel URL from properties.
	 *
	 * @param array $properties Event properties.
	 * @return string|WP_Error Pixel URL on success, WP_Error on failure.
	 */
	public static function build_ch_url( $properties ) {
		$validated = self::validate_and_sanitize( $properties );

		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		return self::CH_PIXEL_URL . '?' . http_build_query( $validated );
	}

	/**
	 * Validate and sanitize event properties.
	 *
	 * @param array $properties Event properties.
	 * @return array|WP_Error Validated properties on success, WP_Error on failure.
	 */
	public static function validate_and_sanitize( $properties ) {
		// Required: event name.
		if ( empty( $properties['_en'] ) ) {
			return new WP_Error( 'invalid_event', 'A valid event must be specified via `_en`', 400 );
		}

		// Validate event name format.
		if ( ! self::event_name_is_valid( $properties['_en'] ) ) {
			return new WP_Error( 'invalid_event_name', 'A valid event name must be specified.' );
		}

		// Delete non-routable IP addresses (geoip would discard these anyway).
		if ( isset( $properties['_via_ip'] ) && preg_match( '/^192\.168|^10\./', $properties['_via_ip'] ) ) {
			unset( $properties['_via_ip'] );
		}

		// Add browser type for server-side tracking.
		$properties['browser_type'] = self::BROWSER_TYPE;

		// Ensure timestamp exists.
		if ( ! isset( $properties['_ts'] ) ) {
			$properties['_ts'] = self::build_timestamp();
		}

		// Validate property names.
		foreach ( array_keys( $properties ) as $key ) {
			if ( '_en' === $key ) {
				continue;
			}
			if ( ! self::prop_name_is_valid( $key ) ) {
				return new WP_Error( 'invalid_prop_name', 'A valid prop name must be specified: ' . $key );
			}
		}

		// Sanitize array values to prevent bracket notation in URL serialization.
		return self::sanitize_property_values( $properties );
	}

	/**
	 * Check if event name is valid.
	 *
	 * @param string $name Event name.
	 * @return bool True if valid, false otherwise.
	 */
	public static function event_name_is_valid( $name ) {
		return (bool) preg_match( self::EVENT_NAME_REGEX, $name );
	}

	/**
	 * Check if a property name is valid.
	 *
	 * @param string $name Property name.
	 * @return bool True if valid, false otherwise.
	 */
	public static function prop_name_is_valid( $name ) {
		return (bool) preg_match( self::PROP_NAME_REGEX, $name );
	}

	/**
	 * Sanitize property values for URL serialization.
	 *
	 * Converts array values to appropriate formats to prevent http_build_query()
	 * from creating bracket notation (e.g., prop[0], prop[1]) which violates
	 * the property name regex.
	 *
	 * @param array $properties Event properties.
	 * @return array Sanitized properties.
	 */
	private static function sanitize_property_values( $properties ) {
		foreach ( $properties as $key => $value ) {
			if ( ! is_array( $value ) ) {
				continue;
			}

			if ( empty( $value ) ) {
				// Empty array becomes empty string.
				$properties[ $key ] = '';
				continue;
			}

			// Check if array is indexed (not associative) and contains only scalar values.
			$is_indexed_array = array_keys( $value ) === range( 0, count( $value ) - 1 );
			$has_scalar_only  = ! array_filter(
				$value,
				function ( $item ) {
					return is_array( $item ) || is_object( $item );
				}
			);

			if ( $is_indexed_array && $has_scalar_only ) {
				// Indexed arrays with scalar values: join as comma string.
				$properties[ $key ] = implode( ',', array_map( 'strval', $value ) );
				continue;
			}

			// Associative arrays or nested arrays become JSON strings.
			$encoded            = wp_json_encode( $value, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES );
			$properties[ $key ] = ( false === $encoded ) ? '' : $encoded;
		}

		return $properties;
	}

	/**
	 * Send pixel requests using batched non-blocking HTTP calls.
	 *
	 * Uses Requests library's request_multiple() for parallel execution via curl_multi.
	 *
	 * @param array $pixels Array of pixel URLs to send.
	 * @return bool True on success.
	 */
	public static function send_pixels_batched( $pixels ) {
		if ( empty( $pixels ) ) {
			return true;
		}

		// Check if batching is supported.
		$can_batch = ( class_exists( 'WpOrg\Requests\Requests' ) && method_exists( 'WpOrg\Requests\Requests', 'request_multiple' ) )
			|| ( class_exists( 'Requests' ) && method_exists( 'Requests', 'request_multiple' ) );

		if ( ! $can_batch ) {
			// Fallback to individual requests.
			foreach ( $pixels as $pixel ) {
				self::send_pixel( $pixel );
			}
			return true;
		}

		// Add timestamp and nocache to all pixels.
		$pixels_to_send = array();
		foreach ( $pixels as $pixel ) {
			$pixels_to_send[] = self::add_request_timestamp_and_nocache( $pixel );
		}

		// Build request array for batch sending.
		$requests = array();
		$options  = array(
			'blocking' => false, // Non-blocking mode.
			'timeout'  => 1,
		);

		foreach ( $pixels_to_send as $pixel ) {
			$requests[] = array(
				'url'     => $pixel,
				'headers' => array(),
				'data'    => array(),
				'type'    => 'GET',
			);
		}

		try {
			if ( class_exists( 'WpOrg\Requests\Requests' ) ) {
				\WpOrg\Requests\Requests::request_multiple( $requests, $options );
			} elseif ( class_exists( 'Requests' ) ) {
				\Requests::request_multiple( $requests, $options ); // phpcs:ignore PHPCompatibility.FunctionUse.RemovedFunctions.requestsDeprecated
			}
		} catch ( \Exception $e ) {
			// Log error but don't break the site - tracking pixels should fail gracefully.
			$error_message = 'WooCommerce Analytics: Batch pixel request failed - ' . $e->getMessage();
			if ( function_exists( 'wc_get_logger' ) ) {
				wc_get_logger()->error( $error_message, array( 'source' => 'woocommerce-analytics' ) );
			} else {
				// Fallback for MU-plugin stage when WooCommerce logger is not available.
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( $error_message );
			}
			return false;
		}

		return true;
	}

	/**
	 * Send a single pixel request.
	 *
	 * @param string $pixel Pixel URL.
	 * @return bool True on success.
	 */
	public static function send_pixel( $pixel ) {
		$pixel = self::add_request_timestamp_and_nocache( $pixel );

		wp_remote_get(
			$pixel,
			array(
				'blocking'    => false,
				'redirection' => 2,
				'httpversion' => '1.1',
				'timeout'     => 1,
			)
		);

		return true;
	}
}
