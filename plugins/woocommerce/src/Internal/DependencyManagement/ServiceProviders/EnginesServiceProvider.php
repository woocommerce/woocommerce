<?php
/**
 * UtilsClassesServiceProvider class file.
 */

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\DependencyManagement\AbstractServiceProvider;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Templating\TemplatingEngine;
use Automattic\WooCommerce\Templating\TemplatingRestController;
use Automattic\WooCommerce\Utilities\TimeUtil;

/**
 * Service provider for the engine classes in the Automattic\WooCommerce\src namespace.
 */
class EnginesServiceProvider extends AbstractServiceProvider {

	/**
	 * The classes/interfaces that are serviced by this service provider.
	 *
	 * @var array
	 */
	protected $provides = array(
		TemplatingEngine::class,
		TemplatingRestController::class,
	);

	/**
	 * Register the classes.
	 */
	public function register() {
		$this->share( TemplatingEngine::class )->addArguments( array( TimeUtil::class, LegacyProxy::class ) );
		$this->share( TemplatingRestController::class )->addArgument( TemplatingEngine::class );
	}
}
