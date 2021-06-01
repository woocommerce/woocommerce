<?php
/**
 * ProductAttributesLookupServiceProvider class file.
 */

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\DependencyManagement\AbstractServiceProvider;
use Automattic\WooCommerce\Internal\ProductAttributesLookup\DataRegenerator;
use Automattic\WooCommerce\Internal\ProductAttributesLookup\LookupDataStore;

/**
 * Service provider for the ProductAttributesLookupServiceProvider namespace.
 */
class ProductAttributesLookupServiceProvider extends AbstractServiceProvider {

	/**
	 * The classes/interfaces that are serviced by this service provider.
	 *
	 * @var array
	 */
	protected $provides = array(
		DataRegenerator::class,
	);

	/**
	 * Register the classes.
	 */
	public function register() {
		$this->share( DataRegenerator::class )->addArgument( LookupDataStore::class );
		$this->share( LookupDataStore::class );
	}
}
