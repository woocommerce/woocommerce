<?php
/**
 * Proxy speed module for WooCommerce Analytics.
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic\Woocommerce_Analytics;

use Automattic\Woocommerce_Analytics;

/**
 * Answers proxy tracking requests before regular plugins finish loading, so they skip the full WordPress boot.
 *
 * @since 0.19.0
 */
class Proxy_Speed_Module {

	/**
	 * Path of the proxy request.
	 *
	 * @var string
	 */
	const PROXY_REQUEST_PATH = 'woocommerce-analytics/v1/track';

	/**
	 * Whether code on this site called enable() in this request.
	 *
	 * @var bool
	 */
	private static $enabled = false;

	/**
	 * Serve proxy tracking requests early on this site.
	 *
	 * From an MU-plugin it serves on `muplugins_loaded`; from a plugin's main file it serves at once.
	 * A call after `plugins_loaded` is refused with a `_doing_it_wrong()` notice.
	 *
	 * @since 0.19.0
	 *
	 * @return void
	 */
	public static function enable() {
		// Plugins have already loaded, so serving now would save nothing.
		if ( did_action( 'plugins_loaded' ) ) {
			_doing_it_wrong( __METHOD__, 'Call it before plugins_loaded, from an MU-plugin or a plugin main file.', '0.19.0' );
			return;
		}

		self::$enabled = true;

		if ( did_action( 'muplugins_loaded' ) ) {
			self::maybe_serve();
			return;
		}

		add_action( 'muplugins_loaded', array( __CLASS__, 'maybe_serve' ) );
	}

	/**
	 * Answer the current request and exit if it is a proxy request this module may serve.
	 *
	 * Returns without output otherwise, so WordPress continues loading and the REST route answers.
	 *
	 * @since 0.19.0
	 *
	 * @return void
	 */
	public static function maybe_serve() {
		if ( self::respond() ) {
			exit;
		}
	}

	/**
	 * Send the response to a proxy request this module may serve.
	 *
	 * @return bool Whether a response was sent.
	 */
	private static function respond() {
		if ( ! self::$enabled || ! self::is_proxy_request() ) {
			return false;
		}

		// The WP Consent API has not loaded yet, so let the REST route apply the stored deny.
		if ( self::is_statistics_consent_denied() ) {
			return false;
		}

		// Off means "do not accelerate", not "do not serve": the REST route answers, with the 403 a cached page needs.
		if ( ! self::is_proxy_tracking_enabled() ) {
			return false;
		}

		try {
			// Match the superglobals the REST route sees.
			wp_magic_quotes();

			// The plugin that turns proxy tracking on has not loaded yet, but it was on at the last init.
			add_filter( 'woocommerce_analytics_experimental_proxy_tracking_enabled', '__return_true' );

			$response = self::handle_events( (string) file_get_contents( 'php://input' ) );
			WC_Analytics_Tracking::send_batched_pixels();

			// Only on success: after an error, the handler empties a cache that may have caused it.
			self::keep_autoloader_cache();
		} catch ( \Throwable $e ) {
			error_log( 'WooCommerce Analytics Proxy Speed Module: Uncaught error while handling the request: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			$response = array(
				'status' => 500,
				'data'   => array(
					'success' => false,
					'error'   => 'Internal server error while processing analytics events.',
				),
			);
		}

		if ( ! headers_sent() ) {
			header( 'Content-Type: application/json; charset=utf-8' );
			header( 'Cache-Control: no-cache, must-revalidate' );
			http_response_code( $response['status'] );
		}

		echo wp_json_encode( $response['data'], JSON_UNESCAPED_SLASHES );

		return true;
	}

	/**
	 * Unhook the Jetpack autoloader's shutdown handler, which reads an exit before `plugins_loaded` as a failed boot.
	 *
	 * Left hooked, it empties the autoloader's plugin cache on every answered request, and the next page rebuilds it.
	 *
	 * @return void
	 */
	private static function keep_autoloader_cache() {
		global $wp_filter;

		if ( ! isset( $wp_filter['shutdown'] ) ) {
			return;
		}

		foreach ( $wp_filter['shutdown']->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$function = $callback['function'];
				// The autoloader generates its classes under a per-plugin, per-version namespace.
				if ( is_object( $function ) && preg_match( '/^Automattic\\\\Jetpack\\\\Autoloader\\\\.+\\\\Shutdown_Handler$/', get_class( $function ) ) ) {
					remove_action( 'shutdown', $function, $priority );
				}
			}
		}
	}

	/**
	 * Record the events in a proxy request body, leaving their pixels queued.
	 *
	 * @param string $body Raw request body.
	 * @return array{status: int, data: array} The response to send.
	 */
	private static function handle_events( $body ) {
		if ( '' === $body ) {
			return self::error_response( 'Empty request body' );
		}

		$events = json_decode( $body, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return self::error_response( 'Invalid JSON' );
		}

		self::replay_recorded_event_props();

		$data = WC_Analytics_Tracking::record_client_events( $events );

		$data['is_proxy_speed_module'] = true;

		return array(
			'status' => $data['success'] ? 200 : 207,
			'data'   => $data,
		);
	}

	/**
	 * Build a 400 response.
	 *
	 * @param string $message Error message.
	 * @return array{status: int, data: array}
	 */
	private static function error_response( $message ) {
		return array(
			'status' => 400,
			'data'   => array(
				'success' => false,
				'error'   => $message,
			),
		);
	}

	/**
	 * Whether the current request is a POST to the proxy endpoint.
	 *
	 * @return bool
	 */
	private static function is_proxy_request() {
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( 'POST' !== $method ) {
			return false;
		}

		$raw_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$path    = is_string( $raw_uri ) ? wp_parse_url( $raw_uri, PHP_URL_PATH ) : null;

		if ( ! is_string( $path ) || preg_match( '/[^A-Za-z0-9\-._~\/]/', $path ) ) {
			return false;
		}

		$normalized_path = rtrim( $path, '/' );
		$proxy_suffix    = '/' . self::PROXY_REQUEST_PATH;

		return strlen( $normalized_path ) >= strlen( $proxy_suffix )
			&& substr( $normalized_path, -strlen( $proxy_suffix ) ) === $proxy_suffix;
	}

	/**
	 * Whether the WP Consent API cookie stores a statistics deny.
	 *
	 * Unlike Consent_Manager, this reads the cookie whatever the consent type, which is not known yet.
	 * Only a prefix filter registered before this runs applies, which is why enable() waits for `muplugins_loaded`.
	 *
	 * @return bool
	 */
	private static function is_statistics_consent_denied() {
		$prefix = apply_filters( 'wp_consent_cookie_prefix', 'wp_consent' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Reads a WP Consent API filter.
		if ( ! is_string( $prefix ) ) {
			return false;
		}

		$cookie_name = $prefix . '_statistics';

		return isset( $_COOKIE[ $cookie_name ] ) && 'deny' === $_COOKIE[ $cookie_name ];
	}

	/**
	 * Add what plugin callbacks on the event props filter returned at the last cron record, since they have not registered yet.
	 *
	 * @return void
	 */
	private static function replay_recorded_event_props() {
		// Mid-deploy, an older Woocommerce_Analytics may lack it.
		if ( ! defined( Woocommerce_Analytics::class . '::PROXY_SPEED_MODULE_EVENT_PROPS_OPTION' ) ) {
			return;
		}

		$recorded = get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_EVENT_PROPS_OPTION );
		if ( ! is_array( $recorded ) || array() === $recorded ) {
			return;
		}

		// First, so a client value is replaced but an MU-plugin callback, which runs here, still sets its own live value.
		add_filter(
			'jetpack_woocommerce_analytics_event_props',
			static function ( $props ) use ( $recorded ) {
				return array_merge( $props, $recorded );
			},
			PHP_INT_MIN
		);
	}

	/**
	 * Whether proxy tracking was on at the last `init`.
	 *
	 * Features cannot answer this early, because no plugin has registered its filters yet. Only an explicit yes serves.
	 *
	 * @return bool
	 */
	private static function is_proxy_tracking_enabled() {
		// Mid-deploy, an older Woocommerce_Analytics may lack it, and this runs outside respond()'s try.
		if ( ! defined( Woocommerce_Analytics::class . '::PROXY_TRACKING_ENABLED_OPTION' ) ) {
			return false;
		}

		return 'yes' === get_option( Woocommerce_Analytics::PROXY_TRACKING_ENABLED_OPTION );
	}
}
