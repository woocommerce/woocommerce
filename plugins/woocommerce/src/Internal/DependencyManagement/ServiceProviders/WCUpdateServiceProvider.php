<?php
/**
 * Service provider for ActionUpdateController class.
 */

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\DependencyManagement\AbstractServiceProvider;
use Automattic\WooCommerce\Internal\Updates\WCActionUpdateController;

/**
 * Class WCUpdateServiceProvider
 *
 * @package Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders
 */
class WCUpdateServiceProvider extends AbstractServiceProvider {

	/**
	 * Services provided by this provider.
	 *
	 * @var string[]
	 */
	protected $provides = array(
		WCActionUpdateController::class,
	);

	/**
	 * Use the register method to register items with the container via the
	 * protected $this->leagueContainer property or the `getLeagueContainer` method
	 * from the ContainerAwareTrait.
	 *
	 * @return void
	 */
	public function register() {
		$this->share( WCActionUpdateController::class );
	}
}
