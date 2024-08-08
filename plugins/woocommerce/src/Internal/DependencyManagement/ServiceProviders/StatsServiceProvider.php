<?php
/**
 * StatsServiceProvider class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\DependencyManagement\AbstractServiceProvider;
use Automattic\WooCommerce\Internal\McStats;

/**
 * Service provider for the Stats.
 */
class StatsServiceProvider extends AbstractServiceProvider {

	/**
	 * The classes/interfaces that are serviced by this service provider.
	 *
	 * @var array
	 */
	protected $provides = array(
		McStats::class,
	);

	/**
	 * Register the classes.
	 */
	public function register() {
		$this->add( McStats::class );
	}
}
