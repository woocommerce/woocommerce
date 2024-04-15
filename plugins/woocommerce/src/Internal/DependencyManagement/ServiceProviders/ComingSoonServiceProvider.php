<?php

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\DependencyManagement\AbstractServiceProvider;
use Automattic\WooCommerce\Internal\ComingSoon\ComingSoonCacheInvalidator;
use Automattic\WooCommerce\Internal\ComingSoon\ComingSoonRequestHandler;
use Automattic\WooCommerce\Internal\ComingSoon\ComingSoonHelper;

/**
 * Service provider for the Coming Soon mode.
 */
class ComingSoonServiceProvider extends AbstractServiceProvider {

	/**
	 * The classes/interfaces that are serviced by this service provider.
	 *
	 * @var array
	 */
	protected $provides = array(
		ComingSoonCacheInvalidator::class,
		ComingSoonHelper::class,
		ComingSoonRequestHandler::class,
	);

	/**
	 * Register the classes.
	 */
	public function register() {
		$this->add( ComingSoonCacheInvalidator::class );
		$this->add( ComingSoonHelper::class );
		$this->add( ComingSoonRequestHandler::class )->addArgument( ComingSoonHelper::class );
	}
}
