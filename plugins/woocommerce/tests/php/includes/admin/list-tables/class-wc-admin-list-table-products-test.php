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
	 * The System Under Test.
	 *
	 * @var WC_Admin_List_Table_Products
	 */
	private $sut;

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::setUp();
		$GLOBALS['typenow'] = 'product'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$this->sut          = new WC_Admin_List_Table_Products();
	}

	/**
	 * @testdox Product searches prioritize title matches for supported search syntax.
	 * @dataProvider search_term_provider
	 *
	 * @param string $search_term Search term.
	 */
	public function test_product_search_prioritizes_title_matches( string $search_term ): void {
		list( $title_match, $content_match ) = $this->create_search_products();
		$results                             = $this->get_search_results( $search_term );

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
		list( $title_match, $content_match ) = $this->create_search_products();
		$results                             = $this->get_search_results(
			'Night Light 35607',
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
			'plain search'  => array( 'Night Light 35607' ),
			'quoted phrase' => array( '"Night Light 35607"' ),
			'OR groups'     => array( 'Night Light 35607 OR Missing Lantern 35607' ),
		);
	}

	/**
	 * Create title and content-only search matches.
	 *
	 * @return WC_Product[]
	 */
	private function create_search_products(): array {
		$title_match = WC_Helper_Product::create_simple_product();
		$title_match->set_name( 'Night Light 35607' );
		$title_match->save();

		$content_match = WC_Helper_Product::create_simple_product();
		$content_match->set_name( 'Archive Lamp Notes 35607' );
		$content_match->set_description( 'Night Light 35607' );
		$content_match->save();

		return array( $title_match, $content_match );
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
}
