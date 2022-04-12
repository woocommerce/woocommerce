<?php

namespace Automattic\WooCommerce\Internal\Admin;

use WP_Comment_Query;

/**
 * Tweaks the WordPress comments page to exclude reviews.
 */
class ReviewsCommentsOverrides {

	/**
	 * Class instance.
	 *
	 * @var ReviewsCommentsOverrides|null instance
	 */
	protected static $instance;

	/**
	 * Constructor.
	 */
	public function __construct() {

		add_filter( 'comments_list_table_query_args', [ $this, 'exclude_reviews_from_comments' ] );
	}

	/**
	 * Gets the class instance.
	 *
	 * @return object instance
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Excludes product reviews from showing in the comments page.
	 *
	 * @param array $args {@see WP_Comment_Query} query args.
	 * @return array
	 */
	public function exclude_reviews_from_comments( $args ) {

		if ( ! empty( $args['post_type'] ) && 'any' !== $args['post_type'] ) {
			$post_types = (array) $args['post_type'];
		} else {
			$post_types = get_post_types();
		}

		$index = array_search( 'product', $post_types );

		if ( false !== $index ) {
			unset( $post_types[ $index ] );
		}

		$args['post_type'] = $post_types;

		return $args;
	}

}
