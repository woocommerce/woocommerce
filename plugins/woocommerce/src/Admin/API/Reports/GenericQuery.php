<?php
declare( strict_types = 1);

namespace Automattic\WooCommerce\Admin\API\Reports;

defined( 'ABSPATH' ) || exit;

use WC_Data_Store;

/**
 * A generic class for a report-specific query to be used in Analytics.
 *
 * Example usage:
 * <pre><code class="language-php">$args = array(
 *          'before'    => '2018-07-19 00:00:00',
 *          'after'     => '2018-07-05 00:00:00',
 *          'page'      => 2,
 *         );
 * $report = new GenericQuery( $args, 'coupons' );
 * $mydata = $report->get_data();
 * </code></pre>
 *
 * It uses the name provided in the class property or in the constructor call to load the `report-{name}` data store.
 *
 * It's used by the {@see GenericController GenericController}.
 *
 * @since 9.3.0
 */
class GenericQuery extends \WC_Object_Query {

	/**
	 * Specific query name.
	 * Will be used to load the `report-{name}` data store,
	 * and to call `woocommerce_analytics_{snake_case(name)}_*` filters.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Create a new query.
	 *
	 * @param array  $args Criteria to query on in a format similar to WP_Query.
	 * @param string $name Query name.
	 * @extends WC_Object_Query::_construct
	 */
	public function __construct( $args, $name = null ) {
		$this->name = $name ?? $this->name;

		return parent::__construct( $args ); // phpcs:ignore Universal.CodeAnalysis.ConstructorDestructorReturn.ReturnValueFound
	}
	/**
	 * Valid fields for Products report.
	 *
	 * @return array
	 */
	protected function get_default_query_vars() {
		return array();
	}

	/**
	 * Get data from `report-{$name}` store, based on the current query vars.
	 * Filters query vars through `woocommerce_analytics_{snake_case(name)}_query_args` filter.
	 * Filters results through `woocommerce_analytics_{snake_case(name)}_select_query` filter.
	 *
	 * @return mixed filtered results from the data store.
	 */
	public function get_data() {
		$snake_name = str_replace( '-', '_', $this->name );
		/**
		 * Filter query args given for the report.
		 *
		 * @since 9.3.0
		 *
		 * @param array $query_args Query args.
		 */
		$args = apply_filters( "woocommerce_analytics_{$snake_name}_query_args", $this->get_query_vars() );

		$data_store = \WC_Data_Store::load( "report-{$this->name}" );
		$results    = $data_store->get_data( $args );
		/**
		 * Filter report query results.
		 *
		 * @since 9.3.0
		 *
		 * @param stdClass|WP_Error $results Results from the data store.
		 * @param array             $args    Query args used to get the data (potentially filtered).
		 */
		return apply_filters( "woocommerce_analytics_{$snake_name}_select_query", $results, $args );
	}
}
