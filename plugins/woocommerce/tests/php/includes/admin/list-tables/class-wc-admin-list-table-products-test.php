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
	 * The system under test.
	 *
	 * @var WC_Admin_List_Table_Products
	 */
	private $sut;

	/**
	 * Original global stock management option.
	 *
	 * @var string|false
	 */
	private $original_manage_stock;

	/**
	 * Original post type for the current admin screen.
	 *
	 * @var string|null
	 */
	private $original_typenow;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->original_manage_stock = get_option( 'woocommerce_manage_stock' );
		$this->original_typenow      = $GLOBALS['typenow'] ?? null;
		$GLOBALS['typenow']          = 'product'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$this->sut                   = new WC_Admin_List_Table_Products();
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

		if ( null === $this->original_typenow ) {
			unset( $GLOBALS['typenow'] );
		} else {
			$GLOBALS['typenow'] = $this->original_typenow; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		parent::tearDown();
	}

	/**
	 * @testdox Product searches prioritize title matches for supported search syntax.
	 * @dataProvider search_term_provider
	 *
	 * @param string $search_format Search term format.
	 */
	public function test_product_search_prioritizes_title_matches( string $search_format ): void {
		list( $title_match, $content_match, $search_phrase ) = $this->create_search_products();
		$results = $this->get_search_results( sprintf( $search_format, $search_phrase ) );

		$this->assertSame(
			array( $title_match->get_id(), $content_match->get_id() ),
			$results,
			'Products with a title match should be listed before products that only match in their content.'
		);
	}

	/**
	 * @testdox Product searches keep explicit admin sorting choices.
	 */
	public function test_product_search_keeps_explicit_orderby(): void {
		list( $title_match, $content_match, $search_phrase ) = $this->create_search_products();
		$results = $this->get_search_results(
			$search_phrase,
			array(
				'orderby' => 'title',
				'order'   => 'ASC',
			)
		);

		$this->assertSame(
			array( $content_match->get_id(), $title_match->get_id() ),
			$results,
			'Explicit title sorting should take precedence over search relevance.'
		);
	}

	/**
	 * Search terms for title-priority coverage.
	 *
	 * @return array<string, array<string>>
	 */
	public function search_term_provider(): array {
		return array(
			'plain search'  => array( '%s' ),
			'quoted phrase' => array( '"%s"' ),
			'OR groups'     => array( '%s OR Missing Lantern' ),
		);
	}

	/**
	 * Products list out-of-stock filter includes variable products with out-of-stock variations.
	 *
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
	 * Create title and content-only search matches.
	 *
	 * @return array{WC_Product, WC_Product, string}
	 */
	private function create_search_products(): array {
		$search_phrase = 'Night Light ' . wp_generate_password( 8, false );

		$title_match = WC_Helper_Product::create_simple_product();
		$title_match->set_name( $search_phrase );
		$title_match->save();

		$content_match = WC_Helper_Product::create_simple_product();
		$content_match->set_name( 'Archive Lamp Notes ' . wp_generate_password( 8, false ) );
		$content_match->set_description( $search_phrase );
		$content_match->save();

		return array( $title_match, $content_match, $search_phrase );
	}

	/**
	 * Run a Products list search.
	 *
	 * @param string $search_term Search term.
	 * @param array  $query_args  Additional query arguments.
	 * @return int[]
	 */
	private function get_search_results( string $search_term, array $query_args = array() ): array {
		$query_vars = $this->sut->request_query(
			array_merge(
				array(
					'post_type'      => 'product',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					's'              => $search_term,
				),
				$query_args
			)
		);

		$query = new WP_Query( $query_vars );
		return array_map( 'intval', $query->get_posts() );
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
	 * @param string      $stock_status      Stock status to query.
	 * @param string|null $additional_filter Optional clause filter to register after the stock filter.
	 * @return array
	 */
	private function query_product_ids_for_stock_status( $stock_status, $additional_filter = null ) {
		$sut          = ( new ReflectionClass( WC_Admin_List_Table_Products::class ) )->newInstanceWithoutConstructor();
		$original_get = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Test cleanup restores the raw original request data.

		$_GET['stock_status'] = $stock_status;
		add_filter( 'posts_clauses', array( $sut, 'filter_stock_status_post_clauses' ) );
		if ( $additional_filter ) {
			add_filter( 'posts_clauses', array( $sut, $additional_filter ) );
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
			if ( $additional_filter ) {
				remove_filter( 'posts_clauses', array( $sut, $additional_filter ) );
			}
			$_GET = $original_get;
		}
	}
}
