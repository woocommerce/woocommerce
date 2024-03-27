<?php

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\ProductQueryFilters\FilterDataProvider;
use Automattic\WooCommerce\Internal\ProductQueryFilters\FilterClausesGenerator;
use Automattic\WooCommerce\Internal\ProductQueryFilters\Controller;

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
		Controller::class,
	);

	/**
	 * Registers services provided by this class.
	 *
	 * @return void
	 */
	public function register() {
		$this->share( FilterClausesGenerator::class );
		$this->share_with_implements_tags( Controller::class )->addArgument( FilterClausesGenerator::class );
		/**
		 * We allow changing the clauses generator at run time, so we use `add`
		 * here to return a new instance with a known default clause generator
		 * when retrieving the data provider from the container.
		 */
		$this->add( FilterDataProvider::class )->addArgument( FilterClausesGenerator::class );
	}
}
