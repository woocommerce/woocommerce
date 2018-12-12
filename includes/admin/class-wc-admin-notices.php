<?php
/**
 * Display notices in admin
 *
 * @package WooCommerce\Admin
 * @version 3.4.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notices Class.
 */
class WC_Admin_Notices {

	/**
	 * Stores notices.
	 *
	 * @var array
	 */
	private static $notices = array();

	/**
	 * Array of notices - name => callback.
	 *
	 * @var array
	 */
	private static $core_notices = array(
		'install'                 => 'install_notice',
		'update'                  => 'update_notice',
		'template_files'          => 'template_file_check_notice',
		'legacy_shipping'         => 'legacy_shipping_notice',
		'no_shipping_methods'     => 'no_shipping_methods_notice',
		'simplify_commerce'       => 'simplify_commerce_notice',
		'regenerating_thumbnails' => 'regenerating_thumbnails_notice',
		'no_secure_connection'    => 'secure_connection_notice',
		'wootenberg'              => 'wootenberg_feature_plugin_notice',
	);

	/**
	 * Constructor.
	 */
	public static function init() {
		self::$notices = get_option( 'woocommerce_admin_notices', array() );

		add_action( 'switch_theme', array( __CLASS__, 'reset_admin_notices' ) );
		add_action( 'woocommerce_installed', array( __CLASS__, 'reset_admin_notices' ) );
		add_action( 'wp_loaded', array( __CLASS__, 'hide_notices' ) );
		add_action( 'shutdown', array( __CLASS__, 'store_notices' ) );

		if ( current_user_can( 'manage_woocommerce' ) ) {
			add_action( 'admin_print_styles', array( __CLASS__, 'add_notices' ) );
			add_action( 'activate_gutenberg/gutenberg.php', array( __CLASS__, 'add_wootenberg_feature_plugin_notice_on_gutenberg_activate' ) );
		}
	}

	/**
	 * Store notices to DB
	 */
	public static function store_notices() {
		update_option( 'woocommerce_admin_notices', self::get_notices() );
	}

	/**
	 * Get notices
	 *
	 * @return array
	 */
	public static function get_notices() {
		return self::$notices;
	}

	/**
	 * Remove all notices.
	 */
	public static function remove_all_notices() {
		self::$notices = array();
	}

	/**
	 * Reset notices for themes when switched or a new version of WC is installed.
	 */
	public static function reset_admin_notices() {
		$simplify_options = get_option( 'woocommerce_simplify_commerce_settings', array() );
		$location         = wc_get_base_location();

		if ( ! class_exists( 'WC_Gateway_Simplify_Commerce_Loader' ) && ! empty( $simplify_options['enabled'] ) && 'yes' === $simplify_options['enabled'] && in_array( $location['country'], apply_filters( 'woocommerce_gateway_simplify_commerce_supported_countries', array( 'US', 'IE' ) ), true ) ) {
			self::add_notice( 'simplify_commerce' );
		}

		if ( ! self::is_ssl() ) {
			self::add_notice( 'no_secure_connection' );
		}

		self::add_wootenberg_feature_plugin_notice();
		self::add_notice( 'template_files' );
	}

	/**
	 * Show a notice.
	 *
	 * @param string $name Notice name.
	 */
	public static function add_notice( $name ) {
		self::$notices = array_unique( array_merge( self::get_notices(), array( $name ) ) );
	}

	/**
	 * Remove a notice from being displayed.
	 *
	 * @param string $name Notice name.
	 */
	public static function remove_notice( $name ) {
		self::$notices = array_diff( self::get_notices(), array( $name ) );
		delete_option( 'woocommerce_admin_notice_' . $name );
	}

	/**
	 * See if a notice is being shown.
	 *
	 * @param string $name Notice name.
	 *
	 * @return boolean
	 */
	public static function has_notice( $name ) {
		return in_array( $name, self::get_notices(), true );
	}

	/**
	 * Hide a notice if the GET variable is set.
	 */
	public static function hide_notices() {
		if ( isset( $_GET['wc-hide-notice'] ) && isset( $_GET['_wc_notice_nonce'] ) ) { // WPCS: input var ok, CSRF ok.
			if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wc_notice_nonce'] ) ), 'woocommerce_hide_notices_nonce' ) ) { // WPCS: input var ok, CSRF ok.
				wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', 'woocommerce' ) );
			}

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( esc_html__( 'You don&#8217;t have permission to do this.', 'woocommerce' ) );
			}

			$hide_notice = sanitize_text_field( wp_unslash( $_GET['wc-hide-notice'] ) ); // WPCS: input var ok, CSRF ok.

			self::remove_notice( $hide_notice );

			update_user_meta( get_current_user_id(), 'dismissed_' . $hide_notice . '_notice', true );

			do_action( 'woocommerce_hide_' . $hide_notice . '_notice' );
		}
	}

	/**
	 * Add notices + styles if needed.
	 */
	public static function add_notices() {
		$notices = self::get_notices();

		if ( empty( $notices ) ) {
			return;
		}

		$screen          = get_current_screen();
		$screen_id       = $screen ? $screen->id : '';
		$show_on_screens = array(
			'dashboard',
			'plugins',
		);

		// Notices should only show on WooCommerce screens, the main dashboard, and on the plugins screen.
		if ( ! in_array( $screen_id, wc_get_screen_ids(), true ) && ! in_array( $screen_id, $show_on_screens, true ) ) {
			return;
		}

		wp_enqueue_style( 'woocommerce-activation', plugins_url( '/assets/css/activation.css', WC_PLUGIN_FILE ), array(), WC_VERSION );

		// Add RTL support.
		wp_style_add_data( 'woocommerce-activation', 'rtl', 'replace' );

		foreach ( $notices as $notice ) {
			if ( ! empty( self::$core_notices[ $notice ] ) && apply_filters( 'woocommerce_show_admin_notice', true, $notice ) ) {
				add_action( 'admin_notices', array( __CLASS__, self::$core_notices[ $notice ] ) );
			} else {
				add_action( 'admin_notices', array( __CLASS__, 'output_custom_notices' ) );
			}
		}
	}

	/**
	 * Add a custom notice.
	 *
	 * @param string $name        Notice name.
	 * @param string $notice_html Notice HTML.
	 */
	public static function add_custom_notice( $name, $notice_html ) {
		self::add_notice( $name );
		update_option( 'woocommerce_admin_notice_' . $name, wp_kses_post( $notice_html ) );
	}

	/**
	 * Output any stored custom notices.
	 */
	public static function output_custom_notices() {
		$notices = self::get_notices();

		if ( ! empty( $notices ) ) {
			foreach ( $notices as $notice ) {
				if ( empty( self::$core_notices[ $notice ] ) ) {
					$notice_html = get_option( 'woocommerce_admin_notice_' . $notice );

					if ( $notice_html ) {
						include dirname( __FILE__ ) . '/views/html-notice-custom.php';
					}
				}
			}
		}
	}

	/**
	 * If we need to update, include a message with the update button.
	 */
	public static function update_notice() {
		if ( version_compare( get_option( 'woocommerce_db_version' ), WC_VERSION, '<' ) ) {
			$updater = new WC_Background_Updater();
			if ( $updater->is_updating() || ! empty( $_GET['do_update_woocommerce'] ) ) { // WPCS: input var ok, CSRF ok.
				include dirname( __FILE__ ) . '/views/html-notice-updating.php';
			} else {
				include dirname( __FILE__ ) . '/views/html-notice-update.php';
			}
		} else {
			include dirname( __FILE__ ) . '/views/html-notice-updated.php';
		}
	}

	/**
	 * If we have just installed, show a message with the install pages button.
	 */
	public static function install_notice() {
		include dirname( __FILE__ ) . '/views/html-notice-install.php';
	}

	/**
	 * Show the Theme Check notice.
	 *
	 * @todo Remove this next major release.
	 */
	public static function theme_check_notice() {
		wc_deprecated_function( 'WC_Admin_Notices::theme_check_notice', '3.3.0' );

		if ( ! current_theme_supports( 'woocommerce' ) ) {
			include dirname( __FILE__ ) . '/views/html-notice-theme-support.php';
		}
	}

	/**
	 * Show a notice highlighting bad template files.
	 */
	public static function template_file_check_notice() {
		$core_templates = WC_Admin_Status::scan_template_files( WC()->plugin_path() . '/templates' );
		$outdated       = false;

		foreach ( $core_templates as $file ) {

			$theme_file = false;
			if ( file_exists( get_stylesheet_directory() . '/' . $file ) ) {
				$theme_file = get_stylesheet_directory() . '/' . $file;
			} elseif ( file_exists( get_stylesheet_directory() . '/' . WC()->template_path() . $file ) ) {
				$theme_file = get_stylesheet_directory() . '/' . WC()->template_path() . $file;
			} elseif ( file_exists( get_template_directory() . '/' . $file ) ) {
				$theme_file = get_template_directory() . '/' . $file;
			} elseif ( file_exists( get_template_directory() . '/' . WC()->template_path() . $file ) ) {
				$theme_file = get_template_directory() . '/' . WC()->template_path() . $file;
			}

			if ( false !== $theme_file ) {
				$core_version  = WC_Admin_Status::get_file_version( WC()->plugin_path() . '/templates/' . $file );
				$theme_version = WC_Admin_Status::get_file_version( $theme_file );

				if ( $core_version && $theme_version && version_compare( $theme_version, $core_version, '<' ) ) {
					$outdated = true;
					break;
				}
			}
		}

		if ( $outdated ) {
			include dirname( __FILE__ ) . '/views/html-notice-template-check.php';
		} else {
			self::remove_notice( 'template_files' );
		}
	}

	/**
	 * Show a notice asking users to convert to shipping zones.
	 */
	public static function legacy_shipping_notice() {
		$maybe_load_legacy_methods = array( 'flat_rate', 'free_shipping', 'international_delivery', 'local_delivery', 'local_pickup' );
		$enabled                   = false;

		foreach ( $maybe_load_legacy_methods as $method ) {
			$options = get_option( 'woocommerce_' . $method . '_settings' );
			if ( $options && isset( $options['enabled'] ) && 'yes' === $options['enabled'] ) {
				$enabled = true;
			}
		}

		if ( $enabled ) {
			include dirname( __FILE__ ) . '/views/html-notice-legacy-shipping.php';
		} else {
			self::remove_notice( 'template_files' );
		}
	}

	/**
	 * No shipping methods.
	 */
	public static function no_shipping_methods_notice() {
		if ( wc_shipping_enabled() && ( empty( $_GET['page'] ) || empty( $_GET['tab'] ) || 'wc-settings' !== $_GET['page'] || 'shipping' !== $_GET['tab'] ) ) { // WPCS: input var ok, CSRF ok.
			$product_count = wp_count_posts( 'product' );
			$method_count  = wc_get_shipping_method_count();

			if ( $product_count->publish > 0 && 0 === $method_count ) {
				include dirname( __FILE__ ) . '/views/html-notice-no-shipping-methods.php';
			}

			if ( $method_count > 0 ) {
				self::remove_notice( 'no_shipping_methods' );
			}
		}
	}

	/**
	 * Simplify Commerce is being removed from core.
	 */
	public static function simplify_commerce_notice() {
		$location = wc_get_base_location();

		if ( class_exists( 'WC_Gateway_Simplify_Commerce_Loader' ) || ! in_array( $location['country'], apply_filters( 'woocommerce_gateway_simplify_commerce_supported_countries', array( 'US', 'IE' ) ), true ) ) {
			self::remove_notice( 'simplify_commerce' );
			return;
		}
		if ( empty( $_GET['action'] ) ) { // WPCS: input var ok, CSRF ok.
			include dirname( __FILE__ ) . '/views/html-notice-simplify-commerce.php';
		}
	}

	/**
	 * Notice shown when regenerating thumbnails background process is running.
	 */
	public static function regenerating_thumbnails_notice() {
		include dirname( __FILE__ ) . '/views/html-notice-regenerating-thumbnails.php';
	}

	/**
	 * Notice about secure connection.
	 */
	public static function secure_connection_notice() {
		if ( self::is_ssl() || get_user_meta( get_current_user_id(), 'dismissed_no_secure_connection_notice', true ) ) {
			return;
		}

		include dirname( __FILE__ ) . '/views/html-notice-secure-connection.php';
	}

	/**
	 * If Gutenberg is active, tell people about the Products block feature plugin.
	 *
	 * @since 3.4.3
	 * @todo  Remove this notice and associated code once the feature plugin has been merged into core.
	 */
	public static function add_wootenberg_feature_plugin_notice() {
		if ( ( is_plugin_active( 'gutenberg/gutenberg.php' ) || version_compare( get_bloginfo( 'version' ), '5.0', '>=' ) ) && ! is_plugin_active( 'woo-gutenberg-products-block/woocommerce-gutenberg-products-block.php' ) ) {
			self::add_notice( 'wootenberg' );
		}
	}

	/**
	 * Tell people about the Products block feature plugin when they activate Gutenberg.
	 *
	 * @since 3.4.3
	 * @todo  Remove this notice and associated code once the feature plugin has been merged into core.
	 */
	public static function add_wootenberg_feature_plugin_notice_on_gutenberg_activate() {
		if ( ! is_plugin_active( 'woo-gutenberg-products-block/woocommerce-gutenberg-products-block.php' ) && version_compare( get_bloginfo( 'version' ), '5.0', '<' ) ) {
			self::add_notice( 'wootenberg' );
		}
	}

	/**
	 * Notice about trying the Products block.
	 */
	public static function wootenberg_feature_plugin_notice() {
		if ( get_user_meta( get_current_user_id(), 'dismissed_wootenberg_notice', true ) || is_plugin_active( 'woo-gutenberg-products-block/woocommerce-gutenberg-products-block.php' ) ) {
			self::remove_notice( 'wootenberg' );
			return;
		}

		include dirname( __FILE__ ) . '/views/html-notice-wootenberg.php';
	}

	/**
	 * Determine if the store is running SSL.
	 *
	 * @return bool Flag SSL enabled.
	 * @since  3.5.1
	 */
	protected static function is_ssl() {
		$shop_page = 0 < wc_get_page_id( 'shop' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : get_home_url();

		return ( is_ssl() && 'https' === substr( $shop_page, 0, 5 ) );
	}

}

WC_Admin_Notices::init();
