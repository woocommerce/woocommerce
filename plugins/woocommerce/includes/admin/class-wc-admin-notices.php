<?php
/**
 * Display notices in admin
 *
 * @package WooCommerce\Admin
 * @version 3.4.0
 */

use Automattic\Jetpack\Constants;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notices Class.
 */
class WC_Admin_Notices {

	/**
	 * Local notices cache.
	 *
	 * DON'T manipulate this field directly!
	 * Always use get_notices and set_notices instead.
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
		'update'                             => 'update_notice',
		'template_files'                     => 'template_file_check_notice',
		'legacy_shipping'                    => 'legacy_shipping_notice',
		'no_shipping_methods'                => 'no_shipping_methods_notice',
		'regenerating_thumbnails'            => 'regenerating_thumbnails_notice',
		'regenerating_lookup_table'          => 'regenerating_lookup_table_notice',
		'no_secure_connection'               => 'secure_connection_notice',
		'maxmind_license_key'                => 'maxmind_missing_license_key_notice',
		'redirect_download_method'           => 'redirect_download_method_notice',
		'uploads_directory_is_unprotected'   => 'uploads_directory_is_unprotected_notice',
		'base_tables_missing'                => 'base_tables_missing_notice',
		'download_directories_sync_complete' => 'download_directories_sync_complete',
		'hpos_sync_on_read_disabled'         => 'sync_on_read_disabled_notice',
	);

	/**
	 * Stores a flag indicating if the code is running in a multisite setup.
	 *
	 * @var bool
	 */
	private static bool $is_multisite;

	/**
	 * Initializes the class.
	 *
	 * @return void
	 */
	public static function init() {
		self::$is_multisite = is_multisite();
		self::set_notices( get_option( 'woocommerce_admin_notices', array() ) );
		if ( defined( 'WC_PHP_MIN_REQUIREMENTS_NOTICE' ) ) {
			self::remove_notice( WC_PHP_MIN_REQUIREMENTS_NOTICE );
		}

		add_action( 'switch_theme', array( __CLASS__, 'reset_admin_notices' ) );
		add_action( 'woocommerce_installed', array( __CLASS__, 'reset_admin_notices' ) );
		add_action( 'admin_init', array( __CLASS__, 'hide_notices' ), 20 );

		// @TODO: This prevents Action Scheduler async jobs from storing empty list of notices during WC installation.
		// That could lead to OBW not starting and 'Run setup wizard' notice not appearing in WP admin, which we want
		// to avoid.
		if ( ! WC_Install::is_new_install() || ! wc_is_running_from_async_action_scheduler() ) {
			add_action( 'shutdown', array( __CLASS__, 'store_notices' ) );
		}

		if ( current_user_can( 'manage_woocommerce' ) ) {
			add_action( 'admin_print_styles', array( __CLASS__, 'add_notices' ) );
		}
	}

	/**
	 * Parses query to create nonces when available.
	 *
	 * @deprecated 5.4.0
	 * @param object $response The WP_REST_Response we're working with.
	 * @return object $response The prepared WP_REST_Response object.
	 */
	public static function prepare_note_with_nonce( $response ) {
		wc_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '5.4.0' );

		return $response;
	}

	/**
	 * Store the locally cached notices to DB.
	 *
	 * @return void
	 */
	public static function store_notices() {
		$current_notices = self::get_notices();
		$prev_notices    = get_option( 'woocommerce_admin_notices', array() );

		// Store notices.
		update_option( 'woocommerce_admin_notices', $current_notices );

		// Clean up removed notices.
		foreach ( array_diff( $prev_notices, $current_notices ) as $notice ) {
			if ( isset( self::$core_notices[ $notice ] ) ) {
				continue;
			}

			delete_option( 'woocommerce_admin_notice_' . $notice );
		}
	}

	/**
	 * Get the value of the locally cached notices array for the current site.
	 *
	 * @return array
	 */
	public static function get_notices() {
		if ( ! self::$is_multisite ) {
			return self::$notices;
		}

		$blog_id = get_current_blog_id();
		$notices = self::$notices[ $blog_id ] ?? null;
		if ( ! is_null( $notices ) ) {
			return $notices;
		}

		self::$notices[ $blog_id ] = get_option( 'woocommerce_admin_notices', array() );
		return self::$notices[ $blog_id ];
	}

	/**
	 * Set the locally cached notices array for the current site.
	 *
	 * @param array $notices New value for the locally cached notices array.
	 * @return void
	 */
	private static function set_notices( array $notices ) {
		if ( self::$is_multisite ) {
			self::$notices[ get_current_blog_id() ] = $notices;
		} else {
			self::$notices = $notices;
		}
	}

	/**
	 * Remove all notices from the locally cached notices array.
	 *
	 * @return void
	 */
	public static function remove_all_notices() {
		self::set_notices( array() );
	}

	/**
	 * Reset notices for themes when switched or a new version of WC is installed.
	 *
	 * @return void
	 */
	public static function reset_admin_notices() {
	}

	/**
	 * Show a notice.
	 *
	 * @param string $name Notice name.
	 * @param bool   $force_save Force saving inside this method instead of at the 'shutdown'.
	 * @return void
	 */
	public static function add_notice( $name, $force_save = false ) {
		self::set_notices( array_unique( array_merge( self::get_notices(), array( $name ) ) ) );

		if ( $force_save ) {
			// Adding early save to prevent more race conditions with notices.
			self::store_notices();
		}
	}

	/**
	 * Remove a notice from being displayed.
	 *
	 * @param string $name Notice name.
	 * @param bool   $force_save Force saving inside this method instead of at the 'shutdown'.
	 * @return void
	 */
	public static function remove_notice( $name, $force_save = false ) {
		if ( self::has_notice( $name ) ) {
			self::set_notices( array_diff( self::get_notices(), array( $name ) ) );
		}

		if ( $force_save ) {
			// Adding early save to prevent more race conditions with notices.
			self::store_notices();
		}
	}

	/**
	 * Remove a given set of notices.
	 *
	 * An array of notice names or a regular expression string can be passed, in the later case
	 * all the notices whose name matches the regular expression will be removed.
	 *
	 * @param array|string $names_array_or_regex An array of notice names, or a string representing a regular expression.
	 * @param bool         $force_save Force saving inside this method instead of at the 'shutdown'.
	 * @return void
	 */
	public static function remove_notices( $names_array_or_regex, $force_save = false ) {
		if ( ! is_array( $names_array_or_regex ) ) {
			$names_array_or_regex = array_filter( self::get_notices(), fn( $notice_name ) => 1 === preg_match( $names_array_or_regex, $notice_name ) );
		}
		self::set_notices( array_diff( self::get_notices(), $names_array_or_regex ) );

		if ( $force_save ) {
			// Adding early save to prevent more race conditions with notices.
			self::store_notices();
		}
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
	 *
	 * @return void
	 */
	public static function hide_notices() {
		if ( isset( $_GET['wc-hide-notice'] ) && isset( $_GET['_wc_notice_nonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wc_notice_nonce'] ) ), 'woocommerce_hide_notices_nonce' ) ) {
				wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', 'woocommerce' ) );
			}

			$notice_name = sanitize_text_field( wp_unslash( $_GET['wc-hide-notice'] ) );

			/**
			 * Filter the capability required to dismiss a given notice.
			 *
			 * @since 6.7.0
			 *
			 * @param string $default_capability The default required capability.
			 * @param string $notice_name The notice name.
			 */
			$required_capability = apply_filters( 'woocommerce_dismiss_admin_notice_capability', 'manage_woocommerce', $notice_name );

			if ( ! current_user_can( $required_capability ) ) {
				wp_die( esc_html__( 'You don&#8217;t have permission to do this.', 'woocommerce' ) );
			}

			self::hide_notice( $notice_name );
		}
	}

	/**
	 * Hide a single notice.
	 *
	 * @param string $name Notice name.
	 * @return void
	 */
	private static function hide_notice( $name ) {
		self::remove_notice( $name );

		update_user_meta( get_current_user_id(), 'dismissed_' . $name . '_notice', true );

		do_action( 'woocommerce_hide_' . $name . '_notice' );
	}

	/**
	 * Check if a given user has dismissed a given admin notice.
	 *
	 * @since 8.5.0
	 *
	 * @param string   $name The name of the admin notice to check.
	 * @param int|null $user_id User id, or null for the current user.
	 * @return bool True if the user has dismissed the notice.
	 */
	public static function user_has_dismissed_notice( string $name, ?int $user_id = null ): bool {
		return (bool) get_user_meta( $user_id ?? get_current_user_id(), "dismissed_{$name}_notice", true );
	}

	/**
	 * Add notices + styles if needed.
	 *
	 * @return void
	 */
	public static function add_notices() {
		$notices = self::get_notices();

		if ( empty( $notices ) ) {
			return;
		}

		require_once WC_ABSPATH . 'includes/admin/wc-admin-functions.php';

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

		wp_enqueue_style( 'woocommerce-activation', plugins_url( '/assets/css/activation.css', WC_PLUGIN_FILE ), array(), Constants::get_constant( 'WC_VERSION' ) );

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
	 * @return void
	 */
	public static function add_custom_notice( $name, $notice_html ) {
		self::add_notice( $name );
		update_option( 'woocommerce_admin_notice_' . $name, wp_kses_post( $notice_html ) );
	}

	/**
	 * Output any stored custom notices.
	 *
	 * @return void
	 */
	public static function output_custom_notices() {
		$notices = self::get_notices();

		if ( ! empty( $notices ) ) {
			foreach ( $notices as $notice ) {
				if ( empty( self::$core_notices[ $notice ] ) ) {
					$notice_html = get_option( 'woocommerce_admin_notice_' . $notice );

					if ( $notice_html ) {
						include __DIR__ . '/views/html-notice-custom.php';
					}
				}
			}
		}
	}

	/**
	 * If we need to update the database, include a message with the DB update button.
	 *
	 * @return void
	 */
	public static function update_notice() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		if ( WC()->is_wc_admin_active() && in_array( $screen_id, wc_get_screen_ids(), true ) ) {
			return;
		}

		if ( WC_Install::needs_db_update() ) {
			$next_scheduled_date = WC()->queue()->get_next( 'woocommerce_run_update_callback', null, 'woocommerce-db-updates' );

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $next_scheduled_date || ! empty( $_GET['do_update_woocommerce'] ) ) {
				include __DIR__ . '/views/html-notice-updating.php';
			} else {
				include __DIR__ . '/views/html-notice-update.php';
			}
		} else {
			include __DIR__ . '/views/html-notice-updated.php';
		}
	}

	/**
	 * If we have just installed, show a message with the install pages button.
	 *
	 * @deprecated 4.6.0
	 * @return void
	 */
	public static function install_notice() {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '4.6.0', esc_html__( 'Onboarding is maintained in WooCommerce Admin.', 'woocommerce' ) );
	}

	/**
	 * Previously showed a notice highlighting bad template files.
	 *
	 * Template override status is now shown in Site Health.
	 *
	 * @return void
	 */
	public static function template_file_check_notice() {
		self::remove_notice( 'template_files' );
	}

	/**
	 * Previously showed a notice asking users to convert to shipping zones.
	 *
	 * Legacy shipping status is now shown in Site Health.
	 *
	 * @return void
	 */
	public static function legacy_shipping_notice() {
		self::remove_notice( 'legacy_shipping' );
	}

	/**
	 * Previously showed a notice when no shipping methods were configured.
	 *
	 * Shipping method status is now shown in Site Health.
	 *
	 * @return void
	 */
	public static function no_shipping_methods_notice() {
		self::remove_notice( 'no_shipping_methods' );
	}

	/**
	 * Previously showed a notice about secure connections.
	 *
	 * Secure connection status is now shown in Site Health.
	 *
	 * @return void
	 */
	public static function secure_connection_notice() {
		self::remove_notice( 'no_secure_connection' );
	}

	/**
	 * Previously showed a notice while thumbnails regenerated in the background.
	 *
	 * Thumbnail regeneration progress is now shown beside the matching status tool.
	 *
	 * @return void
	 */
	public static function regenerating_thumbnails_notice() {
		self::remove_notice( 'regenerating_thumbnails' );
	}

	/**
	 * Previously showed a notice while product lookup tables regenerated.
	 *
	 * Product lookup table regeneration status is now shown beside the matching status tool.
	 *
	 * @since 3.6.0
	 * @return void
	 */
	public static function regenerating_lookup_table_notice() {
		self::remove_notice( 'regenerating_lookup_table' );
	}

	/**
	 * Add notice about minimum PHP and WordPress requirement.
	 *
	 * @deprecated 11.0.0 WordPress and PHP minimum requirements notices are no longer shown.
	 *
	 * @since 3.6.5
	 * @return void
	 */
	public static function add_min_version_notice() {
	}

	/**
	 * Notice about WordPress and PHP minimum requirements.
	 *
	 * @deprecated 8.2.0 WordPress and PHP minimum requirements notices are no longer shown.
	 *
	 * @since 3.6.5
	 * @return void
	 */
	public static function wp_php_min_requirements_notice() {
	}

	/**
	 * Previously added a MaxMind missing license key notice.
	 *
	 * MaxMind geolocation status is now shown in Site Health.
	 *
	 * @since 3.9.0
	 * @return void
	 */
	public static function add_maxmind_missing_license_key_notice() {
		self::remove_notice( 'maxmind_license_key' );
	}

	/**
	 * Previously added a Redirect only download method notice.
	 *
	 * Download method status is now shown in Site Health.
	 *
	 * @return void
	 */
	public static function add_redirect_download_method_notice() {
		self::remove_notice( 'redirect_download_method' );
	}

	/**
	 * Previously displayed the approved download directories sync completion notice.
	 *
	 * Approved download directory sync status is now shown in Site Health. The notice ID
	 * remains stored until the merchant marks it reviewed in Site Health.
	 *
	 * @return void
	 */
	public static function download_directories_sync_complete() {
	}

	/**
	 * Previously displayed a MaxMind missing license key notice.
	 *
	 * MaxMind geolocation status is now shown in Site Health.
	 *
	 * @since 3.9.0
	 * @return void
	 */
	public static function maxmind_missing_license_key_notice() {
		self::remove_notice( 'maxmind_license_key' );
	}

	/**
	 * Previously displayed a Redirect only download method notice.
	 *
	 * Download method status is now shown in Site Health.
	 *
	 * @since 4.0
	 * @return void
	 */
	public static function redirect_download_method_notice() {
		self::remove_notice( 'redirect_download_method' );
	}

	/**
	 * Previously displayed an uploads directory protection notice.
	 *
	 * Uploads directory protection status is now shown in Site Health.
	 *
	 * @since 4.2.0
	 * @return void
	 */
	public static function uploads_directory_is_unprotected_notice() {
		self::remove_notice( 'uploads_directory_is_unprotected' );
	}

	/**
	 * Previously displayed a missing database tables notice.
	 *
	 * Database table status is now shown in Site Health.
	 *
	 * @return void
	 */
	public static function base_tables_missing_notice() {
		self::remove_notice( 'base_tables_missing' );
	}

	/**
	 * Previously displayed a notice about HPOS sync-on-read being disabled by default.
	 *
	 * HPOS sync-on-read status is now shown in Site Health.
	 *
	 * @since 10.7.0
	 * @return void
	 */
	public static function sync_on_read_disabled_notice() {
		self::remove_notice( 'hpos_sync_on_read_disabled' );
	}

	/**
	 * Wrapper for is_plugin_active.
	 *
	 * @param string $plugin Plugin to check.
	 * @return boolean
	 */
	protected static function is_plugin_active( $plugin ) {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		return is_plugin_active( $plugin );
	}

	/**
	 * Simplify Commerce is no longer in core.
	 *
	 * @deprecated 3.6.0 No longer shown.
	 * @return void
	 */
	public static function simplify_commerce_notice() {
		wc_deprecated_function( 'WC_Admin_Notices::simplify_commerce_notice', '3.6.0' );
	}

	/**
	 * Show the Theme Check notice.
	 *
	 * @deprecated 3.3.0 No longer shown.
	 * @return void
	 */
	public static function theme_check_notice() {
		wc_deprecated_function( 'WC_Admin_Notices::theme_check_notice', '3.3.0' );
	}
}

WC_Admin_Notices::init();
