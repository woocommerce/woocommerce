<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\AddressProvider;

use Automattic\WooCommerce\StoreApi\Utilities\JsonWebToken;
use Automattic\Jetpack\Constants;
use WC_Address_Provider;

/**
 * Abstract Automattic address provider is an abstract implementation of the WC_Address_Provider that is meant to be used by Automattic services to get support for address autocomplete and maps with minimal code maintenance.
 *
 * @since 10.1.0
 * @package WooCommerce
 */
abstract class AbstractAutomatticAddressProvider extends WC_Address_Provider {

	/**
	 * The JWT for the address service.
	 *
	 * @var string
	 */
	private $jwt = null;

	/**
	 * Loads up the JWT for the address service and saves it to transient.
	 */
	public function __construct() {
		add_filter( 'pre_update_option_woocommerce_address_autocomplete_enabled', array( $this, 'refresh_cache' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );

		// Powered by Google branding.
		$this->branding_html = 'Powered by&nbsp;<img style="height: 15px; width: 45px; margin-bottom: -2px;" src="' . plugins_url( '/assets/images/address-autocomplete/google.svg', WC_PLUGIN_FILE ) . '" alt="Google logo" />';
	}

	/**
	 * Get the JWT for the address service, a service should implement an A8C hosted API or some mechanism to get a JWT, this will be passed to frontend code to be used in the address autocomplete and maps.
	 *
	 * This method shouldn't implement any caching, it should only fetch the token or throw an exception, if you must handle caching, consider also overriding get_jwt.
	 *
	 * @return string The JWT for the address service.
	 */
	abstract public function get_address_service_jwt();

	/**
	 * Get the telemetry status for the address service, this is meant to be overridden by the implementor to return true if the service has permission to send telemetry data.
	 *
	 * @return bool The telemetry status for the address service.
	 */
	public function can_telemetry() {
		return false;
	}

	/**
	 * Loads up a JWT from cache or from the implementor side.
	 *
	 * @return void
	 *
	 * phpcs:ignore Squiz.Commenting.FunctionCommentThrowTag.Missing -- As we wrap the throw in a try/catch.
	 */
	public function load_jwt() {

		// If the address autocomplete is disabled, we don't load the JWT.
		if ( wc_string_to_bool( get_option( 'woocommerce_address_autocomplete_enabled', 'no' ) ) !== true ) {
			return;
		}

		// If we already have a loaded, valid token, we return early.
		if ( $this->jwt && is_string( $this->jwt ) && JsonWebToken::shallow_validate( $this->jwt ) ) {
			return;
		}

		$cached_jwt = $this->get_cached_option( 'address_autocomplete_jwt' );
		// If we have a cached, valid token, we load it to class and return early.
		if ( $cached_jwt && is_string( $cached_jwt ) && JsonWebToken::shallow_validate( $cached_jwt ) ) {
			$this->jwt = $cached_jwt;
			return;
		}

		$retry_data = $this->get_cached_option( 'jwt_retry_data' );

		if ( $retry_data && isset( $retry_data['try_after'] ) && $retry_data['try_after'] > time() ) {
			return;
		}

		try {
			$fresh_jwt = $this->get_address_service_jwt();
			if ( $fresh_jwt && is_string( $fresh_jwt ) && JsonWebToken::shallow_validate( $fresh_jwt ) ) {
				$this->set_jwt( $fresh_jwt );
				// Clear retry data on success.
				$this->delete_cached_option( 'jwt_retry_data' );
				return;
			} else {
				throw new \Exception( 'Invalid JWT received from address service.' );
			}
		} catch ( \Exception $e ) {
			$retry_data['attempts'] = isset( $retry_data['attempts'] ) ? $retry_data['attempts'] + 1 : 1;
			wc_get_logger()->error(
				sprintf(
					'Failed loading JWT for %1$s address autocomplete service (attempt %2$d) with error %3$s.',
					$this->name,
					$retry_data['attempts'],
					$e->getMessage()
				),
				'address-autocomplete'
			);
			$backoff_hours           = pow( 2, $retry_data['attempts'] - 1 ); // 1, 2, 4, 8 hours.
			$retry_data['try_after'] = time() + ( $backoff_hours * HOUR_IN_SECONDS );
			$this->update_cached_option( 'jwt_retry_data', $retry_data, DAY_IN_SECONDS );
		}
	}

	/**
	 * Gets the JWT for the address service.
	 *
	 * @return string The JWT for the address service.
	 */
	public function get_jwt() {
		if ( null === $this->jwt ) {
			$this->load_jwt();
		}

		return $this->jwt;
	}

	/**
	 * Sets the JWT for the address service.
	 *
	 * @param string $jwt The JWT for the address service.
	 */
	public function set_jwt( $jwt ) {
		$this->jwt = $jwt;
		if ( null !== $jwt ) {
			$cache_duration = $this->get_jwt_cache_duration( $jwt );
			// If the token is expired, we don't cache it and we fetch a new one.
			if ( 0 === $cache_duration ) {
				$this->jwt = null;
				$this->load_jwt();
				return;
			}
			$this->update_cached_option( 'address_autocomplete_jwt', $jwt, $cache_duration );
		} else {
			$this->delete_cached_option( 'address_autocomplete_jwt' );
		}
	}

	/**
	 * Gets the cache duration for the JWT.
	 *
	 * @param string $jwt The JWT for the address service.
	 * @return int The cache duration for the JWT.
	 */
	public function get_jwt_cache_duration( $jwt ) {
		$parts = JsonWebToken::get_parts( $jwt );
		if ( property_exists( $parts->payload, 'exp' ) ) {
			return max( $parts->payload->exp - time(), 0 );
		}
	}

	/**
	 * Deletes the cached token if we disable the autocomplete service or fetches a new one if it's enabled.
	 *
	 * @param string $setting If the service is enabled or disabled.
	 * @return string the setting value.
	 */
	public function refresh_cache( $setting ) {
		if ( wc_string_to_bool( $setting ) ) {
			$this->load_jwt();
		} else {
			$this->set_jwt( null );
		}

		return $setting;
	}

	/**
	 * Gets the cached option.
	 *
	 * @param string $key The key of the option.
	 * @return mixed|null The cached option.
	 */
	private function get_cached_option( $key ) {
		$data = get_option( $this->id . '_' . $key );
		if ( is_array( $data ) && isset( $data['data'] ) ) {
			if ( ! self::is_expired( $data ) ) {
				return $data['data'];
			}
			$this->delete_cached_option( $key );
		}
		return null;
	}

	/**
	 * Updates the cached option.
	 *
	 * @param string $key The key of the option.
	 * @param mixed  $value The value of the option.
	 * @param int    $ttl The TTL of the option.
	 */
	private function update_cached_option( $key, $value, $ttl = DAY_IN_SECONDS ) {
		$result = update_option(
			$this->id . '_' . $key,
			array(
				'data'    => $value,
				'updated' => time(),
				'ttl'     => $ttl,
			),
			false
		);
		if ( false === $result ) {
			wp_cache_delete( $this->id . '_' . $key, 'options' );
		}
	}

	/**
	 * Deletes the cached option.
	 *
	 * @param string $key The key of the option.
	 */
	private function delete_cached_option( $key ) {
		if ( delete_option( $this->id . '_' . $key ) ) {
			wp_cache_delete( $this->id . '_' . $key, 'options' );
		}
	}

	/**
	 * Checks if the cache value is expired.
	 *
	 * @param array $cache_contents The cache contents.
	 *
	 * @return boolean True if the contents are expired. False otherwise.
	 */
	private static function is_expired( $cache_contents ) {
		if ( ! is_array( $cache_contents ) || ! isset( $cache_contents['updated'] ) || ! isset( $cache_contents['ttl'] ) ) {
			// Treat bad/invalid cache contents as expired.
			return true;
		}

		// Double-check that we have integers for `updated` and `ttl`.
		if ( ! is_int( $cache_contents['updated'] ) || ! is_int( $cache_contents['ttl'] ) ) {
			return true;
		}

		$expires = $cache_contents['updated'] + $cache_contents['ttl'];
		$now     = time();
		return $expires < $now;
	}

	/**
	 * Return asset URL, copied from WC_Frontend_Scripts::get_asset_url.
	 *
	 * @param string $path Assets path.
	 * @return string
	 */
	public static function get_asset_url( $path ) {
		/**
		 * Filters the asset URL.
		 *
		 * @since 3.2.0
		 *
		 * @param string $url The asset URL.
		 * @param string $path The asset path.
		 * @return string The filtered asset URL.
		 */
		return apply_filters( 'woocommerce_get_asset_url', plugins_url( $path, Constants::get_constant( 'WC_PLUGIN_FILE' ) ), $path );
	}


	/**
	 * Enqueues the checkout script, checks if it's already registered or not so we don't duplicate, and prints out the JWT to the page to be consumed.
	 */
	public function load_scripts() {
		// If the address autocomplete setting is disabled, don't load the scripts.
		if ( wc_string_to_bool( get_option( 'woocommerce_address_autocomplete_enabled', 'no' ) ) !== true ) {
			return;
		}

		if ( ! is_checkout() ) {
			return;
		}

		if ( ! $this->get_jwt() ) {
			return;
		}

		$suffix  = Constants::is_true( 'SCRIPT_DEBUG' ) ? '' : '.min';
		$version = Constants::get_constant( 'WC_VERSION' );

		if ( ! wp_script_is( 'a8c-address-autocomplete-service', 'registered' ) ) {
			wp_register_script( 'a8c-address-autocomplete-service', self::get_asset_url( 'assets/js/frontend/a8c-address-autocomplete-service' . $suffix . '.js' ), array( 'wc-address-autocomplete' ), $version, array( 'strategy' => 'defer' ) );
		}

		if ( ! wp_script_is( 'a8c-address-autocomplete-service', 'enqueued' ) ) {
			wp_enqueue_script( 'a8c-address-autocomplete-service' );
		}

		wp_add_inline_script(
			'a8c-address-autocomplete-service',
			sprintf(
				'var a8cAddressAutocompleteServiceKeys = a8cAddressAutocompleteServiceKeys || {}; a8cAddressAutocompleteServiceKeys[ %1$s ] = { key: %2$s, canTelemetry: %3$s };',
				wp_json_encode( $this->id, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES ),
				wp_json_encode( $this->get_jwt(), JSON_HEX_TAG | JSON_UNESCAPED_SLASHES ),
				wp_json_encode( false !== $this->can_telemetry() && (bool) $this->can_telemetry(), JSON_HEX_TAG | JSON_UNESCAPED_SLASHES )
			),
			'before'
		);
	}
}
