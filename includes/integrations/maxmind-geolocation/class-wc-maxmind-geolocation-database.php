<?php
/**
 * MaxMind Geolocation Database
 *
 * @version 3.9.0
 * @package WooCommerce/Integrations
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC MaxMind Geolocation Database
 *
 * @version 3.9.0
 */
class WC_MaxMind_Geolocation_Database {

	/**
	 * The license key for interacting with the MaxMind service.
	 *
	 * @var string
	 */
	private $license_key;

	/**
	 * Initialize the database.
	 *
	 * @param string $license_key The license key to interact with the MaxMind service.
	 */
	public function __construct( $license_key ) {
		$this->license_key = $license_key;
	}

	/**
	 * Fetches the database from the MaxMind service.
	 *
	 * @return string|WP_Error The path to the database file or an error if invalid.
	 */
	public function download_database() {
		$download_uri  = 'https://download.maxmind.com/app/geoip_download?';
		$download_uri .= http_build_query(
			array(
				'edition_id'  => 'GeoLite2-Country',
				'license_key' => $this->license_key,
				'suffix'      => 'tar.gz',
			)
		);

		// Needed for the download_url call right below.
		require_once ABSPATH . 'wp-admin/includes/file.php';

		$tmp_archive_path = download_url( $download_uri );
		if ( is_wp_error( $tmp_archive_path ) ) {
			// Transform the error into something more informative.
			$error_data = $tmp_archive_path->get_error_data();
			if ( isset( $error_data['code'] ) ) {
				switch ( $error_data['code'] ) {
					case 401:
						return new WP_Error(
							'woocommerce_maxmind_geolocation_database_license_key',
							__( 'The MaxMind license key is invalid.', 'woocommerce' )
						);
				}
			}

			return new WP_Error( 'woocommerce_maxmind_geolocation_database_download', __( 'Failed to download the MaxMind database.', 'woocommerce' ) );
		}

		// Extract the database from the archive.
		try {
			$file = new PharData( $tmp_archive_path );

			$tmp_database_path = trailingslashit( dirname( $tmp_archive_path ) ) . trailingslashit( $file->current()->getFilename() ) . 'GeoLite2-Country.mmdb';

			$file->extractTo(
				dirname( $tmp_database_path ),
				trailingslashit( $file->current()->getFilename() ) . 'GeoLite2-Country.mmdb',
				true
			);
		} catch ( Exception $exception ) {
			return new WP_Error( 'woocommerce_maxmind_geolocation_database_archive', $exception->getMessage() );
		} finally {
			// Remove the archive since we only care about a single file in it.
			unlink( $tmp_archive_path );
		}

		return $tmp_database_path;
	}
}
