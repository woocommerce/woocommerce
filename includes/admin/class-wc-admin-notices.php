<?php
/**
 * Display notices in admin.
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin
 * @version     2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Admin_Notices Class
 */
class WC_Admin_Notices {

	/**
	 * Array of notices - name => callback
	 * @var array
	 */
	private $core_notices = array(
		'install'             => 'install_notice',
		'update'              => 'update_notice',
		'template_files'      => 'template_file_check_notice',
		'theme_support'       => 'theme_check_notice',
		'translation_upgrade' => 'translation_upgrade_notice'
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'switch_theme', array( $this, 'reset_admin_notices' ) );
		add_action( 'woocommerce_installed', array( $this, 'reset_admin_notices' ) );
		add_action( 'wp_loaded', array( $this, 'hide_notices' ) );
		add_action( 'woocommerce_hide_translation_upgrade_notice', array( $this, 'hide_translation_upgrade_notice' ) );

		if ( current_user_can( 'manage_woocommerce' ) ) {
			add_action( 'admin_print_styles', array( $this, 'add_notices' ) );
		}
	}

	/**
	 * Remove all notices
	 */
	public static function remove_all_notices() {
		delete_option( 'woocommerce_admin_notices' );
	}

	/**
	 * Reset notices for themes when switched or a new version of WC is installed
	 */
	public function reset_admin_notices() {
		if ( ! current_theme_supports( 'woocommerce' ) && ! in_array( get_option( 'template' ), wc_get_core_supported_themes() ) ) {
			self::add_notice( 'theme_support' );
		}
		self::add_notice( 'template_files' );
	}

	/**
	 * Show a notice
	 * @param  string $name
	 */
	public static function add_notice( $name ) {
		$notices = array_unique( array_merge( get_option( 'woocommerce_admin_notices', array() ), array( $name ) ) );
		update_option( 'woocommerce_admin_notices', $notices );
	}

	/**
	 * Remove a notice from being displayed
	 * @param  string $name
	 */
	public static function remove_notice( $name ) {
		$notices = array_diff( get_option( 'woocommerce_admin_notices', array() ), array( $name ) );
		update_option( 'woocommerce_admin_notices', $notices );
	}

	/**
	 * See if a notice is being shown
	 * @param  string  $name
	 * @return boolean
	 */
	public static function has_notice( $name ) {
		return in_array( $name, get_option( 'woocommerce_admin_notices', array() ) );
	}

	/**
	 * Hide a notice if the GET variable is set.
	 */
	public function hide_notices() {
		if ( isset( $_GET['wc-hide-notice'] ) && isset( $_GET['_wc_notice_nonce'] ) ) {
			if ( ! wp_verify_nonce( $_GET['_wc_notice_nonce'], 'woocommerce_hide_notices_nonce' ) ) {
				wp_die( __( 'Action failed. Please refresh the page and retry.', 'woocommerce' ) );
			}

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( __( 'Cheatin&#8217; huh?', 'woocommerce' ) );
			}

			$hide_notice = sanitize_text_field( $_GET['wc-hide-notice'] );
			self::remove_notice( $hide_notice );
			do_action( 'woocommerce_hide_' . $hide_notice . '_notice' );
		}
	}

	/**
	 * Hide translation upgrade message
	 */
	public function hide_translation_upgrade_notice() {
		update_option( 'woocommerce_language_pack_version', array( WC_VERSION, get_locale() ) );
	}

	/**
	 * Add notices + styles if needed.
	 */
	public function add_notices() {
		$notices = get_option( 'woocommerce_admin_notices', array() );

		if ( $notices ) {
			wp_enqueue_style( 'woocommerce-activation', plugins_url(  '/assets/css/activation.css', WC_PLUGIN_FILE ) );
			foreach ( $notices as $notice ) {
				if ( ! empty( $this->core_notices[ $notice ] ) && apply_filters( 'woocommerce_show_admin_notice', true, $notice ) ) {
					add_action( 'admin_notices', array( $this, $this->core_notices[ $notice ] ) );
				}
			}
		}
	}

	/**
	 * If we need to update, include a message with the update button
	 */
	public function update_notice() {
		include( 'views/html-notice-update.php' );
	}

	/**
	 * If we have just installed, show a message with the install pages button
	 */
	public function install_notice() {
		include( 'views/html-notice-install.php' );
	}

	/**
	 * Show the Theme Check notice
	 */
	public function theme_check_notice() {
		if ( ! current_theme_supports( 'woocommerce' ) && ! in_array( get_option( 'template' ), wc_get_core_supported_themes() ) ) {
			include( 'views/html-notice-theme-support.php' );
		} else {
			self::remove_notice( 'theme_support' );
		}
	}

	/**
	 * Show the translation upgrade notice
	 */
	public function translation_upgrade_notice() {
		$screen = get_current_screen();
		$locale = get_locale();

		if ( 'en_US' === $locale ) {
			self::hide_translation_upgrade_notice();
		}

		if ( 'update-core' !== $screen->id && 'en_US' !== $locale ) {
			include( 'views/html-notice-translation-upgrade.php' );
		}
	}

	/**
	 * Show a notice highlighting bad template files
	 */
	public function template_file_check_notice() {
		$core_templates = WC_Admin_Status::scan_template_files( WC()->plugin_path() . '/templates' );
		$outdated       = false;

		foreach ( $core_templates as $file ) {

			$theme_file = false;
			if ( file_exists( get_stylesheet_directory() . '/' . $file ) ) {
				$theme_file = get_stylesheet_directory() . '/' . $file;
			} elseif ( file_exists( get_stylesheet_directory() . '/woocommerce/' . $file ) ) {
				$theme_file = get_stylesheet_directory() . '/woocommerce/' . $file;
			} elseif ( file_exists( get_template_directory() . '/' . $file ) ) {
				$theme_file = get_template_directory() . '/' . $file;
			} elseif( file_exists( get_template_directory() . '/woocommerce/' . $file ) ) {
				$theme_file = get_template_directory() . '/woocommerce/' . $file;
			}

			if ( $theme_file !== false ) {
				$core_version  = WC_Admin_Status::get_file_version( WC()->plugin_path() . '/templates/' . $file );
				$theme_version = WC_Admin_Status::get_file_version( $theme_file );

				if ( $core_version && $theme_version && version_compare( $theme_version, $core_version, '<' ) ) {
					$outdated = true;
					break;
				}
			}
		}

		if ( $outdated ) {
			include( 'views/html-notice-template-check.php' );
		} else {
			self::remove_notice( 'template_files' );
		}
	}
}

new WC_Admin_Notices();
