<?php
/**
 * Beta Tester Plugin Live Branches feature class.
 *
 * @package WC_Beta_Tester
 */

defined( 'ABSPATH' ) || exit;

require_once ABSPATH . 'wp-admin/includes/plugin.php';

const LIVE_BRANCH_PLUGIN_PREFIX = 'wc_beta_tester_live_branch';

/**
 * WC_Beta_Tester Live Branches Installer Class.
 */
class WC_Beta_Tester_Live_Branches_Installer {

	/**
	 * Keep an instance of the WP Filesystem API.
	 *
	 * @var Object The WP_Filesystem API instance
	 */
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
			return new WP_Error( 'fs_api_error', __( 'WooCommerce Beta Tester: No File System access', 'woocommerce-beta-tester' ) ); // @codingStandardsIgnoreLine.
		}

		global $wp_filesystem;

		return $wp_filesystem;
	}

	/**
	 * Get the download url of a WooCommerce plugin version from the manifest.
	 *
	 * @param string $branch The name of the branch.
	 */
	public function get_branch_info_from_manifest( $branch ) {
		$response = wp_remote_get( 'https://betadownload.jetpack.me/woocommerce-branches.json' );
		$body     = wp_remote_retrieve_body( $response );

		$obj = json_decode( $body );

		foreach ( $obj->pr as $key => $value ) {
			if ( $value->branch === $branch ) {
				return $value;
			}
		}

		return false;
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
				sprintf( __( 'Error Downloading: <a href="%1$s">%1$s</a> - Error: %2$s', 'woocommerce-beta-tester' ), $download_url, $tmp_dir->get_error_message() ) // @codingStandardsIgnoreLine.
			);
		}

		// Unzip the plugin.
		$plugin_dir  = str_replace( ABSPATH, $this->file_system->abspath(), WP_PLUGIN_DIR );
		$plugin_path = $plugin_dir . '/' . LIVE_BRANCH_PLUGIN_PREFIX . "_$version";
		$unzip_path  = $plugin_dir . "/woocommerce-$version";

		$unzip = unzip_file( $tmp_dir, $unzip_path );

		// The plugin is nested under woocommerce-dev, so we need to move it up one level.
		$this->file_system->mkdir( $plugin_path );
		$this->move( $unzip_path . '/woocommerce-dev', $plugin_path );

		if ( is_wp_error( $unzip ) ) {
			return new WP_Error( 'unzip_error', sprintf( __( 'Error Unzipping file: Error: %1$s', 'woocommerce-beta-tester' ), $result->get_error_message() ) ); // @codingStandardsIgnoreLine.
		}

		// Delete the downloaded zip file.
		unlink( $tmp_dir );

		return true;
	}

	/**
	 * Move all files from one folder to another.
	 *
	 * @param string $from The folder to move files from.
	 * @param string $to The folder to move files to.
	 */
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

	/**
	 * Deactivate all currently active WooCommerce plugins.
	 */
	public function deactivate_woocommerce() {
		// First check is the regular woo plugin active.
		if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			deactivate_plugins( 'woocommerce/woocommerce.php' );
		}

		// Check if any beta tester installed plugins are active.
		$active_plugins = get_option( 'active_plugins' );

		$active_woo_plugins = array_filter(
			$active_plugins,
			function( $plugin ) {
				return str_contains( $plugin, LIVE_BRANCH_PLUGIN_PREFIX );
			}
		);

		if ( ! empty( $active_woo_plugins ) ) {
			deactivate_plugins( $active_woo_plugins );
		}
	}

	/**
	 * Activate a beta tester installed WooCommerce plugin
	 *
	 * @param string $version The version of the plugin to activate.
	 */
	public function activate( $version ) {
		if ( ! is_plugin_active( LIVE_BRANCH_PLUGIN_PREFIX . "_$version/woocommerce.php" ) ) {
			activate_plugin( LIVE_BRANCH_PLUGIN_PREFIX . "_$version/woocommerce.php" );
		}
	}

	/**
	 * Check the install status of a plugin version.
	 *
	 * @param string $version The version of the plugin to check.
	 */
	public function check_install_status( $version ) {
		$plugin_path = WP_PLUGIN_DIR . '/' . LIVE_BRANCH_PLUGIN_PREFIX . "_$version/woocommerce.php";

		if ( ! file_exists( $plugin_path ) ) {
			return 'not-installed';
		}

		if ( is_plugin_active( LIVE_BRANCH_PLUGIN_PREFIX . "_$version/woocommerce.php" ) ) {
			return 'active';
		}

		return 'installed';
	}
}
