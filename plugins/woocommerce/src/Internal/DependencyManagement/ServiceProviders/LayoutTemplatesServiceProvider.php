<?php

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\DependencyManagement\AbstractServiceProvider;
use Automattic\WooCommerce\LayoutTemplates\LayoutTemplateRegistry;

/**
 * Service provider for layout templates.
 */
class LayoutTemplatesServiceProvider extends AbstractServiceProvider {

	/**
	 * The classes/interfaces that are serviced by this service provider.
	 *
	 * @var array
	 */
	protected $provides = array(
		LayoutTemplateRegistry::class,
	);

	/**
	 * Register the classes.
	 */
	public function register() {
		$this->share( LayoutTemplateRegistry::class );
	}
}
