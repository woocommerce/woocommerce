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
	/**
	 * Constructor.
	 */
	public function __construct() {
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
	 */
	public function install( $download_url, $pr_name ) {
		// Download the plugin.
		$tmp_dir = download_url( $download_url );

		if ( is_wp_error( $tmp_dir ) ) {
			return new WP_Error(
				'download_error',
				sprintf( __( 'Error Downloading: <a href="%1$s">%1$s</a> - Error: %2$s', 'woocommerce-beta-tester' ), $info->download_url, $temp_path->get_error_message() )
			);
		}

		$wp_filesystem = $this->init_filesystem();

		if ( is_wp_error( $wp_filesystem ) ) {
			return $wp_filesystem;
		}

		// Unzip the plugin.
		$plugin_path = str_replace( ABSPATH, $wp_filesystem->abspath(), WP_PLUGIN_DIR );
		$unzip       = unzip_file( $tmp_dir, $plugin_path );

		if ( is_wp_error( $unzip ) ) {
			return new WP_Error( 'unzip_error', sprintf( __( 'Error Unzipping file: Error: %1$s', 'woocommerce-beta-tester' ), $result->get_error_message() ) );
		}

		// Delete the downloaded zip file.
		unlink( $tmp_dir );

		// Activate the plugin.
		$activate = activate_plugin( $this->get_plugin_path( $download_url ) );

		if ( is_wp_error( $activate ) ) {
			return $activate;
		}

		// Record the installed version in a JSON file
		// TODO generate a banner for this.
		$plugin_info = (object) array(
			'source' => $download_url,
			'pr'     => $pr_name,
		);

		$wp_filesystem->put_contents(
			"$plugin_path/$pr_name/.wc-beta-tester.json",
			wp_json_encode( $info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
		);

		return true;
	}
}
