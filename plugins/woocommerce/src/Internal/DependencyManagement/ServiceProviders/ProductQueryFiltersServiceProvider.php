<?php

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\ProductQueryFilters\FilterData;
use Automattic\WooCommerce\Internal\ProductQueryFilters\FilterClauses;
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
		FilterClauses::class,
		FilterData::class,
		MainQueryFilters::class,
	);

	/**
	 * Registers services provided by this class.
	 *
	 * @return void
	 */
	public function register() {
		$this->share( FilterClauses::class );
		$this->share_with_implements_tags( MainQueryFilters::class )->addArgument( FilterClauses::class );
		$this->add( FilterData::class )->addArgument( FilterClauses::class );
	}
}
