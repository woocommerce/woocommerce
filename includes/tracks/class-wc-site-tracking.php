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
	 * Constructor. Sets up tracks support on init.
	 */
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'init' ) );
	}

	/**
	 * Check if tracking is enabled.
	 *
	 * @return bool
	 */
	public static function is_tracking_enabled() {
		/**
		 * Don't track users if a filter has been applied to turn it off.
		 * `woocommerce_apply_tracking` will be deprecated. Please use
		 * `woocommerce_apply_user_tracking` instead.
		 */
		if ( ! apply_filters( 'woocommerce_apply_user_tracking', true ) || ! apply_filters( 'woocommerce_apply_tracking', true ) ) {
			return false;
		}

		// Check if tracking is actively being opted into.
		$is_obw_opting_in = isset( $_POST['wc_tracker_checkbox'] ) && 'yes' === sanitize_text_field( $_POST['wc_tracker_checkbox'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput

		/**
		 * Don't track users who haven't opted-in to tracking or aren't in
		 * the process of opting-in.
		 */
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

		$allow_frontend_tracks = apply_filters( 'woocommerce_apply_frontend_user_tracking', true );

		// Expose tracking via a function in the wcTracks global namespace directly before wc_print_js.
		add_filter( 'admin_footer', array( __CLASS__, 'admin_tracking_function' ), 24 );

		if ( $allow_frontend_tracks ) {
			// Expose tracking via a function in the wcTracks global namespace on frontend.
			add_action( 'wp_footer', array( __CLASS__, 'frontend_tracking_function' ) );
		}
	}

	/**
	 * Render tracking function with admin prefix.
	 */
	public static function admin_tracking_function() {
		self::render_tracking_function( WC_Tracks::ADMIN_PREFIX );
	}

	/**
	 * Render tracking function with frontend prefix.
	 */
	public static function frontend_tracking_function() {
		self::render_tracking_function( WC_Tracks::FRONTEND_PREFIX );
	}

	/**
	 * Renders a tracking function with specified event prefix.
	 *
	 * @param string $event_name_prefix Prefix to add to all triggered events.
	 */
	public static function render_tracking_function( $event_name_prefix ) {
		?>
		<!-- WooCommerce Tracks -->
		<script type="text/javascript">
			window.wcTracks = window.wcTracks || {};
			window.wcTracks.recordEvent = function( name, properties ) {
				var eventName = '<?php echo esc_attr( $event_name_prefix ); ?>' + name;
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
	 * Set up admin tracking.
	 */
	public static function init_admin_events() {
		include_once WC_ABSPATH . 'includes/tracks/events/class-wc-admin-setup-wizard-tracking.php';
		include_once WC_ABSPATH . 'includes/tracks/events/class-wc-extensions-tracking.php';
		include_once WC_ABSPATH . 'includes/tracks/events/class-wc-importer-tracking.php';
		include_once WC_ABSPATH . 'includes/tracks/events/class-wc-products-tracking.php';
		include_once WC_ABSPATH . 'includes/tracks/events/class-wc-orders-tracking.php';
		include_once WC_ABSPATH . 'includes/tracks/events/class-wc-settings-tracking.php';
		include_once WC_ABSPATH . 'includes/tracks/events/class-wc-status-tracking.php';
		include_once WC_ABSPATH . 'includes/tracks/events/class-wc-coupons-tracking.php';

		$tracking_classes = array(
			'WC_Admin_Setup_Wizard_Tracking',
			'WC_Extensions_Tracking',
			'WC_Importer_Tracking',
			'WC_Products_Tracking',
			'WC_Orders_Tracking',
			'WC_Settings_Tracking',
			'WC_Status_Tracking',
			'WC_Coupons_Tracking',
		);

		foreach ( $tracking_classes as $tracking_class ) {
			$tracker_instance    = new $tracking_class();
			$tracker_init_method = array( $tracker_instance, 'init' );

			if ( is_callable( $tracker_init_method ) ) {
				call_user_func( $tracker_init_method );
			}
		}
	}

	/**
	 * Set up frontend tracking.
	 */
	public static function init_frontend_events() {
		include_once WC_ABSPATH . 'includes/tracks/events/frontend/class-wc-cart-tracking.php';

		$tracking_classes = array(
			'WC_Cart_Tracking',
		);

		foreach ( $tracking_classes as $tracking_class ) {
			$tracker_instance    = new $tracking_class();
			$tracker_init_method = array( $tracker_instance, 'init' );

			if ( is_callable( $tracker_init_method ) ) {
				call_user_func( $tracker_init_method );
			}
		}
	}

	/**
	 * Init tracking.
	 */
	public static function init() {
		if ( ! self::is_tracking_enabled() ) {

			// Define window.wcTracks.recordEvent in case there is an attempt to use it when tracking is turned off.
			add_filter( 'admin_footer', array( __CLASS__, 'add_empty_tracking_function' ), 24 );
			add_action( 'wp_footer', array( __CLASS__, 'add_empty_tracking_function' ) );

			return;
		}

		self::enqueue_scripts();

		if ( is_admin() ) {
			self::init_admin_events();
		}

		self::init_frontend_events();
	}
}

new WC_Site_Tracking();
