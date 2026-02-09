<?php
/**
 * Send Tracks events on behalf of a user.
 *
 * @package WooCommerce\Tracks
 */

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Utilities\NumberUtil;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Tracks_Client class.
 */
class WC_Tracks_Client {

	/**
	 * Pixel URL.
	 */
	const PIXEL = 'https://pixel.wp.com/t.gif';

	/**
	 * Browser type.
	 */
	const BROWSER_TYPE = 'php-agent';

	/**
	 * User agent.
	 */
	const USER_AGENT_SLUG = 'tracks-client';

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
	 * Initialize tracks client class
	 *
	 * @return void
	 */
	public static function init() {
		// Use wp hook for setting the identity cookie to avoid headers already sent warnings.
		add_action( 'admin_init', array( __CLASS__, 'maybe_set_identity_cookie' ) );
	}

	/**
	 * Check if identity cookie is set, if not set it.
	 *
	 * @return void
	 */
	public static function maybe_set_identity_cookie() {
		// Do not set on AJAX requests.
		if ( Constants::is_true( 'DOING_AJAX' ) ) {
			return;
		}

		// Bail if cookie already set.
		if ( isset( $_COOKIE['tk_ai'] ) ) {
			return;
		}

		$user = wp_get_current_user();

		// We don't want to track user events during unit tests/CI runs.
		if ( $user instanceof WP_User && 'wptests_capabilities' === $user->cap_key ) {
			return false;
		}
		$user_id = $user->ID;
		$anon_id = get_user_meta( $user_id, '_woocommerce_tracks_anon_id', true );

		// If an id is still not found, create one and save it.
		if ( ! $anon_id ) {
			$anon_id = self::get_anon_id();
			update_user_meta( $user_id, '_woocommerce_tracks_anon_id', $anon_id );
		}

		// Don't set cookie on API requests.
		if ( ! Constants::is_true( 'REST_REQUEST' ) && ! Constants::is_true( 'XMLRPC_REQUEST' ) ) {
			WC_Site_Tracking::set_tracking_cookie( 'tk_ai', $anon_id );
		}
	}

	/**
	 * Record a Tracks event
	 *
	 * @param  array $event Array of event properties.
	 * @return bool|WP_Error         True on success, WP_Error on failure.
	 */
	public static function record_event( $event ) {
		if ( ! $event instanceof WC_Tracks_Event ) {
			$event = new WC_Tracks_Event( $event );
		}

		if ( is_wp_error( $event ) ) {
			return $event;
		}

		$pixel = $event->build_pixel_url( $event );

		if ( ! $pixel ) {
			return new WP_Error( 'invalid_pixel', 'cannot generate tracks pixel for given input', 400 );
		}

		return self::record_pixel( $pixel );
	}

	/**
	 * Record a Tracks event using batched requests for improved performance.
	 * Events are queued and sent together on the shutdown hook.
	 *
	 * @since 10.5.0
	 *
	 * @param  array $event Array of event properties.
	 * @return bool|WP_Error         True on success, WP_Error on failure.
	 */
	public static function record_event_batched( $event ) {
		if ( ! $event instanceof WC_Tracks_Event ) {
			$event = new WC_Tracks_Event( $event );
		}

		if ( isset( $event->error ) && is_wp_error( $event->error ) ) {
			return $event->error;
		}

		$pixel = $event->build_pixel_url();

		if ( ! $pixel ) {
			return new WP_Error( 'invalid_pixel', 'cannot generate tracks pixel for given input', 400 );
		}

		return self::record_pixel_batched( $pixel );
	}

	/**
	 * Synchronously request the pixel.
	 *
	 * @param string $pixel pixel url and query string.
	 * @return bool Always returns true.
	 */
	public static function record_pixel( $pixel ) {
		// Add the Request Timestamp and no cache parameter just before the HTTP request.
		$pixel = self::add_request_timestamp_and_nocache( $pixel );

		wp_safe_remote_get(
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

	/**
	 * Record a pixel using batched requests for improved performance.
	 * Pixels are queued and sent together on the shutdown hook.
	 *
	 * @since 10.5.0
	 *
	 * @param string $pixel pixel url and query string.
	 * @return bool Always returns true.
	 */
	public static function record_pixel_batched( $pixel ) {
		// Check if batching is enabled and supported.
		$use_batching = self::can_use_batch_requests();

		/**
		 * Filters whether to use batch requests for tracking pixels.
		 *
		 * @since 10.5.0
		 *
		 * @param bool $use_batching Whether to use batch requests. Default true if supported.
		 */
		$use_batching = apply_filters( 'wc_tracks_use_batch_requests', $use_batching );

		if ( $use_batching ) {
			// Queue the pixel and send on shutdown.
			self::queue_pixel_for_batch( $pixel );
			return true;
		}

		// Fallback to immediate sending if batching is not supported or disabled.
		return self::record_pixel( $pixel );
	}

	/**
	 * Create a timestamp representing milliseconds since 1970-01-01
	 *
	 * @return string A string representing a timestamp.
	 */
	public static function build_timestamp() {
		$ts = NumberUtil::round( microtime( true ) * 1000 );

		return number_format( $ts, 0, '', '' );
	}

	/**
	 * Add request timestamp and no cache parameter to pixel.
	 * Use this the latest possible before the HTTP request.
	 *
	 * @param string $pixel Pixel URL.
	 * @return string Pixel URL with request timestamp and URL terminator.
	 */
	public static function add_request_timestamp_and_nocache( $pixel ) {
		$pixel .= '&_rt=' . self::build_timestamp() . '&_=_';

		return $pixel;
	}

	/**
	 * Get a user's identity to send to Tracks. If Jetpack exists, default to its implementation.
	 *
	 * @param int $user_id User id.
	 * @return array Identity properties.
	 */
	public static function get_identity( $user_id ) {
		$jetpack_lib = '/tracks/client.php';

		if ( class_exists( 'Jetpack' ) && Constants::is_defined( 'JETPACK__VERSION' ) ) {
			if ( version_compare( Constants::get_constant( 'JETPACK__VERSION' ), '7.5', '<' ) ) {
				if ( file_exists( jetpack_require_lib_dir() . $jetpack_lib ) ) {
					include_once jetpack_require_lib_dir() . $jetpack_lib;
					if ( function_exists( 'jetpack_tracks_get_identity' ) ) {
						return jetpack_tracks_get_identity( $user_id );
					}
				}
			} else {
				$tracking = new Automattic\Jetpack\Tracking();
				return $tracking->tracks_get_identity( $user_id );
			}
		}

		// Start with a previously set cookie.
		$anon_id = isset( $_COOKIE['tk_ai'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['tk_ai'] ) ) : false;

		// If there is no cookie, apply a saved id.
		if ( ! $anon_id ) {
			$anon_id = get_user_meta( $user_id, '_woocommerce_tracks_anon_id', true );
		}

		// If an id is still not found, create one and save it.
		if ( ! $anon_id ) {
			$anon_id = self::get_anon_id();

			update_user_meta( $user_id, '_woocommerce_tracks_anon_id', $anon_id );
		}

		return array(
			'_ut' => 'anon',
			'_ui' => $anon_id,
		);
	}

	/**
	 * Grabs the user's anon id from cookies, or generates and sets a new one
	 *
	 * @return string An anon id for the user
	 */
	public static function get_anon_id() {
		static $anon_id = null;

		if ( ! isset( $anon_id ) ) {

			// Did the browser send us a cookie?
			if ( isset( $_COOKIE['tk_ai'] ) ) {
				$anon_id = sanitize_text_field( wp_unslash( $_COOKIE['tk_ai'] ) );
			} else {

				$binary = '';

				// Generate a new anonId and try to save it in the browser's cookies.
				// Note that base64-encoding an 18 character string generates a 24-character anon id.
				for ( $i = 0; $i < 18; ++$i ) {
					$binary .= chr( wp_rand( 0, 255 ) );
				}

				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				$anon_id = 'woo:' . base64_encode( $binary );
			}
		}

		return $anon_id;
	}

	/**
	 * Check if batch requests are supported.
	 *
	 * @since 10.5.0
	 *
	 * @return bool Whether batch requests are supported.
	 */
	private static function can_use_batch_requests() {
		// Check if the Requests library supports request_multiple().
		return ( class_exists( 'WpOrg\Requests\Requests' ) && method_exists( 'WpOrg\Requests\Requests', 'request_multiple' ) ) // @phpstan-ignore function.alreadyNarrowedType
			|| ( class_exists( 'Requests' ) && method_exists( 'Requests', 'request_multiple' ) ); // @phpstan-ignore function.alreadyNarrowedType
	}

	/**
	 * Queue a pixel URL for batch sending.
	 *
	 * @since 10.5.0
	 *
	 * @param string $pixel The pixel URL to queue.
	 */
	private static function queue_pixel_for_batch( string $pixel ): void {
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
	 * Uses Requests library's request_multiple() for true parallel batching via curl_multi.
	 *
	 * @since 10.5.0
	 */
	public static function send_batched_pixels(): void {
		if ( empty( self::$pixel_batch_queue ) ) {
			return;
		}

		// Add request timestamp and nocache to all pixels.
		$pixels_to_send = array();
		foreach ( self::$pixel_batch_queue as $pixel ) {
			$pixels_to_send[] = self::add_request_timestamp_and_nocache( $pixel );
		}

		// Send with Requests library for true parallel batching.
		self::send_with_requests_multiple( $pixels_to_send );

		// Clear the queue.
		self::$pixel_batch_queue = array();
	}

	/**
	 * Send pixels using Requests::request_multiple() for parallel non-blocking execution.
	 * Uses blocking => false for true non-blocking behavior via curl_multi.
	 *
	 * @since 10.5.0
	 *
	 * @param array $pixels Array of pixel URLs to send.
	 */
	private static function send_with_requests_multiple( array $pixels ): void {
		$requests = array();
		$options  = array(
			'blocking' => false, // Non-blocking mode - returns immediately.
			'timeout'  => 1,
		);

		foreach ( $pixels as $pixel ) {
			$requests[] = array(
				'url'     => $pixel,
				'headers' => array(),
				'data'    => array(),
				'type'    => 'GET',
			);
		}

		try {
			// Try modern namespaced version first.
			if ( class_exists( 'WpOrg\Requests\Requests' ) ) {
				\WpOrg\Requests\Requests::request_multiple( $requests, $options );
			} elseif ( class_exists( 'Requests' ) ) {
				\Requests::request_multiple( $requests, $options ); // phpcs:ignore PHPCompatibility.FunctionUse.RemovedFunctions.requestsDeprecated
			}
		} catch ( \Exception $e ) {
			// Log error but don't break the site - tracking pixels should fail gracefully.
			if ( function_exists( 'wc_get_logger' ) ) {
				wc_get_logger()->error( 'WC_Tracks_Client: Batch pixel request failed - ' . $e->getMessage(), array( 'source' => 'wc-tracks' ) );
			}
		}
	}
}

WC_Tracks_Client::init();
