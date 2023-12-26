<?php
/**
 * ProductImageBySKUServiceProvider class file.
 */

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\DependencyManagement\AbstractServiceProvider;
use Automattic\WooCommerce\Internal\ProductImage\MatchImageBySKU;

/**
 * Service provider for the ProductImageBySKUServiceProvider namespace.
 */
class ProductImageBySKUServiceProvider extends AbstractServiceProvider {

	/**
	 * The classes/interfaces that are serviced by this service provider.
	 *
	 * @var array
	 */
	protected $provides = array(
		MatchImageBySKU::class,
	);

	/**
	 * Register the classes.
	 */
	public function register() {
		$this->share( MatchImageBySKU::class );
	}
}
