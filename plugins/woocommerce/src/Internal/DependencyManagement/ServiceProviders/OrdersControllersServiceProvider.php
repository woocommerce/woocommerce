<?php
/**
 * OrdersControllersServiceProvider class file.
 */

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\Orders\CouponsController;
use Automattic\WooCommerce\Internal\Orders\SourceAttributionController;
use Automattic\WooCommerce\Internal\Orders\TaxesController;
use Automattic\WooCommerce\Proxies\LegacyProxy;

/**
 * Service provider for the orders controller classes in the Automattic\WooCommerce\Internal\Orders namespace.
 */
class OrdersControllersServiceProvider extends AbstractInterfaceServiceProvider {

	/**
	 * The classes/interfaces that are serviced by this service provider.
	 *
	 * @var array
	 */
	protected $provides = array(
		CouponsController::class,
		SourceAttributionController::class,
		TaxesController::class,
	);

	/**
	 * Register the classes.
	 */
	public function register() {
		$this->share( CouponsController::class );
		$this->share_with_implements_tags( SourceAttributionController::class )->addArguments(
			array(
				LegacyProxy::class,
				FeaturesController::class,
			)
		);
		$this->share( TaxesController::class );
	}
}
