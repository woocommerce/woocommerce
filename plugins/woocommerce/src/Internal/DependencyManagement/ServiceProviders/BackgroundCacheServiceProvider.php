<?php

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Caching\BackgroundCache;
use Automattic\WooCommerce\Internal\DependencyManagement\AbstractServiceProvider;

/**
 * Service provider for the background cache mechanism.
 */
class BackgroundCacheServiceProvider extends AbstractServiceProvider {

	/**
	 * The classes/interfaces that are serviced by this service provider.
	 *
	 * @var array
	 */
	protected $provides = array(
		BackgroundCache::class,
	);

	/**
	 * Register the classes.
	 */
	public function register() {
		$this->share( BackgroundCache::class );
	}
}
