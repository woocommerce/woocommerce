<?php
/**
 * MarketingServiceProvider class file.
 */

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Admin\Marketing\InstalledExtensions;
use Automattic\WooCommerce\Admin\Marketing\MarketingChannels;
use Automattic\WooCommerce\Internal\DependencyManagement\AbstractServiceProvider;

/**
 * Service provider for the non-static utils classes in the Automattic\WooCommerce\src namespace.
 *
 * @since x.x.x
 */
class MarketingServiceProvider extends AbstractServiceProvider {
	/**
	 * The classes/interfaces that are serviced by this service provider.
	 *
	 * @var array
	 */
	protected $provides = array(
		MarketingChannels::class,
	);

	/**
	 * Register the classes.
	 */
	public function register() {
		$this->share( MarketingChannels::class );
		$this->share( InstalledExtensions::class )->addArgument( MarketingChannels::class );
	}
}
