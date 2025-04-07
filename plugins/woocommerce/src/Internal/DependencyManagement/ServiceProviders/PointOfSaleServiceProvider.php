<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\PointOfSale\PointOfSaleController;
use Automattic\WooCommerce\Internal\Features\FeaturesController;

/**
 * Service provider for PointOfSaleController.
 */
class PointOfSaleServiceProvider extends AbstractInterfaceServiceProvider {

	/**
	 * The classes/interfaces that are serviced by this service provider.
	 *
	 * @var array
	 */
	protected $provides = array(
		PointOfSaleController::class,
	);

	/**
	 * Register the classes.
	 */
	public function register() {
		$this->share_with_implements_tags( PointOfSaleController::class )->addArgument( FeaturesController::class );
	}
} 