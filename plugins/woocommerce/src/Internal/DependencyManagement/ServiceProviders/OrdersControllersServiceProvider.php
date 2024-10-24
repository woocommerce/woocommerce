<?php
/**
 * OrdersControllersServiceProvider class file.
 */

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\DependencyManagement\AbstractServiceProvider;
use Automattic\WooCommerce\Internal\Orders\CouponsController;
use Automattic\WooCommerce\Internal\Orders\OrderActionsRestController;
use Automattic\WooCommerce\Internal\Orders\TaxesController;

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
		TaxesController::class,
		OrderActionsRestController::class,
	);

	/**
	 * Register the classes.
	 */
	public function register() {
		$this->share( CouponsController::class );
		$this->share( TaxesController::class );
		$this->share_with_implements_tags( OrderActionsRestController::class );
	}
}
