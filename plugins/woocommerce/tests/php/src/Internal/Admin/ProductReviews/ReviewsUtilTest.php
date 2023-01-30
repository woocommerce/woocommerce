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
	 * @testdox `comments_clauses_without_product_reviews` modifies the comment query clauses to exclude product reviews if the current screen is for the `edit-comments` page.
	 *
	 * @covers \Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsUtil::comments_clauses_without_product_reviews()
	 * @dataProvider provider_can_get_comments_clauses_without_product_reviews
	 *
	 * @param string $current_screen_value The current screen value.
	 * @param string $where_value          The current WHERE clause value.
	 * @param string $expected_join        The expected JOIN value.
	 * @param string $expected_where       The expected WHERE value.
	 */
	public function test_can_get_comments_clauses_without_product_reviews( string $current_screen_value, string $where_value, string $expected_join, string $expected_where ) : void {
		global $wpdb, $current_screen;

		$wpdb = (object) [ 'posts' => 'test_table' ]; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$current_screen = (object) [ 'base' => $current_screen_value ]; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$clauses = ReviewsUtil::comments_clauses_without_product_reviews(
			[
				'join' => '',
				'where' => $where_value,
			]
		);

		$this->assertArrayHasKey( 'join', $clauses );
		$this->assertArrayHasKey( 'where', $clauses );
		$this->assertSame( $expected_join, $clauses['join'] );
		$this->assertSame( $expected_where, $clauses['where'] );
	}

	/** @see test_can_get_comments_clauses_without_product_reviews */
	public function provider_can_get_comments_clauses_without_product_reviews() : Generator {

		$join = ' LEFT JOIN test_table AS wp_posts_to_exclude_reviews ON comment_post_ID = wp_posts_to_exclude_reviews.ID ';
		$where = ' wp_posts_to_exclude_reviews.post_type NOT IN (\'product\') ';

		yield 'Current screen is not edit comments' => [
			'current_screen_value' => 'test-page',
			'where_value' => '',
			'expected_join' => '',
			'expected_where' => '',
		];

		yield 'Where is empty' => [
			'current_screen_value' => 'edit-comments',
			'where_value' => '',
			'expected_join' => $join,
			'expected_where' => $where,
		];

		yield 'Where is not empty' => [
			'current_screen_value' => 'edit-comments',
			'where_value' => 'WHERE 1=1',
			'expected_join' => $join,
			'expected_where' => 'WHERE 1=1 AND ' . $where,
		];
	}

}
