<?php
/**
 * UtilsClassesServiceProvider class file.
 */

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\DependencyManagement\AbstractServiceProvider;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\TransientFiles\TransientFilesEngine;
use Automattic\WooCommerce\TransientFiles\TransientFilesRestController;

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
		TransientFilesEngine::class,
		TransientFilesRestController::class,
	);

	/**
	 * Register the classes.
	 */
	public function register() {
		$this->share( TransientFilesEngine::class )->addArgument( LegacyProxy::class );
		$this->share( TransientFilesRestController::class );
	}
}
