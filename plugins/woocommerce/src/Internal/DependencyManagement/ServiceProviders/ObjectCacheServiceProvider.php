<?php

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Caching\TransientsEngine;
use Automattic\WooCommerce\Caching\WpCacheEngine;
use Automattic\WooCommerce\Internal\DependencyManagement\AbstractServiceProvider;

/**
 * Service provider for the object cache mechanism.
 */
class ObjectCacheServiceProvider extends AbstractServiceProvider {

	/**
	 * The classes/interfaces that are serviced by this service provider.
	 *
	 * @var array
	 */
	protected $provides = array(
		WpCacheEngine::class,
		TransientsEngine::class,
	);

	/**
	 * Register the classes.
	 */
	public function register() {
		$this->share( WpCacheEngine::class );
		$this->share( TransientsEngine::class );
	}
}
