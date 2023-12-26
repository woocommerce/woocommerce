<?php
/**
 * A class for logging tracked events.
 *
 * @package WC_Beta_Tester
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Tracks_Debug_Log.
 */
class Tracks_Debug_Log {
	/**
	 * Logger class to use.
	 *
	 * @var WC_Logger_Interface|null
	 */
	private $logger;

	/**
	 * Logger source.
	 *
	 * @var string logger source.
	 */
	private $source = 'tracks';

	/**
	 * Get the logger instance.
	 *
	 * @return WC_Logger_Interface|null
	 */
	private function get_logger() {
		// Sometimes between installations of WC versions the logger will not be available.
		if ( ! $this->logger && function_exists( 'wc_get_logger' ) ) {
			$this->logger = wc_get_logger();
		}

		return $this->logger;
	}

	/**
	 * Initialize hooks.
	 */
	public function __construct() {
		// WooCommerce might not be installed/activated between installs of WC versions.
		if ( defined( 'WC_ABSPATH' ) ) {
			include_once WC_ABSPATH . 'includes/tracks/class-wc-tracks-client.php';
			include_once WC_ABSPATH . 'includes/tracks/class-wc-tracks-footer-pixel.php';

			add_action( 'admin_footer', array( $this, 'log_footer_pixels' ), 5 );
			add_action( 'pre_http_request', array( $this, 'log_remote_pixels' ), 10, 3 );
		}
	}

	/**
	 * Log the event.
	 *
	 * @param string $event_name Event name.
	 * @param array  $properties Event properties.
	 */
	public function log_event( $event_name, $properties ) {
		$logger = $this->get_logger();

		if ( $logger ) {
			$logger->debug(
				$event_name,
				array( 'source' => $this->source )
			);
		}

		foreach ( $properties as $key => $property ) {
			if ( is_array( $property ) ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
				$property = print_r( $property, true );
			}
			if ( $logger ) {
				$logger->debug(
					"  - {$key}: {$property}",
					array( 'source' => $this->source )
				);
			}
		}
	}

	/**
	 * Log events passed as footer pixels.
	 */
	public function log_footer_pixels() {
		$events = WC_Tracks_Footer_Pixel::get_events();
		foreach ( $events as $event ) {
			$this->log_event( $event->_en, $event );
		}
	}

	/**
	 * Log events that are retrieved by remote request.
	 *
	 * @param false|array|WP_Error $preempt     A preemptive return value of an HTTP request. Default false.
	 * @param array                $parsed_args HTTP request arguments.
	 * @param string               $url         The request URL.
	 */
	public function log_remote_pixels( $preempt, $parsed_args, $url ) {
		if ( strpos( $url, WC_Tracks_Client::PIXEL ) === 0 ) {
			$parsed_url = wp_parse_url( $url );
			parse_str( $parsed_url['query'], $params );
			$this->log_event( $params['_en'], $params );
		}

		return $preempt;
	}
}

new Tracks_Debug_Log();
