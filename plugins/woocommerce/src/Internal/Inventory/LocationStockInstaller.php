<?php
/**
 * LocationStockInstaller class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Inventory;

defined( 'ABSPATH' ) || exit;

/**
 * Ensures option-backed POS locations are initialized.
 *
 * @internal
 */
class LocationStockInstaller {

	/**
	 * Option key used to latch default POS location setup.
	 */
	public const POS_LOCATION_CREATED_OPTION = 'woocommerce_pos_location_stock_pos_location_created';

	/**
	 * Feature and configuration gate.
	 *
	 * @var LocationStockGate
	 */
	private LocationStockGate $gate;

	/**
	 * Location stock service.
	 *
	 * @var LocationStockService
	 */
	private LocationStockService $location_stock_service;

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 */
	final public function init( LocationStockGate $gate, LocationStockService $location_stock_service ): void {
		$this->gate                   = $gate;
		$this->location_stock_service = $location_stock_service;
	}

	/**
	 * Register installer hooks.
	 */
	public function register(): void {
		add_action( 'woocommerce_installed', array( $this, 'maybe_initialize_locations' ) );
		add_action( 'woocommerce_updated', array( $this, 'maybe_initialize_locations' ) );
		add_action( 'init', array( $this, 'maybe_initialize_locations' ), 5 );
	}

	/**
	 * Initialize the default POS location when POS location stock is enabled.
	 */
	public function maybe_initialize_locations(): void {
		if ( ! $this->gate->feature_is_enabled() ) {
			return;
		}

		if ( 'yes' === get_option( self::POS_LOCATION_CREATED_OPTION, 'no' ) && $this->location_stock_service->has_locations() ) {
			return;
		}

		$this->location_stock_service->ensure_pos_location();
		update_option( self::POS_LOCATION_CREATED_OPTION, 'yes' );
	}
}
