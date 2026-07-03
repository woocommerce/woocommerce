<?php
/**
 * PosLocationSeeder class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiLocationInventory;

use Automattic\WooCommerce\Internal\Locations\Location;
use Automattic\WooCommerce\Internal\Locations\LocationsController;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Seeds the default pos location from store settings once the locations table is ready.
 *
 * @internal
 */
class PosLocationSeeder {

	public const POS_LOCATION_TYPE = 'pos';

	/**
	 * Locations controller.
	 *
	 * @var LocationsController
	 */
	private $locations_controller;

	/**
	 * Dependency injection.
	 *
	 * @internal
	 *
	 * @param LocationsController $locations_controller Locations controller.
	 */
	final public function init( LocationsController $locations_controller ): void {
		$this->locations_controller = $locations_controller;
	}

	/**
	 * Register hooks. Called once from the bootstrap.
	 */
	public function register(): void {
		add_action( LocationsController::LOCATIONS_READY_ACTION, array( $this, 'maybe_seed' ) );
	}

	/**
	 * Seed the default pos location from store settings, if the inventory feature is on
	 * and none exists yet. Idempotent.
	 *
	 * The address is a one-time snapshot, not a live mirror of store settings.
	 */
	public function maybe_seed(): void {
		if ( ! FeaturesUtil::feature_is_enabled( ProductInventoryController::FEATURE_ID ) ) {
			return;
		}
		if ( $this->locations_controller->get_default_location_id( self::POS_LOCATION_TYPE ) > 0 ) {
			return;
		}

		$name = (string) get_option( 'woocommerce_pos_store_name', '' );
		if ( '' === $name ) {
			$name = (string) get_bloginfo( 'name' );
		}

		$base = wc_get_base_location();

		$location = new Location();
		$location->set_type( self::POS_LOCATION_TYPE );
		$location->set_name( $name );
		$location->set_address_1( (string) get_option( 'woocommerce_store_address', '' ) );
		$location->set_address_2( (string) get_option( 'woocommerce_store_address_2', '' ) );
		$location->set_city( (string) get_option( 'woocommerce_store_city', '' ) );
		$location->set_postcode( (string) get_option( 'woocommerce_store_postcode', '' ) );
		$location->set_country( (string) ( $base['country'] ?? '' ) );
		$location->set_state( (string) ( $base['state'] ?? '' ) );
		$location->save();

		$this->locations_controller->set_default_location( self::POS_LOCATION_TYPE, $location->get_id() );
	}
}
