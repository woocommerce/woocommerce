<?php
/**
 * Send Tracks events on behalf of a user using pixel images in page footer.
 *
 * @package WooCommerce\Tracks
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Tracks_Footer_Pixel class.
 */
class WC_Tracks_Footer_Pixel {
	/**
	 * Singleton instance.
	 *
	 * @var WC_Tracks_Footer_Pixel
	 */
	protected static $instance = null;

	/**
	 * Tracks pixels to add to footer.
	 *
	 * @var array
	 */
	protected $pixels = array();

	/**
	 * Instantiate the singleton.
	 *
	 * @return WC_Tracks_Footer_Pixel
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new WC_Tracks_Footer_Pixel();
		}

		return self::$instance;
	}

	/**
	 * Constructor - attach hooks to the singleton instance.
	 */
	public function __construct() {
		add_action( 'admin_footer', array( $this, 'render_tracking_pixels' ) );
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
	 * Add a pixel to the queue.
	 *
	 * @param string $pixel pixel url and query string.
	 */
	public function add_pixel( $pixel ) {
		$this->pixels[] = $pixel;
	}

	/**
	 * Queue a pixel for inclusion.
	 *
	 * @param string $pixel pixel url and query string.
	 * @return bool Always returns true.
	 */
	public static function record_pixel( $pixel ) {
		self::instance()->add_pixel( $pixel );

		return true;
	}

	/**
	 * Add tracking pixels to page footer.
	 */
	public function render_tracking_pixels() {
		if ( empty( $this->pixels ) ) {
			return;
		}

		foreach ( $this->pixels as $pixel ) {
			echo '<img src="', esc_url( $pixel ), '" />';
		}
	}
}
