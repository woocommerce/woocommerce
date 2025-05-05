<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\ProductReviews;

use Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsUtil;
use Generator;
use WC_Unit_Test_Case;

/**
 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsUtil()
 */
class ReviewsUtilTest extends WC_Unit_Test_Case {

	/**
	 * Sets the global vars before each test.
	 */
	public function setUp() : void {
		global $wpdb, $current_screen;

		$this->old_wpdb = $wpdb;
		$this->old_current_screen = $current_screen;

		parent::setUp();
	}

	/**
	 * Restores the global vars after each test.
	 */
	public function tearDown() : void {
		global $wpdb, $current_screen;

		$wpdb = $this->old_wpdb; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$current_screen = $this->old_current_screen; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		parent::tearDown();
	}

	/**
	 * @testdox      `comments_clauses_without_product_reviews` modifies the comment query clauses to exclude product reviews for most queries
	 *                where it can be assumed reviews are not being explicitly requested.
	 *
	 * @covers       \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsUtil::comments_clauses_without_product_reviews()
	 * @dataProvider provider_comments_clauses_without_product_reviews_filter
	 *
	 * @param array $args The query args passed to WP_Comment_Query.
	 * @param bool  $should_exclude_reviews Whether the query should be modified to exclude reviews.
	 *
	 */
	public function test_comments_clauses_without_product_reviews_filter( array $args, bool $should_exclude_reviews ) {
		global $wpdb;

		$join  = " LEFT JOIN {$wpdb->posts} AS wp_posts_to_exclude_reviews ON comment_post_ID = wp_posts_to_exclude_reviews.ID ";
		$where = ' wp_posts_to_exclude_reviews.post_type NOT IN (\'product\') ';
		$query = new \WP_Comment_Query();
		$query->query( $args );
		$sql = $query->request;

		if ( $should_exclude_reviews ) {
			$this->assertStringContainsString( $join, $sql );
			$this->assertStringContainsString( $where, $sql );
		} else {
			$this->assertStringNotContainsString( $join, $sql );
			$this->assertStringNotContainsString( $where, $sql );
		}
	}

	/** @see test_comments_clauses_without_product_reviews_filter */
	public function provider_comments_clauses_without_product_reviews_filter() {
		yield 'Query for product comments' => array(
			'args'                   => array(
				'post_type' => 'product',
			),
			'should_exclude_reviews' => false,
		);

		yield 'Query for product and post comments' => array(
			'args'                   => array(
				'post_type' => 'post,product',
			),
			'should_exclude_reviews' => false,
		);

		yield 'Query for post comments' => array(
			'args'                   => array(
				'post_type' => 'post',
			),
			'should_exclude_reviews' => true,
		);

		yield 'Query by comment ID' => array(
			'args'                   => array(
				'type' => 'comment',
			),
			'should_exclude_reviews' => false,
		);

		yield 'Query by non-Product Post ID' => array(
			'args'                   => array(
				'post_id' => PHP_INT_MAX,
			),
			'should_exclude_reviews' => true,
		);
	}

}
