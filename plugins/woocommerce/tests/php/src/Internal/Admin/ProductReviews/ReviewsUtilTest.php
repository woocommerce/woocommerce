<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\ProductReviews;

use Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsUtil;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper;
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

	/**
	 * @testdox      `modify_product_review_moderation_urls` modifies the moderation URLs in email notifications for product reviews.
	 *
	 * @covers       \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsUtil::modify_product_review_moderation_urls()
	 * @dataProvider provider_modify_product_review_moderation_urls
	 *
	 * @param string $post_type  The post type of the comment's post.
	 * @param string $message    The original message.
	 * @param string $expected   The expected modified message.
	 */
	public function test_modify_product_review_moderation_urls( $post_type, $message, $expected ) {
		// Create a test post of the specified type.
		$post_id = wp_insert_post(
			array(
				'post_title'  => 'Test ' . $post_type,
				'post_type'   => $post_type,
				'post_status' => 'publish',
			)
		);

		// Create a comment/review for the post.
		$comment_id = null;
		if ( $post_id ) {
			$comment_id = ProductHelper::create_product_review(
				$post_id,
				'Test review content',
				'0' // Unapproved status.
			);
		}

		// Filter admin_url to return predictable URLs for testing.
		add_filter(
			'admin_url',
			function ( $url, $path ) {
				if ( 'edit.php?post_type=product&page=product-reviews' === $path ) {
					return 'https://example.com/wp-admin/edit.php?post_type=product&page=product-reviews';
				}
				if ( 'edit-comments.php?comment_status=moderated#wpbody-content' === $path ) {
					return 'https://example.com/wp-admin/edit-comments.php?comment_status=moderated#wpbody-content';
				}
				return $url;
			},
			10,
			2
		);

		$result = ReviewsUtil::modify_product_review_moderation_urls( $message, $comment_id );
		$this->assertEquals( $expected, $result );

		// Clean up.
		if ( $comment_id ) {
			wp_delete_comment( $comment_id, true );
		}
		if ( $post_id ) {
			wp_delete_post( $post_id, true );
		}

		// Remove the filter.
		remove_all_filters( 'admin_url' );
	}

	/** @see test_modify_product_review_moderation_urls */
	public function provider_modify_product_review_moderation_urls() {
		$original_message = 'Please moderate: https://example.com/wp-admin/edit-comments.php?comment_status=moderated#wpbody-content';
		$modified_message = 'Please moderate: https://example.com/wp-admin/edit.php?post_type=product&page=product-reviews&comment_status=moderated';

		yield 'Product review comment' => array(
			'post_type' => 'product',
			'message'   => $original_message,
			'expected'  => $modified_message,
		);

		yield 'Non-product review comment' => array(
			'post_type' => 'post',
			'message'   => $original_message,
			'expected'  => $original_message,
		);
	}

	/**
	 * @testdox      `modify_product_review_moderation_urls` does not modify URLs when comment does not exist
	 *
	 * @covers       \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsUtil::modify_product_review_moderation_urls()
	 */
	public function test_modify_product_review_moderation_urls_nonexistent_comment() {
		$original_message = 'Please moderate: https://example.com/wp-admin/edit-comments.php?comment_status=moderated#wpbody-content';

		// Filter admin_url to return predictable URLs for testing.
		add_filter(
			'admin_url',
			function ( $url, $path ) {
				if ( 'edit-comments.php?comment_status=moderated#wpbody-content' === $path ) {
					return 'https://example.com/wp-admin/edit-comments.php?comment_status=moderated#wpbody-content';
				}
				return $url;
			},
			10,
			2
		);

		$result = ReviewsUtil::modify_product_review_moderation_urls( $original_message, 999999 ); // Non-existent comment ID.
		$this->assertEquals( $original_message, $result );

		// Remove the filter.
		remove_all_filters( 'admin_url' );
	}
}
