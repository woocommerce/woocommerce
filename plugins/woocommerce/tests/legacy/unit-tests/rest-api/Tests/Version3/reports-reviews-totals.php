<?php
/**
 * Tests for the reports reviews totals REST API.
 *
 * @package WooCommerce\Tests\API
 * @since 3.5.0
 */

class WC_Tests_API_Reports_Reviews_Totals extends WC_REST_Unit_Test_Case {

	/**
	 * Sequence number keeping each submitted review distinct from the last.
	 *
	 * @var int
	 */
	private $review_sequence = 0;

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Test route registration.
	 *
	 * @since 3.5.0
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/v3/reports/reviews/totals', $routes );
	}

	/**
	 * Submit a review the way the storefront does.
	 *
	 * wp_handle_comment_submission() posts comment_post_ID and rating and passes a default
	 * comment_type, which is what lets WC_Comments::update_comment_type() promote the comment to a
	 * review on preprocess_comment and WC_Comments::add_comment_rating() store the rating meta on
	 * comment_post. The comment_type has to be passed: wp_new_comment() only defaults it after
	 * preprocess_comment has run, so omitting it leaves WooCommerce's callback nothing to promote.
	 *
	 * @param int    $product_id Product being reviewed.
	 * @param int    $rating     Rating from 1 to 5.
	 * @param string $status     Comment status to settle on, 'approve' or 'hold'.
	 * @return int
	 */
	private function submit_review_through_comment_form( $product_id, $rating, $status = 'approve' ) {
		++$this->review_sequence;

		$original_post = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The superglobal is saved and restored, not read as form data; the review form's fields are set below to drive WooCommerce's own comment hooks.

		try {
			$_POST['comment_post_ID'] = $product_id;
			$_POST['rating']          = $rating;

			$comment_id = wp_new_comment(
				array(
					'comment_post_ID'      => $product_id,
					'comment_author'       => 'Storefront reviewer ' . $this->review_sequence,
					'comment_author_email' => 'storefront' . $this->review_sequence . '@example.test',
					'comment_author_url'   => '',
					'comment_content'      => 'Storefront review ' . $this->review_sequence,
					'comment_type'         => 'comment',
					'comment_parent'       => 0,
					'user_id'              => 0,
				),
				true
			);
		} finally {
			$_POST = $original_post;
		}

		$this->assertNotWPError( $comment_id );

		wp_set_comment_status( $comment_id, $status );

		// Fail loudly if the writer path stops promoting the comment or stops storing the rating.
		$this->assertSame( 'review', get_comment( $comment_id )->comment_type );
		$this->assertEquals( $rating, get_comment_meta( $comment_id, 'rating', true ) );

		return (int) $comment_id;
	}

	/**
	 * Submit a review through the REST reviews endpoint.
	 *
	 * @param int $product_id Product being reviewed.
	 * @param int $rating     Rating from 1 to 5.
	 * @return int
	 */
	private function submit_review_through_rest( $product_id, $rating ) {
		++$this->review_sequence;

		$request = new WP_REST_Request( 'POST', '/wc/v3/products/reviews' );
		$request->set_body_params(
			array(
				'product_id'     => $product_id,
				'review'         => 'REST review ' . $this->review_sequence,
				'reviewer'       => 'REST reviewer ' . $this->review_sequence,
				'reviewer_email' => 'rest' . $this->review_sequence . '@example.test',
				'rating'         => $rating,
				'status'         => 'approved',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status() );

		$comment_id = (int) $response->get_data()['id'];

		$this->assertSame( 'review', get_comment( $comment_id )->comment_type );
		$this->assertEquals( $rating, get_comment_meta( $comment_id, 'rating', true ) );

		return $comment_id;
	}

	/**
	 * Insert a comment directly, for states no writer path produces.
	 *
	 * @param int         $post_id Post the comment belongs to.
	 * @param string|null $rating  Rating meta value, or null to store no rating at all.
	 * @param array       $args    Overrides for the comment row.
	 * @return int
	 */
	private function create_rated_comment( $post_id, $rating, $args = array() ) {
		$comment_id = $this->factory->comment->create(
			array_merge(
				array(
					'comment_post_ID'  => $post_id,
					'comment_approved' => '1',
					'comment_type'     => 'review',
				),
				$args
			)
		);

		if ( null !== $rating ) {
			add_comment_meta( $comment_id, 'rating', $rating );
		}

		return $comment_id;
	}

	/**
	 * Fetch the endpoint and index the totals by slug.
	 *
	 * @return array
	 */
	private function get_totals_by_slug() {
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/reports/reviews/totals' ) );

		$this->assertEquals( 200, $response->get_status() );

		return wp_list_pluck( $response->get_data(), 'total', 'slug' );
	}

	/**
	 * The totals the endpoint produced before this report was rewritten, kept as the regression
	 * reference so the new aggregate is compared against real previous behaviour rather than
	 * against numbers written down by hand.
	 *
	 * @return array
	 */
	private function get_totals_from_previous_implementation() {
		$totals = array();

		for ( $i = 1; $i <= 5; $i++ ) {
			$totals[ 'rated_' . $i . '_out_of_5' ] = (int) get_comments(
				array(
					'count'      => true,
					'post_type'  => 'product',
					'meta_key'   => 'rating', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- The previous implementation is reproduced verbatim so the endpoint can be compared against it.
					'meta_value' => $i, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- The previous implementation is reproduced verbatim so the endpoint can be compared against it.
				)
			);
		}

		return $totals;
	}

	/**
	 * Test getting all product reviews.
	 *
	 * @since 3.5.0
	 */
	public function test_get_reports() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/reports/reviews/totals' ) );
		$report   = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 5, count( $report ) );

		// Every bucket is reported, in order, even with nothing to count.
		foreach ( $report as $index => $row ) {
			$rating = $index + 1;

			$this->assertEquals( 'rated_' . $rating . '_out_of_5', $row['slug'] );
			$this->assertEquals( sprintf( 'Rated %s out of 5', $rating ), $row['name'] );
			$this->assertSame( 0, $row['total'] );
		}
	}

	/**
	 * The totals count every rating a comment query would have counted, and nothing else.
	 */
	public function test_get_reports_counts_only_rated_product_comments() {
		wp_set_current_user( $this->user );

		$product = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();
		$page    = $this->factory->post->create( array( 'post_type' => 'page' ) );

		// Read the empty totals first so the reviews added below have to invalidate them.
		$this->assertSame( 0, $this->get_totals_by_slug()['rated_5_out_of_5'] );

		// Reviews written the way real reviews are written.
		$this->submit_review_through_comment_form( $product->get_id(), 5 );
		$this->submit_review_through_comment_form( $product->get_id(), 5, 'hold' );
		$this->submit_review_through_rest( $product->get_id(), 4 );

		// States no writer path produces, inserted directly.
		$this->create_rated_comment( $product->get_id(), '5', array( 'comment_approved' => 'spam' ) );
		$this->create_rated_comment( $product->get_id(), '5', array( 'comment_approved' => 'trash' ) );
		$this->create_rated_comment( $product->get_id(), '4', array( 'comment_type' => '' ) );
		$this->create_rated_comment( $product->get_id(), '4', array( 'comment_type' => 'order_note' ) );
		$this->create_rated_comment( $product->get_id(), '2', array( 'comment_type' => 'webhook_delivery' ) );
		$this->create_rated_comment( $product->get_id(), '3', array( 'comment_type' => 'note' ) );
		$this->create_rated_comment( $product->get_id(), '3', array( 'comment_type' => 'action_log' ) );
		$this->create_rated_comment( $product->get_id(), '0' );
		$this->create_rated_comment( $product->get_id(), '05' );
		$this->create_rated_comment( $product->get_id(), null );
		$this->create_rated_comment( $page, '3' );

		$totals = $this->get_totals_by_slug();

		// The endpoint agrees with the implementation it replaced, bucket for bucket.
		$this->assertSame( $this->get_totals_from_previous_implementation(), $totals );

		// Guard against both sides agreeing on nothing at all.
		$this->assertGreaterThan( 0, $totals['rated_5_out_of_5'] );
		$this->assertGreaterThan( 0, $totals['rated_4_out_of_5'] );

		// Buckets with nothing to count are still reported, which the oracle cannot prove on its own.
		$this->assertSame( 0, $totals['rated_3_out_of_5'] );
		$this->assertSame( 0, $totals['rated_2_out_of_5'] );
		$this->assertSame( 0, $totals['rated_1_out_of_5'] );
	}

	/**
	 * A third party narrowing the comment query through comments_clauses still narrows this report.
	 */
	public function test_get_reports_applies_comments_clauses_filter() {
		wp_set_current_user( $this->user );

		$product = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();

		$this->submit_review_through_comment_form( $product->get_id(), 5 );
		$this->create_rated_comment( $product->get_id(), '4', array( 'comment_type' => '' ) );

		$this->assertSame( 1, $this->get_totals_by_slug()['rated_5_out_of_5'] );

		$hide_reviews = static function ( $clauses ) {
			$clauses['where'] .= " AND comment_type != 'review' ";

			return $clauses;
		};

		add_filter( 'comments_clauses', $hide_reviews );

		try {
			// The filter does not move the comment last_changed value, so the cached totals have to go.
			wp_cache_flush();

			$totals = $this->get_totals_by_slug();
		} finally {
			remove_filter( 'comments_clauses', $hide_reviews );
		}

		// The review typed comment is filtered out, the plain comment is not.
		$this->assertSame( 0, $totals['rated_5_out_of_5'] );
		$this->assertSame( 1, $totals['rated_4_out_of_5'] );
	}

	/**
	 * Tests to make sure product reviews cannot be viewed without valid permissions.
	 *
	 * @since 3.5.0
	 */
	public function test_get_reports_without_permission() {
		wp_set_current_user( 0 );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/reports/reviews/totals' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test the product review schema.
	 *
	 * @since 3.5.0
	 */
	public function test_product_review_schema() {
		wp_set_current_user( $this->user );
		$product    = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();
		$request    = new WP_REST_Request( 'OPTIONS', '/wc/v3/reports/reviews/totals' );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 3, count( $properties ) );
		$this->assertArrayHasKey( 'slug', $properties );
		$this->assertArrayHasKey( 'name', $properties );
		$this->assertArrayHasKey( 'total', $properties );
	}
}
