<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Reports;

use Automattic\WooCommerce\Internal\Admin\Reports\ProductSearchQuery;
use WC_Product_Simple;
use WC_Unit_Test_Case;

/**
 * Tests for the ProductSearchQuery class.
 */
class ProductSearchQueryTest extends WC_Unit_Test_Case {

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_filter( 'wc_product_sku_enabled', '__return_false' );
		parent::tearDown();
	}

	/**
	 * @testdox Should split a comma separated string into terms.
	 */
	public function test_parse_terms_splits_a_comma_separated_string(): void {
		$this->assertSame(
			array( 'widget', 'gadget' ),
			ProductSearchQuery::parse_terms( 'widget,gadget' )
		);
	}

	/**
	 * @testdox Should keep multi word terms intact instead of splitting them on whitespace.
	 */
	public function test_parse_terms_keeps_multi_word_terms_intact(): void {
		$this->assertSame(
			array( 'blue widget', 'red gadget' ),
			ProductSearchQuery::parse_terms( 'blue widget, red gadget' ),
			'Splitting on whitespace would turn one multi word search into several single word searches'
		);
	}

	/**
	 * @testdox Should accept a list of terms as well as a string.
	 */
	public function test_parse_terms_accepts_an_array(): void {
		$this->assertSame(
			array( 'blue widget', 'gadget' ),
			ProductSearchQuery::parse_terms( array( 'blue widget', 'gadget' ) )
		);
	}

	/**
	 * @testdox Should drop terms that are empty or whitespace only.
	 */
	public function test_parse_terms_drops_empty_terms(): void {
		$this->assertSame(
			array( 'widget', 'gadget' ),
			ProductSearchQuery::parse_terms( 'widget,,   ,gadget,' )
		);
	}

	/**
	 * @testdox Should sanitize each term.
	 */
	public function test_parse_terms_sanitizes_each_term(): void {
		$this->assertSame(
			array( 'widget' ),
			ProductSearchQuery::parse_terms( '<b>widget</b>' )
		);
	}

	/**
	 * @testdox Should return an empty list when there is nothing to search for.
	 *
	 * @dataProvider empty_search_value_provider
	 *
	 * @param string|string[] $value Raw argument value.
	 */
	public function test_parse_terms_returns_an_empty_list_for_empty_values( $value ): void {
		$this->assertSame( array(), ProductSearchQuery::parse_terms( $value ) );
	}

	/**
	 * Data provider for the empty search value tests.
	 *
	 * @return array[]
	 */
	public function empty_search_value_provider(): array {
		return array(
			'empty string'    => array( '' ),
			'whitespace only' => array( '   ' ),
			'commas only'     => array( ',,,' ),
			'empty array'     => array( array() ),
			'array of blanks' => array( array( '', ' ' ) ),
		);
	}

	/**
	 * @testdox Should trim each term.
	 */
	public function test_parse_terms_trims_each_term(): void {
		$this->assertSame(
			array( 'widget', 'blue gadget' ),
			ProductSearchQuery::parse_terms( '  widget  ,  blue   gadget  ' ),
			'sanitize_text_field() trims and collapses internal whitespace runs'
		);
	}

	/**
	 * @testdox Should return an empty statement when there is nothing to search for.
	 */
	public function test_get_ids_subquery_returns_an_empty_statement_when_there_is_nothing_to_search_for(): void {
		$this->assertSame( '', ProductSearchQuery::get_ids_subquery( array() ) );
		$this->assertSame( '', ProductSearchQuery::get_ids_subquery( '' ) );
		$this->assertSame( '', ProductSearchQuery::get_ids_subquery( '  ,  ' ) );
	}

	/**
	 * @testdox Should match products whose title contains the term.
	 */
	public function test_get_ids_subquery_matches_products_by_partial_title(): void {
		$match    = $this->create_product( 'Kingston Widget' );
		$no_match = $this->create_product( 'Unrelated Gadget' );

		$found = $this->run_subquery( ProductSearchQuery::get_ids_subquery( array( 'kingston' ) ) );

		$this->assertContains( $match, $found, 'A partial, case insensitive title match should be returned' );
		$this->assertNotContains( $no_match, $found );
	}

	/**
	 * @testdox Should match products by SKU.
	 */
	public function test_get_ids_subquery_matches_products_by_sku(): void {
		$match    = $this->create_product( 'Unrelated Gadget', 'KINGSTON-1' );
		$no_match = $this->create_product( 'Another Gadget', 'OTHER-1' );

		$found = $this->run_subquery( ProductSearchQuery::get_ids_subquery( array( 'KINGSTON-1' ) ) );

		$this->assertContains( $match, $found );
		$this->assertNotContains( $no_match, $found );
	}

	/**
	 * @testdox Should not match products by SKU when SKUs are disabled.
	 */
	public function test_get_ids_subquery_ignores_skus_when_they_are_disabled(): void {
		$product = $this->create_product( 'Unrelated Gadget', 'KINGSTON-1' );

		add_filter( 'wc_product_sku_enabled', '__return_false' );

		$found = $this->run_subquery( ProductSearchQuery::get_ids_subquery( array( 'KINGSTON-1' ) ) );

		$this->assertNotContains( $product, $found );
	}

	/**
	 * @testdox Should match a product that matches any of the given terms.
	 */
	public function test_get_ids_subquery_matches_any_of_the_given_terms(): void {
		$first    = $this->create_product( 'Kingston Widget' );
		$second   = $this->create_product( 'Brighton Gadget' );
		$no_match = $this->create_product( 'Unrelated Thing' );

		$found = $this->run_subquery( ProductSearchQuery::get_ids_subquery( array( 'Kingston', 'Brighton' ) ) );

		$this->assertContains( $first, $found );
		$this->assertContains( $second, $found );
		$this->assertNotContains( $no_match, $found );
	}

	/**
	 * @testdox Should accept a comma separated string of terms.
	 */
	public function test_get_ids_subquery_accepts_a_comma_separated_string(): void {
		$first  = $this->create_product( 'Kingston Widget' );
		$second = $this->create_product( 'Brighton Gadget' );

		$found = $this->run_subquery( ProductSearchQuery::get_ids_subquery( 'Kingston,Brighton' ) );

		$this->assertContains( $first, $found );
		$this->assertContains( $second, $found );
	}

	/**
	 * @testdox Should intersect the matches with the given product IDs.
	 */
	public function test_get_ids_subquery_intersects_with_the_given_product_ids(): void {
		$kept    = $this->create_product( 'Kingston Widget' );
		$dropped = $this->create_product( 'Kingston Gadget' );

		$found = $this->run_subquery( ProductSearchQuery::get_ids_subquery( array( 'Kingston' ), array( $kept ) ) );

		$this->assertSame( array( $kept ), $found );
	}

	/**
	 * @testdox Should match nothing when the given product IDs exclude every match.
	 */
	public function test_get_ids_subquery_matches_nothing_when_restricted_to_unrelated_ids(): void {
		$this->create_product( 'Kingston Widget' );
		$unrelated = $this->create_product( 'Unrelated Thing' );

		$found = $this->run_subquery( ProductSearchQuery::get_ids_subquery( array( 'Kingston' ), array( $unrelated ) ) );

		$this->assertSame( array(), $found );
	}

	/**
	 * @testdox Should match nothing when every given product ID is unusable.
	 */
	public function test_get_ids_subquery_matches_nothing_when_restricted_to_unusable_ids(): void {
		$this->create_product( 'Kingston Widget' );

		$found = $this->run_subquery( ProductSearchQuery::get_ids_subquery( array( 'Kingston' ), array( 0 ) ) );

		$this->assertSame( array(), $found );
	}

	/**
	 * @testdox Should not read a negative product ID as the product with the matching positive ID.
	 *
	 * WP_Query runs `post__in` through `absint()`, which turns the report filters' `-1` into 1.
	 */
	public function test_get_ids_subquery_does_not_flip_negative_product_ids(): void {
		$product = $this->create_product( 'Kingston Widget' );

		$found = $this->run_subquery( ProductSearchQuery::get_ids_subquery( array( 'Kingston' ), array( -$product ) ) );

		$this->assertSame( array(), $found );
	}

	/**
	 * @testdox Should not match products in a status that is hidden from search.
	 */
	public function test_get_ids_subquery_excludes_products_hidden_from_search(): void {
		$published = $this->create_product( 'Kingston Widget' );

		$trashed = $this->create_product( 'Kingston Trashed' );
		wp_trash_post( $trashed );

		$found = $this->run_subquery( ProductSearchQuery::get_ids_subquery( array( 'Kingston' ) ) );

		$this->assertContains( $published, $found );
		$this->assertNotContains( $trashed, $found );
	}

	/**
	 * @testdox Should match the same unpublished products the search box returns.
	 */
	public function test_get_ids_subquery_matches_unpublished_products(): void {
		$draft   = $this->create_product( 'Kingston Draft', '', 'draft' );
		$private = $this->create_product( 'Kingston Private', '', 'private' );

		$found = $this->run_subquery( ProductSearchQuery::get_ids_subquery( array( 'Kingston' ) ) );

		// Matches the search box, and a drafted product can still have sales worth reporting.
		$this->assertContains( $draft, $found );
		$this->assertContains( $private, $found );
	}

	/**
	 * @testdox Should not match other post types.
	 */
	public function test_get_ids_subquery_does_not_match_other_post_types(): void {
		$product = $this->create_product( 'Kingston Widget' );
		$post    = self::factory()->post->create( array( 'post_title' => 'Kingston Widget' ) );

		$found = $this->run_subquery( ProductSearchQuery::get_ids_subquery( array( 'Kingston' ) ) );

		$this->assertContains( $product, $found );
		$this->assertNotContains( $post, $found );
	}

	/**
	 * @testdox Should treat LIKE wildcards in the search term as literal characters when matching titles.
	 *
	 * @dataProvider like_wildcard_provider
	 *
	 * @param string $term          Search term containing a LIKE wildcard.
	 * @param string $matching_name Title of the product the term should match.
	 * @param string $other_name    Title of the product the term should not match.
	 */
	public function test_get_ids_subquery_escapes_like_wildcards( string $term, string $matching_name, string $other_name ): void {
		$match    = $this->create_product( $matching_name );
		$no_match = $this->create_product( $other_name );

		$found = $this->run_subquery( ProductSearchQuery::get_ids_subquery( array( $term ) ) );

		$this->assertContains( $match, $found );
		$this->assertNotContains( $no_match, $found, "\"{$term}\" should not be treated as a wildcard pattern" );
	}

	/**
	 * Data provider for the LIKE wildcard escaping test.
	 *
	 * @return array[]
	 */
	public function like_wildcard_provider(): array {
		return array(
			'percent sign' => array( '100%', '100% Cotton Shirt', '1000 Cotton Shirts' ),
			'underscore'   => array( 'a_b', 'Model a_b', 'Model axb' ),
		);
	}

	/**
	 * @testdox Should compare the SKU against the raw term, LIKE wildcards included.
	 *
	 * Admin\API\Products leaves the term unescaped, so a wildcard in it is a pattern rather than a
	 * literal. Escaping it here would make the report disagree with the search box.
	 */
	public function test_get_ids_subquery_does_not_escape_wildcards_in_the_sku_clause(): void {
		$match    = $this->create_product( 'Unrelated Gadget', 'A-100' );
		$no_match = $this->create_product( 'Another Gadget', 'B-200' );

		$found = $this->run_subquery( ProductSearchQuery::get_ids_subquery( array( 'A-1%' ) ) );

		$this->assertContains( $match, $found, 'The SKU clause should treat the term as a LIKE pattern, as the search box does' );
		$this->assertNotContains( $no_match, $found );
	}

	/**
	 * @testdox Should honour a posts_where filter, the way the search box does.
	 *
	 * Multilingual plugins restrict products to the active language through the WP_Query clause
	 * filters, and so does the search box the report has to agree with.
	 */
	public function test_get_ids_subquery_honours_a_posts_where_filter(): void {
		$kept    = $this->create_product( 'Kingston Widget' );
		$hidden  = $this->create_product( 'Kingston Gadget' );
		$exclude = function ( $where ) use ( $hidden ) {
			global $wpdb;

			return $where . " AND {$wpdb->posts}.ID != {$hidden} ";
		};

		add_filter( 'posts_where', $exclude );
		$found = $this->run_subquery( ProductSearchQuery::get_ids_subquery( array( 'Kingston' ) ) );
		remove_filter( 'posts_where', $exclude );

		$this->assertContains( $kept, $found );
		$this->assertNotContains( $hidden, $found );
	}

	/**
	 * @testdox Should honour a posts_join filter, the way the search box does.
	 */
	public function test_get_ids_subquery_honours_a_posts_join_filter(): void {
		$translated = $this->create_product( 'Kingston Widget' );
		update_post_meta( $translated, '_test_language', 'fr' );

		// Not translated, so the join drops it.
		$this->create_product( 'Kingston Gadget' );

		$join = function ( $clause ) {
			global $wpdb;

			return $clause . " INNER JOIN {$wpdb->postmeta} AS language_meta ON {$wpdb->posts}.ID = language_meta.post_id AND language_meta.meta_key = '_test_language' ";
		};

		add_filter( 'posts_join', $join );
		$found = $this->run_subquery( ProductSearchQuery::get_ids_subquery( array( 'Kingston' ) ) );
		remove_filter( 'posts_join', $join );

		$this->assertSame( array( $translated ), $found );
	}

	/**
	 * @testdox Should honour a pre_get_posts restriction on the products it searches.
	 *
	 * A multivendor plugin scopes products to the vendor that authored them. The search resolves
	 * to a report restriction rather than a listing, so a product left out here is one the report
	 * cannot reach either.
	 */
	public function test_get_ids_subquery_honours_a_pre_get_posts_author_restriction(): void {
		$vendor = self::factory()->user->create( array( 'role' => 'editor' ) );
		$own    = $this->create_product( 'Kingston Widget' );

		// Authored by someone else, so the scope drops it.
		$this->create_product( 'Kingston Gadget' );

		wp_update_post(
			array(
				'ID'          => $own,
				'post_author' => $vendor,
			)
		);

		$scope = function ( $query ) use ( $vendor ) {
			$query->set( 'author', $vendor );
		};

		add_action( 'pre_get_posts', $scope );
		$found = $this->run_subquery( ProductSearchQuery::get_ids_subquery( array( 'Kingston' ) ) );
		remove_action( 'pre_get_posts', $scope );

		$this->assertSame( array( $own ), $found );
	}

	/**
	 * @testdox Should apply a pre_get_posts restriction on top of the report's own product filter.
	 */
	public function test_get_ids_subquery_keeps_the_report_filter_alongside_a_pre_get_posts_restriction(): void {
		$vendor       = self::factory()->user->create( array( 'role' => 'editor' ) );
		$in_both      = $this->create_product( 'Kingston Widget' );
		$vendors_only = $this->create_product( 'Kingston Gadget' );
		$report_only  = $this->create_product( 'Kingston Sprocket' );

		foreach ( array( $in_both, $vendors_only ) as $product ) {
			wp_update_post(
				array(
					'ID'          => $product,
					'post_author' => $vendor,
				)
			);
		}

		$scope = function ( $query ) use ( $vendor ) {
			$query->set( 'author', $vendor );
		};

		add_action( 'pre_get_posts', $scope );
		$found = $this->run_subquery(
			ProductSearchQuery::get_ids_subquery( array( 'Kingston' ), array( $in_both, $report_only ) )
		);
		remove_action( 'pre_get_posts', $scope );

		$this->assertSame( array( $in_both ), $found, 'Only the product both the scope and the report filter allow should match' );
	}

	/**
	 * @testdox Should leave queries other than its own alone.
	 */
	public function test_get_ids_subquery_does_not_affect_later_queries(): void {
		$product = $this->create_product( 'Kingston Widget' );
		$other   = $this->create_product( 'Unrelated Thing' );

		ProductSearchQuery::get_ids_subquery( array( 'Kingston' ) );

		$query = new \WP_Query(
			array(
				'post_type'      => 'product',
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
			)
		);

		$this->assertContains( $product, $query->posts );
		$this->assertContains( $other, $query->posts, 'The search filters should no longer be attached' );
	}

	/**
	 * @testdox Should let a query run from one of its own filters return its results.
	 *
	 * A plugin can run a query while working out how to filter this one, and short circuiting
	 * every query for the duration would hand it an empty result to decide on.
	 */
	public function test_get_ids_subquery_does_not_short_circuit_a_query_run_from_a_filter(): void {
		$product = $this->create_product( 'Kingston Widget' );
		$nested  = null;
		$running = false;

		$run_nested_query = function ( $where ) use ( &$nested, &$running ) {
			if ( $running ) {
				return $where;
			}

			$running = true;
			$nested  = ( new \WP_Query(
				array(
					'post_type'      => 'product',
					'post_status'    => 'any',
					'fields'         => 'ids',
					'posts_per_page' => -1,
					'no_found_rows'  => true,
				)
			) )->posts;
			$running = false;

			return $where;
		};

		add_filter( 'posts_where', $run_nested_query );
		ProductSearchQuery::get_ids_subquery( array( 'Kingston' ) );
		remove_filter( 'posts_where', $run_nested_query );

		$this->assertContains( $product, (array) $nested, 'A query run from a filter should still return its own rows' );
	}

	/**
	 * @testdox Should detach its filters even when the query throws.
	 */
	public function test_get_ids_subquery_detaches_its_filters_when_the_query_throws(): void {
		$product = $this->create_product( 'Kingston Widget' );
		$boom    = function () {
			throw new \RuntimeException( 'Thrown from pre_get_posts' );
		};

		add_action( 'pre_get_posts', $boom );
		try {
			ProductSearchQuery::get_ids_subquery( array( 'Kingston' ) );
			$this->fail( 'The exception should have propagated' );
		} catch ( \RuntimeException $e ) {
			$this->assertSame( 'Thrown from pre_get_posts', $e->getMessage() );
		} finally {
			remove_action( 'pre_get_posts', $boom );
		}

		$query = new \WP_Query(
			array(
				'post_type'      => 'product',
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
			)
		);

		$this->assertContains( $product, $query->posts, 'A left behind filter would empty every later query' );
	}

	/**
	 * @testdox Should return null when there is nothing to search for, and the matches otherwise.
	 */
	public function test_get_ids_tells_an_absent_search_apart_from_one_that_matched_nothing(): void {
		$product = $this->create_product( 'Kingston Widget' );

		$this->assertNull( ProductSearchQuery::get_ids( array() ), 'No search is not the same as a search that matched nothing' );
		$this->assertSame( array(), ProductSearchQuery::get_ids( array( 'nothing matches this' ) ) );
		$this->assertSame( array( $product ), ProductSearchQuery::get_ids( array( 'Kingston' ) ) );
	}

	/**
	 * @testdox Should intersect the matches with the given product IDs.
	 */
	public function test_get_ids_intersects_with_the_given_product_ids(): void {
		$kept = $this->create_product( 'Kingston Widget' );
		$this->create_product( 'Kingston Gadget' );

		$this->assertSame( array( $kept ), ProductSearchQuery::get_ids( array( 'Kingston' ), array( $kept ) ) );
		$this->assertSame( array(), ProductSearchQuery::get_ids( array( 'Kingston' ), array( '-1' ) ) );
	}

	/**
	 * Runs a statement built by the system under test and returns the product IDs it yields.
	 *
	 * @param string $sql SQL statement.
	 * @return int[]
	 */
	private function run_subquery( string $sql ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Running the statement produced by the system under test.
		$ids = $wpdb->get_col( $sql );

		$ids = array_map( 'intval', $ids );
		sort( $ids );

		return $ids;
	}

	/**
	 * Creates a simple product.
	 *
	 * @param string $name   Product name.
	 * @param string $sku    Optional. Product SKU.
	 * @param string $status Optional. Product status.
	 * @return int Product ID.
	 */
	private function create_product( string $name, string $sku = '', string $status = 'publish' ): int {
		$product = new WC_Product_Simple();
		$product->set_name( $name );
		$product->set_regular_price( '10' );
		$product->set_status( $status );

		if ( '' !== $sku ) {
			$product->set_sku( $sku );
		}

		return $product->save();
	}
}
