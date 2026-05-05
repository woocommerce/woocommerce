<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\ProductViews;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Adds a sortable "Views" column to the admin Products list table.
 *
 * @since 10.8.0
 */
class ProductViewCountAdminList implements RegisterHooksInterface {

	/**
	 * Register WordPress hooks.
	 *
	 * @since 10.8.0
	 * @return void
	 */
	public function register(): void {
		add_filter( 'manage_product_posts_columns', array( $this, 'add_views_column' ) );
		add_action( 'manage_product_posts_custom_column', array( $this, 'render_views_column' ), 10, 2 );
		add_filter( 'manage_edit-product_sortable_columns', array( $this, 'register_sortable_column' ) );
		add_action( 'pre_get_posts', array( $this, 'handle_orderby' ) );
	}

	/**
	 * Insert a "Views" column before the "Date" column.
	 *
	 * @since 10.8.0
	 * @param string[] $columns Existing column definitions.
	 * @return string[]
	 */
	public function add_views_column( array $columns ): array {
		$reordered = array();

		foreach ( $columns as $key => $label ) {
			if ( 'date' === $key ) {
				$reordered['view_count'] = __( 'Views', 'woocommerce' );
			}
			$reordered[ $key ] = $label;
		}

		if ( ! isset( $reordered['view_count'] ) ) {
			$reordered['view_count'] = __( 'Views', 'woocommerce' );
		}

		return $reordered;
	}

	/**
	 * Output the view count value for a given product row.
	 *
	 * @since 10.8.0
	 * @param string $column  Column slug.
	 * @param int    $post_id Product post ID.
	 * @return void
	 */
	public function render_views_column( string $column, int $post_id ): void {
		if ( 'view_count' !== $column ) {
			return;
		}

		$count = (int) get_post_meta( $post_id, ProductViewCountTracker::META_KEY, true );
		echo esc_html( number_format_i18n( $count ) );
	}

	/**
	 * Register "Views" as a sortable column.
	 *
	 * @since 10.8.0
	 * @param string[] $columns Existing sortable columns.
	 * @return string[]
	 */
	public function register_sortable_column( array $columns ): array {
		$columns['view_count'] = 'view_count';
		return $columns;
	}

	/**
	 * Modify the query when the admin list is sorted by view count.
	 *
	 * @since 10.8.0
	 * @param \WP_Query $query The current WP_Query instance.
	 * @return void
	 */
	public function handle_orderby( \WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( 'view_count' !== $query->get( 'orderby' ) ) {
			return;
		}

		$query->set( 'meta_key', ProductViewCountTracker::META_KEY );
		$query->set( 'orderby', 'meta_value_num' );
	}
}
