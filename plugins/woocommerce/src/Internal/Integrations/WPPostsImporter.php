<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Integrations;

/**
 * Class WPPostsImporter
 *
 * @since 10.1.0
 */
class WPPostsImporter {

	/**
	 * Register the WP Posts importer.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_import_posts', array( $this, 'register_product_attribute_taxonomies' ), 100, 1 );
	}

	/**
	 * Register product attribute taxonomies when importing posts via the WXR importer.
	 *
	 * @since 10.1.0
	 *
	 * @param array $posts The posts to process.
	 * @return array
	 */
	public function register_product_attribute_taxonomies( $posts ) {
		if ( ! is_array( $posts ) || empty( $posts ) ) {
			return $posts;
		}

		foreach ( $posts as $post ) {

			if ( 'product' !== $post['post_type'] || empty( $post['terms'] ) ) {
				continue;
			}

			foreach ( $post['terms'] as $term ) {
				if ( ! strstr( $term['domain'], 'pa_' ) ) {
					continue;
				}

				if ( taxonomy_exists( $term['domain'] ) ) {
					continue;
				}

				$attribute_name = wc_attribute_taxonomy_slug( $term['domain'] );

				// Create the taxonomy.
				if ( ! in_array( $attribute_name, wc_get_attribute_taxonomies(), true ) ) {
					wc_create_attribute(
						array(
							'name'         => $attribute_name,
							'slug'         => $attribute_name,
							'type'         => 'select',
							'order_by'     => 'menu_order',
							'has_archives' => false,
						)
					);
				}

				// Register the taxonomy so that the import works.
				register_taxonomy(
					$term['domain'],
					// phpcs:ignore
					apply_filters( 'woocommerce_taxonomy_objects_' . $term['domain'], array( 'product' ) ),
					// phpcs:ignore
					apply_filters(
						'woocommerce_taxonomy_args_' . $term['domain'],
						array(
							'hierarchical' => true,
							'show_ui'      => false,
							'query_var'    => true,
							'rewrite'      => false,
						)
					)
				);
			}
		}

		return $posts;
	}
}
