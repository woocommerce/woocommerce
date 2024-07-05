<?php
namespace Automattic\WooCommerce\Admin\API\Reports;

defined( 'ABSPATH' ) || exit;

use WC_Data_Store;

/**
 * A generic class for a report-specific query to be used in Analytics.
 *
 * Example usage:
 * $args = array(
 *          'before'    => '2018-07-19 00:00:00',
 *          'after'     => '2018-07-05 00:00:00',
 *          'page'      => 2,
 *         );
 * $report = new GenericQuery( $args, 'coupons' );
 * $mydata = $report->get_data();
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
	 * @return array
	 */
	public function get_data() {
		$snake_name = str_replace( '-', '_', $this->name );
		$args = apply_filters( "woocommerce_analytics_{$snake_name}_query_args", $this->get_query_vars() );

		$data_store = \WC_Data_Store::load( "report-{$this->name}" );
		$results    = $data_store->get_data( $args );
		return apply_filters( "woocommerce_analytics_{$snake_name}_select_query", $results, $args );
	}
}
