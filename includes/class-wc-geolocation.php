<?php
/**
 * Geolocation class.
 *
 * Handles geolocation and updating the geolocation database.
 *
 * This product uses GeoLite2 data created by MaxMind, available from http://www.maxmind.com
 *
 * @author   WooThemes
 * @category Admin
 * @package  WooCommerce/Classes
 * @version  2.3.0
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

	/** @var array API endpoints for looking up user IP address */
	private static $ip_lookup_apis = array(
		'icanhazip'         => 'http://ipv4.icanhazip.com',
		'ipify'             => 'http://api.ipify.org/',
		'ipecho'            => 'http://ipecho.net/plain',
		'ident'             => 'http://v4.ident.me',
		'whatismyipaddress' => 'http://bot.whatismyipaddress.com',
		'ip.appspot'        => 'http://ip.appspot.com'
	);

	/** @var array API endpoints for geolocating an IP address */
	private static $geoip_apis = array(
		'freegeoip'        => 'https://freegeoip.net/json/%s',
		'telize'           => 'http://www.telize.com/geoip/%s',
		'geoip-api.meteor' => 'http://geoip-api.meteor.com/lookup/%s'
	);

	/**
	 * Hook in tabs.
	 */
	public static function init() {
		add_action( 'woocommerce_geoip_updater', array( __CLASS__, 'update_database' ) );
		add_action( 'woocommerce_installed', array( __CLASS__, 'update_database' ) );
	}

	/**
	 * Get current user IP Address
	 * @return string
	 */
	public static function get_ip_address() {
		if ( isset( $_SERVER['X-Real-IP'] ) ) {
			return $_SERVER['X-Real-IP'];
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			// Proxy servers can send through this header like this: X-Forwarded-For: client1, proxy1, proxy2
			// Make sure we always only send through the first IP in the list which should always be the client IP.
			return trim( current( explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
		} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			return $_SERVER['REMOTE_ADDR'];
		}
		return '';
	}

	/**
	 * Get user IP Address using a service
	 * @return string
	 */
	public static function get_external_ip_address() {
		$transient_name      = 'external_ip_address_' . self::get_ip_address();
		$external_ip_address = get_transient( $transient_name );

		if ( false === $external_ip_address ) {
			$external_ip_address     = '0.0.0.0';
			$ip_lookup_services      = apply_filters( 'woocommerce_geolocation_ip_lookup_apis', self::$ip_lookup_apis );
			$ip_lookup_services_keys = array_keys( $ip_lookup_services );
			shuffle( $ip_lookup_services_keys );

			foreach ( $ip_lookup_services_keys as $service_name ) {
				$service_endpoint = $ip_lookup_services[ $service_name ];
				$response         = wp_remote_get( $service_endpoint, array( 'timeout' => 2 ) );

				if ( ! is_wp_error( $response ) && $response['body'] ) {
					$external_ip_address = apply_filters( 'woocommerce_geolocation_ip_lookup_api_response', wc_clean( $response['body'] ), $service_name );
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
	public static function geolocate_ip( $ip_address = '', $fallback = true ) {
		// If GEOIP is enabled in CloudFlare, we can use that (Settings -> CloudFlare Settings -> Settings Overview)
		if ( ! empty( $_SERVER[ "HTTP_CF_IPCOUNTRY" ] ) ) {
			$country_code = sanitize_text_field( strtoupper( $_SERVER["HTTP_CF_IPCOUNTRY"] ) );
		} else {
			$ip_address = $ip_address ? $ip_address : self::get_ip_address();

			if ( file_exists( self::get_local_database_path() ) ) {
				$country_code = self::geolocate_via_db( $ip_address );
			} else {
				$country_code = self::geolocate_via_api( $ip_address );
			}

			if ( ! $country_code && $fallback ) {
				// May be a local environment - find external IP
				return self::geolocate_ip( self::get_external_ip_address(), false );
			}
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
		$logger = new WC_Logger();

		if ( ! is_callable( 'gzopen' ) ) {
			$logger->add( 'geolocation', 'Server does not support gzopen' );
			return;
		}

		require_once( ABSPATH . 'wp-admin/includes/file.php' );

		$tmp_database = download_url( self::GEOLITE_DB );

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
			$logger->add( 'geolocation', 'Unable to download GeoIP Database: ' . $tmp_database->get_error_message() );
		}
	}

	/**
	 * Use MAXMIND GeoLite database to geolocation the user.
	 * @param  string $ip_address
	 * @return string
	 */
	private static function geolocate_via_db( $ip_address ) {
		if ( ! class_exists( 'WC_Geo_IP' ) ) {
			include_once( 'class-wc-geo-ip.php' );
		}
		$database = self::get_local_database_path();
		$gi       = new WC_Geo_IP();

		$gi->geoip_open( $database, 0 );
		$country_code = $gi->geoip_country_code_by_addr( $ip_address );
		$gi->geoip_close();

		return sanitize_text_field( strtoupper( $country_code ) );
	}

	/**
	 * Use APIs to Geolocate the user.
	 * @param  string $ip_address
	 * @return string|bool
	 */
	private static function geolocate_via_api( $ip_address ) {
		$country_code = get_transient( 'geoip_' . $ip_address );

		if ( false === $country_code ) {
			$geoip_services      = apply_filters( 'woocommerce_geolocation_geoip_apis', self::$geoip_apis );
			$geoip_services_keys = array_keys( $geoip_services );
			shuffle( $geoip_services_keys );

			foreach ( $geoip_services_keys as $service_name ) {
				$service_endpoint = $geoip_services[ $service_name ];
				$response         = wp_remote_get( sprintf( $service_endpoint, $ip_address ), array( 'timeout' => 2 ) );

				if ( ! is_wp_error( $response ) && $response['body'] ) {
					switch ( $service_name ) {
						case 'geoip-api.meteor' :
							$data         = json_decode( $response['body'] );
							$country_code = isset( $data->country ) ? $data->country : '';
						break;
						case 'freegeoip' :
						case 'telize' :
							$data         = json_decode( $response['body'] );
							$country_code = isset( $data->country_code ) ? $data->country_code : '';
						break;
						default :
							$country_code = apply_filters( 'woocommerce_geolocation_geoip_response_' . $service_name, '', $response['body'] );
						break;
					}

					$country_code = sanitize_text_field( strtoupper( $country_code ) );

					if ( $country_code ) {
						break;
					}
				}
			}

			set_transient( 'geoip_' . $ip_address, $country_code, WEEK_IN_SECONDS );
		}

		return $country_code;
	}
}

WC_Geolocation::init();
