<?php

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\DependencyManagement\AbstractServiceProvider;
use Automattic\WooCommerce\Internal\ComingSoon\ComingSoonCacheInvalidator;
use Automattic\WooCommerce\Internal\ComingSoon\ComingSoonRequestHandler;
use Automattic\WooCommerce\Internal\ComingSoon\ComingSoonHelper;

class ComingSoonServiceProvider extends AbstractServiceProvider {

	protected $provides = array(
		ComingSoonCacheInvalidator::class,
		ComingSoonHelper::class,
		ComingSoonRequestHandler::class,
	);

	public function register() {
		$this->add( ComingSoonCacheInvalidator::class );
		$this->add( ComingSoonHelper::class );
		$this->add( ComingSoonRequestHandler::class )->addArgument( ComingSoonHelper::class );
	}
}
