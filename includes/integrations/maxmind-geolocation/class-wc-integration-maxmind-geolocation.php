<?php
/**
 * MaxMind Geolocation Integration
 *
 * @version 3.9.0
 * @package WooCommerce/Integrations
 */

defined( 'ABSPATH' ) || exit;

require_once 'class-wc-integration-maxmind-geolocation-database.php';

/**
 * WC Integration MaxMind Geolocation
 *
 * @version 3.9.0
 */
class WC_Integration_MaxMind_Geolocation extends WC_Integration {

	/**
	 * Initialize the integration.
	 */
	public function __construct() {
		$this->id                 = 'woocommerce_maxmind_geolocation';
		$this->method_title       = __( 'WooCommerce MaxMind Geolocation', 'woocommerce' );
		$this->method_description = __( 'An integration for utilizing MaxMind to do Geolocation lookups.', 'woocommerce' );

		$this->init_form_fields();
		$this->init_settings();

		// Bind to the save action for the settings.
		add_action( 'woocommerce_update_options_integration_' . $this->id, array( $this, 'process_admin_options' ) );

		/**
		 * Allows for the automatic database update to be disabled.
		 *
		 * @deprecated 3.9.0
		 * @return bool Whether or not the database should be updated periodically.
		 */
		$bind_updater = apply_filters_deprecated(
			'woocommerce_geolocation_update_database_periodically',
			array( true ),
			'3.9.0',
			false,
			'MaxMind\'s TOS requires that the databases be updated or removed periodically.'
		);

		// Bind to the scheduled updater action.
		if ( $bind_updater ) {
			add_action( 'woocommerce_geoip_updater', array( __CLASS__, 'update_database' ) );
		}
	}

	/**
	 * Initializes the settings fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'license_key' => array(
				'title'       => __( 'MaxMind License Key', 'woocommerce' ),
				'type'        => 'password',
				'description' => sprintf(
					/* translators: %1$s: Documentation URL */
					__(
						'The key that will be used when dealing with MaxMind Geolocation services. You can read how to generate one in <a href="%1$s">MaxMind\'s License Key Documentation</a>.',
						'woocommerce'
					),
					'https://support.maxmind.com/account-faq/account-related/how-do-i-generate-a-license-key/'
				),
				'desc_tip'    => false,
				'default'     => '',
			),
		);
	}

	/**
	 * Checks to make sure that the license key is valid.
	 *
	 * @param string $key The key of the field.
	 * @param mixed  $value The value of the field.
	 * @return mixed
	 * @throws Exception When the license key is invalid.
	 */
	public function validate_license_key_field( $key, $value ) {
		// Empty license keys have no need to validate the data.
		if ( empty( $value ) ) {
			return $value;
		}

		// Check the license key by attempting to download the Geolocation database.
		$tmp_database_path = WC_Integration_MaxMind_Geolocation_Database::download_database( $value );
		if ( is_wp_error( $tmp_database_path ) ) {
			WC_Admin_Settings::add_error( $tmp_database_path->get_error_message() );

			// Throw an exception to keep from changing this value. This will prevent
			// users from accidentally losing their license key, which cannot
			// be viewed again after generating.
			throw new Exception( $tmp_database_path->get_error_message() );
		}

		// We may as well put this archive to good use, now that we've downloaded one.
		self::update_database( $tmp_database_path );

		return $value;
	}

	/**
	 * Updates the database used for geolocation queries.
	 *
	 * @param string|null $new_database_path The path to the new database file. Null will fetch a new archive.
	 */
	public static function update_database( $new_database_path = null ) {
		$logger = wc_get_logger();

		// Allow us to easily interact with the filesystem.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
		global $wp_filesystem;

		// Remove any existing archives to comply with the MaxMind TOS.
		$target_database_path = WC_Integration_MaxMind_Geolocation_Database::get_database_path();
		if ( $wp_filesystem->exists( $target_database_path ) ) {
			$wp_filesystem->delete( $target_database_path );
		}

		if ( isset( $new_database_path ) ) {
			$tmp_database_path = $new_database_path;
		} else {
			// We can't download a database if there's no license key configured.
			$license_key = ( new WC_Integration_MaxMind_Geolocation() )->get_option( 'license_key' );
			if ( empty( $license_key ) ) {
				return;
			}

			$tmp_database_path = WC_Integration_MaxMind_Geolocation_Database::download_database( $license_key );
			if ( is_wp_error( $tmp_database_path ) ) {
				$logger->notice( $tmp_database_path->get_error_message(), array( 'source' => 'geolocation' ) );
				return;
			}
		}

		// Move the new database into position.
		$wp_filesystem->move( $tmp_database_path, $target_database_path, true );
		$wp_filesystem->delete( dirname( $tmp_database_path ) );
	}
}
