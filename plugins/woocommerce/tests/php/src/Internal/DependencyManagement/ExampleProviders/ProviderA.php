<?php

namespace Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleProviders;

use Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders\AbstractInterfaceServiceProvider;

/**
 * Example service provider for the dependency injection container, it registers classes both by name and by interface.
 */
class ProviderA extends AbstractInterfaceServiceProvider {
	// phpcs:disable Squiz.Commenting

	protected $provides = array(
		ClassA::class,
		ClassAWithInterface1::class,
		ClassAWithInterface2::class,
	);

	public function register() {
		$this->share( ClassA::class );
		$this->share_with_implements_tags( ClassAWithInterface1::class );
		$this->share_with_implements_tags( ClassAWithInterface2::class );
	}

	// phpcs:enable Squiz.Commenting
}
