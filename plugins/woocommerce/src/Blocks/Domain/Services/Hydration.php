<?php
namespace Automattic\WooCommerce\Blocks\Domain\Services;

use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;

/**
 * Service class that handles hydration of API data for blocks.
 */
class Hydration {
	/**
	 * Instance of the asset data registry.
	 *
	 * @var AssetDataRegistry
	 */
	protected $asset_data_registry;

	/**
	 * Cached notices to restore after hydrating the API.
	 *
	 * @var array
	 */
	protected $cached_store_notices = [];

	/**
	 * Constructor.
	 *
	 * @param AssetDataRegistry $asset_data_registry Instance of the asset data registry.
	 */
	public function __construct( AssetDataRegistry $asset_data_registry ) {
		$this->asset_data_registry = $asset_data_registry;
	}

	/**
	 * Hydrates the asset data registry with data from the API. Disables notices and nonces so requests contain valid
	 * data that is not polluted by the current session.
	 *
	 * @param array $path API paths to hydrate e.g. '/wc/store/v1/cart'.
	 * @return array Response data.
	 */
	public function get_rest_api_response_data( $path = '' ) {
		$this->cache_store_notices();
		$this->disable_nonce_check();

		// Preload the request and add it to the array. It will be $preloaded_requests['path']  and contain 'body' and 'headers'.
		$preloaded_requests = rest_preload_api_request( [], $path );

		$this->restore_cached_store_notices();
		$this->restore_nonce_check();

		// Returns just the single preloaded request, or an empty array if it doesn't exist.
		return $preloaded_requests[ $path ] ?? [];
	}

	/**
	 * Disable the nonce check temporarily.
	 */
	protected function disable_nonce_check() {
		add_filter( 'woocommerce_store_api_disable_nonce_check', [ $this, 'disable_nonce_check_callback' ] );
	}

	/**
	 * Callback to disable the nonce check. While we could use `__return_true`, we use a custom named callback so that
	 * we can remove it later without affecting other filters.
	 */
	public function disable_nonce_check_callback() {
		return true;
	}

	/**
	 * Restore the nonce check.
	 */
	protected function restore_nonce_check() {
		remove_filter( 'woocommerce_store_api_disable_nonce_check', [ $this, 'disable_nonce_check_callback' ] );
	}

	/**
	 * Cache notices before hydrating the API if the customer has a session.
	 */
	protected function cache_store_notices() {
		if ( ! did_action( 'woocommerce_init' ) || null === WC()->session ) {
			return;
		}
		$this->cached_store_notices = WC()->session->get( 'wc_notices', array() );
		WC()->session->set( 'wc_notices', null );
	}

	/**
	 * Restore notices into current session from cache.
	 */
	protected function restore_cached_store_notices() {
		if ( ! did_action( 'woocommerce_init' ) || null === WC()->session ) {
			return;
		}
		WC()->session->set( 'wc_notices', $this->cached_store_notices );
		$this->cached_store_notices = [];
	}
}
