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

				// Creating the attribute also registers its taxonomy.
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
			}
		}

		return $posts;
	}
}
