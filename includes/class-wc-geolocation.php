<?php
/**
 * Geolocation class.
 *
 * Handles geolocation and updating the geolocation database.
 *
 * This product uses GeoLite2 data created by MaxMind, available from http://www.maxmind.com
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Classes
 * @version     2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Geolocation Class
 */
class WC_Geolocation {

	/** URL to the geolocation database we're using */
	const GEOLITE_DB = 'http://geolite.maxmind.com/download/geoip/database/GeoLiteCountry/GeoIP.dat.gz';

	/**
	 * Hook in tabs.
	 */
	public static function init() {
		add_action( 'woocommerce_geoip_updater', array( __CLASS__, 'update_database' ) );
	}

	/**
	 * Get current user IP Address
	 * @return string
	 */
	public static function get_ip_address() {
		return isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * Get user IP Address using a service
	 * @return string
	 */
	public static function get_external_ip_address() {
		$transient_name      = 'external_ip_address_' . self::get_ip_address();
		$external_ip_address = get_transient( $transient_name );

		if ( false === $external_ip_address ) {
			$external_ip_address = '0.0.0.0';
			$ip_lookup_services  = array(
				'http://ipv4.icanhazip.com',
				'http://api.ipify.org/',
				'http://ipecho.net/plain',
				'http://v4.ident.me',
				'http://bot.whatismyipaddress.com',
				'http://ip.appspot.com',
			);

			shuffle( $ip_lookup_services );

			foreach ( $ip_lookup_services as $service ) {
				$response = wp_remote_get( $service, array( 'timeout' => 2 ) );

				if ( ! is_wp_error( $response ) && $response['body'] ) {
					$external_ip_address = $response['body'];
					break;
				}
			}

			set_transient( $transient_name, $external_ip_address, WEEK_IN_SECONDS );
		}

		return $external_ip_address;
	}

	/**
	 * Geolocate an IP address
	 * @param  string $ip_address
	 * @return array
	 */
	public static function geolocate_ip( $ip_address = '' ) {
		$ip_address = $ip_address ? $ip_address : self::get_ip_address();
		$database   = self::get_local_database_path();

		if ( file_exists( $database ) ) {
			$country_code = self::geolite_geolocation( $ip_address );
		} else {
			$country_code = self::freegeoip_geolocation( $ip_address );
		}

		if ( ! $country_code ) {
			// May be a local environment - find external IP
			return self::geolocate_ip( self::get_external_ip_address() );
		}

		return array(
			'country' => $country_code,
			'state'   => ''
		);
	}

	/**
	 * Path to our local db
	 * @return string
	 */
	private static function get_local_database_path() {
		$upload_dir = wp_upload_dir();
		return $upload_dir['basedir'] . '/GeoIP.dat';
	}

	/**
	 * Update geoip database. Adapted from https://wordpress.org/plugins/geoip-detect/.
	 */
	public static function update_database() {
		require_once( ABSPATH . 'wp-admin/includes/file.php' );

		$tmp_database = download_url( self::GEOLITE_DB );
		$logger       = new WC_Logger();

		if ( ! is_wp_error( $tmp_database ) ) {
			$gzhandle = @gzopen( $tmp_database, 'r' );
			$handle   = @fopen( self::get_local_database_path(), 'w' );

			if ( $gzhandle && $handle ) {
				while ( ( $string = gzread( $gzhandle, 4096 ) ) != false ) {
					fwrite( $handle, $string, strlen( $string ) );
				}
				gzclose( $gzhandle );
				fclose( $handle );
			} else {
				$logger->add( 'geolocation', 'Unable to open database file' );
			}
			@unlink( $tmp_database );
		} else {
			$logger->add( 'geolocation', 'Unable to download GeoIP Database: ' . $tmp_database->get_message() );
		}
	}

	/**
	 * Use MAXMIND GeoLite database to geolocation the user.
	 * @param  string $ip_address
	 * @return string
	 */
	private static function geolite_geolocation( $ip_address ) {
		if ( ! class_exists( 'GeoIP' ) ) {
			include_once( 'libraries/geoip.php' );
		}
		$database     = self::get_local_database_path();
		$gi           = geoip_open( $database, GEOIP_STANDARD );
		$country_code = geoip_country_code_by_addr( $gi, $ip_address );
		geoip_close( $gi );

		return $country_code;
	}

	/**
	 * Use freegeoip.net
	 * @param  string $ip_address
	 * @return string
	 */
	private static function freegeoip_geolocation( $ip_address ) {
		$country_code = get_transient( 'geoip_' . $ip_address );

		if ( false === $country_code ) {
			$json_geo_ip = wp_remote_get( 'https://freegeoip.net/json/' . $ip_address );

			if ( ! is_wp_error( $json_geo_ip ) ) {
				$data         = json_decode( $json_geo_ip['body'] );
				$country_code = sanitize_text_field( $data->country_code );
				set_transient( 'geoip_' . $ip_address, $country_code, WEEK_IN_SECONDS );
			} else {
				$country_code = '';
			}
		}

		return $country_code;
	}
}

WC_Geolocation::init();
