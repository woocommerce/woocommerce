<?php
/**
 * Product mapper for catalog generation.
 *
 * @package WooCommerce\ProductCatalog
 * @since   10.4.0
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\ProductCatalog;

use Automattic\WooCommerce\ProductCatalog\Interfaces\ProductMapperInterface;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Maps WooCommerce products to catalog format.
 *
 * @package WooCommerce\ProductCatalog
 */
class ProductMapper implements ProductMapperInterface {
	/**
	 * Map WooCommerce product to catalog row
	 *
	 * @param WC_Product $product Product to map.
	 * @return array Mapped product data array.
	 */
	public function map_product( WC_Product $product ): array {
		$row = array(
			'id'                => $this->get_id( $product ),
			'name'              => $this->get_name( $product ),
			'type'              => $this->get_type( $product ),
			'description'       => $this->get_description( $product ),
			'short_description' => $this->get_short_description( $product ),
			'sku'               => $this->get_sku( $product ),
			'global_unique_id'  => $this->get_global_unique_id( $product ),
			'price'             => $this->get_price( $product ),
			'downloadable'      => $this->get_downloadable( $product ),
			'parent_id'         => $this->get_parent_id( $product ),
			'images'            => $this->get_images( $product ),
			'attributes'        => $this->get_attributes( $product ),
			'manage_stock'      => $this->get_manage_stock( $product ),
			'stock_quantity'    => $this->get_stock_quantity( $product ),
			'stock_status'      => $this->get_stock_status( $product ),
		);

		/**
		 * Filter mapped catalog product data.
		 *
		 * @since 1.0.0
		 * @param array      $row     Mapped product data.
		 * @param WC_Product $product Product object.
		 */
		return apply_filters( 'oapfw_map_catalog_product', $row, $product );
	}

	/**
	 * Get product ID
	 *
	 * @param WC_Product $product Product object.
	 * @return int Product ID.
	 */
	protected function get_id( WC_Product $product ): int {
		return $product->get_id();
	}

	/**
	 * Get product name
	 *
	 * @param WC_Product $product Product object.
	 * @return string Product name.
	 */
	protected function get_name( WC_Product $product ): string {
		return wp_strip_all_tags( $product->get_name() );
	}

	/**
	 * Get product type
	 *
	 * @param WC_Product $product Product object.
	 * @return string Product type.
	 */
	protected function get_type( WC_Product $product ): string {
		return $product->get_type();
	}

	/**
	 * Get product description
	 *
	 * @param WC_Product $product Product object.
	 * @return string Product description.
	 */
	protected function get_description( WC_Product $product ): string {
		$description = $product->get_description();
		return $description ? wp_strip_all_tags( $description ) : '';
	}

	/**
	 * Get product short description
	 *
	 * @param WC_Product $product Product object.
	 * @return string Product short description.
	 */
	protected function get_short_description( WC_Product $product ): string {
		$short_description = $product->get_short_description();
		return $short_description ? wp_strip_all_tags( $short_description ) : '';
	}

	/**
	 * Get product SKU
	 *
	 * @param WC_Product $product Product object.
	 * @return string Product SKU.
	 */
	protected function get_sku( WC_Product $product ): string {
		$sku = $product->get_sku();
		return $sku ? $sku : '';
	}

	/**
	 * Get product global unique ID
	 *
	 * @param WC_Product $product Product object.
	 * @return string Global unique ID.
	 */
	protected function get_global_unique_id( WC_Product $product ): string {
		return $product->get_global_unique_id();
	}

	/**
	 * Get product price
	 *
	 * @param WC_Product $product Product object.
	 * @return float Product price.
	 */
	protected function get_price( WC_Product $product ): float {
		$price = $product->get_price();
		return $price ? (float) $price : 0.0;
	}

	/**
	 * Get product downloadable status
	 *
	 * @param WC_Product $product Product object.
	 * @return bool True if downloadable.
	 */
	protected function get_downloadable( WC_Product $product ): bool {
		return $product->is_downloadable();
	}

	/**
	 * Get parent product ID
	 *
	 * @param WC_Product $product Product object.
	 * @return int Parent product ID or 0.
	 */
	protected function get_parent_id( WC_Product $product ): int {
		return $product->is_type( 'variation' ) ? $product->get_parent_id() : 0;
	}

	/**
	 * Get product images
	 * NOTE: from WC_REST_Products_V4_Controller.get_images in core.
	 *
	 * @param WC_Product $product Product object.
	 * @return array Array of image objects matching WooCommerce API schema.
	 */
	protected function get_images( WC_Product $product ): array {
		$images         = array();
		$attachment_ids = array();

		// Add featured image.
		if ( $product->get_image_id() ) {
			$attachment_ids[] = $product->get_image_id();
		}

		// Add gallery images.
		$attachment_ids = array_merge( $attachment_ids, $product->get_gallery_image_ids() );

		// Build image data.
		foreach ( $attachment_ids as $attachment_id ) {
			$attachment_post = get_post( $attachment_id );
			if ( is_null( $attachment_post ) ) {
				continue;
			}

			$attachment = wp_get_attachment_image_src( $attachment_id, 'full' );

			if ( ! is_array( $attachment ) ) {
				continue;
			}
			$thumbnail = wp_get_attachment_image_src( $attachment_id, 'woocommerce_thumbnail' );

			$images[] = array(
				'id'                => (int) $attachment_id,
				'date_created'      => wc_rest_prepare_date_response( $attachment_post->post_date, false ),
				'date_created_gmt'  => wc_rest_prepare_date_response( strtotime( $attachment_post->post_date_gmt ) ),
				'date_modified'     => wc_rest_prepare_date_response( $attachment_post->post_modified, false ),
				'date_modified_gmt' => wc_rest_prepare_date_response( strtotime( $attachment_post->post_modified_gmt ) ),
				'src'               => current( $attachment ),
				'name'              => get_the_title( $attachment_id ),
				'alt'               => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
				'srcset'            => (string) wp_get_attachment_image_srcset( $attachment_id, 'full' ),
				'sizes'             => (string) wp_get_attachment_image_sizes( $attachment_id, 'full' ),
				'thumbnail'         => is_array( $thumbnail ) ? current( $thumbnail ) : '',
			);
		}

		return $images;
	}

	/**
	 * Get product attributes
	 * NOTE: from WC_REST_Products_V1_Controller in core.
	 *
	 * @param WC_Product $product Product object.
	 * @return array Product attributes.
	 */
	protected function get_attributes( WC_Product $product ): array {
		$attributes = array();

		if ( $product->is_type( 'variation' ) ) {
			// Variation attributes.
			foreach ( $product->get_variation_attributes() as $attribute_name => $attribute ) {
				$name = str_replace( 'attribute_', '', $attribute_name );

				if ( ! $attribute ) {
					continue;
				}

				// Taxonomy-based attributes are prefixed with `pa_`, otherwise simply `attribute_`.
				if ( 0 === strpos( $attribute_name, 'attribute_pa_' ) ) {
					$option_term  = get_term_by( 'slug', $attribute, $name );
					$attributes[] = array(
						'id'     => wc_attribute_taxonomy_id_by_name( $name ),
						'name'   => $this->get_attribute_taxonomy_label( $name ),
						'option' => $option_term && ! is_wp_error( $option_term ) ? $option_term->name : $attribute,
					);
				} else {
					$attributes[] = array(
						'id'     => 0,
						'name'   => $name,
						'option' => $attribute,
					);
				}
			}
		} else {
			foreach ( $product->get_attributes() as $attribute ) {
				if ( $attribute['is_taxonomy'] ) {
					$attributes[] = array(
						'id'        => wc_attribute_taxonomy_id_by_name( $attribute['name'] ),
						'name'      => $this->get_attribute_taxonomy_label( $attribute['name'] ),
						'position'  => (int) $attribute['position'],
						'visible'   => (bool) $attribute['is_visible'],
						'variation' => (bool) $attribute['is_variation'],
						'options'   => $this->get_attribute_options( $product->get_id(), $attribute ),
					);
				} else {
					$attributes[] = array(
						'id'        => 0,
						'name'      => $attribute['name'],
						'position'  => (int) $attribute['position'],
						'visible'   => (bool) $attribute['is_visible'],
						'variation' => (bool) $attribute['is_variation'],
						'options'   => $this->get_attribute_options( $product->get_id(), $attribute ),
					);
				}
			}
		}

		return $attributes;
	}

	/**
	 * NOTE: from WC_REST_Products_V1_Controller in core.
	 * Get attribute taxonomy label.
	 *
	 * @param  string $name Taxonomy name.
	 * @return string
	 */
	protected function get_attribute_taxonomy_label( $name ) {
		$tax = get_taxonomy( $name );
		if ( ! $tax ) {
			return '';
		}

		$labels = get_taxonomy_labels( $tax );
		return $labels->singular_name;
	}

	/**
	 * NOTE: from WC_REST_Products_V1_Controller in core.
	 * Get attribute options.
	 *
	 * @param int   $product_id Product ID.
	 * @param array $attribute  Attribute data.
	 * @return array
	 */
	protected function get_attribute_options( $product_id, $attribute ) {
		if ( isset( $attribute['is_taxonomy'] ) && $attribute['is_taxonomy'] ) {
			return wc_get_product_terms( $product_id, $attribute['name'], array( 'fields' => 'names' ) );
		} elseif ( isset( $attribute['value'] ) ) {
			return array_map( 'trim', explode( '|', $attribute['value'] ) );
		}

		return array();
	}

	/**
	 * Get manage stock status
	 *
	 * @param WC_Product $product Product object.
	 * @return bool True if managing stock.
	 */
	protected function get_manage_stock( WC_Product $product ): bool {
		return $product->get_manage_stock();
	}

	/**
	 * Get stock quantity
	 *
	 * @param WC_Product $product Product object.
	 * @return int|null Stock quantity or null.
	 */
	protected function get_stock_quantity( WC_Product $product ): ?int {
		return $product->get_stock_quantity();
	}

	/**
	 * Get stock status
	 *
	 * @param WC_Product $product Product object.
	 * @return string Stock status.
	 */
	protected function get_stock_status( WC_Product $product ): string {
		return $product->get_stock_status();
	}
}
