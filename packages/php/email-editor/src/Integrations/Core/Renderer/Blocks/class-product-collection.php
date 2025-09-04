<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use WP_Query;

/**
 * Renders a product collection block for email.
 */
class Product_Collection extends Abstract_Block_Renderer {
	/**
	 * Render the product collection block content for email.
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	protected function render_content( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		// Create a query for the Product Collection block.
		$query = $this->prepare_and_execute_query( $parsed_block );

		$content = '';

		foreach ( $parsed_block['innerBlocks'] as $inner_block ) {
			switch ( $inner_block['blockName'] ) {
				case 'woocommerce/product-template':
					$content .= $this->render_product_template( $inner_block, $query, $parsed_block, $rendering_context );
					break;
				default:
					$content .= render_block( $inner_block );
					break;
			}
		}

		// Reset post data.
		wp_reset_postdata();

		return $content;
	}

	/**
	 * Render the product template block.
	 *
	 * @param array             $inner_block Inner block data.
	 * @param \WP_Query         $query WP_Query object.
	 * @param array             $parent_block Parent block data.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	private function render_product_template( array $inner_block, \WP_Query $query, array $parent_block, Rendering_Context $rendering_context ): string {
		if ( ! $query->have_posts() ) {
			return $this->render_no_results_message();
		}

		$display_layout = $parent_block['attrs']['displayLayout'] ?? array(
			'type'    => 'flex',
			'columns' => 3,
		);
		$columns        = max( 1, intval( $display_layout['columns'] ?? 3 ) );

		$products       = $query->get_posts();
		$total_products = count( $products );

		if ( 0 === $total_products ) {
			return $this->render_no_results_message();
		}

		return $this->render_product_grid( $products, $columns, $inner_block, $rendering_context );
	}

	/**
	 * Render product grid using HTML table structure for email compatibility.
	 *
	 * @param array             $products Array of WP_Post objects.
	 * @param int               $columns Number of columns.
	 * @param array             $inner_block Inner block data.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	private function render_product_grid( array $products, int $columns, array $inner_block, Rendering_Context $rendering_context ): string {
		$total_products = count( $products );
		$rows           = ceil( $total_products / $columns );

		$table_style = 'width: 100%; border-collapse: collapse; margin: 0; padding: 0;';
		$cell_width  = floor( 100 / $columns );

		$content = sprintf( '<table style="%s">', esc_attr( $table_style ) );

		for ( $row = 0; $row < $rows; $row++ ) {
			$content .= '<tr>';

			for ( $col = 0; $col < $columns; $col++ ) {
				$product_index = ( $row * $columns ) + $col;

				$cell_style = sprintf(
					'width: %d%%; vertical-align: top; padding: 10px; text-align: center; border: 0;',
					$cell_width
				);

				$content .= sprintf( '<td style="%s">', esc_attr( $cell_style ) );

				if ( $product_index < $total_products ) {
					$product = wc_get_product( $products[ $product_index ]->ID );
					if ( $product ) {
						$content .= $this->render_product_cell_content( $product, $inner_block, $rendering_context );
					}
				} else {
					$content .= '&nbsp;';
				}

				$content .= '</td>';
			}

			$content .= '</tr>';
		}

		$content .= '</table>';

		return $content;
	}

	/**
	 * Render individual product cell content.
	 *
	 * @param \WC_Product       $product Product object.
	 * @param array             $parsed_block Parsed block data.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	private function render_product_cell_content( \WC_Product $product, array $parsed_block, Rendering_Context $rendering_context ): string {
		return $this->render_default_product_content( $product );
	}

	/**
	 * Render default product content when no inner blocks are present.
	 *
	 * @param \WC_Product $product Product object.
	 * @return string
	 */
	private function render_default_product_content( \WC_Product $product ): string {
		$content = '';

		// Product image - using table for email compatibility.
		$image_id = $product->get_image_id();
		if ( $image_id ) {
			$image_url = wp_get_attachment_image_url( (int) $image_id, 'medium' );
			if ( $image_url ) {
				$content .= sprintf(
					'<table width="100%%" style="border-collapse: collapse; margin-bottom: 10px;"><tr><td style="text-align: center; padding: 0;"><img src="%s" alt="%s" style="max-width: 100%%; height: auto; display: block;" /></td></tr></table>',
					esc_url( $image_url ),
					esc_attr( $product->get_name() )
				);
			}
		}

		// Product title - using table for email compatibility.
		$content .= sprintf(
			'<table width="100%%" style="border-collapse: collapse; margin-bottom: 10px;"><tr><td style="text-align: center; padding: 0;"><h3 style="margin: 0; font-size: 16px; font-weight: bold;"><a href="%s" style="color: #333; text-decoration: none;">%s</a></h3></td></tr></table>',
			esc_url( $product->get_permalink() ),
			esc_html( $product->get_name() )
		);

		// Product price - using table for email compatibility.
		$price_html = $product->get_price_html();
		if ( $price_html ) {
			$content .= sprintf(
				'<table width="100%%" style="border-collapse: collapse; margin-bottom: 10px;"><tr><td style="text-align: center; padding: 0; font-size: 14px;">%s</td></tr></table>',
				$price_html
			);
		}

		// Add to cart button - using table for email compatibility.
		$content .= sprintf(
			'<table width="100%%" style="border-collapse: collapse;"><tr><td style="text-align: center; padding: 0;"><a href="%s" style="display: inline-block; padding: 8px 16px; background-color: #0073aa; color: white; text-decoration: none; border-radius: 3px; font-size: 14px;">%s</a></td></tr></table>',
			esc_url( $product->add_to_cart_url() ),
			esc_html__( 'Add to Cart', 'woocommerce' )
		);

		return $content;
	}

	/**
	 * Prepare and execute a query for the Product Collection block using the original QueryBuilder.
	 *
	 * @param array $parsed_block Parsed block data.
	 * @return WP_Query
	 */
	private function prepare_and_execute_query( array $parsed_block ): WP_Query {
		$collection  = $parsed_block['attrs']['collection'] ?? '';
		$query_attrs = $parsed_block['attrs']['query'] ?? array();

		// Build a direct WP_Query for email rendering (not using ProductCollection QueryBuilder).
		// The QueryBuilder is designed for REST/frontend context, not email rendering.
		$query_args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => (int) ( $query_attrs['perPage'] ?? 9 ),
			'orderby'        => $query_attrs['orderBy'] ?? 'menu_order',
			'order'          => $query_attrs['order'] ?? 'asc',
			'meta_query'     => array(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'tax_query'      => array(), // phpcs:ignore WordPress.DB.SlowDBQuery
		);

		// Handle search.
		if ( ! empty( $query_attrs['search'] ) ) {
			$query_args['s'] = (string) $query_attrs['search'];
		}

		// Handle offset.
		if ( isset( $query_attrs['offset'] ) ) {
			$query_args['offset'] = (int) $query_attrs['offset'];
		}

		// Handle exclusions.
		if ( isset( $query_attrs['exclude'] ) && is_array( $query_attrs['exclude'] ) ) {
			$query_args['post__not_in'] = array_map(
				static function ( $id ) {
					return is_numeric( $id ) ? (int) $id : 0;
				},
				$query_attrs['exclude']
			);
		}

		// Handle handpicked products.
		if ( ! empty( $query_attrs['woocommerceHandPickedProducts'] ) ) {
			$query_args['post__in'] = array_map(
				static function ( $id ) {
					return is_numeric( $id ) ? (int) $id : 0;
				},
				$query_attrs['woocommerceHandPickedProducts']
			);
		}

		// Handle featured products - use the WooCommerce way.
		$is_featured = $query_attrs['featured'] ?? false;
		if ( 'woocommerce/product-collection/featured' === $collection || $is_featured ) {
			// Use WooCommerce's built-in function to get featured products query.
			$featured_query = wc_get_product_visibility_term_ids();
			if ( isset( $featured_query['featured'] ) ) {
				$query_args['tax_query'][] = array(
					'taxonomy' => 'product_visibility',
					'field'    => 'term_taxonomy_id',
					'terms'    => array( $featured_query['featured'] ),
					'operator' => 'IN',
				);
			}
		}

		// Handle on-sale products.
		$is_on_sale = $query_attrs['woocommerceOnSale'] ?? false;
		if ( 'woocommerce/product-collection/on-sale' === $collection || $is_on_sale ) {
			$query_args['meta_query'][] = array(
				'relation' => 'OR',
				array(
					'key'     => '_sale_price',
					'value'   => '',
					'compare' => '!=',
				),
			);
		}

		// Handle stock status (only if not all statuses are selected).
		$stock_status = $query_attrs['woocommerceStockStatus'] ?? array();
		if ( ! empty( $stock_status ) && ! $this->is_all_stock_statuses( $stock_status ) ) {
			$query_args['meta_query'][] = array(
				'key'     => '_stock_status',
				'value'   => $stock_status,
				'compare' => 'IN',
			);
		}

		// Handle taxonomies (categories, tags, etc.).
		if ( ! empty( $query_attrs['taxQuery'] ) ) {
			$tax_queries             = $this->build_tax_query( $query_attrs['taxQuery'] );
			$query_args['tax_query'] = array_merge( $query_args['tax_query'], $tax_queries ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		// Handle product attributes.
		if ( ! empty( $query_attrs['woocommerceAttributes'] ) ) {
			$attribute_queries       = $this->build_attribute_query( $query_attrs['woocommerceAttributes'] );
			$query_args['tax_query'] = array_merge( $query_args['tax_query'], $attribute_queries ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		// Handle special collections: upsells, cross-sells, related.
		$product_ids_to_include = $this->get_collection_specific_product_ids( $collection, $parsed_block );
		if ( ! empty( $product_ids_to_include ) ) {
			$query_args['post__in'] = $product_ids_to_include;
		}

		// Set tax_query relation if multiple tax queries.
		if ( count( $query_args['tax_query'] ) > 1 ) {
			$query_args['tax_query']['relation'] = 'AND';
		}

		$wp_query = new WP_Query( $query_args );

		return $wp_query;
	}


	/**
	 * Check if all stock statuses are selected (meaning no filtering needed).
	 *
	 * @param array $stock_status Stock status values from block attributes.
	 * @return bool
	 */
	private function is_all_stock_statuses( array $stock_status ): bool {
		if ( empty( $stock_status ) ) {
			return true; // Empty means all statuses.
		}

		$all_stock_statuses = array_keys( wc_get_product_stock_status_options() );
		return count( $stock_status ) === count( $all_stock_statuses ) &&
			count( array_diff( $stock_status, $all_stock_statuses ) ) === 0 &&
			count( array_diff( $all_stock_statuses, $stock_status ) ) === 0;
	}

	/**
	 * Build tax query from taxQuery block attributes.
	 *
	 * @param array $tax_query_input Tax query input from block attributes.
	 * @return array
	 */
	private function build_tax_query( array $tax_query_input ): array {
		$tax_queries = array();

		if ( empty( $tax_query_input ) ) {
			return $tax_queries;
		}

		$first_key = array_key_first( $tax_query_input );
		// If not a numeric array of clauses, assume object map: { taxonomy => [termIds] }.
		if ( ! is_int( $first_key ) ) {
			foreach ( $tax_query_input as $taxonomy => $terms ) {
				if ( ! empty( $terms ) ) {
					$tax_queries[] = array(
						'taxonomy' => $taxonomy,
						'field'    => 'term_id',
						'terms'    => array_map(
							static function ( $id ) {
								return is_numeric( $id ) ? (int) $id : 0;
							},
							(array) $terms
						),
					);
				}
			}
		} else {
			$tax_queries = $tax_query_input;
		}

		return $tax_queries;
	}

	/**
	 * Build attribute query from woocommerceAttributes block attributes.
	 *
	 * @param array $attributes Attribute filters from block attributes.
	 * @return array
	 */
	private function build_attribute_query( array $attributes ): array {
		$attribute_queries = array();

		foreach ( $attributes as $attribute ) {
			if ( ! empty( $attribute['taxonomy'] ) && ! empty( $attribute['termId'] ) ) {
				$attribute_queries[] = array(
					'taxonomy' => $attribute['taxonomy'],
					'field'    => 'term_id',
					'terms'    => array( (int) $attribute['termId'] ),
				);
			}
		}

		return $attribute_queries;
	}

	/**
	 * Get specific product IDs for collection types that need them (upsell, cross-sell, related).
	 *
	 * @param string $collection Collection type.
	 * @param array  $parsed_block Parsed block data.
	 * @return array Array of product IDs or empty array.
	 */
	private function get_collection_specific_product_ids( string $collection, array $parsed_block ): array {
		switch ( $collection ) {
			case 'woocommerce/product-collection/upsells':
				return $this->get_upsell_product_ids( $parsed_block );

			case 'woocommerce/product-collection/cross-sells':
				return $this->get_cross_sell_product_ids( $parsed_block );

			case 'woocommerce/product-collection/related':
				return $this->get_related_product_ids( $parsed_block );

			default:
				return array();
		}
	}

	/**
	 * Get upsell product IDs.
	 *
	 * @param array $parsed_block Parsed block data.
	 * @return array Array of upsell product IDs.
	 */
	private function get_upsell_product_ids( array $parsed_block ): array {
		$product_references = $this->get_product_references_for_collection( $parsed_block );

		if ( empty( $product_references ) ) {
			return array( -1 ); // Return -1 to ensure no products are found.
		}

		$products = array_filter( array_map( 'wc_get_product', $product_references ) );

		if ( empty( $products ) ) {
			return array( -1 );
		}

		$all_upsells = array();
		foreach ( $products as $product ) {
			$all_upsells = array_merge( $all_upsells, $product->get_upsell_ids() );
		}

		// Remove duplicates and product references (don't show what's already in context).
		$unique_upsells = array_unique( $all_upsells );
		$upsells        = array_diff( $unique_upsells, $product_references );

		return ! empty( $upsells ) ? $upsells : array( -1 );
	}

	/**
	 * Get cross-sell product IDs.
	 *
	 * @param array $parsed_block Parsed block data.
	 * @return array Array of cross-sell product IDs.
	 */
	private function get_cross_sell_product_ids( array $parsed_block ): array {
		$product_references = $this->get_product_references_for_collection( $parsed_block );

		if ( empty( $product_references ) ) {
			return array( -1 ); // Return -1 to ensure no products are found.
		}

		$products = array_filter( array_map( 'wc_get_product', $product_references ) );

		if ( empty( $products ) ) {
			return array( -1 );
		}

		$product_ids = array_map(
			function ( $product ) {
				return $product->get_id();
			},
			$products
		);

		$all_cross_sells = array();
		foreach ( $products as $product ) {
			$all_cross_sells = array_merge( $all_cross_sells, $product->get_cross_sell_ids() );
		}

		// Remove duplicates and product references (don't show what's already in context).
		$unique_cross_sells = array_unique( $all_cross_sells );
		$cross_sells        = array_diff( $unique_cross_sells, $product_ids );

		return ! empty( $cross_sells ) ? $cross_sells : array( -1 );
	}

	/**
	 * Get related product IDs.
	 *
	 * @param array $parsed_block Parsed block data.
	 * @return array Array of related product IDs.
	 */
	private function get_related_product_ids( array $parsed_block ): array {
		$product_references = $this->get_product_references_for_collection( $parsed_block );

		if ( empty( $product_references ) ) {
			return array( -1 ); // Return -1 to ensure no products are found.
		}

		// For related products, we only use the first product reference.
		$product_reference = $product_references[0];

		if ( empty( $product_reference ) ) {
			return array( -1 );
		}

		// Get related products using WooCommerce's built-in function.
		$related_ids = wc_get_related_products( $product_reference, 100 );
		return ! empty( $related_ids ) ? $related_ids : array( -1 );
	}

	/**
	 * Get product references for collections (handles different contexts).
	 *
	 * @param array $parsed_block Parsed block data.
	 * @return array Array of product IDs or empty array.
	 */
	private function get_product_references_for_collection( array $parsed_block ): array {
		$query_attrs        = $parsed_block['attrs']['query'] ?? array();
		$product_references = array();

		// First try to get from productReference in query attributes.
		if ( ! empty( $query_attrs['productReference'] ) ) {
			$product_references = array( (int) $query_attrs['productReference'] );
		}

		// If no product reference found, try to get from global context.
		if ( empty( $product_references ) ) {
			global $product;
			if ( $product && is_a( $product, 'WC_Product' ) ) {
				$product_references = array( $product->get_id() );
			}
		}

		// In email context, we might need additional context sources.
		// This could be extended based on email type (order confirmation, etc.).

		return $product_references;
	}

	/**
	 * Render a no results message.
	 *
	 * @return string
	 */
	private function render_no_results_message(): string {
		return sprintf(
			'<div style="text-align: center; padding: 20px; color: #666;">%s</div>',
			esc_html__( 'No products found.', 'woocommerce' )
		);
	}
}
