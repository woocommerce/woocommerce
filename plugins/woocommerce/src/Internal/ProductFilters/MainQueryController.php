<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFilters;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;

defined( 'ABSPATH' ) || exit;
/**
 * Hooks into WordPress filters to handle product filters for the main query.
 */
class MainQueryController implements RegisterHooksInterface {

	/**
	 * Instance of QueryClauses.
	 *
	 * @var QueryClauses
	 */
	private $query_clauses;

	/**
	 * Initialize dependencies.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 *
	 * @param QueryClauses $query_clauses Instance of QueryClauses.
	 *
	 * @return void
	 */
	final public function init( QueryClauses $query_clauses ): void {
		$this->query_clauses = $query_clauses;
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'posts_clauses', array( $this->query_clauses, 'add_query_clauses_for_main_query' ), 10, 2 );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
	}

	/**
	 * Register custom query vars for our filters. Price, stock status, and attribute query vars are
	 * already registered at WC_Query.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 *
	 * @param array $query_vars Query vars.
	 * @return array
	 */
	public function add_query_vars( $query_vars ) {
		return array_merge( $query_vars, $this->query_clauses->get_filter_url_params() );
	}
}
