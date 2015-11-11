<?php
/**
 * Geolocation class
 *
 * Handles geolocation and updating the geolocation database.
 *
 * This product includes GeoLite data created by MaxMind, available from http://www.maxmind.com.
 *
 * @author   WooThemes
 * @category Admin
 * @package  WooCommerce/Classes
 * @version  2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Geolocation Class.
 */
class WC_Geolocation {

	/** URL to the geolocation database we're using */
	const GEOLITE_DB      = 'http://geolite.maxmind.com/download/geoip/database/GeoLiteCountry/GeoIP.dat.gz';
	const GEOLITE_IPV6_DB = 'http://geolite.maxmind.com/download/geoip/database/GeoIPv6.dat.gz';

	/** @var array API endpoints for looking up user IP address */
	private static $ip_lookup_apis = array(
		'icanhazip'         => 'http://icanhazip.com',
		'ipify'             => 'http://api.ipify.org/',
		'ipecho'            => 'http://ipecho.net/plain',
		'ident'             => 'http://ident.me',
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
		// Only download the database from MaxMind if the geolocation function is enabled, or a plugin specifically requests it
		if ( 'geolocation' === get_option( 'woocommerce_default_customer_address' ) || apply_filters( 'woocommerce_geolocation_update_database_periodically', false ) ) {
			add_action( 'woocommerce_geoip_updater', array( __CLASS__, 'update_database' ) );
		}
		add_filter( 'pre_update_option_woocommerce_default_customer_address', array( __CLASS__, 'maybe_update_database' ), 10, 2 );
	}

	/**
	 * Maybe trigger a DB update for the first time.
	 * @param  string $new_value
	 * @param  string $old_value
	 * @return string
	 */
	public static function maybe_update_database( $new_value, $old_value ) {
		if ( $new_value !== $old_value && 'geolocation' === $new_value ) {
			self::update_database();
		}
		return $new_value;
	}

	/**
	 * Get current user IP Address.
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
	 * Get user IP Address using a service.
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
				$response         = wp_safe_remote_get( $service_endpoint, array( 'timeout' => 2 ) );

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
	 * Geolocate an IP address.
	 * @param  string $ip_address
	 * @param  bool   $fallback
	 * @return array
	 */
	public static function geolocate_ip( $ip_address = '', $fallback = true ) {
		// If GEOIP is enabled in CloudFlare, we can use that (Settings -> CloudFlare Settings -> Settings Overview)
		if ( ! empty( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) {
			$country_code = sanitize_text_field( strtoupper( $_SERVER['HTTP_CF_IPCOUNTRY'] ) );
		} else {
			$ip_address = $ip_address ? $ip_address : self::get_ip_address();

			if ( self::is_IPv6( $ip_address ) ) {
				$database = self::get_local_database_path( 'v6' );
			} else {
				$database = self::get_local_database_path();
			}

			if ( file_exists( $database ) ) {
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
	 * Path to our local db.
	 * @param  string $version
	 * @return string
	 */
	private static function get_local_database_path( $version = 'v4' ) {
		$version    = ( 'v4' == $version ) ? '' : 'v6';
		$upload_dir = wp_upload_dir();

		return $upload_dir['basedir'] . '/GeoIP' . $version . '.dat';
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

		$tmp_databases = array(
			'v4' => download_url( self::GEOLITE_DB ),
			'v6' => download_url( self::GEOLITE_IPV6_DB )
		);

		foreach ( $tmp_databases as $tmp_database_version => $tmp_database_path ) {
			if ( ! is_wp_error( $tmp_database_path ) ) {
				$gzhandle = @gzopen( $tmp_database_path, 'r' );
				$handle   = @fopen( self::get_local_database_path( $tmp_database_version ), 'w' );

				if ( $gzhandle && $handle ) {
					while ( $string = gzread( $gzhandle, 4096 ) ) {
						fwrite( $handle, $string, strlen( $string ) );
					}
					gzclose( $gzhandle );
					fclose( $handle );
				} else {
					$logger->add( 'geolocation', 'Unable to open database file' );
				}
				@unlink( $tmp_database_path );
			} else {
				$logger->add( 'geolocation', 'Unable to download GeoIP Database: ' . $tmp_database_path->get_error_message() );
			}
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

		$gi = new WC_Geo_IP();

		if ( self::is_IPv6( $ip_address ) ) {
			$database = self::get_local_database_path( 'v6' );
			$gi->geoip_open( $database, 0 );
			$country_code = $gi->geoip_country_code_by_addr_v6( $ip_address );
		} else {
			$database = self::get_local_database_path();
			$gi->geoip_open( $database, 0 );
			$country_code = $gi->geoip_country_code_by_addr( $ip_address );
		}

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
				$response         = wp_safe_remote_get( sprintf( $service_endpoint, $ip_address ), array( 'timeout' => 2 ) );

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

	/**
	 * Test if is IPv6.
	 *
	 * @since  2.4.0
	 *
	 * @param  string $ip_address
	 * @return bool
	 */
	private static function is_IPv6( $ip_address ) {
		return false !== filter_var( $ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 );
	}
}

WC_Geolocation::init();
