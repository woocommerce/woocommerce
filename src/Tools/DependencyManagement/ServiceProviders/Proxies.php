<?php
/**
 * Proxies class file.
 *
 * @package Automattic/WooCommerce/Tools/DependencyManagement/ServiceProviders
 */

namespace Automattic\WooCommerce\Tools\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Tools\DependencyManagement\AbstractServiceProvider;
use Automattic\WooCommerce\Tools\Proxies as ProxyClasses;

/**
 * Service provider for the classes in the Automattic\WooCommerce\Tools\Proxies namespace.
 */
class Proxies extends AbstractServiceProvider {

	/**
	 * The classes/interfaces that are serviced by this service provider.
	 *
	 * @var array
	 */
	protected $provides = array(
		ProxyClasses\LegacyProxy::class,
		ProxyClasses\ActionsProxy::class,
	);

	/**
	 * Register the classes.
	 */
	public function register() {
		$this->share( ProxyClasses\ActionsProxy::class );
		$this->shareWithAutoArguments( ProxyClasses\LegacyProxy::class );
	}
}
