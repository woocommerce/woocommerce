<?php //phpcs:ignore Generic.PHP.RequireStrictTypes.MissingDeclaration
/**
 * WooCommerce Settings.
 */

namespace Automattic\WooCommerce\Admin\Features;

use Automattic\WooCommerce\Admin\PageController;

/**
 * Contains backend logic for the Settings feature.
 */
class Settings {
	/**
	 * Class instance.
	 *
	 * @var Settings instance
	 */
	protected static $instance = null;

	/**
	 * Get class instance.
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Hook into WooCommerce.
	 */
	public function __construct() {
		if ( ! is_admin() ) {
			return;
		}

		add_filter( 'woocommerce_admin_shared_settings', array( __CLASS__, 'add_component_settings' ) );
		// Run this after the original WooCommerce settings have been added.
		add_action( 'admin_menu', array( $this, 'register_pages' ), 60 );
		add_action( 'init', array( $this, 'redirect_core_settings_pages' ) );
	}

	/**
	 * Add the necessary data to initially load the WooCommerce Settings pages.
	 *
	 * @param array $settings Array of component settings.
	 * @return array Array of component settings.
	 */
	public static function add_component_settings( $settings ) {
		if ( ! is_admin() ) {
			return $settings;
		}

		$setting_pages = \WC_Admin_Settings::get_settings_pages();
		$pages         = array();
		foreach ( $setting_pages as $setting_page ) {
			$pages = $setting_page->add_settings_page( $pages );
		}

		$settings['settingsPages'] = $pages;

		return $settings;
	}

	/**
	 * Registers settings pages.
	 */
	public function register_pages() {
		$controller = PageController::get_instance();

		$setting_pages = \WC_Admin_Settings::get_settings_pages();
		$settings      = array();
		foreach ( $setting_pages as $setting_page ) {
			$settings = $setting_page->add_settings_page( $settings );
		}

		$order = 0;
		foreach ( $settings as $key => $setting ) {
			$order        += 10;
			$settings_page = array(
				'parent' => 'woocommerce-settings',
				'title'  => $setting,
				'id'     => 'settings-' . $key,
				'path'   => "/settings/$key",
			);

			// Replace the old menu with the first settings item.
			if ( 10 === $order ) {
				$this->replace_settings_page( $settings_page );
			}

			$controller->register_page( $settings_page );
		}
	}

	/**
	 * Replace the Settings page in the original WooCommerce menu.
	 *
	 * @param array $page Page used to replace the original.
	 */
	protected function replace_settings_page( $page ) {
		global $submenu;

		// Check if WooCommerce parent menu has been registered.
		if ( ! isset( $submenu['woocommerce'] ) ) {
			return;
		}

		foreach ( $submenu['woocommerce'] as &$item ) {
			// The "slug" (aka the path) is the third item in the array.
			if ( 0 === strpos( $item[2], 'wc-settings' ) ) {
				$item[2] = wc_admin_url( "&path={$page['path']}" );
			}
		}
	}

	/**
	 * Redirect the old settings page URLs to the new ones.
	 */
	public function redirect_core_settings_pages() {
		/* phpcs:disable WordPress.Security.NonceVerification */
		if ( ! isset( $_GET['page'] ) || 'wc-settings' !== $_GET['page'] ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$setting_pages   = \WC_Admin_Settings::get_settings_pages();
		$default_setting = isset( $setting_pages[0] ) ? $setting_pages[0]->get_id() : '';
		$setting         = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : $default_setting;
		/* phpcs:enable */

		wp_safe_redirect( wc_admin_url( "&path=/settings/$setting" ) );
		exit;
	}
}
