<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\WooCommerce\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use WP_Query;

/**
 * Renders a product collection block for email.
 */
class Product_Collection extends Abstract_Product_Block_Renderer {
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
		$query = $this->prepare_and_execute_query( $parsed_block, $rendering_context );

		// Get collection type to pass to child blocks.
		$collection_type = $parsed_block['attrs']['collection'] ?? '';

		$content = '';

		foreach ( $parsed_block['innerBlocks'] as $inner_block ) {
			switch ( $inner_block['blockName'] ) {
				case 'woocommerce/product-template':
					$content .= $this->render_product_template( $inner_block, $query, $collection_type );
					break;
				default:
					$content .= render_block( $inner_block );
					break;
			}
		}

		wp_reset_postdata();

		return $content;
	}

	/**
	 * Render the product template block.
	 *
	 * @param array     $inner_block Inner block data.
	 * @param \WP_Query $query WP_Query object.
	 * @param string    $collection_type Collection type identifier.
	 * @return string
	 */
	private function render_product_template( array $inner_block, \WP_Query $query, string $collection_type ): string {
		if ( ! $query->have_posts() ) {
			return $this->render_no_results_message();
		}

		$posts       = $query->get_posts();
		$total_count = count( $posts );

		if ( 0 === $total_count ) {
			return $this->render_no_results_message();
		}

		$products = array_filter(
			array_map(
				function ( $post ) {
					return $post instanceof \WP_Post ? wc_get_product( $post->ID ) : null;
				},
				$posts
			)
		);
		return $this->render_product_grid( $products, $inner_block, $collection_type );
	}

	/**
	 * Render product grid using HTML table structure for email compatibility.
	 *
	 * @param array  $products Array of WC_Product objects.
	 * @param array  $inner_block Inner block data.
	 * @param string $collection_type Collection type identifier.
	 * @return string
	 */
	private function render_product_grid( array $products, array $inner_block, string $collection_type ): string {
		// We start with supporting 1 product per row.
		$content = '';
		foreach ( $products as $product ) {
			$content .= $this->add_spacer(
				$this->render_product_content( $product, $inner_block, $collection_type ),
				$inner_block['email_attrs'] ?? array()
			);
		}

		return $content;
	}

	/**
	 * Render default product content when no inner blocks are present.
	 *
	 * @param \WC_Product|null $product Product object.
	 * @param array            $template_block Inner block data.
	 * @param string           $collection_type Collection type identifier.
	 * @return string
	 */
	private function render_product_content( ?\WC_Product $product, array $template_block, string $collection_type ): string {
		$content = '';

		if ( ! $product ) {
			return $content;
		}

		foreach ( $template_block['innerBlocks'] as $inner_block ) {
			switch ( $inner_block['blockName'] ) {
				case 'woocommerce/product-price':
				case 'woocommerce/product-button':
				case 'woocommerce/product-sale-badge':
				case 'woocommerce/product-image':
					$inner_block['context']               = $inner_block['context'] ?? array();
					$inner_block['context']['postId']     = $product->get_id();
					$inner_block['context']['collection'] = $collection_type;
					$content                             .= render_block( $inner_block );
					break;
				case 'core/post-title':
					global $post;
					$original_post           = $post;
					$original_global_product = $GLOBALS['product'] ?? null;

					$product_post = get_post( $product->get_id() );

					$post               = $product_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					$GLOBALS['product'] = $product;

					$inner_block['context']           = $inner_block['context'] ?? array();
					$inner_block['context']['postId'] = $product->get_id();

					$content .= render_block( $inner_block );

					$post               = $original_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					$GLOBALS['product'] = $original_global_product;
					break;
				default:
					break;
			}
		}

		return $content;
	}

	/**
	 * Prepare and execute a query for the Product Collection block using the original QueryBuilder.
	 *
	 * @param array             $parsed_block Parsed block data.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return WP_Query
	 */
	private function prepare_and_execute_query( array $parsed_block, Rendering_Context $rendering_context ): WP_Query {
		$collection  = $parsed_block['attrs']['collection'] ?? '';
		$query_attrs = $parsed_block['attrs']['query'] ?? array();

		// Build a direct WP_Query for email rendering (not using ProductCollection QueryBuilder).
		// The QueryBuilder is designed for REST/frontend context, not email rendering.
		$query_args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => (int) ( $query_attrs['perPage'] ?? 9 ),
			'orderby'        => sanitize_key( $query_attrs['orderBy'] ?? 'menu_order' ),
			'order'          => sanitize_key( $query_attrs['order'] ?? 'asc' ),
			'meta_query'     => array(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'tax_query'      => array(), // phpcs:ignore WordPress.DB.SlowDBQuery
		);

		// Handle search.
		if ( ! empty( $query_attrs['search'] ) ) {
			$query_args['s'] = sanitize_text_field( (string) $query_attrs['search'] );
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
			$query_args['orderby']  = 'post__in';
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
					'terms'    => array( (int) $featured_query['featured'] ),
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

		// Handle special collections: upsells, cross-sells, related, cart-contents.
		$product_ids_to_include = $this->get_collection_specific_product_ids( $collection, $parsed_block, $rendering_context );
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
	 * Get specific product IDs for collection types that need them (upsell, cross-sell, related, cart-contents).
	 *
	 * @param string            $collection Collection type.
	 * @param array             $parsed_block Parsed block data.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return array Array of product IDs or empty array.
	 */
	private function get_collection_specific_product_ids( string $collection, array $parsed_block, Rendering_Context $rendering_context ): array {
		switch ( $collection ) {
			case 'woocommerce/product-collection/upsells':
				return $this->get_upsell_product_ids( $parsed_block );

			case 'woocommerce/product-collection/cross-sells':
				return $this->get_cross_sell_product_ids( $parsed_block );

			case 'woocommerce/product-collection/related':
				return $this->get_related_product_ids( $parsed_block );

			case 'woocommerce/product-collection/cart-contents':
				return $this->get_cart_contents_product_ids( $parsed_block, $rendering_context );

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
	 * Get cart contents product IDs for email rendering.
	 *
	 * @param array             $parsed_block Parsed block data.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return array Array of cart product IDs or sample products for preview.
	 */
	private function get_cart_contents_product_ids( array $parsed_block, Rendering_Context $rendering_context ): array {
		// Try to get cart product IDs from the user's cart using user ID or email.
		$cart_product_ids = $this->get_user_cart_product_ids_from_context( $rendering_context );

		if ( ! empty( $cart_product_ids ) ) {
			return $cart_product_ids;
		}

		// For preview emails, show sample products so users can see what the email will look like.
		if ( $rendering_context->get( 'is_user_preview', false ) ) {
			return $this->get_sample_product_ids_for_preview();
		}

		// For real emails with empty cart, return -1 to ensure no products are shown.
		return array( -1 );
	}

	/**
	 * Get cart product IDs from rendering context using user ID or email.
	 *
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return array Array of product IDs from the user's cart.
	 */
	private function get_user_cart_product_ids_from_context( Rendering_Context $rendering_context ): array {
		$user_id = $rendering_context->get_user_id();
		$email   = $rendering_context->get_recipient_email();

		// Use shared utility if available (WooCommerce 10.4+).
		if ( class_exists( '\Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils' ) &&
			method_exists( '\Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils', 'get_cart_product_ids_for_user' ) ) {
			// @phpstan-ignore-next-line - Method exists in newer WooCommerce versions.
			return \Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils::get_cart_product_ids_for_user( $user_id, $email );
		}

		// Fallback: Get user ID from email if needed, then fetch cart.
		if ( ! $user_id && $email ) {
			$user = get_user_by( 'email', $email );
			if ( $user ) {
				$user_id = $user->ID;
			}
		}

		if ( ! $user_id ) {
			return array();
		}

		// Fallback implementation for older WooCommerce versions.
		$cart_data = get_user_meta( $user_id, '_woocommerce_persistent_cart_' . get_current_blog_id(), true );

		if ( ! is_array( $cart_data ) || empty( $cart_data ) || ! isset( $cart_data['cart'] ) || ! is_array( $cart_data['cart'] ) ) {
			return array();
		}

		$product_ids = array();

		foreach ( $cart_data['cart'] as $cart_item ) {
			if ( is_array( $cart_item ) && isset( $cart_item['product_id'] ) && is_numeric( $cart_item['product_id'] ) ) {
				$product_ids[] = (int) $cart_item['product_id'];
			}
		}

		return array_unique( $product_ids );
	}

	/**
	 * Get sample product IDs for preview emails.
	 * This ensures that preview emails show representative content even when the cart is empty.
	 *
	 * @return array Array of sample product IDs.
	 */
	private function get_sample_product_ids_for_preview(): array {
		$query = new WP_Query(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => 3,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'fields'         => 'ids',
			)
		);

		if ( ! empty( $query->posts ) && is_array( $query->posts ) ) {
			return array_map(
				static function ( $id ) {
					return is_numeric( $id ) ? (int) $id : 0;
				},
				$query->posts
			);
		}

		return array( -1 );
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
