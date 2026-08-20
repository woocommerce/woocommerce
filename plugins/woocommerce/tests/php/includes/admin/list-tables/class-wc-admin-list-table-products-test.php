<?php
/**
 * Unit tests for the products admin list table.
 *
 * @package WooCommerce\Tests\Admin
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Enums\ProductStockStatus;

require_once WC_ABSPATH . 'includes/admin/list-tables/class-wc-admin-list-table-products.php';

/**
 * WC_Admin_List_Table_Products tests.
 *
 * @package WooCommerce\Tests\Admin
 */
class WC_Admin_List_Table_Products_Test extends WC_Unit_Test_Case {

	/**
	 * Original global stock management option.
	 *
	 * @var string|false
	 */
	private $original_manage_stock;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->original_manage_stock = get_option( 'woocommerce_manage_stock' );
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		if ( false === $this->original_manage_stock ) {
			delete_option( 'woocommerce_manage_stock' );
		} else {
			update_option( 'woocommerce_manage_stock', $this->original_manage_stock );
		}

		parent::tearDown();
	}

	/**
	 * @testdox Products list out-of-stock filter includes variable products with out-of-stock variations.
	 */
	public function test_out_of_stock_filter_includes_variable_products_with_out_of_stock_variations() {
		update_option( 'woocommerce_manage_stock', 'no' );

		$simple_out_of_stock = WC_Helper_Product::create_simple_product();
		$simple_out_of_stock->set_manage_stock( false );
		$simple_out_of_stock->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$simple_out_of_stock->set_virtual( true );
		$simple_out_of_stock->save();

		$variable_with_out_of_stock_child = $this->create_variable_product_with_child_stock_statuses(
			array(
				ProductStockStatus::OUT_OF_STOCK,
				ProductStockStatus::OUT_OF_STOCK,
				ProductStockStatus::IN_STOCK,
			)
		);

		$variable_in_stock = $this->create_variable_product_with_child_stock_statuses(
			array(
				ProductStockStatus::IN_STOCK,
				ProductStockStatus::IN_STOCK,
			)
		);

		$variable_with_private_out_of_stock_child = $this->create_variable_product_with_child_stock_statuses(
			array(
				ProductStockStatus::OUT_OF_STOCK,
				ProductStockStatus::IN_STOCK,
			)
		);

		$private_variation = null;
		foreach ( $variable_with_private_out_of_stock_child->get_children() as $child_id ) {
			$child = wc_get_product( $child_id );
			if ( ProductStockStatus::OUT_OF_STOCK === $child->get_stock_status() ) {
				$private_variation = $child;
				break;
			}
		}
		$this->assertInstanceOf( WC_Product_Variation::class, $private_variation );
		$private_variation->set_status( 'private' );
		$private_variation->save();
		WC_Product_Variable::sync( $variable_with_private_out_of_stock_child );
		$variable_with_private_out_of_stock_child = wc_get_product( $variable_with_private_out_of_stock_child->get_id() );

		$this->assertSame( ProductStockStatus::IN_STOCK, $variable_with_out_of_stock_child->get_stock_status() );
		$this->assertSame( ProductStockStatus::IN_STOCK, $variable_with_private_out_of_stock_child->get_stock_status() );

		$fixture_ids = array(
			$simple_out_of_stock->get_id(),
			$variable_with_out_of_stock_child->get_id(),
			$variable_in_stock->get_id(),
			$variable_with_private_out_of_stock_child->get_id(),
		);

		$this->assertSame(
			array( $simple_out_of_stock->get_id(), $variable_with_out_of_stock_child->get_id() ),
			array_values( array_intersect( $this->query_product_ids_for_stock_status( ProductStockStatus::OUT_OF_STOCK ), $fixture_ids ) )
		);
		$this->assertSame(
			array( $variable_with_out_of_stock_child->get_id(), $variable_in_stock->get_id(), $variable_with_private_out_of_stock_child->get_id() ),
			array_values( array_intersect( $this->query_product_ids_for_stock_status( ProductStockStatus::IN_STOCK ), $fixture_ids ) )
		);
		$this->assertSame(
			array( $simple_out_of_stock->get_id() ),
			array_values( array_intersect( $this->query_product_ids_for_stock_status( ProductStockStatus::OUT_OF_STOCK, 'filter_virtual_post_clauses' ), $fixture_ids ) )
		);
	}

	/**
	 * Every stock status leaves the lookup table joined for later posts_clauses callbacks.
	 *
	 * The out-of-stock branch builds a self-contained subquery and does not read the alias itself, but
	 * callbacks registered after this one have always been able to rely on it being joined whenever a
	 * stock filter is active. Dropping it for one status only breaks those callbacks asymmetrically.
	 *
	 * @testdox Every stock status leaves the lookup table joined for later posts_clauses callbacks.
	 *
	 * @dataProvider stock_status_provider
	 *
	 * @param string $stock_status Stock status the products list is filtered by.
	 */
	public function test_stock_status_filter_always_joins_the_product_meta_lookup_table( string $stock_status ): void {
		global $wpdb;

		$args = $this->filter_clauses_for_stock_status( $stock_status );

		$this->assertStringContainsString(
			"LEFT JOIN {$wpdb->wc_product_meta_lookup} wc_product_meta_lookup",
			$args['join'],
			"The {$stock_status} filter must leave the wc_product_meta_lookup alias joined so later posts_clauses callbacks can reference it."
		);
	}

	/**
	 * A join an earlier callback already added is left alone.
	 *
	 * Joining unconditionally is only safe because of this: extensions that append their own join must
	 * not end up with a duplicate, which the database rejects outright.
	 *
	 * @testdox A lookup table join added by an earlier callback is not duplicated.
	 *
	 * @dataProvider stock_status_provider
	 *
	 * @param string $stock_status Stock status the products list is filtered by.
	 */
	public function test_stock_status_filter_does_not_duplicate_an_existing_lookup_join( string $stock_status ): void {
		global $wpdb;

		$existing_join = " LEFT JOIN {$wpdb->wc_product_meta_lookup} wc_product_meta_lookup ON {$wpdb->posts}.ID = wc_product_meta_lookup.product_id ";

		$args = $this->filter_clauses_for_stock_status(
			$stock_status,
			array(
				'join'  => $existing_join,
				'where' => '',
			)
		);

		$this->assertSame(
			1,
			substr_count( $args['join'], 'wc_product_meta_lookup ON' ),
			"The {$stock_status} filter must not join wc_product_meta_lookup a second time; a duplicate alias is a hard SQL error."
		);
	}

	/**
	 * An array-shaped stock_status request still selects the matching branch.
	 *
	 * @testdox An array-shaped stock_status request still uses the variation-aware out-of-stock branch.
	 */
	public function test_array_shaped_stock_status_uses_the_matching_branch(): void {
		$args = $this->filter_clauses_for_stock_status( array( ProductStockStatus::OUT_OF_STOCK ) );

		$this->assertStringContainsString(
			'stock_status_products',
			$args['where'],
			'An array-shaped out-of-stock request should take the same branch as the scalar form.'
		);
	}

	/**
	 * Values that normalise to nothing still join the lookup table.
	 *
	 * The filter is registered on a non-empty raw request value, so anything that survives that check
	 * but empties out during sanitisation still reaches the filter. Those requests have always been
	 * joined and have always matched no product; deciding the join after normalising would silently
	 * drop it for exactly them.
	 *
	 * @testdox A stock_status that normalises to nothing still joins the lookup table and matches no product.
	 *
	 * @dataProvider degenerate_stock_status_provider
	 *
	 * @param string|array $stock_status Value of the stock_status request parameter.
	 */
	public function test_degenerate_stock_status_still_joins_the_lookup_table( $stock_status ): void {
		global $wpdb;

		$args = $this->filter_clauses_for_stock_status( $stock_status );

		$this->assertStringContainsString(
			"LEFT JOIN {$wpdb->wc_product_meta_lookup} wc_product_meta_lookup",
			$args['join'],
			'A stock_status that sanitises away must still leave the lookup table joined for later callbacks.'
		);
		$this->assertStringContainsString(
			"wc_product_meta_lookup.stock_status=''",
			$args['where'],
			'A stock_status that sanitises away should keep matching no product rather than dropping the filter.'
		);
	}

	/**
	 * A multi-value stock_status honours the first value.
	 *
	 * @testdox A multi-value stock_status request filters on the first value.
	 */
	public function test_multi_value_stock_status_uses_the_first_value(): void {
		$args = $this->filter_clauses_for_stock_status(
			array( ProductStockStatus::OUT_OF_STOCK, ProductStockStatus::IN_STOCK )
		);

		$this->assertStringContainsString(
			'stock_status_products',
			$args['where'],
			'A multi-value request should filter on its first value rather than matching the whole catalogue.'
		);
	}

	/**
	 * Request values that survive the non-empty check but sanitise away.
	 *
	 * @return array<string, array<mixed>>
	 */
	public function degenerate_stock_status_provider(): array {
		return array(
			'whitespace only'       => array( ' ' ),
			'markup only'           => array( '<b>' ),
			'array with empty item' => array( array( '' ) ),
			'nested array'          => array( array( array( 'x' ) ) ),
		);
	}

	/**
	 * Clauses arriving without a usable join string are tolerated.
	 *
	 * @testdox Clauses arriving without a join string do not break the stock filter.
	 */
	public function test_stock_status_filter_tolerates_missing_join_clause(): void {
		global $wpdb;

		$args = $this->filter_clauses_for_stock_status( ProductStockStatus::OUT_OF_STOCK, array( 'where' => '' ) );

		$this->assertStringContainsString(
			"LEFT JOIN {$wpdb->wc_product_meta_lookup} wc_product_meta_lookup",
			$args['join'],
			'A callback that drops the join clause should not stop the filter from joining the lookup table.'
		);
	}

	/**
	 * Later callbacks can read the lookup alias without joining it themselves.
	 *
	 * This is the behaviour the join exists for. Asserting the join string alone cannot catch a change
	 * that leaves the alias unusable in the assembled query, so drive a real query through it.
	 *
	 * @testdox Out-of-stock filtering works for callbacks that read the lookup alias without joining it.
	 */
	public function test_out_of_stock_filter_supports_callbacks_relying_on_the_lookup_join(): void {
		update_option( 'woocommerce_manage_stock', 'no' );

		$out_of_stock = WC_Helper_Product::create_simple_product();
		$out_of_stock->set_manage_stock( false );
		$out_of_stock->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$out_of_stock->save();

		// Stands in for an extension: reads the alias, appends no join of its own.
		$consumer = static function ( $clauses ) {
			$clauses['where'] .= ' AND wc_product_meta_lookup.virtual = 0 ';
			return $clauses;
		};

		$ids = $this->query_product_ids_for_stock_status( ProductStockStatus::OUT_OF_STOCK, $consumer );

		$this->assertContains(
			$out_of_stock->get_id(),
			$ids,
			'A posts_clauses callback that references wc_product_meta_lookup without joining it should still produce a valid query.'
		);
	}

	/**
	 * Stock statuses offered by the products list filter.
	 *
	 * @return array<string, array<string>>
	 */
	public function stock_status_provider(): array {
		return array(
			'in stock'     => array( ProductStockStatus::IN_STOCK ),
			'out of stock' => array( ProductStockStatus::OUT_OF_STOCK ),
			'on backorder' => array( ProductStockStatus::ON_BACKORDER ),
		);
	}

	/**
	 * Run the stock status clause filter for a given request value.
	 *
	 * @param string|array $stock_status Value of the stock_status request parameter.
	 * @param array|null   $clauses      Clause array to filter; defaults to an empty join and where.
	 * @return array
	 */
	private function filter_clauses_for_stock_status( $stock_status, ?array $clauses = null ): array {
		$clauses = $clauses ?? array(
			'join'  => '',
			'where' => '',
		);

		$sut = ( new ReflectionClass( WC_Admin_List_Table_Products::class ) )->newInstanceWithoutConstructor();

		return $this->with_stock_status(
			$stock_status,
			function () use ( $sut, $clauses ) {
				return $sut->filter_stock_status_post_clauses( $clauses );
			}
		);
	}

	/**
	 * Run a callback with the stock_status request parameter set, restoring $_GET afterwards.
	 *
	 * @param string|array $stock_status Value of the stock_status request parameter.
	 * @param callable     $callback     Callback to run.
	 * @return mixed
	 */
	private function with_stock_status( $stock_status, callable $callback ) {
		$original_get = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Test cleanup restores the raw original request data.

		$_GET['stock_status'] = $stock_status;

		try {
			return $callback();
		} finally {
			$_GET = $original_get;
		}
	}

	/**
	 * Create a variable product with children that use the supplied stock statuses.
	 *
	 * @param array $stock_statuses Child variation stock statuses.
	 * @return WC_Product_Variable
	 */
	private function create_variable_product_with_child_stock_statuses( array $stock_statuses ) {
		$product = new WC_Product_Variable();
		$product->set_manage_stock( false );
		$product->save();

		foreach ( $stock_statuses as $index => $stock_status ) {
			$variation = new WC_Product_Variation();
			$variation->set_parent_id( $product->get_id() );
			$variation->set_status( 'publish' );
			$variation->set_regular_price( 10 + $index );
			$variation->set_manage_stock( false );
			$variation->set_stock_status( $stock_status );
			$variation->save();
		}

		$product = wc_get_product( $product->get_id() );
		WC_Product_Variable::sync( $product );

		return wc_get_product( $product->get_id() );
	}

	/**
	 * Query product IDs through the products list table stock-status filter.
	 *
	 * @param string               $stock_status      Stock status to query.
	 * @param string|callable|null $additional_filter Optional clause filter to register after the stock
	 *                                                filter: a method name on the list table, or any callable.
	 * @return array
	 */
	private function query_product_ids_for_stock_status( $stock_status, $additional_filter = null ) {
		$sut = ( new ReflectionClass( WC_Admin_List_Table_Products::class ) )->newInstanceWithoutConstructor();

		return $this->with_stock_status(
			$stock_status,
			function () use ( $sut, $additional_filter ) {
				$extra = is_string( $additional_filter ) ? array( $sut, $additional_filter ) : $additional_filter;

				add_filter( 'posts_clauses', array( $sut, 'filter_stock_status_post_clauses' ) );
				if ( $extra ) {
					add_filter( 'posts_clauses', $extra );
				}

				try {
					$query = new WP_Query(
						array(
							'fields'         => 'ids',
							'orderby'        => 'ID',
							'order'          => 'ASC',
							'post_status'    => 'publish',
							'post_type'      => 'product',
							'posts_per_page' => -1,
						)
					);

					return array_map( 'intval', $query->posts );
				} finally {
					remove_filter( 'posts_clauses', array( $sut, 'filter_stock_status_post_clauses' ) );
					if ( $extra ) {
						remove_filter( 'posts_clauses', $extra );
					}
				}
			}
		);
	}
}
