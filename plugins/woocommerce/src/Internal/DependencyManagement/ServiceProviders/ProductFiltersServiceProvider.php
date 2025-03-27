<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\ProductFilters\MainQueryController;
use Automattic\WooCommerce\Internal\ProductFilters\FilterDataProvider;
use Automattic\WooCommerce\Internal\ProductFilters\QueryClauses;

/**
 * ProductFiltersServiceProvider class.
 */
class ProductFiltersServiceProvider extends AbstractInterfaceServiceProvider {
	/**
	 * List services provided by this class.
	 *
	 * @var string[]
	 */
	protected $provides = array(
		QueryClauses::class,
		MainQueryController::class,
		FilterDataProvider::class,
	);

	/**
	 * Registers services provided by this class.
	 *
	 * @return void
	 */
	public function register() {
		$this->share( QueryClauses::class );
		$this->share_with_implements_tags( MainQueryController::class )->addArgument( QueryClauses::class );
		$this->share( FilterDataProvider::class );
	}
}
