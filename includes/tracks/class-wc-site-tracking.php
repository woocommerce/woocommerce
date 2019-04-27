<?php
/**
 * Nosara Tracks for WooCommerce
 *
 * @package WooCommerce\Tracks
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class adds actions to track usage of WooCommerce.
 */
class WC_Site_Tracking {
	/**
	 * Check if tracking is enabled.
	 *
	 * @return bool
	 */
	public static function is_tracking_enabled() {
		/**
		 * Don't track users who haven't opted-in to tracking or if a filter
		 * has been applied to turn it off.
		 */

		if ( ! apply_filters( 'woocommerce_apply_user_tracking', true ) ) {
			return false;
		}

		// Check if tracking is actively being opted into.
		$is_obw_opting_in = isset( $_POST['wc_tracker_checkbox'] ) && 'yes' === sanitize_text_field( $_POST['wc_tracker_checkbox'] ); // phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification, WordPress.Security.ValidatedSanitizedInput

		if ( 'yes' !== get_option( 'woocommerce_allow_tracking' ) && ! $is_obw_opting_in ) {
			return false;
		}

		if ( ! class_exists( 'WC_Tracks' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Add scripts required to record events from javascript.
	 */
	public static function enqueue_scripts() {

		// Add w.js to the page.
		wp_enqueue_script( 'woo-tracks', 'https://stats.wp.com/w.js', array(), gmdate( 'YW' ), true );

		// Expose tracking via a function in the wcTracks global namespace directly before wc_print_js.
		add_filter( 'admin_footer', array( __CLASS__, 'add_tracking_function' ), 24 );

	}

	/**
	 * Adds the tracking function to the admin footer.
	 */
	public static function add_tracking_function() {
		?>
		<!-- WooCommerce Tracks -->
		<script type="text/javascript">
			window.wcTracks = window.wcTracks || {};
			window.wcTracks.recordEvent = function( name, properties ) {
				var eventName = '<?php echo esc_attr( WC_Tracks::PREFIX ); ?>' + name;
				var eventProperties = properties || {};
				eventProperties.url = '<?php echo esc_html( home_url() ); ?>'
				eventProperties.products_count = '<?php echo intval( WC_Tracks::get_products_count() ); ?>';
				window._tkq = window._tkq || [];
				window._tkq.push( [ 'recordEvent', eventName, eventProperties ] );
			}
		</script>
		<?php
	}

	/**
	 * Add empty tracking function to admin footer when tracking is disabled in case
	 * it's called without checking if it's defined beforehand.
	 */
	public static function add_empty_tracking_function() {
		?>
		<script type="text/javascript">
			window.wcTracks = window.wcTracks || {};
			window.wcTracks.recordEvent = function() {};
		</script>
		<?php
	}

	/**
	 * Init tracking.
	 */
	public static function init() {

		if ( ! self::is_tracking_enabled() ) {

			// Define window.wcTracks.recordEvent in case there is an attempt to use it when tracking is turned off.
			add_filter( 'admin_footer', array( __CLASS__, 'add_empty_tracking_function' ), 24 );

			return;
		}

		self::enqueue_scripts();

		include_once WC_ABSPATH . 'includes/tracks/events/class-wc-admin-setup-wizard-tracking.php';
		include_once WC_ABSPATH . 'includes/tracks/events/class-wc-extensions-tracking.php';
		include_once WC_ABSPATH . 'includes/tracks/events/class-wc-importer-tracking.php';
		include_once WC_ABSPATH . 'includes/tracks/events/class-wc-products-tracking.php';
		include_once WC_ABSPATH . 'includes/tracks/events/class-wc-orders-tracking.php';
		include_once WC_ABSPATH . 'includes/tracks/events/class-wc-settings-tracking.php';

		$tracking_classes = array(
			'WC_Admin_Setup_Wizard_Tracking',
			'WC_Extensions_Tracking',
			'WC_Importer_Tracking',
			'WC_Products_Tracking',
			'WC_Orders_Tracking',
			'WC_Settings_Tracking',
		);

		foreach ( $tracking_classes as $tracking_class ) {
			$tracker_instance    = new $tracking_class();
			$tracker_init_method = array( $tracker_instance, 'init' );

			if ( is_callable( $tracker_init_method ) ) {
				call_user_func( $tracker_init_method );
			}
		}
	}
}
