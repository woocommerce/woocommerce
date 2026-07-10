<?php
/**
 * Tests for Products List Tables in WooCommerce Admin.
 */

declare( strict_types = 1 );

require_once WC_ABSPATH . '/includes/admin/list-tables/class-wc-admin-list-table-products.php';

/**
 * WC Admin List Table Products test.
 */
class WC_Admin_List_Table_Products_Test extends WC_Unit_Test_Case {

	/**
	 * Original post type global.
	 *
	 * @var string|null
	 */
	private $previous_typenow;

	/**
	 * The System Under Test.
	 *
	 * @var WC_Admin_List_Table_Products|null
	 */
	private $sut;

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->previous_typenow = $GLOBALS['typenow'] ?? null;
		$GLOBALS['typenow']     = 'product'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$this->sut              = new WC_Admin_List_Table_Products();
	}

	/**
	 * Tear down the test.
	 */
	public function tearDown(): void {
		$this->remove_list_table_hooks();

		if ( null === $this->previous_typenow ) {
			unset( $GLOBALS['typenow'] );
		} else {
			$GLOBALS['typenow'] = $this->previous_typenow; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		parent::tearDown();
	}

	/**
	 * @testdox Product searches preserve WooCommerce search result ordering when no explicit sort is requested.
	 */
	public function test_product_search_preserves_search_result_order_without_explicit_sort(): void {
		$title_match = WC_Helper_Product::create_simple_product();
		$title_match->set_name( 'Codex Night Light 35607' );
		$title_match->save();
		wp_update_post(
			array(
				'ID'            => $title_match->get_id(),
				'post_date'     => '2022-01-01 00:00:00',
				'post_date_gmt' => '2022-01-01 00:00:00',
			)
		);

		$content_match = WC_Helper_Product::create_simple_product();
		$content_match->set_name( 'Codex Summer 35607' );
		$content_match->set_description( 'Codex Night Light 35607' );
		$content_match->save();
		wp_update_post(
			array(
				'ID'            => $content_match->get_id(),
				'post_date'     => '2023-01-01 00:00:00',
				'post_date_gmt' => '2023-01-01 00:00:00',
			)
		);

		$query_vars = $this->sut->request_query(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				's'              => 'Codex Night Light 35607',
			)
		);

		$query   = new WP_Query( $query_vars );
		$results = array_map( 'intval', $query->get_posts() );

		$this->assertSame(
			array( $title_match->get_id(), $content_match->get_id() ),
			$results,
			'Product list search should keep WooCommerce search result order instead of falling back to newest post date.'
		);
		$this->assertSame( 'post__in', $query_vars['orderby'], 'Search results should be ordered by the injected product IDs.' );
		$this->assertArrayNotHasKey( 's', $query_vars, 'The native WordPress search parameter should be replaced by WooCommerce product IDs.' );
		$this->assertTrue( $query_vars['product_search'], 'The product search marker should be set for the admin search label.' );
	}

	/**
	 * @testdox Product searches keep explicit admin sorting choices.
	 */
	public function test_product_search_keeps_explicit_orderby(): void {
		$query_vars = $this->sut->request_query(
			array(
				'post_type' => 'product',
				's'         => 'Codex 35607 no explicit fixtures required',
				'orderby'   => 'title',
				'order'     => 'ASC',
			)
		);

		$this->assertSame( 'title', $query_vars['orderby'], 'Explicit admin sorting should not be replaced by post__in ordering.' );
	}

	/**
	 * Remove hooks registered by the list table instance.
	 */
	private function remove_list_table_hooks(): void {
		if ( ! $this->sut ) {
			return;
		}

		remove_action( 'manage_posts_extra_tablenav', array( $this->sut, 'maybe_render_blank_state' ) );
		remove_filter( 'view_mode_post_types', array( $this->sut, 'disable_view_mode' ) );
		remove_action( 'restrict_manage_posts', array( $this->sut, 'restrict_manage_posts' ) );
		remove_filter( 'request', array( $this->sut, 'request_query' ) );
		remove_filter( 'post_row_actions', array( $this->sut, 'row_actions' ), 100 );
		remove_filter( 'default_hidden_columns', array( $this->sut, 'default_hidden_columns' ) );
		remove_filter( 'list_table_primary_column', array( $this->sut, 'list_table_primary_column' ) );
		remove_filter( 'manage_edit-product_sortable_columns', array( $this->sut, 'define_sortable_columns' ) );
		remove_filter( 'manage_product_posts_columns', array( $this->sut, 'define_columns' ) );
		remove_filter( 'bulk_actions-edit-product', array( $this->sut, 'define_bulk_actions' ) );
		remove_action( 'manage_product_posts_custom_column', array( $this->sut, 'render_columns' ) );
		remove_filter( 'handle_bulk_actions-edit-product', array( $this->sut, 'handle_bulk_actions' ) );
		remove_filter( 'disable_months_dropdown', '__return_true' );
		remove_filter( 'query_vars', array( $this->sut, 'add_custom_query_var' ) );
		remove_filter( 'views_edit-product', array( $this->sut, 'product_views' ) );
		remove_filter( 'get_search_query', array( $this->sut, 'search_label' ) );
		remove_filter( 'posts_clauses', array( $this->sut, 'posts_clauses' ) );
		remove_action( 'load-edit.php', array( $this->sut, 'prime_status_counts_cache' ) );
		remove_filter( 'the_posts', array( $this->sut, 'prime_thumbnail_caches' ) );

		$this->sut = null;
	}
}
