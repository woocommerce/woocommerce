<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Caches;

/**
 * Tax rate version string invalidation handler.
 *
 * This class provides an 'invalidate' method that will invalidate
 * the version string for a given tax rate, which in turn invalidates
 * any cached REST API responses containing that tax rate.
 *
 * @since 10.6.0
 */
class TaxRateVersionStringInvalidator {

	/**
	 * Initialize the invalidator and register hooks.
	 *
	 * Hooks are only registered when both conditions are met:
	 * - The REST API caching feature is enabled
	 * - The backend caching setting is active
	 *
	 * @return void
	 *
	 * @since 10.6.0
	 *
	 * @internal
	 */
	final public function init(): void {
		// We can't use FeaturesController::feature_is_enabled at this point
		// (before the 'init' action is triggered) because that would cause
		// "Translation loading for the woocommerce domain was triggered too early" warnings.
		if ( 'yes' !== get_option( 'woocommerce_feature_rest_api_caching_enabled' ) ) {
			return;
		}

		if ( 'yes' === get_option( 'woocommerce_rest_api_enable_backend_caching', 'no' ) ) {
			$this->register_hooks();
		}
	}

	/**
	 * Register all tax rate-related hooks.
	 *
	 * Registers hooks for tax rate CRUD operations fired by WC_Tax class.
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		add_action( 'woocommerce_tax_rate_added', array( $this, 'handle_woocommerce_tax_rate_added' ), 10, 1 );
		add_action( 'woocommerce_tax_rate_updated', array( $this, 'handle_woocommerce_tax_rate_updated' ), 10, 1 );
		add_action( 'woocommerce_tax_rate_deleted', array( $this, 'handle_woocommerce_tax_rate_deleted' ), 10, 1 );
	}

	/**
	 * Handle the woocommerce_tax_rate_added hook.
	 *
	 * @param int $tax_rate_id The tax rate ID.
	 *
	 * @return void
	 *
	 * @since 10.6.0
	 *
	 * @internal
	 */
	public function handle_woocommerce_tax_rate_added( $tax_rate_id ): void {
		$this->invalidate( (int) $tax_rate_id );
		$this->invalidate_tax_rates_list();
	}

	/**
	 * Handle the woocommerce_tax_rate_updated hook.
	 *
	 * @param int $tax_rate_id The tax rate ID.
	 *
	 * @return void
	 *
	 * @since 10.6.0
	 *
	 * @internal
	 */
	public function handle_woocommerce_tax_rate_updated( $tax_rate_id ): void {
		$this->invalidate( (int) $tax_rate_id );
		$this->invalidate_tax_rates_list();
	}

	/**
	 * Handle the woocommerce_tax_rate_deleted hook.
	 *
	 * @param int $tax_rate_id The tax rate ID.
	 *
	 * @return void
	 *
	 * @since 10.6.0
	 *
	 * @internal
	 */
	public function handle_woocommerce_tax_rate_deleted( $tax_rate_id ): void {
		$this->invalidate( (int) $tax_rate_id );
		$this->invalidate_tax_rates_list();
	}

	/**
	 * Invalidate the tax rates list version string.
	 *
	 * Called when tax rates are added, updated, or deleted,
	 * as these operations affect collection/list endpoints.
	 *
	 * @return void
	 */
	private function invalidate_tax_rates_list(): void {
		wc_get_container()->get( VersionStringGenerator::class )->delete_version( 'list_tax_rates' );
	}

	/**
	 * Invalidate a tax rate version string.
	 *
	 * @param int $tax_rate_id The tax rate ID.
	 *
	 * @return void
	 *
	 * @since 10.6.0
	 */
	public function invalidate( int $tax_rate_id ): void {
		wc_get_container()->get( VersionStringGenerator::class )->delete_version( "tax_rate_{$tax_rate_id}" );
	}
}
