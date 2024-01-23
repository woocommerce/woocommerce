<?php

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\ProductQueryFilters\FilterDataProvider;
use Automattic\WooCommerce\Internal\ProductQueryFilters\FilterClausesGenerator;
use Automattic\WooCommerce\Internal\ProductQueryFilters\MainQueryFilters;

/**
 * LoggingServiceProvider class.
 */
class ProductQueryFiltersServiceProvider extends AbstractInterfaceServiceProvider {
	/**
	 * List services provided by this class.
	 *
	 * @var string[]
	 */
	protected $provides = array(
		FilterClausesGenerator::class,
		FilterDataProvider::class,
		MainQueryFilters::class,
	);

	/**
	 * Registers services provided by this class.
	 *
	 * @return void
	 */
	public function register() {
		$this->share( FilterClausesGenerator::class );
		$this->share_with_implements_tags( MainQueryFilters::class )->addArgument( FilterClausesGenerator::class );
		$this->add( FilterDataProvider::class )->addArgument( FilterClausesGenerator::class );
	}
}
