<?php
/**
 * Tests for the reports reviews totals REST API.
 *
 * @package WooCommerce\Tests\API
 * @since 3.5.0
 */

class WC_Tests_API_Reports_Reviews_Totals extends WC_REST_Unit_Test_Case {

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
	 * Create a comment carrying a rating.
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

		$this->create_rated_comment( $product->get_id(), '5' );
		$this->create_rated_comment( $product->get_id(), '5', array( 'comment_approved' => '0' ) );
		$this->create_rated_comment( $product->get_id(), '5', array( 'comment_approved' => 'spam' ) );
		$this->create_rated_comment( $product->get_id(), '5', array( 'comment_approved' => 'trash' ) );
		$this->create_rated_comment( $product->get_id(), '4', array( 'comment_type' => '' ) );
		$this->create_rated_comment( $product->get_id(), '4', array( 'comment_type' => 'order_note' ) );
		$this->create_rated_comment( $product->get_id(), '2', array( 'comment_type' => 'webhook_delivery' ) );
		$this->create_rated_comment( $product->get_id(), '0' );
		$this->create_rated_comment( $product->get_id(), null );
		$this->create_rated_comment( $page, '3' );

		$totals = $this->get_totals_by_slug();

		// Two five star reviews: the approved one plus the one still awaiting moderation.
		$this->assertSame( 2, $totals['rated_5_out_of_5'] );

		// One four star review: the plain comment counts, the order note does not.
		$this->assertSame( 1, $totals['rated_4_out_of_5'] );

		// Reviews on other post types, hidden comment types, unrated and zero rated comments are all left out.
		$this->assertSame( 0, $totals['rated_3_out_of_5'] );
		$this->assertSame( 0, $totals['rated_2_out_of_5'] );
		$this->assertSame( 0, $totals['rated_1_out_of_5'] );
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
