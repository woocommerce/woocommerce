<?php
/**
 * Provider class file.
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFilters;

use Automattic\WooCommerce\Internal\ProductFilters\Interfaces\QueryClausesGenerator;
use Automattic\WooCommerce\Internal\ProductFilters\TaxonomyHierarchyData;

defined( 'ABSPATH' ) || exit;

/**
 * Provider class.
 *
 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
 */
class FilterDataProvider {
	/**
	 * Hold initialized providers.
	 *
	 * @var array Product filter data providers.
	 */
	private $providers = array();

	/**
	 * Instance of TaxonomyHierarchyData.
	 *
	 * @var TaxonomyHierarchyData
	 */
	private $taxonomy_hierarchy_data;

	/**
	 * Initialize dependencies.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 *
	 * @param TaxonomyHierarchyData $taxonomy_hierarchy_data Instance of TaxonomyHierarchyData.
	 *
	 * @return void
	 */
	final public function init( TaxonomyHierarchyData $taxonomy_hierarchy_data ): void {
		$this->taxonomy_hierarchy_data = $taxonomy_hierarchy_data;
	}

	/**
	 * Get the data provider with desired query clauses generator.
	 *
	 * @param QueryClausesGenerator $query_clauses_generator The query clauses generator instance.
	 */
	public function with( QueryClausesGenerator $query_clauses_generator ) {
		$class_name = get_class( $query_clauses_generator );

		if ( ! isset( $this->providers[ $class_name ] ) ) {
			$this->providers[ $class_name ] = new FilterData( $query_clauses_generator, $this->taxonomy_hierarchy_data );
		}

		return $this->providers[ $class_name ];
	}
}
