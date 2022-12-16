<?php
/**
 * Beta Tester Plugin Live Branches feature class.
 *
 * @package WC_Beta_Tester
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Beta_Tester Live Branches Installer Class.
 */
class WC_Beta_Tester_Live_Branches_Installer {
	private $file_system;
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->file_system = $this->init_filesystem();
	}

	/**
	 * Initialize the WP_Filesystem API
	 */
	private function init_filesystem() {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		$creds = request_filesystem_credentials( site_url() . '/wp-admin/', '', false, false, array() );

		if ( ! WP_Filesystem( $creds ) ) {
			return new WP_Error( 'fs_api_error', __( 'Jetpack Beta: No File System access', 'jetpack-beta' ) );
		}

		global $wp_filesystem;

		return $wp_filesystem;
	}

	/**
	 * Install a WooCommerce plugin version by download url.
	 *
	 * @param string $download_url The download url of the plugin version.
	 * @param string $pr_name The name of the associated PR.
	 * @param string $version The version of the plugin.
	 */
	public function install( $download_url, $pr_name, $version ) {
		// Download the plugin.
		$tmp_dir = download_url( $download_url );

		if ( is_wp_error( $tmp_dir ) ) {
			return new WP_Error(
				'download_error',
				sprintf( __( 'Error Downloading: <a href="%1$s">%1$s</a> - Error: %2$s', 'woocommerce-beta-tester' ), $download_url, $tmp_dir->get_error_message() )
			);
		}

		// Unzip the plugin.
		$plugin_dir  = str_replace( ABSPATH, $this->file_system->abspath(), WP_PLUGIN_DIR );
		$plugin_path = $plugin_dir . "/woocommerce-$version";

		$unzip = unzip_file( $tmp_dir, $plugin_path );

		// The plugin is nested under woocommerce-dev, so we need to move it up one level.
		$this->file_system->mkdir( $plugin_dir . '/woocommerce-dev' );
		$this->move( $plugin_path . '/woocommerce-dev', $plugin_dir . '/woocommerce-dev' );

		if ( is_wp_error( $unzip ) ) {
			return new WP_Error( 'unzip_error', sprintf( __( 'Error Unzipping file: Error: %1$s', 'woocommerce-beta-tester' ), $result->get_error_message() ) );
		}

		// Delete the downloaded zip file.
		unlink( $tmp_dir );

		return true;
	}

	private function move( $from, $to ) {
		$files     = scandir( $from );
		$oldfolder = "$from/";
		$newfolder = "$to/";

		foreach ( $files as $fname ) {
			if ( '.' !== $fname && '..' !== $fname ) {
				$this->file_system->move( $oldfolder . $fname, $newfolder . $fname );
			}
		}
	}

	public function deactivate() {
		deactivate_plugins( 'woocommerce/woocommerce.php' );

		return true;
	}

	public function activate() {
		activate_plugin( 'woocommerce-dev/woocommerce.php' );
	}
}
