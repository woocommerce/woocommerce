<?php
namespace Automattic\WooCommerce\Blocks\Utils;

use WP_Query;

/**
 * BlocksWpQuery query.
 *
 * Wrapper for WP Query with additional helper methods.
 * Allows query args to be set and parsed without doing running it, so that a cache can be used.
 *
 * @deprecated 2.5.0
 */
class BlocksWpQuery extends WP_Query {
	/**
	 * Constructor.
	 *
	 * Sets up the WordPress query, if parameter is not empty.
	 *
	 * Unlike the constructor in WP_Query, this does not RUN the query.
	 *
	 * @param string|array $query URL query string or array of vars.
	 */
	public function __construct( $query = '' ) {
		if ( ! empty( $query ) ) {
			$this->init();

			// Remove current product from query to avoid infinite loops and out of memory issues.
			// That might happen if the store is using Gutenberg for product descriptions.
			// See https://github.com/woocommerce/woocommerce-blocks/issues/6416.
			global $post;
			if ( $post && array_key_exists( 'post__in', $query ) && is_array( $query['post__in'] ) ) {
				$global_post_id    = $post->ID;
				$filtered_post__in = array_filter(
					$query['post__in'],
					static function ( $post_id ) use ( $global_post_id ) {
						if ( $global_post_id === $post_id ) {
							return false;
						}
						return true;
					}
				);
				$query['post__in'] = $filtered_post__in;
			}

			$this->query      = wp_parse_args( $query );
			$this->query_vars = $this->query;
			$this->parse_query_vars();
		}
	}

	/**
	 * Get cached posts, if a cache exists.
	 *
	 * A hash is generated using the array of query_vars. If doing custom queries via filters such as posts_where
	 * (where the SQL query is manipulated directly) you can still ensure there is a unique hash by injecting custom
	 * query vars via the parse_query filter. For example:
	 *
	 *      add_filter( 'parse_query', function( $wp_query ) {
	 *           $wp_query->query_vars['my_custom_query_var'] = true;
	 *      } );
	 *
	 * Doing so won't have any negative effect on the query itself, and it will cause the hash to change.
	 *
	 * @param string $transient_version Transient version to allow for invalidation.
	 * @return WP_Post[]|int[] Array of post objects or post IDs.
	 */
	public function get_cached_posts( $transient_version = '' ) {
		$hash            = md5( wp_json_encode( $this->query_vars ) );
		$transient_name  = 'wc_blocks_query_' . $hash;
		$transient_value = get_transient( $transient_name );

		if ( isset( $transient_value, $transient_value['version'], $transient_value['value'] ) && $transient_value['version'] === $transient_version ) {
			return $transient_value['value'];
		}

		$results = $this->get_posts();

		set_transient(
			$transient_name,
			array(
				'version' => $transient_version,
				'value'   => $results,
			),
			DAY_IN_SECONDS * 30
		);

		return $results;
	}
}
