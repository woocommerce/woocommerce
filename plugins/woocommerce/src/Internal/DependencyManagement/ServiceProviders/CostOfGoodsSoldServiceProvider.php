<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\CostOfGoodsSold\CostOfGoodsSoldController;
use Automattic\WooCommerce\Internal\Features\FeaturesController;

/**
 * Service provider for the Cost of Goods Sold feature.
 */
class CostOfGoodsSoldServiceProvider extends AbstractInterfaceServiceProvider {

	/**
	 * The classes/interfaces that are serviced by this service provider.
	 *
	 * @var array
	 */
	protected $provides = array(
		CostOfGoodsSoldController::class,
	);

	/**
	 * Register the classes.
	 */
	public function register() {
		$this->share_with_implements_tags( CostOfGoodsSoldController::class )->addArgument( FeaturesController::class );
	}
}
