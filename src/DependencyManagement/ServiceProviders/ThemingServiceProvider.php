<?php
/**
 * Theming class file.
 *
 * @package Automattic/WooCommerce/Tools/DependencyManagement/ServiceProviders
 */

namespace Automattic\WooCommerce\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Theming\ThemeSupport;
use Automattic\WooCommerce\DependencyManagement\AbstractServiceProvider;

/**
 * Service provider for the classes in the Automattic\WooCommerce\Theming namespace.
 */
class ThemingServiceProvider extends AbstractServiceProvider {

	/**
	 * The classes/interfaces that are serviced by this service provider.
	 *
	 * @var array
	 */
	protected $provides = array(
		ThemeSupport::class,
	);

	/**
	 * Register the classes.
	 */
	public function register() {
		$this->share_with_auto_arguments( ThemeSupport::class );
	}
}
