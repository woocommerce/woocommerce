<?php

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Caching\BackgroundScheduler;
use Automattic\WooCommerce\Internal\DependencyManagement\AbstractServiceProvider;

/**
 * Service provider for the background cache mechanism.
 */
class BackgroundSchedulerServiceProvider extends AbstractServiceProvider {

	/**
	 * The classes/interfaces that are serviced by this service provider.
	 *
	 * @var array
	 */
	protected $provides = array(
		BackgroundScheduler::class,
	);

	/**
	 * Register the classes.
	 */
	public function register() {
		$this->share( BackgroundScheduler::class );
	}
}
