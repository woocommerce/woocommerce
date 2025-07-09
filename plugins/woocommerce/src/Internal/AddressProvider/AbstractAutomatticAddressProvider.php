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
	 * @return string|null The JWT for the address service.
	 */
	public function load_jwt() {

		// If we already have a loaded, valid token, we return early.
		if ( $this->jwt && JsonWebToken::shallow_validate( $this->jwt ) ) {
			return $this->jwt;
		}

		$transient_key = $this->id . 'address_autocomplete_jwt';
		$cached_jwt    = get_transient( $transient_key );
		// If we have a cached, valid token, we load it to class and return early.
		if ( $cached_jwt && JsonWebToken::shallow_validate( $cached_jwt ) && 'local' !== wp_get_environment_type() ) {
			$this->jwt = $cached_jwt;
			return $this->jwt;
		}

		// Otherwise, we fetch a fresh token.
		try {
			$fresh_jwt = $this->get_address_service_jwt();
			if ( $fresh_jwt && JsonWebToken::shallow_validate( $fresh_jwt ) ) {
				$this->set_jwt( $fresh_jwt );
				return $this->jwt;
			}
		} catch ( \Exception $e ) {
			wc_get_logger()->error( sprintf( 'Failed loding JWT for %1$s address autocomplete service with error %2$s.', $this->name, $e->getMessage() ), 'address-autocomplete' );
		}

		return $this->jwt;
	}

	/**
	 * Gets the JWT for the address service.
	 *
	 * @return string The JWT for the address service.
	 */
	public function get_jwt() {
		if ( null === $this->jwt ) {
			return $this->load_jwt();
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
			set_transient( $this->id . 'address_autocomplete_jwt', $jwt, $this->get_jwt_cache_duration( $jwt ) );
		} else {
			delete_transient( $this->id . 'address_autocomplete_jwt' );
		}
	}

	/**
	 * Gets the cache duration for the JWT.
	 *
	 * @param string $jwt The JWT for the address service.
	 * @return int The cache duration for the JWT.
	 */
	public function get_jwt_cache_duration( $jwt ) {
		if ( JsonWebToken::shallow_validate( $jwt ) ) {
			$parts = JsonWebToken::get_parts( $jwt );
			if ( property_exists( $parts->payload, 'exp' ) ) {
				return $parts->payload->exp - time();
			}
		}

		return DAY_IN_SECONDS;
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
				'var a8cAddressAutocompleteServiceKeys = a8cAddressAutocompleteServiceKeys || {}; a8cAddressAutocompleteServiceKeys[ "%1$s" ] = { key: %2$s, canTelemetry: %3$s };',
				wp_json_encode( $this->id ),
				wp_json_encode( $this->get_jwt() ),
				wp_json_encode( false !== $this->can_telemetry() && (bool) $this->can_telemetry() )
			),
			'before'
		);
	}
}
