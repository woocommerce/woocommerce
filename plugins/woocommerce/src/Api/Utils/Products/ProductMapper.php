<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Utils\Products;

use Automattic\WooCommerce\Api\Enums\Products\ProductStatus;
use Automattic\WooCommerce\Api\Enums\Products\ProductType;
use Automattic\WooCommerce\Api\Enums\Products\StockStatus;
use Automattic\WooCommerce\Api\Pagination\Connection;
use Automattic\WooCommerce\Api\Pagination\Edge;
use Automattic\WooCommerce\Api\Pagination\PageInfo;
use Automattic\WooCommerce\Api\Types\Products\ExternalProduct;
use Automattic\WooCommerce\Api\Types\Products\ProductAttribute;
use Automattic\WooCommerce\Api\Types\Products\ProductDimensions;
use Automattic\WooCommerce\Api\Types\Products\ProductImage;
use Automattic\WooCommerce\Api\Types\Products\ProductReview;
use Automattic\WooCommerce\Api\Types\Products\ProductVariation;
use Automattic\WooCommerce\Api\Types\Products\SelectedAttribute;
use Automattic\WooCommerce\Api\Types\Products\SimpleProduct;
use Automattic\WooCommerce\Api\Types\Products\VariableProduct;

/**
 * Maps a WC_Product to the appropriate product DTO.
 */
class ProductMapper {
	/**
	 * Map a WC_Product to the appropriate product DTO based on its type.
	 *
	 * @param \WC_Product $wc_product The WooCommerce product object.
	 * @param ?array      $query_info Unified query info tree from the GraphQL request.
	 * @return object
	 */
	public static function from_wc_product(
		\WC_Product $wc_product,
		?array $query_info = null,
	): object {
		$product = match ( $wc_product->get_type() ) {
			'external'  => self::build_external_product( $wc_product ),
			'variable'  => self::build_variable_product( $wc_product, $query_info ),
			'variation' => self::build_product_variation( $wc_product ),
			default     => new SimpleProduct(),
		};

		self::populate_common_fields( $product, $wc_product, $query_info );

		return $product;
	}

	/**
	 * Build an ExternalProduct with type-specific fields.
	 *
	 * @param \WC_Product $wc_product The external product.
	 * @return ExternalProduct
	 */
	private static function build_external_product( \WC_Product $wc_product ): ExternalProduct {
		$product = new ExternalProduct();

		$url                  = $wc_product->get_product_url();
		$product->product_url = ! empty( $url ) ? $url : null;
		$text                 = $wc_product->get_button_text();
		$product->button_text = ! empty( $text ) ? $text : null;

		return $product;
	}

	/**
	 * Build a VariableProduct with type-specific fields.
	 *
	 * @param \WC_Product $wc_product The variable product.
	 * @param ?array      $query_info Unified query info tree from the GraphQL request.
	 * @return VariableProduct
	 */
	private static function build_variable_product( \WC_Product $wc_product, ?array $query_info = null ): VariableProduct {
		$product = new VariableProduct();

		$child_ids = $wc_product->get_children();
		$edges     = array();
		$nodes     = array();

		foreach ( $child_ids as $child_id ) {
			$child_product = wc_get_product( $child_id );
			if ( ! $child_product ) {
				continue;
			}

			$variation = self::from_wc_product( $child_product );

			$edge         = new Edge();
			$edge->cursor = base64_encode( (string) $child_id );
			$edge->node   = $variation;

			$edges[] = $edge;
			$nodes[] = $variation;
		}

		$page_info                    = new PageInfo();
		$page_info->has_next_page     = false;
		$page_info->has_previous_page = false;
		$page_info->start_cursor      = ! empty( $edges ) ? $edges[0]->cursor : null;
		$page_info->end_cursor        = ! empty( $edges ) ? $edges[ count( $edges ) - 1 ]->cursor : null;

		$connection              = new Connection();
		$connection->edges       = $edges;
		$connection->nodes       = $nodes;
		$connection->page_info   = $page_info;
		$connection->total_count = count( $nodes );

		// Explicitly slice the variations connection if pagination args were provided.
		// The auto-generated resolver's subsequent slice() call becomes a no-op
		// because Connection marks itself as sliced after the first slice.
		if ( null !== $query_info ) {
			$pagination_args = $query_info['...VariableProduct']['variations']['__args']
				?? $query_info['variations']['__args']
				?? array();
			$connection      = $connection->slice( $pagination_args );
		}

		$product->variations = $connection;

		return $product;
	}

	/**
	 * Build a ProductVariation with type-specific fields.
	 *
	 * @param \WC_Product $wc_product The variation product.
	 * @return ProductVariation
	 */
	private static function build_product_variation( \WC_Product $wc_product ): ProductVariation {
		$product            = new ProductVariation();
		$product->parent_id = $wc_product->get_parent_id();

		$selected_attributes = array();
		foreach ( $wc_product->get_attributes() as $taxonomy => $value ) {
			$attr       = new SelectedAttribute();
			$attr->name = $taxonomy;

			// For taxonomy attributes, resolve the slug to a human-readable term name.
			if ( taxonomy_exists( $taxonomy ) && ! empty( $value ) ) {
				$term = get_term_by( 'slug', $value, $taxonomy );
				if ( $term && ! is_wp_error( $term ) ) {
					$attr->value = $term->name;
				} else {
					$attr->value = $value;
				}
			} else {
				$attr->value = $value;
			}

			$selected_attributes[] = $attr;
		}
		$product->selected_attributes = $selected_attributes;

		return $product;
	}

	/**
	 * Populate the common fields shared by all product types.
	 *
	 * @param object      $product    The product DTO to populate.
	 * @param \WC_Product $wc_product The WooCommerce product object.
	 * @param ?array      $query_info Unified query info tree from the GraphQL request.
	 */
	private static function populate_common_fields(
		object $product,
		\WC_Product $wc_product,
		?array $query_info,
	): void {
		$product->id                = $wc_product->get_id();
		$product->name              = $wc_product->get_name();
		$product->slug              = $wc_product->get_slug();
		$sku                        = $wc_product->get_sku();
		$product->sku               = '' !== $sku ? $sku : null;
		$product->description       = $wc_product->get_description();
		$product->short_description = $wc_product->get_short_description();
		$product->status            = ProductStatus::from( $wc_product->get_status() );
		$product->product_type      = ProductType::from( $wc_product->get_type() );

		// Price fields support a "formatted" argument for currency display.
		$format_regular         = $query_info['regular_price']['__args']['formatted'] ?? true;
		$product->regular_price = $format_regular
			? wc_price( (float) $wc_product->get_regular_price() )
			: $wc_product->get_regular_price();

		$format_sale = $query_info['sale_price']['__args']['formatted'] ?? true;
		$raw_sale    = $wc_product->get_sale_price();
		if ( '' === $raw_sale ) {
			$product->sale_price = null;
		} else {
			$product->sale_price = $format_sale
				? wc_price( (float) $raw_sale )
				: $raw_sale;
		}

		$product->stock_status   = self::map_stock_status( $wc_product->get_stock_status() );
		$product->stock_quantity = $wc_product->get_stock_quantity();

		// Nested output type: dimensions.
		$product->dimensions = self::build_dimensions( $wc_product );

		// Array of objects: images.
		$product->images = self::build_images( $wc_product );

		// Array of objects: attributes.
		$product->attributes = self::build_attributes( $wc_product );

		// Sub-collection connection: reviews.
		// Only populate if explicitly requested (optimization via $query_info).
		if ( null === $query_info || array_key_exists( 'reviews', $query_info ) ) {
			$product->reviews = self::build_reviews( $wc_product->get_id() );
		} else {
			$product->reviews = self::empty_connection();
		}

		$product->date_created  = $wc_product->get_date_created()?->format( \DateTimeInterface::ATOM );
		$product->date_modified = $wc_product->get_date_modified()?->format( \DateTimeInterface::ATOM );

		// Ignored field — set to null; it won't appear in the schema.
		$product->internal_notes = null;
	}

	/**
	 * Map WooCommerce stock status string to the int-backed StockStatus enum.
	 *
	 * @param string $wc_status The WC stock status string.
	 * @return StockStatus
	 */
	private static function map_stock_status( string $wc_status ): StockStatus {
		return match ( $wc_status ) {
			'instock'     => StockStatus::InStock,
			'outofstock'  => StockStatus::OutOfStock,
			'onbackorder' => StockStatus::OnBackorder,
			default       => StockStatus::OutOfStock,
		};
	}

	/**
	 * Build product dimensions from a WC_Product.
	 *
	 * @param \WC_Product $wc_product The product.
	 * @return ?ProductDimensions
	 */
	private static function build_dimensions( \WC_Product $wc_product ): ?ProductDimensions {
		$length = $wc_product->get_length();
		$width  = $wc_product->get_width();
		$height = $wc_product->get_height();
		$weight = $wc_product->get_weight();

		if ( '' === $length && '' === $width && '' === $height && '' === $weight ) {
			return null;
		}

		$dims         = new ProductDimensions();
		$dims->length = '' !== $length ? (float) $length : null;
		$dims->width  = '' !== $width ? (float) $width : null;
		$dims->height = '' !== $height ? (float) $height : null;
		$dims->weight = '' !== $weight ? (float) $weight : null;

		return $dims;
	}

	/**
	 * Build product images from a WC_Product.
	 *
	 * @param \WC_Product $wc_product The product.
	 * @return ProductImage[]
	 */
	private static function build_images( \WC_Product $wc_product ): array {
		$images   = array();
		$position = 0;

		// Include the featured image first.
		$featured_id = $wc_product->get_image_id();
		if ( $featured_id ) {
			$image = self::build_image( (int) $featured_id, $position );
			if ( null !== $image ) {
				$images[] = $image;
				++$position;
			}
		}

		// Then gallery images.
		foreach ( $wc_product->get_gallery_image_ids() as $image_id ) {
			$image = self::build_image( (int) $image_id, $position );
			if ( null !== $image ) {
				$images[] = $image;
				++$position;
			}
		}

		return $images;
	}

	/**
	 * Build product attributes from a WC_Product.
	 *
	 * For variations, attributes are simple key→value pairs (handled by selected_attributes),
	 * so this returns an empty array. For other product types, it returns full attribute definitions.
	 *
	 * @param \WC_Product $wc_product The product.
	 * @return ProductAttribute[]
	 */
	private static function build_attributes( \WC_Product $wc_product ): array {
		// Variations store attributes as simple string values, not WC_Product_Attribute objects.
		if ( 'variation' === $wc_product->get_type() ) {
			return array();
		}

		$attributes = array();
		foreach ( $wc_product->get_attributes() as $wc_attr ) {
			if ( ! $wc_attr instanceof \WC_Product_Attribute ) {
				continue;
			}

			$attr       = new ProductAttribute();
			$attr->slug = $wc_attr->get_name();

			if ( $wc_attr->is_taxonomy() ) {
				$attr->name    = wc_attribute_label( $wc_attr->get_name() );
				$attr->options = array_map(
					function ( $term ) {
						return $term->name;
					},
					$wc_attr->get_terms() ? $wc_attr->get_terms() : array()
				);
			} else {
				$attr->name    = $wc_attr->get_name();
				$attr->options = $wc_attr->get_options();
			}

			$attr->position    = $wc_attr->get_position();
			$attr->visible     = $wc_attr->get_visible();
			$attr->variation   = $wc_attr->get_variation();
			$attr->is_taxonomy = $wc_attr->is_taxonomy();

			$attributes[] = $attr;
		}//end foreach

		return $attributes;
	}

	/**
	 * Build a single ProductImage from an attachment ID.
	 *
	 * @param int $attachment_id The WordPress attachment ID.
	 * @param int $position      The display position.
	 * @return ?ProductImage
	 */
	private static function build_image( int $attachment_id, int $position ): ?ProductImage {
		$url = wp_get_attachment_url( $attachment_id );
		if ( ! $url ) {
			return null;
		}

		$image           = new ProductImage();
		$image->id       = $attachment_id;
		$image->url      = $url;
		$alt             = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
		$image->alt      = ! empty( $alt ) ? $alt : '';
		$image->position = $position;

		return $image;
	}

	/**
	 * Build a reviews connection for a product.
	 *
	 * @param int $product_id The product ID.
	 * @return Connection
	 */
	private static function build_reviews( int $product_id ): Connection {
		$comments = get_comments(
			array(
				'post_id' => $product_id,
				'type'    => 'review',
				'status'  => 'approve',
				'orderby' => 'comment_date',
				'order'   => 'DESC',
				'number'  => 10,
			)
		);

		$edges = array();
		$nodes = array();

		foreach ( $comments as $comment ) {
			$review               = new ProductReview();
			$review->id           = (int) $comment->comment_ID;
			$review->product_id   = $product_id;
			$review->reviewer     = $comment->comment_author;
			$review->review       = $comment->comment_content;
			$review->rating       = (int) get_comment_meta( $comment->comment_ID, 'rating', true );
			$review->date_created = $comment->comment_date_gmt
				? ( new \DateTimeImmutable( $comment->comment_date_gmt, new \DateTimeZone( 'UTC' ) ) )->format( \DateTimeInterface::ATOM )
				: null;

			$edge         = new Edge();
			$edge->cursor = base64_encode( (string) $review->id );
			$edge->node   = $review;

			$edges[] = $edge;
			$nodes[] = $review;
		}

		$page_info                    = new PageInfo();
		$page_info->has_next_page     = count( $comments ) >= 10;
		$page_info->has_previous_page = false;
		$page_info->start_cursor      = ! empty( $edges ) ? $edges[0]->cursor : null;
		$page_info->end_cursor        = ! empty( $edges ) ? $edges[ count( $edges ) - 1 ]->cursor : null;

		$connection              = new Connection();
		$connection->edges       = $edges;
		$connection->nodes       = $nodes;
		$connection->page_info   = $page_info;
		$connection->total_count = count( $comments );

		return $connection;
	}

	/**
	 * Return an empty connection (for skipped sub-collections).
	 *
	 * @return Connection
	 */
	private static function empty_connection(): Connection {
		$page_info                    = new PageInfo();
		$page_info->has_next_page     = false;
		$page_info->has_previous_page = false;
		$page_info->start_cursor      = null;
		$page_info->end_cursor        = null;

		$connection              = new Connection();
		$connection->edges       = array();
		$connection->nodes       = array();
		$connection->page_info   = $page_info;
		$connection->total_count = 0;

		return $connection;
	}
}
