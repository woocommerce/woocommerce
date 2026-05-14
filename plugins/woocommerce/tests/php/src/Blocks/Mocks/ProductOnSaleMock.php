<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Mocks;

use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\BlockTypes\ProductOnSale;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use Automattic\WooCommerce\Blocks\Package;

/**
 * ProductOnSaleMock exposes ProductOnSale (and its AbstractProductGrid
 * base class) internals so they can be exercised from unit tests.
 */
class ProductOnSaleMock extends ProductOnSale {

	/**
	 * Initialize our mock class without re-running register_block_type to
	 * avoid colliding with the globally registered block type.
	 */
	public function __construct() {
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$this->asset_api             = Package::container()->get( Api::class );
		$this->asset_data_registry   = Package::container()->get( AssetDataRegistry::class );
		$this->integration_registry  = new IntegrationRegistry();
		// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

	/**
	 * Re-run the AbstractProductGrid initialize() path to register the
	 * add-to-cart notice suppression filter without re-registering the
	 * block type with WordPress.
	 *
	 * @return void
	 */
	public function trigger_filter_registration(): void {
		$reflection = new \ReflectionClass( ProductOnSale::class );

		// Reset the static guard so the filter is (re-)registered for the test.
		$prop = $reflection->getParentClass()->getProperty( 'single_product_grid_atc_filter_registered' );
		$prop->setAccessible( true );
		$prop->setValue( null, false );

		$method = $reflection->getParentClass()->getMethod( 'register_single_product_grid_atc_notice_filter' );
		$method->setAccessible( true );
		$method->invoke( null );
	}
}
