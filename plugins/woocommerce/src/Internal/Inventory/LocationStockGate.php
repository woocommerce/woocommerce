<?php
/**
 * LocationStockGate class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Inventory;

use Automattic\WooCommerce\Internal\Features\FeaturesController;

defined( 'ABSPATH' ) || exit;

/**
 * Shared feature flag and location configuration checks for POS location stock.
 *
 * @internal
 */
class LocationStockGate {

	/**
	 * Feature controller.
	 *
	 * @var FeaturesController
	 */
	private FeaturesController $features_controller;

	/**
	 * Location stock service.
	 *
	 * @var LocationStockService
	 */
	private LocationStockService $location_stock_service;

	/**
	 * Initialize dependencies.
	 *
	 * @param FeaturesController   $features_controller Feature controller.
	 * @param LocationStockService $location_stock_service Location stock service.
	 *
	 * @internal
	 */
	final public function init( FeaturesController $features_controller, LocationStockService $location_stock_service ): void {
		$this->features_controller    = $features_controller;
		$this->location_stock_service = $location_stock_service;
	}

	/**
	 * Check whether the POS location stock feature flag is enabled.
	 */
	public function feature_is_enabled(): bool {
		return $this->features_controller->feature_is_enabled( InventoryController::FEATURE_ID );
	}

	/**
	 * Check whether a stock location has been configured.
	 *
	 * @param string $location_slug Location slug.
	 */
	public function location_is_configured( string $location_slug ): bool {
		return $this->location_stock_service->is_known_location_slug( $location_slug );
	}

	/**
	 * Check whether POS location stock can be managed for a location.
	 *
	 * @param string $location_slug Location slug.
	 */
	public function can_manage( string $location_slug = LocationStockService::LOCATION_POS ): bool {
		return $this->feature_is_enabled() && $this->location_is_configured( $location_slug );
	}
}
