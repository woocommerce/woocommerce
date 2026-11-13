<?php
/**
 * Product reviews query tests.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Internal\Abilities\Domain\ProductReviewsQuery;
use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;
use Automattic\WooCommerce\Abilities\ExtensibleAbilities;

/**
 * Tests review queries through the Abilities API.
 */
class ProductReviewsQueryTest extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var \WP_Ability
	 */
	private $sut;

	/**
	 * An administrator that every test acts as.
	 *
	 * @var int
	 */
	private static $user_id;

	/**
	 * Create the fixtures that no test changes beyond its own transaction.
	 *
	 * @param \WP_UnitTest_Factory $factory WordPress unit test factory.
	 */
	public static function wpSetUpBeforeClass( $factory ): void {
		self::$user_id = $factory->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * Set up an authorized caller.
	 */
	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( self::$user_id );
		$this->sut = new \WP_Ability( ProductReviewsQuery::get_name(), ProductReviewsQuery::get_registration_args() );
	}

	/**
	 * Create a review fixture.
	 *
	 * @param int   $product Product ID.
	 * @param int   $rating Star rating, or zero to omit it.
	 * @param array $args Comment overrides.
	 * @return int
	 */
	private function review( int $product, int $rating, array $args = array() ): int {
		$id = self::factory()->comment->create(
			array_merge(
				array(
					'comment_post_ID'      => $product,
					'comment_type'         => 'review',
					'comment_approved'     => '1',
					'comment_content'      => '<p>Lovely item</p>',
					'comment_author'       => 'Private Reviewer',
					'comment_author_email' => 'private@example.test',
					'comment_author_IP'    => '192.0.2.1',
					'comment_date_gmt'     => '2026-10-01 12:00:00',
				),
				$args
			)
		);
		if ( $rating ) {
			update_comment_meta( $id, 'rating', $rating );
		}
		return $id;
	}

	/**
	 * @testdox Should page matching reviews while keeping whole-scope ratings and protecting reviewer identity.
	 */
	public function test_ratings_paging_and_privacy(): void {
		$product = \WC_Helper_Product::create_simple_product()->get_id();
		$first   = $this->review( $product, 1 );
		$second  = $this->review( $product, 5 );
		$this->review( $product, 0 );
		$input = array(
			'product_ids' => array( $product ),
			'per_page'    => 1,
		);
		$page1 = $this->sut->execute( $input );
		$page2 = $this->sut->execute( array_merge( $input, array( 'page' => 2 ) ) );
		$this->assertIsArray( $page1 );
		$this->assertSame( 3, $page1['total'] );
		$this->assertSame( 3, $page1['total_pages'] );
		$this->assertNotSame( $page1['reviews'][0]['id'], $page2['reviews'][0]['id'] );
		$this->assertSame( 3.0, $page1['summary']['average_rating'] );
		$filtered = $this->sut->execute( array_merge( $input, array( 'max_rating' => 1 ) ) );
		$this->assertSame( 1, $filtered['total'] );
		$this->assertSame( $first, $filtered['reviews'][0]['id'] );
		$this->assertSame( $page1['summary']['total_reviews'], $filtered['summary']['total_reviews'] );
		$this->assertSame( 3.0, $filtered['by_product'][0]['average_rating'] );
		$this->assertSame( 'Lovely item', $filtered['reviews'][0]['review'] );
		$this->assertStringNotContainsString( 'Private Reviewer', wp_json_encode( $filtered ) );
		$this->assertStringNotContainsString( 'private@example.test', wp_json_encode( $filtered ) );
		foreach ( array( 'reviewer', 'reviewer_email', 'comment_author', 'comment_author_email', 'comment_author_IP' ) as $key ) {
			$this->assertArrayNotHasKey( $key, $filtered['reviews'][0] );
		}
		$included = $this->sut->execute( array( 'include' => array( $second ) ) );
		$this->assertSame( array( $second ), array_column( $included['reviews'], 'id' ) );
	}

	/**
	 * @testdox Should distinguish unrated products from missing products and rank the whole scope.
	 */
	public function test_ranking_and_products_without_reviews(): void {
		$worst = \WC_Helper_Product::create_simple_product()->get_id();
		$best  = \WC_Helper_Product::create_simple_product()->get_id();
		$empty = \WC_Helper_Product::create_simple_product()->get_id();
		$this->review( $worst, 1 );
		$this->review( $best, 5 );
		$unknown = 99999999;
		$result  = $this->sut->execute(
			array(
				'product_ids' => array( $best, $empty, $worst, $unknown ),
				'per_page'    => 1,
			)
		);
		$this->assertSame( array( $worst, $best ), array_column( $result['by_product'], 'product_id' ) );
		$this->assertSame( array( $empty ), $result['products_without_reviews'] );
		$this->assertSame( array( $unknown ), $result['unknown_product_ids'] );
		$this->assertSame( 2, $result['summary']['total_products'] );
	}

	/**
	 * @testdox Should count legacy rated comments and unrated reviews but exclude replies and other post types.
	 */
	public function test_review_scope(): void {
		$product = \WC_Helper_Product::create_simple_product()->get_id();
		$legacy  = $this->review( $product, 4, array( 'comment_type' => 'comment' ) );
		$unrated = $this->review( $product, 0 );
		$this->review( $product, 0, array( 'comment_type' => 'comment' ) );
		$this->review( $product, 5, array( 'comment_parent' => $legacy ) );
		$this->review( self::factory()->post->create(), 5 );
		$held   = $this->review( $product, 2, array( 'comment_approved' => '0' ) );
		$result = $this->sut->execute();
		$this->assertSame( 2, $result['total'] );
		$this->assertSame( 4.0, $result['summary']['average_rating'] );
		$result = $this->sut->execute( array( 'status' => 'hold' ) );
		$this->assertSame( array( $held ), array_column( $result['reviews'], 'id' ) );
		$result = $this->sut->execute(
			array(
				'status'  => 'all',
				'include' => array( $unrated ),
			)
		);
		$this->assertSame( 'approved', $result['reviews'][0]['status'] );
	}

	/**
	 * @testdox Should search review text only without changing the summary.
	 */
	public function test_search(): void {
		$product = \WC_Helper_Product::create_simple_product()->get_id();
		$this->review( $product, 5 );
		$result = $this->sut->execute( array( 'search' => 'Private Reviewer' ) );
		$this->assertSame( 0, $result['total'] );
		$this->assertSame( 1, $result['summary']['total_reviews'] );
		$this->assertSame( 'review_text', $result['search_scope'] );
		$this->assertArrayNotHasKey( 'by_product', $result );
		$result = $this->sut->execute( array( 'search' => 'Lovely' ) );
		$this->assertSame( 1, $result['total'] );
		$result = $this->sut->execute( array( 'search' => '0' ) );
		$this->assertSame( 0, $result['total'] );
	}

	/**
	 * @testdox Should reject invalid inputs and unauthorized callers.
	 */
	public function test_validation_and_permissions(): void {
		foreach ( array( array( 'include' => array() ), array( 'product_ids' => array() ), array( 'per_page' => 0 ), array( 'page' => 0 ), array( 'min_rating' => 6 ), array( 'status' => 'invalid' ) ) as $input ) {
			$this->assertWPError( $this->sut->execute( $input ) );
		}
		$result = $this->sut->execute(
			array(
				'min_rating' => 5,
				'max_rating' => 1,
			)
		);
		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_product_review_invalid_rating_range', $result->get_error_code() );
		$this->assertSame( 400, rest_convert_error_to_response( $result )->get_status() );
		foreach ( array( 'customer', 'subscriber' ) as $role ) {
			wp_set_current_user( self::factory()->user->create( array( 'role' => $role ) ) );
			$this->assertWPError( $this->sut->execute() );
		}
		wp_set_current_user( 0 );
		$this->assertWPError( $this->sut->execute() );
	}

	/**
	 * @testdox Should retain both ends of a large rating ranking and omit unrated products above the cap.
	 */
	public function test_large_ranking(): void {
		$rank = new \ReflectionMethod( ProductReviewsQuery::class, 'rank' );
		$rank->setAccessible( true );
		$row  = static fn( int $id, float $rating ): array => array(
			'product_id'     => $id,
			'average_rating' => $rating,
			'review_count'   => 1,
		);
		$rows = array( $row( 1, 0.0 ) );
		for ( $id = 2; $id <= 203; ++$id ) {
			$rows[] = $row( $id, 102 > $id ? 1.0 : ( 104 > $id ? 3.0 : 5.0 ) );
		}

		$capped = $rank->invoke( null, $rows );

		$this->assertCount( 200, $capped );
		$this->assertSame(
			array(
				1 => 100,
				5 => 100,
			),
			array_count_values( array_map( 'intval', array_column( $capped, 'average_rating' ) ) ),
			'Both ends of the rated ranking are kept, not its first entries.'
		);
		$this->assertNotContains( 1, array_column( $capped, 'product_id' ) );

		$rated_within_cap = $rank->invoke( null, array_merge( array_slice( $rows, 0, 3 ), array_slice( $rows, 5, 198 ), array( $row( 204, 0.0 ) ) ) );

		$this->assertCount( 200, $rated_within_cap, 'Only the unrated products are dropped when the rated ones fit.' );
		$this->assertNotContains( 0.0, array_column( $rated_within_cap, 'average_rating' ) );
	}

	/**
	 * @testdox Should count every reviewed product in the summary when the ranking is capped.
	 */
	public function test_summary_counts_products_beyond_the_ranking_cap(): void {
		global $wpdb;
		$products = 201;
		$marker   = wp_unique_id( 'ranking-cap-' );
		$now      = current_time( 'mysql', true );

		// Bulk rows keep this end-to-end check cheap; the factories cost seconds at this size.
		$posts = array();
		for ( $i = 0; $i < $products; ++$i ) {
			$posts[] = $wpdb->prepare( "(%s, %s, '', '', '', '', '', 'publish', 'product', %s, %s, %s, %s)", $marker, '', $now, $now, $now, $now );
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery -- Every row is prepared above.
		$wpdb->query( "INSERT INTO {$wpdb->posts} (post_title, post_content, post_excerpt, to_ping, pinged, post_content_filtered, post_password, post_status, post_type, post_date, post_date_gmt, post_modified, post_modified_gmt) VALUES " . implode( ', ', $posts ) );
		$product_ids = array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s", $marker ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		$comments = array();
		foreach ( $product_ids as $product_id ) {
			$comments[] = $wpdb->prepare( "(%d, 'review', '1', 'Lovely item', 'Reviewer', '', '', '', %s, %s)", $product_id, $now, $now );
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery -- Every row is prepared above.
		$wpdb->query( "INSERT INTO {$wpdb->comments} (comment_post_ID, comment_type, comment_approved, comment_content, comment_author, comment_author_email, comment_author_url, comment_author_IP, comment_date, comment_date_gmt) VALUES " . implode( ', ', $comments ) );
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prepare(
				"INSERT INTO {$wpdb->commentmeta} (comment_id, meta_key, meta_value)
				SELECT c.comment_ID, 'rating', '4' FROM {$wpdb->comments} c INNER JOIN {$wpdb->posts} p ON p.ID = c.comment_post_ID WHERE p.post_title = %s",
				$marker
			)
		);
		wp_cache_set_last_changed( 'posts' );
		wp_cache_set_comments_last_changed();

		$result = $this->sut->execute( array( 'per_page' => 1 ) );

		$this->assertIsArray( $result );
		$this->assertSame( $products, $result['total'] );
		$this->assertSame( $products, $result['summary']['total_products'], 'The summary counts the products the ranking leaves out.' );
		$this->assertCount( 200, $result['by_product'] );
	}

	/**
	 * @testdox Should break ranking ties by product ID, including at the output cap.
	 */
	public function test_ranking_ties_are_deterministic(): void {
		$rank = new \ReflectionMethod( ProductReviewsQuery::class, 'rank' );
		$rank->setAccessible( true );
		$rows = array(
			array(
				'product_id'     => 3,
				'average_rating' => 3.0,
				'review_count'   => 1,
			),
			array(
				'product_id'     => 2,
				'average_rating' => 3.0,
				'review_count'   => 2,
			),
			array(
				'product_id'     => 1,
				'average_rating' => 3.0,
				'review_count'   => 1,
			),
			array(
				'product_id'     => 4,
				'average_rating' => 1.0,
				'review_count'   => 1,
			),
			array(
				'product_id'     => 5,
				'average_rating' => 0.0,
				'review_count'   => 3,
			),
		);
		$this->assertSame( array( 4, 2, 1, 3, 5 ), array_column( $rank->invoke( null, $rows ), 'product_id' ) );
		$rows     = array_map(
			static fn( $id ) => array(
				'product_id'     => $id,
				'average_rating' => 3.0,
				'review_count'   => 1,
			),
			range( 202, 1 )
		);
		$expected = array_merge( range( 1, 100 ), range( 103, 202 ) );
		$this->assertSame( $expected, array_column( $rank->invoke( null, $rows ), 'product_id' ) );
		$this->assertSame( $expected, array_column( $rank->invoke( null, array_reverse( $rows ) ), 'product_id' ) );
	}

	/**
	 * @testdox Should format UTC dates and verified purchases, and handle missing dates and empty pages.
	 */
	public function test_dates_verified_and_empty_results(): void {
		$schema = $this->sut->get_output_schema()['properties']['reviews']['items']['properties']['date_created'];
		$this->assertSame( 'string', $schema['type'] );
		$this->assertSame( 'date-time', $schema['format'] );
		$product = \WC_Helper_Product::create_simple_product()->get_id();
		$id      = $this->review( $product, 4 );
		update_comment_meta( $id, 'verified', 1 );
		$result = $this->sut->execute();
		$this->assertIsArray( $result );
		$this->assertSame( '2026-10-01T12:00:00Z', $result['reviews'][0]['date_created'] );
		$this->assertTrue( $result['reviews'][0]['verified'] );
		global $wpdb;
		// WordPress repairs zero dates on update, but imports can store them directly.
		$wpdb->update( $wpdb->comments, array( 'comment_date_gmt' => '0000-00-00 00:00:00' ), array( 'comment_ID' => $id ) );
		clean_comment_cache( $id );
		$result = $this->sut->execute();
		$this->assertArrayNotHasKey( 'date_created', $result['reviews'][0] );
		$result = $this->sut->execute( array( 'page' => 2 ) );
		$this->assertSame( array(), $result['reviews'] );
		$this->assertSame( 1, $result['total'] );
		$result = $this->sut->execute( array( 'status' => 'spam' ) );
		$this->assertSame( 0, $result['total'] );
		$this->assertSame( 0, $result['total_pages'] );
	}

	/**
	 * @testdox Should expose valid extension fields and propagate extension failures only while the feature is enabled.
	 * @testWith ["valid", ""]
	 *           ["invalid", "woocommerce_ability_field_output_invalid"]
	 *           ["throws", "woocommerce_ability_extension_failed"]
	 * @param string $behavior Extension callback behavior.
	 * @param string $error_code Expected error for a failing callback.
	 */
	public function test_extension_fields( string $behavior, string $error_code ): void {
		$saved = array();
		foreach ( array( 'resources', 'fields' ) as $name ) {
			$property = new \ReflectionProperty( ExtensibleAbilities::class, $name );
			$property->setAccessible( true );
			$saved[ $name ] = $property->getValue();
			$property->setValue( null, array() );
		}
		try {
			update_option( 'woocommerce_feature_extensible_abilities_enabled', 'no' );
			$product  = \WC_Helper_Product::create_simple_product()->get_id();
			$ids      = array( $this->review( $product, 1 ), $this->review( $product, 5 ) );
			$input    = array( 'include' => $ids );
			$baseline = ProductReviewsQuery::execute( $input );
			$schema   = ProductReviewsQuery::get_registration_args()['output_schema'];
			update_option( 'woocommerce_feature_extensible_abilities_enabled', 'yes' );
			$this->assertSame( $baseline, ProductReviewsQuery::execute( $input ) );
			$this->assertSame( $schema, ProductReviewsQuery::get_registration_args()['output_schema'] );
			add_action( 'woocommerce_extensible_abilities_resources_init', array( AbilitiesLoader::class, 'register_core_resources' ), 0 );
			do_action( 'woocommerce_extensible_abilities_resources_init' );
			add_action(
				'woocommerce_extensible_abilities_init',
				static function () use ( $behavior ) {
					ExtensibleAbilities::register_field(
						array(
							'resource'      => 'product_review',
							'namespace'     => 'test-ext',
							'field'         => 'review_id',
							'output_schema' => array( 'type' => 'integer' ),
							'get_callback'  => static function ( \WP_Comment $comment ) use ( $behavior ) {
								if ( 'throws' === $behavior ) {
									throw new \RuntimeException( 'Private extension failure details' );
								}
								return 'invalid' === $behavior ? 'not an integer' : (int) $comment->comment_ID;
							},
						)
					);
				}
			);
			do_action( 'woocommerce_extensible_abilities_init' );
			$ability = new \WP_Ability( ProductReviewsQuery::get_name(), ProductReviewsQuery::get_registration_args() );
			if ( $error_code ) {
				$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_field' );
			}
			$result = $ability->execute( $input );
			if ( $error_code ) {
				$this->assertWPError( $result );
				$this->assertSame( $error_code, $result->get_error_code() );
				$this->assertSame( 500, rest_convert_error_to_response( $result )->get_status() );
				$this->assertStringNotContainsString( 'Private extension failure details', $result->get_error_message() );
				return;
			}
			$this->assertIsArray( $result );
			$this->assertCount( 2, $result['reviews'] );
			foreach ( $result['reviews'] as $review ) {
				$this->assertSame( $review['id'], $review['extensions']['test-ext']['review_id'] );
				$this->assertArrayNotHasKey( 'reviewer', $review );
				$this->assertArrayNotHasKey( 'reviewer_email', $review );
			}
			$this->assertSame( 'integer', $ability->get_output_schema()['properties']['reviews']['items']['properties']['extensions']['properties']['test-ext']['properties']['review_id']['type'] );
			update_option( 'woocommerce_feature_extensible_abilities_enabled', 'no' );
			$this->assertSame( $baseline, ProductReviewsQuery::execute( $input ) );
			$this->assertSame( $schema, ProductReviewsQuery::get_registration_args()['output_schema'] );
		} finally {
			foreach ( $saved as $name => $value ) {
				$property = new \ReflectionProperty( ExtensibleAbilities::class, $name );
				$property->setAccessible( true );
				$property->setValue( null, $value );
			}
		}
	}
	/**
	 * @testdox Should include spam and trash only when requested and preserve totals beyond the last page.
	 */
	public function test_moderation_statuses_and_empty_pages(): void {
		$product = \WC_Helper_Product::create_simple_product()->get_id();
		foreach ( array( '1', '0', 'spam', 'trash' ) as $status ) {
			$this->review( $product, 4, array( 'comment_approved' => $status ) );
		}
		$all = $this->sut->execute( array( 'status' => 'all' ) );
		$this->assertSame( 4, $all['total'] );
		$this->assertCount( 4, $all['reviews'] );
		foreach ( array( 'spam', 'trash' ) as $status ) {
			$result = $this->sut->execute(
				array(
					'status'   => $status,
					'page'     => 2,
					'per_page' => 1,
				)
			);
			$this->assertSame( array(), $result['reviews'] );
			$this->assertSame( 1, $result['total'] );
			$this->assertSame( 1, $result['total_pages'] );
		}
	}

	/**
	 * @testdox Should escape literal search characters and recognize requested trashed products without reviews.
	 */
	public function test_literal_search_and_trashed_products(): void {
		$product = \WC_Helper_Product::create_simple_product()->get_id();
		$id      = $this->review( $product, 3, array( 'comment_content' => '100%_match' ) );
		$this->review( $product, 5, array( 'comment_content' => '100 other match' ) );
		$result = $this->sut->execute( array( 'search' => '%_' ) );
		$this->assertSame( array( $id ), array_column( $result['reviews'], 'id' ) );
		$empty = \WC_Helper_Product::create_simple_product()->get_id();
		wp_trash_post( $empty );
		$result = $this->sut->execute( array( 'product_ids' => array( $empty ) ) );
		$this->assertSame( array( $empty ), $result['products_without_reviews'] );
		$this->assertArrayNotHasKey( 'unknown_product_ids', $result );
	}
	/**
	 * @testdox Should count approved and held direct replies from store staff, so an agent can skip reviews the store answered.
	 */
	public function test_store_reply_counts(): void {
		$product  = \WC_Helper_Product::create_simple_product()->get_id();
		$staff    = get_current_user_id();
		$customer = self::factory()->user->create( array( 'role' => 'customer' ) );
		$answered = $this->review( $product, 1 );
		$open     = $this->review( $product, 2 );
		$reply    = function ( int $review_id, string $approved, int $user_id ) use ( $product ): int {
			return self::factory()->comment->create(
				array(
					'comment_post_ID'  => $product,
					'comment_parent'   => $review_id,
					'comment_approved' => $approved,
					'user_id'          => $user_id,
				)
			);
		};
		$approved = $reply( $answered, '1', $staff );
		$reply( $answered, '0', $staff );
		$reply( $answered, 'spam', $staff );
		$reply( $approved, '1', $staff );
		$reply( $open, 'trash', $staff );
		$reply( $open, '1', $customer );
		$reply( $open, '1', 0 );
		self::factory()->comment->create(
			array(
				'comment_post_ID' => self::factory()->post->create(),
				'comment_parent'  => $open,
				'user_id'         => $staff,
			)
		);

		$result = $this->sut->execute( array( 'product_ids' => array( $product ) ) );
		$counts = array_column( $result['reviews'], 'store_reply_count', 'id' );

		$this->assertSame( 2, $counts[ $answered ] ?? null, 'Spam and nested replies do not count.' );
		$this->assertSame( 0, $counts[ $open ] ?? null, 'Customer, guest, trashed and other posts\' replies do not count.' );
	}

	/**
	 * @testdox Should leave nested comment queries unchanged and remove its query filter afterward.
	 */
	public function test_nested_comment_queries(): void {
		$post_comment = self::factory()->comment->create(
			array(
				'comment_post_ID' => self::factory()->post->create(),
				'comment_type'    => 'comment',
			)
		);
		$product      = \WC_Helper_Product::create_simple_product()->get_id();
		$this->review( $product, 5 );
		$nested = null;
		$filter = static function ( $clauses ) use ( &$nested, $post_comment ) {
			if ( null === $nested ) {
				$nested = array();
				$nested = get_comments(
					array(
						'comment__in' => array( $post_comment ),
						'status'      => 'all',
					)
				);
			}
			return $clauses;
		};
		add_filter( 'comments_clauses', $filter, 20 );
		try {
			$result = $this->sut->execute();
			$this->assertSame( 1, $result['total'] );
			$this->assertSame( array( $post_comment ), array_map( 'intval', wp_list_pluck( $nested, 'comment_ID' ) ) );
		} finally {
			remove_filter( 'comments_clauses', $filter, 20 );
		}
		$this->assertCount(
			1,
			get_comments(
				array(
					'comment__in'  => array( $post_comment ),
					'status'       => 'all',
					'cache_domain' => 'after-review-query',
				)
			)
		);
	}
}
