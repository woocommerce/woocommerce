<?php
/**
 * WooCommerce.com Product Installation.
 *
 * @package WooCommerce\WCCom
 * @since   3.7.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_WCCOM_Site_Installer Class
 *
 * Contains functionalities to install products via WooCommerce.com helper connection.
 */
class WC_WCCOM_Site_Installer {

	/**
	 * An instance of the WP_Upgrader class to be used for installation.
	 *
	 * @var \WP_Upgrader $wp_upgrader
	 */
	private static $wp_upgrader;


	/**
	 * Get WP.org plugin's main file.
	 *
	 * @since 3.7.0
	 * @param string $dir Directory name of the plugin.
	 * @return bool|string
	 */
	public static function get_wporg_plugin_main_file( $dir ) {
		// Ensure that exact dir name is used.
		$dir = trailingslashit( $dir );

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = get_plugins();
		foreach ( $plugins as $path => $plugin ) {
			if ( 0 === strpos( $path, $dir ) ) {
				return $path;
			}
		}

		return false;
	}


	/**
	 * Get plugin info
	 *
	 * @since 3.9.0
	 * @param string $dir Directory name of the plugin.
	 * @return bool|array
	 */
	public static function get_plugin_info( $dir ) {
		$plugin_folder = basename( $dir );

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = get_plugins();

		$related_plugins = array_filter(
			$plugins,
			function( $key ) use ( $plugin_folder ) {
				return strpos( $key, $plugin_folder . '/' ) === 0;
			},
			ARRAY_FILTER_USE_KEY
		);

		if ( 1 === count( $related_plugins ) ) {
			$plugin_key  = array_keys( $related_plugins )[0];
			$plugin_data = $plugins[ $plugin_key ];
			return array(
				'name'    => $plugin_data['Name'],
				'version' => $plugin_data['Version'],
				'active'  => is_plugin_active( $plugin_key ),
			);
		}
		return false;
	}

	/**
	 * Get an instance of WP_Upgrader to use for installing plugins.
	 *
	 * @return WP_Upgrader
	 */
	public static function get_wp_upgrader() {
		if ( ! empty( self::$wp_upgrader ) ) {
			return self::$wp_upgrader;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		WP_Filesystem();
		self::$wp_upgrader = new WP_Upgrader( new Automatic_Upgrader_Skin() );
		self::$wp_upgrader->init();
		wp_clean_plugins_cache();

		return self::$wp_upgrader;
	}
}
