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
		 * Don't track users if a filter has been applied to turn it off.
		 * `woocommerce_apply_tracking` will be deprecated. Please use
		 * `woocommerce_apply_user_tracking` instead.
		 *
		 * @since 3.6.0
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
	 * Register scripts required to record events from javascript.
	 */
	public static function register_scripts() {
		wp_register_script( 'woo-tracks', 'https://stats.wp.com/w.js', array( 'wp-hooks' ), gmdate( 'YW' ), false );
	}

	/**
	 * Add scripts required to record events from javascript.
	 */
	public static function enqueue_scripts() {
		wp_enqueue_script( 'woo-tracks' );
	}

	/**
	 * Adds the tracking function to the admin footer.
	 */
	public static function add_tracking_function() {
		/**
		 * Add global tracks event properties.
		 *
		 * @since 6.5.0
		 */
		$filtered_properties = apply_filters( 'woocommerce_tracks_event_properties', array(), false );
		?>
		<!-- WooCommerce Tracks -->
		<script type="text/javascript">
			window.wcTracks = window.wcTracks || {};
			window.wcTracks.isEnabled = <?php echo self::is_tracking_enabled() ? 'true' : 'false'; ?>;
			window.wcTracks.recordEvent = function( name, properties ) {
				if ( ! window.wcTracks.isEnabled ) {
					return;
				}

				const eventName = '<?php echo esc_attr( WC_Tracks::PREFIX ); ?>' + name;
				let eventProperties = properties || {};
				eventProperties = { ...eventProperties, ...<?php echo json_encode( $filtered_properties ); ?> };
				if ( window.wp && window.wp.hooks && window.wp.hooks.applyFilters ) {
					eventProperties = window.wp.hooks.applyFilters( 'woocommerce_tracks_client_event_properties', eventProperties, eventName );
					delete( eventProperties._ui );
					delete( eventProperties._ut );
				}
				window._tkq = window._tkq || [];
				window._tkq.push( [ 'recordEvent', eventName, eventProperties ] );
			}
		</script>
		<?php
	}

	/**
	 * Adds a function to load tracking scripts and enable them client-side on the fly.
	 * Note that this function does not update `woocommerce_allow_tracking` in the database
	 * and will not persist enabled tracking across page loads.
	 */
	public static function add_enable_tracking_function() {
		global $wp_scripts;

		if ( ! isset( $wp_scripts->registered['woo-tracks'] ) ) {
			return;
		}

		$woo_tracks_script = $wp_scripts->registered['woo-tracks']->src;

		?>
		<script type="text/javascript">
			window.wcTracks.enable = function( callback ) {
				window.wcTracks.isEnabled = true;

				var scriptUrl = '<?php echo esc_url( $woo_tracks_script ); ?>';
				var existingScript = document.querySelector( `script[src="${ scriptUrl }"]` );
				if ( existingScript ) {
					return;
				}

				var script = document.createElement('script');
				script.src = scriptUrl;
				document.body.append(script);

				// Callback after scripts have loaded.
				script.onload = function() {
					if ( 'function' === typeof callback ) {
						callback( true );
					}
				}

				// Callback triggered if the script fails to load.
				script.onerror = function() {
					if ( 'function' === typeof callback ) {
						callback( false );
					}
				}
			}
		</script>
		<?php
	}

	/**
	 * Init tracking.
	 */
	public static function init() {
		add_filter( 'woocommerce_tracks_event_properties', array( __CLASS__, 'add_global_properties' ), 10, 2 );

		// Define window.wcTracks.recordEvent in case it is enabled client-side.
		self::register_scripts();
		add_filter( 'admin_footer', array( __CLASS__, 'add_tracking_function' ), 24 );

		if ( ! self::is_tracking_enabled() ) {
			add_filter( 'admin_footer', array( __CLASS__, 'add_enable_tracking_function' ), 24 );
			return;
		}

		self::enqueue_scripts();

		include_once WC_ABSPATH . 'includes/tracks/events/class-wc-admin-setup-wizard-tracking.php';
		include_once WC_ABSPATH . 'includes/tracks/events/class-wc-extensions-tracking.php';
		include_once WC_ABSPATH . 'includes/tracks/events/class-wc-importer-tracking.php';
		include_once WC_ABSPATH . 'includes/tracks/events/class-wc-products-tracking.php';
		include_once WC_ABSPATH . 'includes/tracks/events/class-wc-orders-tracking.php';
		include_once WC_ABSPATH . 'includes/tracks/events/class-wc-settings-tracking.php';
		include_once WC_ABSPATH . 'includes/tracks/events/class-wc-status-tracking.php';
		include_once WC_ABSPATH . 'includes/tracks/events/class-wc-coupons-tracking.php';
		include_once WC_ABSPATH . 'includes/tracks/events/class-wc-order-tracking.php';
		include_once WC_ABSPATH . 'includes/tracks/events/class-wc-coupon-tracking.php';
		include_once WC_ABSPATH . 'includes/tracks/events/class-wc-theme-tracking.php';

		$tracking_classes = array(
			'WC_Extensions_Tracking',
			'WC_Importer_Tracking',
			'WC_Products_Tracking',
			'WC_Orders_Tracking',
			'WC_Settings_Tracking',
			'WC_Status_Tracking',
			'WC_Coupons_Tracking',
			'WC_Order_Tracking',
			'WC_Coupon_Tracking',
			'WC_Theme_Tracking',
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
	 * Get total product counts.
	 *
	 * @return int Number of products.
	 */
	public static function get_products_count() {
		$product_counts = WC_Tracker::get_product_counts();
		return $product_counts['total'];
	}

	/**
	 * Gather blog related properties.
	 *
	 * @param int $user_id User id.
	 * @return array Blog details.
	 */
	public static function get_blog_details( $user_id ) {
		$blog_details = get_transient( 'wc_tracks_blog_details' );
		if ( false === $blog_details ) {
			$blog_details = array(
				'url'            => home_url(),
				'blog_lang'      => get_user_locale( $user_id ),
				'blog_id'        => class_exists( 'Jetpack_Options' ) ? Jetpack_Options::get_option( 'id' ) : null,
				'products_count' => self::get_products_count(),
				'wc_version'     => WC()->version,
			);
			set_transient( 'wc_tracks_blog_details', $blog_details, DAY_IN_SECONDS );
		}
		return $blog_details;
	}

	/**
	 * Gather details from the request to the server.
	 *
	 * @return array Server details.
	 */
	public static function get_server_details() {
		$data = array();

		$data['_via_ua'] = isset( $_SERVER['HTTP_USER_AGENT'] ) ? wc_clean( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$data['_via_ip'] = isset( $_SERVER['REMOTE_ADDR'] ) ? wc_clean( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$data['_lg']     = isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ? wc_clean( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) : '';
		$data['_dr']     = isset( $_SERVER['HTTP_REFERER'] ) ? wc_clean( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';

		$uri         = isset( $_SERVER['REQUEST_URI'] ) ? wc_clean( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$host        = isset( $_SERVER['HTTP_HOST'] ) ? wc_clean( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$data['_dl'] = isset( $_SERVER['REQUEST_SCHEME'] ) ? wc_clean( wp_unslash( $_SERVER['REQUEST_SCHEME'] ) ) . '://' . $host . $uri : '';

		return $data;
	}

	/**
	 * Add global properties to tracks.
	 *
	 * @param array $properties Array of event properties.
	 * @param array $event_name Name of the event, if passed.
	 * @return array
	 */
	public static function add_global_properties( $properties, $event_name = null ) {
		$user = wp_get_current_user();
		$data = $event_name
			? array(
				'_en' => $event_name,
				'_ts' => WC_Tracks_Client::build_timestamp(),
			)
			: array();

		$server_details = self::get_server_details();
		$identity       = WC_Tracks_Client::get_identity( $user->ID );
		$blog_details   = self::get_blog_details( $user->ID );

		return array_merge( $data, $properties, $server_details, $identity, $blog_details );
	}
}
