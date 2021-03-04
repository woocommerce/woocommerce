<?php
/**
 * AttributesHelperServiceProvider class file.
 */

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\DependencyManagement\AbstractServiceProvider;
use Automattic\WooCommerce\Internal\AttributesHelper;

/**
 * Service provider for the AttributesHelperServiceProvider class.
 */
class AttributesHelperServiceProvider extends AbstractServiceProvider {

	/**
	 * The classes/interfaces that are serviced by this service provider.
	 *
	 * @var array
	 */
	protected $provides = array(
		AttributesHelper::class,
	);

	/**
	 * Register the classes.
	 */
	public function register() {
		$this->share( AttributesHelper::class );
	}
}
