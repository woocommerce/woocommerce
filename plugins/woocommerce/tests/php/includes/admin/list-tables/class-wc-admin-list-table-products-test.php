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
	 * @testdox Product searches prioritize titles that match parsed search terms.
	 * @dataProvider parsed_search_term_provider
	 *
	 * @param string $title_format  Product title format.
	 * @param string $search_format Search term format.
	 */
	public function test_product_search_prioritizes_titles_matching_parsed_terms( string $title_format, string $search_format ): void {
		$token        = wp_generate_password( 8, false );
		$product_name = sprintf( $title_format, $token );
		$search_term  = sprintf( $search_format, $token );

		$title_match = WC_Helper_Product::create_simple_product();
		$title_match->set_name( $product_name );
		$title_match->set_date_created( '2024-01-01 00:00:00' );
		$title_match->save();

		$content_match = WC_Helper_Product::create_simple_product();
		$content_match->set_name( 'Archive Lamp Notes ' . wp_generate_password( 8, false ) );
		$content_match->set_description( $product_name );
		$content_match->set_date_created( '2024-01-02 00:00:00' );
		$content_match->save();

		$results = $this->get_search_results( $search_term );

		$this->assertSame(
			array( $title_match->get_id(), $content_match->get_id() ),
			$results,
			'Products whose titles match every parsed search term should be listed before content-only matches.'
		);
	}

	/**
	 * Parsed search terms for title-priority coverage.
	 *
	 * @return array<string, array<string>>
	 */
	public function parsed_search_term_provider(): array {
		return array(
			'multiple quoted phrases' => array( 'Night Light %1$s Warm Glow', '"Night Light %1$s" "Warm Glow"' ),
			'single-quoted terms'     => array( 'Night Light %1$s', "'Night Light %1\$s'" ),
			'comma-separated terms'   => array( 'Night Light %1$s', 'Night,Light,%1$s' ),
			'plus-separated terms'    => array( 'Night Light %1$s', 'Night+Light+%1$s' ),
			'ignored stopword'        => array( 'Night Light %1$s', 'Night the Light %1$s' ),
			'title punctuation'       => array( 'Night-Light %1$s', 'Night Light %1$s' ),
		);
	}

	/**
	 * @testdox Product title ranking prepends a relevance clause only for default search ordering.
	 */
	public function test_product_search_ranking_controls_the_orderby_clause(): void {
		list( , , $search_phrase ) = $this->create_search_products();

		$captured_orderby = null;
		$capture_orderby  = static function ( $clauses ) use ( &$captured_orderby ) {
			$captured_orderby = is_array( $clauses ) && isset( $clauses['orderby'] ) ? $clauses['orderby'] : null;
			return $clauses;
		};
		add_filter( 'posts_clauses', $capture_orderby, PHP_INT_MAX );

		try {
			$this->get_search_results( $search_phrase );
			$this->assertStringContainsString( 'CASE WHEN', (string) $captured_orderby, 'A default-ordered search should rank title matches first.' );
			$this->assertStringContainsString( 'post_title LIKE', (string) $captured_orderby, 'The relevance clause should match against product titles.' );

			$captured_orderby = null;
			$this->get_search_results(
				$search_phrase,
				array(
					'orderby' => 'title',
					'order'   => 'ASC',
				)
			);
			$this->assertStringNotContainsString( 'CASE WHEN', (string) $captured_orderby, 'An explicitly sorted search should keep the requested ordering untouched.' );

			// A stopword-only term falls back to ranking by the whole group, mirroring search_products().
			// \S* absorbs the LIKE wildcards around the term: at the posts_clauses stage they are still
			// dynamic wpdb placeholder-escape hashes, not %.
			$captured_orderby = null;
			$this->get_search_results( 'the' );
			$this->assertMatchesRegularExpression( "/CASE WHEN.+post_title LIKE '\\S*the\\S*'/", (string) $captured_orderby, 'A stopword-only search should rank by the raw search group.' );
		} finally {
			remove_filter( 'posts_clauses', $capture_orderby, PHP_INT_MAX );
		}
	}

	/**
	 * @testdox Product title ranking uses the same translated stopwords as product inclusion.
	 */
	public function test_product_search_uses_woocommerce_stopwords_for_title_ranking(): void {
		$token       = wp_generate_password( 8, false );
		$search_term = 'Night the ' . $token;

		$translate_stopwords = static function ( $translation, $text, $context, $domain ) {
			$source = 'about,an,are,as,at,be,by,com,for,from,how,in,is,it,of,on,or,that,the,this,to,was,what,when,where,who,will,with,www';
			if ( $source !== $text || 'Comma-separated list of search stopwords in your language' !== $context ) {
				return $translation;
			}

			return 'woocommerce' === $domain ? 'the' : 'about';
		};
		add_filter( 'gettext_with_context', $translate_stopwords, 10, 4 );

		try {
			$title_match = WC_Helper_Product::create_simple_product();
			$title_match->set_name( 'Night ' . $token );
			$title_match->set_date_created( '2024-01-01 00:00:00' );
			$title_match->save();

			$content_match = WC_Helper_Product::create_simple_product();
			$content_match->set_name( 'Archive translated notes' );
			$content_match->set_description( $search_term );
			$content_match->set_date_created( '2024-01-02 00:00:00' );
			$content_match->save();

			$results = $this->get_search_results( $search_term );
		} finally {
			remove_filter( 'gettext_with_context', $translate_stopwords, 10 );
		}

		$this->assertSame(
			array( $title_match->get_id(), $content_match->get_id() ),
			$results,
			'Title ranking should discard the same translated stopwords as the broad product search.'
		);
	}

	/**
	 * @testdox Product searches retain broad content, SKU, GTIN, and variation matches after title matches.
	 */
	public function test_product_search_retains_broad_matches_after_title_matches(): void {
		$search_term = (string) wp_rand( 10000000, 99999999 );

		$title_match = WC_Helper_Product::create_simple_product();
		$title_match->set_name( 'Night Light ' . $search_term );
		$title_match->save();

		$content_match = WC_Helper_Product::create_simple_product();
		$content_match->set_name( 'Archive Content Match' );
		$content_match->set_description( $search_term );
		$content_match->save();

		$gtin_match = WC_Helper_Product::create_simple_product();
		$gtin_match->set_name( 'Catalog GTIN Match' );
		$gtin_match->set_global_unique_id( '111' . $search_term . '11' );
		$gtin_match->save();

		$sku_match = WC_Helper_Product::create_simple_product();
		$sku_match->set_name( 'Catalog SKU Match' );
		$sku_match->set_sku( 'sku-' . $search_term );
		$sku_match->save();

		$variable_match = new WC_Product_Variable();
		$variable_match->set_name( 'Variation Match' );
		$variable_match->save();

		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $variable_match->get_id() );
		$variation->set_regular_price( '10' );
		$variation->set_sku( 'variation-' . $search_term );
		$variation->save();

		$results = $this->get_search_results( $search_term );

		$this->assertSame( $title_match->get_id(), $results[0], 'The title match should be ranked first.' );

		$expected_broad_matches = array(
			$content_match->get_id(),
			$gtin_match->get_id(),
			$sku_match->get_id(),
			$variable_match->get_id(),
		);
		$actual_broad_matches   = array_slice( $results, 1 );
		sort( $expected_broad_matches );
		sort( $actual_broad_matches );

		$this->assertSame( $expected_broad_matches, $actual_broad_matches, 'Broad product matches should remain available after title-priority ranking.' );
	}

	/**
	 * @testdox Product searches preserve default date ordering within relevance groups.
	 */
	public function test_product_search_preserves_date_order_within_relevance_groups(): void {
		$search_term = 'Lantern ' . wp_generate_password( 8, false );

		$title_older = WC_Helper_Product::create_simple_product();
		$title_older->set_name( 'Alpha ' . $search_term );
		$title_older->set_date_created( '2024-01-01 00:00:00' );
		$title_older->save();

		$title_newer = WC_Helper_Product::create_simple_product();
		$title_newer->set_name( 'Zulu ' . $search_term );
		$title_newer->set_date_created( '2024-01-04 00:00:00' );
		$title_newer->save();

		$content_older = WC_Helper_Product::create_simple_product();
		$content_older->set_name( 'Bravo catalog notes' );
		$content_older->set_description( $search_term );
		$content_older->set_date_created( '2024-01-02 00:00:00' );
		$content_older->save();

		$content_newer = WC_Helper_Product::create_simple_product();
		$content_newer->set_name( 'Yankee catalog notes' );
		$content_newer->set_description( $search_term );
		$content_newer->set_date_created( '2024-01-03 00:00:00' );
		$content_newer->save();

		$this->assertSame(
			array(
				$title_newer->get_id(),
				$title_older->get_id(),
				$content_newer->get_id(),
				$content_older->get_id(),
			),
			$this->get_search_results( $search_term ),
			'Title relevance should add buckets without replacing the existing date order inside each bucket.'
		);
	}

	/**
	 * @testdox Product searches distinguish core status-view ordering from explicit modified-date sorting.
	 * @dataProvider core_status_ordering_provider
	 *
	 * @param string $post_status     Product status.
	 * @param string $order           Status-view order.
	 * @param bool   $explicit_order  Whether the order came from an explicit request.
	 */
	public function test_product_search_handles_status_view_ordering( string $post_status, string $order, bool $explicit_order ): void {
		$search_term = 'Lantern ' . wp_generate_password( 8, false );

		$create_product = static function ( string $name, string $description, string $modified ) use ( $post_status ): WC_Product {
			global $wpdb;

			$product = WC_Helper_Product::create_simple_product();
			$product->set_name( $name );
			$product->set_description( $description );
			$product->set_status( $post_status );
			$product->save();

			// wp_insert_post() overwrites modified timestamps with the current time.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$wpdb->posts,
				array(
					'post_modified'     => $modified,
					'post_modified_gmt' => $modified,
				),
				array( 'ID' => $product->get_id() )
			);
			clean_post_cache( $product->get_id() );

			return $product;
		};

		$title_older   = $create_product( 'Alpha ' . $search_term, '', '2024-01-01 00:00:00' );
		$content_older = $create_product( 'Bravo catalog notes', $search_term, '2024-01-02 00:00:00' );
		$content_newer = $create_product( 'Yankee catalog notes', $search_term, '2024-01-03 00:00:00' );
		$title_newer   = $create_product( 'Zulu ' . $search_term, '', '2024-01-04 00:00:00' );

		if ( $explicit_order ) {
			$expected = 'ASC' === $order
				? array( $title_older->get_id(), $content_older->get_id(), $content_newer->get_id(), $title_newer->get_id() )
				: array( $title_newer->get_id(), $content_newer->get_id(), $content_older->get_id(), $title_older->get_id() );
		} else {
			$expected = 'ASC' === $order
				? array( $title_older->get_id(), $title_newer->get_id(), $content_older->get_id(), $content_newer->get_id() )
				: array( $title_newer->get_id(), $title_older->get_id(), $content_newer->get_id(), $content_older->get_id() );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Preserve the exact test-global state around this request simulation.
		$had_orderby      = array_key_exists( 'orderby', $_GET );
		$previous_orderby = $had_orderby ? $_GET['orderby'] : null;
		// phpcs:enable
		if ( $explicit_order ) {
			$_GET['orderby'] = 'modified';
		} else {
			unset( $_GET['orderby'] );
		}

		try {
			$results = $this->get_search_results(
				$search_term,
				array(
					'post_status' => $post_status,
					'orderby'     => 'modified',
					'order'       => $order,
				)
			);
		} finally {
			if ( $had_orderby ) {
				$_GET['orderby'] = $previous_orderby;
			} else {
				unset( $_GET['orderby'] );
			}
		}

		$this->assertSame(
			$expected,
			$results,
			$explicit_order
				? 'Explicit modified-date sorting should take precedence over search relevance.'
				: 'Core status-view ordering should remain the tiebreak within title and content relevance groups.'
		);
	}

	/**
	 * Core status-view ordering for title-priority coverage.
	 *
	 * @return array<string, array{string, string, bool}>
	 */
	public function core_status_ordering_provider(): array {
		return array(
			'drafts use core modified descending'       => array( 'draft', 'DESC', false ),
			'pending uses core modified ascending'      => array( 'pending', 'ASC', false ),
			'drafts keep explicit modified descending'  => array( 'draft', 'DESC', true ),
			'pending keeps explicit modified ascending' => array( 'pending', 'ASC', true ),
		);
	}

	/**
	 * @testdox Product searches prioritize titles containing a literal backslash.
	 */
	public function test_product_search_prioritizes_title_matches_with_literal_backslashes(): void {
		$search_term = 'Back\\Slash ' . wp_generate_password( 8, false );

		$title_match = WC_Helper_Product::create_simple_product();
		$title_match->set_name( $search_term );
		$title_match->set_date_created( '2024-01-01 00:00:00' );
		$title_match->save();

		$content_match = WC_Helper_Product::create_simple_product();
		$content_match->set_name( 'Archive backslash notes' );
		$content_match->set_description( $search_term );
		$content_match->set_date_created( '2024-01-02 00:00:00' );
		$content_match->save();

		// Preserve the literal backslash as WordPress expects slashed post data.
		wp_update_post(
			wp_slash(
				array(
					'ID'         => $title_match->get_id(),
					'post_title' => $search_term,
				)
			)
		);
		wp_update_post(
			wp_slash(
				array(
					'ID'           => $content_match->get_id(),
					'post_content' => $search_term,
				)
			)
		);

		$this->assertSame(
			array( $title_match->get_id(), $content_match->get_id() ),
			$this->get_search_results( wp_slash( $search_term ) ),
			'A request-unslashed literal backslash should survive the ranking parser.'
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
	 * @testdox Product searches keep orderby values added during pre_get_posts.
	 */
	public function test_product_search_keeps_late_explicit_orderby(): void {
		list( $title_match, $content_match, $search_phrase ) = $this->create_search_products();

		$set_title_order = static function ( WP_Query $query ): void {
			if ( $query->get( 'product_search' ) ) {
				$query->set( 'orderby', 'title' );
				$query->set( 'order', 'ASC' );
			}
		};
		add_action( 'pre_get_posts', $set_title_order );

		try {
			$results = $this->get_search_results( $search_phrase );
		} finally {
			remove_action( 'pre_get_posts', $set_title_order );
		}

		$this->assertSame(
			array( $content_match->get_id(), $title_match->get_id() ),
			$results,
			'An explicit orderby added after request parsing should take precedence over search relevance.'
		);
	}

	/**
	 * @testdox Product searches honor later posts_orderby filters.
	 */
	public function test_product_search_keeps_later_posts_orderby_filter(): void {
		list( $title_match, $content_match, $search_phrase ) = $this->create_search_products();

		$set_title_order = static function ( $orderby, WP_Query $query ) {
			global $wpdb;

			return $query->get( 'product_search' ) ? "{$wpdb->posts}.post_title ASC" : $orderby;
		};
		add_filter( 'posts_orderby', $set_title_order, 20, 2 );

		try {
			$results = $this->get_search_results( $search_phrase );
		} finally {
			remove_filter( 'posts_orderby', $set_title_order, 20 );
		}

		$this->assertSame(
			array( $content_match->get_id(), $title_match->get_id() ),
			$results,
			'A later posts_orderby filter should take precedence over search relevance.'
		);
	}

	/**
	 * @testdox Product searches defer to a primary sort an earlier posts_clauses filter set.
	 */
	public function test_product_search_defers_to_earlier_posts_clauses_ordering(): void {
		list( $title_match, $content_match, $search_phrase ) = $this->create_search_products();

		// Prepending takes over the primary sort, so ranking has to stand down entirely.
		$lead_with_title = static function ( $clauses, $query ) {
			global $wpdb;

			if ( is_array( $clauses ) && isset( $clauses['orderby'] ) && $query->get( 'product_search' ) ) {
				$clauses['orderby'] = "{$wpdb->posts}.post_title ASC, " . $clauses['orderby'];
			}

			return $clauses;
		};
		add_filter( 'posts_clauses', $lead_with_title, 20, 2 );

		try {
			$results = $this->get_search_results( $search_phrase );
		} finally {
			remove_filter( 'posts_clauses', $lead_with_title, 20 );
		}

		$this->assertSame(
			array( $content_match->get_id(), $title_match->get_id() ),
			$results,
			'A primary sort set by an earlier posts_clauses filter should take precedence over search relevance.'
		);
	}

	/**
	 * @testdox Product searches still rank when an earlier posts_clauses filter only appends a tiebreak.
	 */
	public function test_product_search_ranks_when_earlier_posts_clauses_filter_appends_a_tiebreak(): void {
		list( $title_match, $content_match, $search_phrase ) = $this->create_search_products();

		// Reading the clause on posts_clauses must not turn a plugin that adds a tiebreak into one that
		// silently disables relevance: core still owns the primary sort, so ranking leads and this survives.
		$append_tiebreak = static function ( $clauses, $query ) {
			global $wpdb;

			if ( is_array( $clauses ) && isset( $clauses['orderby'] ) && $query->get( 'product_search' ) ) {
				$clauses['orderby'] .= ", {$wpdb->posts}.ID ASC";
			}

			return $clauses;
		};
		add_filter( 'posts_clauses', $append_tiebreak, 20, 2 );

		$captured_orderby = null;
		$capture          = static function ( $clauses ) use ( &$captured_orderby ) {
			$captured_orderby = is_array( $clauses ) && isset( $clauses['orderby'] ) ? $clauses['orderby'] : null;
			return $clauses;
		};
		add_filter( 'posts_clauses', $capture, PHP_INT_MAX );

		try {
			$results = $this->get_search_results( $search_phrase );
		} finally {
			remove_filter( 'posts_clauses', $append_tiebreak, 20 );
			remove_filter( 'posts_clauses', $capture, PHP_INT_MAX );
		}

		global $wpdb;
		$this->assertStringContainsString( 'CASE WHEN', (string) $captured_orderby, 'An appended tiebreak should not suppress the relevance clause.' );
		$this->assertStringEndsWith( "{$wpdb->posts}.ID ASC", (string) $captured_orderby, "The appending filter's tiebreak should survive after the relevance clause." );
		$this->assertSame(
			array( $title_match->get_id(), $content_match->get_id() ),
			$results,
			'Title matches should still be listed first when an earlier filter only appends a tiebreak.'
		);
	}

	/**
	 * @testdox Product search ranking leaves the core default clause visible to every posts_orderby filter.
	 */
	public function test_product_search_ranking_preserves_clause_for_other_posts_orderby_filters(): void {
		global $wpdb;

		list( $title_match, $content_match, $search_phrase ) = $this->create_search_products();

		// PHP_INT_MAX because ranking runs on posts_clauses: no posts_orderby priority, however late,
		// should ever observe the relevance clause.
		$guard_fired = 0;
		$guard       = static function ( $orderby ) use ( &$guard_fired, $wpdb ) {
			if ( "{$wpdb->posts}.post_date DESC" === $orderby ) {
				++$guard_fired;
			}
			return $orderby;
		};
		add_filter( 'posts_orderby', $guard, PHP_INT_MAX );

		try {
			$results = $this->get_search_results( $search_phrase );
		} finally {
			remove_filter( 'posts_orderby', $guard, PHP_INT_MAX );
		}

		$this->assertGreaterThan( 0, $guard_fired, 'A posts_orderby filter at any priority that matches on the core default clause should still see it unmodified.' );
		$this->assertSame(
			array( $title_match->get_id(), $content_match->get_id() ),
			$results,
			'Title ranking should still apply when other filters leave the clause unchanged.'
		);
	}

	/**
	 * Search terms for title-priority coverage.
	 *
	 * @return array<string, array<string>>
	 */
	public function search_term_provider(): array {
		return array(
			'plain search'   => array( '%s' ),
			'quoted phrase'  => array( '"%s"' ),
			'first OR group' => array( '%s OR Missing Lantern' ),
			'later OR group' => array( 'Missing Lantern OR %s' ),
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
	 * @testdox An extension join to the lookup table under another alias does not suppress WooCommerce's aliased join.
	 */
	public function test_stock_status_filter_joins_despite_a_foreign_alias_lookup_join(): void {
		global $wpdb;

		$args = $this->filter_clauses_for_stock_status(
			ProductStockStatus::IN_STOCK,
			array(
				'join'  => " LEFT JOIN {$wpdb->wc_product_meta_lookup} extension_lookup ON {$wpdb->posts}.ID = extension_lookup.product_id ",
				'where' => '',
			)
		);

		$this->assertStringContainsString(
			"LEFT JOIN {$wpdb->wc_product_meta_lookup} wc_product_meta_lookup",
			$args['join'],
			'A join to the lookup table under another alias must not suppress the wc_product_meta_lookup alias the filter references.'
		);
	}

	/**
	 * @testdox In-stock filtering still lists products when an earlier callback joins the lookup table under its own alias.
	 */
	public function test_stock_status_filter_survives_an_extension_join_under_another_alias(): void {
		global $wpdb;

		update_option( 'woocommerce_manage_stock', 'no' );

		$in_stock = WC_Helper_Product::create_simple_product();
		$in_stock->set_manage_stock( false );
		$in_stock->set_stock_status( ProductStockStatus::IN_STOCK );
		$in_stock->save();

		// The callback stands in for an extension that joins the lookup table under its own alias before WooCommerce's callback runs.
		$extension_join = static function ( $clauses ) use ( $wpdb ) {
			$clauses['join'] .= " LEFT JOIN {$wpdb->wc_product_meta_lookup} extension_lookup ON {$wpdb->posts}.ID = extension_lookup.product_id ";
			return $clauses;
		};
		add_filter( 'posts_clauses', $extension_join, 5 );

		try {
			$ids = $this->query_product_ids_for_stock_status( ProductStockStatus::IN_STOCK );
		} finally {
			remove_filter( 'posts_clauses', $extension_join, 5 );
		}

		$this->assertContains(
			$in_stock->get_id(),
			$ids,
			'The in-stock filter should keep producing a valid query when an extension has already joined the lookup table under a different alias.'
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

		return $this->with_stock_status(
			$stock_status,
			function () use ( $clauses ) {
				return $this->sut->filter_stock_status_post_clauses( $clauses );
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
	 * Create title and content-only search matches.
	 *
	 * @return array{WC_Product, WC_Product, string}
	 */
	private function create_search_products(): array {
		$search_phrase = 'Night Light ' . wp_generate_password( 8, false );

		// The content-only match is deliberately the newer one, so date ordering alone puts it first and
		// only ranking can restore the expected order. Undated fixtures tie, and the assertion then rides
		// on an unspecified tie-break rather than the clause under test.
		$title_match = WC_Helper_Product::create_simple_product();
		$title_match->set_name( $search_phrase );
		$title_match->set_date_created( '2024-01-01 00:00:00' );
		$title_match->save();

		$content_match = WC_Helper_Product::create_simple_product();
		$content_match->set_name( 'Archive Lamp Notes ' . wp_generate_password( 8, false ) );
		$content_match->set_description( $search_phrase );
		$content_match->set_date_created( '2024-01-02 00:00:00' );
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
