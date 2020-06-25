<?php
/**
 * Proxies class file.
 *
 * @package Automattic/WooCommerce/Tools/DependencyManagement/ServiceProviders
 */

namespace Automattic\WooCommerce\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\DependencyManagement\AbstractServiceProvider;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Proxies\ActionsProxy;

/**
 * Service provider for the classes in the Automattic\WooCommerce\Tools\Proxies namespace.
 */
class ProxiesServiceProvider extends AbstractServiceProvider {

	/**
	 * The classes/interfaces that are serviced by this service provider.
	 *
	 * @var array
	 */
	protected $provides = array(
		LegacyProxy::class,
		ActionsProxy::class,
	);

	/**
	 * Register the classes.
	 */
	public function register() {
		$this->share( ActionsProxy::class );
		$this->share_with_auto_arguments( LegacyProxy::class );
	}
}
