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
		$this->branding_html = '<div class="mock-address-provider-branding" aria-label="Powered by Google" style="display: flex; align-items: center;">Powered by <svg style="margin-left: 4px; margin-bottom: -1px;" width="43" height="15" viewBox="0 0 43 15" xmlns="http://www.w3.org/2000/svg">
<path d="M10.5013 5.44515H5.51792V6.92384H9.05244C8.87731 8.99361 7.15264 9.88202 5.52389 9.88202C5.00791 9.88428 4.49664 9.78374 4.01995 9.58625C3.54325 9.38876 3.11068 9.09828 2.74747 8.73178C2.38427 8.36528 2.09771 7.93009 1.90454 7.45163C1.71137 6.97317 1.61545 6.46102 1.62238 5.94508C1.62238 3.70655 3.35541 1.98307 5.52867 1.98307C7.20518 1.98307 8.19309 3.05178 8.19309 3.05178L9.22797 1.97949C9.22797 1.97949 7.89855 0.5 5.47453 0.5C2.38779 0.5 0 3.10512 0 5.91881C0 8.67598 2.24609 11.3647 5.55255 11.3647C8.46097 11.3647 10.59 9.37214 10.59 6.4259C10.59 5.80418 10.4997 5.44515 10.4997 5.44515H10.5013Z" fill="#222222" fill-opacity="0.66"/>
<path d="M14.5838 4.37396C12.5388 4.37396 11.0732 5.97286 11.0732 7.83684C11.0732 9.72909 12.4942 11.3574 14.6077 11.3574C16.5207 11.3574 18.0877 9.89546 18.0877 7.87744C18.0877 5.56885 16.2647 4.37396 14.5838 4.37396ZM14.6037 5.74558C15.6092 5.74558 16.5621 6.55876 16.5621 7.86869C16.5621 9.15035 15.6132 9.98701 14.599 9.98701C13.4845 9.98701 12.6088 9.09462 12.6088 7.85834C12.6088 6.64832 13.4765 5.74558 14.6069 5.74558H14.6037Z" fill="#222222" fill-opacity="0.66"/>
<path d="M22.2184 4.37418C20.1733 4.37418 18.7078 5.97307 18.7078 7.83706C18.7078 9.7293 20.1288 11.3576 22.2423 11.3576C24.1553 11.3576 25.7223 9.89568 25.7223 7.87766C25.7223 5.56907 23.8993 4.37378 22.2184 4.37378V4.37418ZM22.2383 5.7458C23.2438 5.7458 24.1967 6.55898 24.1967 7.8689C24.1967 9.15056 23.2477 9.98723 22.2336 9.98723C21.1191 9.98723 20.2434 9.09484 20.2434 7.85855C20.2434 6.64853 21.1111 5.7458 22.2415 5.7458H22.2383Z" fill="#222222" fill-opacity="0.66"/>
<path d="M29.7068 4.37811C27.8297 4.37811 26.3546 6.02199 26.3546 7.86726C26.3546 9.96887 28.0649 11.3628 29.6742 11.3628C30.6693 11.3628 31.1986 10.9675 31.5847 10.5142V11.2028C31.5847 12.4076 30.8531 13.1292 29.749 13.1292C28.6823 13.1292 28.1473 12.336 27.9579 11.8862L26.6157 12.4434C27.0917 13.4505 28.0502 14.5001 29.7602 14.5001C31.6309 14.5001 33.0487 13.3247 33.0487 10.8593V4.58788H31.5903V5.17935C31.1405 4.69375 30.5244 4.37811 29.7076 4.37811H29.7068ZM29.8421 5.74734C30.7624 5.74734 31.7073 6.53306 31.7073 7.87483C31.7073 9.23888 30.764 9.99037 29.8218 9.99037C28.8212 9.99037 27.8902 9.17838 27.8902 7.88756C27.8902 6.54699 28.8574 5.74734 29.8421 5.74734Z" fill="#222222" fill-opacity="0.66"/>
<path d="M39.5667 4.36957C37.7962 4.36957 36.3096 5.7786 36.3096 7.85633C36.3096 10.0559 37.9666 11.3602 39.7327 11.3602C41.2102 11.3602 42.1209 10.5522 42.6582 9.82778L41.451 9.02455C41.1377 9.51015 40.6139 9.9858 39.7402 9.9858C38.7587 9.9858 38.3073 9.44846 38.0275 8.92704L42.7095 6.98464L42.4707 6.41545C42.0186 5.30096 40.963 4.36957 39.5667 4.36957ZM39.6276 5.71253C40.2656 5.71253 40.7246 6.05165 40.9196 6.45844L37.7927 7.76518C37.6573 6.75338 38.6166 5.71253 39.6236 5.71253H39.6276Z" fill="#222222" fill-opacity="0.66"/>
<path d="M34.074 11.1533H35.612V0.861389H34.074V11.1533Z" fill="#222222" fill-opacity="0.66"/>
</svg></div>';
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
	 */
	public function load_jwt() {

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
				wp_json_encode( $this->id ),
				wp_json_encode( $this->get_jwt() ),
				wp_json_encode( false !== $this->can_telemetry() && (bool) $this->can_telemetry() )
			),
			'before'
		);
	}
}
