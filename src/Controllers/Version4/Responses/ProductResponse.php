<?php
/**
 * Convert a product object to the product schema format.
 *
 * @package WooCommerce/RestApi
 */

namespace WooCommerce\RestApi\Controllers\Version4\Responses;

defined( 'ABSPATH' ) || exit;

/**
 * ProductResponse class.
 */
class ProductResponse extends AbstractObjectResponse {

	/**
	 * Convert object to match data in the schema.
	 *
	 * @param \WC_Product_Simple|\WC_Product_Grouped|\WC_Product_Variable|\WC_Product_External $object Product data.
	 * @param string                                                                           $context Request context. Options: 'view' and 'edit'.
	 * @return array
	 */
	public function prepare_response( $object, $context ) {
		$data = array(
			'id'                    => $object->get_id(),
			'name'                  => $object->get_name( $context ),
			'slug'                  => $object->get_slug( $context ),
			'permalink'             => $object->get_permalink(),
			'date_created'          => wc_rest_prepare_date_response( $object->get_date_created( $context ), false ),
			'date_created_gmt'      => wc_rest_prepare_date_response( $object->get_date_created( $context ) ),
			'date_modified'         => wc_rest_prepare_date_response( $object->get_date_modified( $context ), false ),
			'date_modified_gmt'     => wc_rest_prepare_date_response( $object->get_date_modified( $context ) ),
			'type'                  => $object->get_type(),
			'status'                => $object->get_status( $context ),
			'featured'              => $object->is_featured(),
			'catalog_visibility'    => $object->get_catalog_visibility( $context ),
			'description'           => $object->get_description( $context ),
			'short_description'     => $object->get_short_description( $context ),
			'sku'                   => $object->get_sku( $context ),
			'price'                 => $object->get_price( $context ),
			'regular_price'         => $object->get_regular_price( $context ),
			'sale_price'            => $object->get_sale_price( $context ) ? $object->get_sale_price( $context ) : '',
			'date_on_sale_from'     => wc_rest_prepare_date_response( $object->get_date_on_sale_from( $context ), false ),
			'date_on_sale_from_gmt' => wc_rest_prepare_date_response( $object->get_date_on_sale_from( $context ) ),
			'date_on_sale_to'       => wc_rest_prepare_date_response( $object->get_date_on_sale_to( $context ), false ),
			'date_on_sale_to_gmt'   => wc_rest_prepare_date_response( $object->get_date_on_sale_to( $context ) ),
			'price_html'            => $object->get_price_html(),
			'on_sale'               => $object->is_on_sale( $context ),
			'purchasable'           => $object->is_purchasable(),
			'total_sales'           => $object->get_total_sales( $context ),
			'virtual'               => $object->is_virtual(),
			'downloadable'          => $object->is_downloadable(),
			'downloads'             => $this->prepare_downloads( $object ),
			'download_limit'        => $object->get_download_limit( $context ),
			'download_expiry'       => $object->get_download_expiry( $context ),
			'external_url'          => '',
			'button_text'           => '',
			'tax_status'            => $object->get_tax_status( $context ),
			'tax_class'             => $object->get_tax_class( $context ),
			'manage_stock'          => $object->managing_stock(),
			'stock_quantity'        => $object->get_stock_quantity( $context ),
			'stock_status'          => $object->get_stock_status( $context ),
			'backorders'            => $object->get_backorders( $context ),
			'backorders_allowed'    => $object->backorders_allowed(),
			'backordered'           => $object->is_on_backorder(),
			'sold_individually'     => $object->is_sold_individually(),
			'weight'                => $object->get_weight( $context ),
			'dimensions'            => array(
				'length' => $object->get_length( $context ),
				'width'  => $object->get_width( $context ),
				'height' => $object->get_height( $context ),
			),
			'shipping_required'     => $object->needs_shipping(),
			'shipping_taxable'      => $object->is_shipping_taxable(),
			'shipping_class'        => $object->get_shipping_class(),
			'shipping_class_id'     => $object->get_shipping_class_id( $context ),
			'reviews_allowed'       => $object->get_reviews_allowed( $context ),
			'average_rating'        => $object->get_average_rating( $context ),
			'rating_count'          => $object->get_rating_count(),
			'related_ids'           => wp_parse_id_list( wc_get_related_products( $object->get_id() ) ),
			'upsell_ids'            => wp_parse_id_list( $object->get_upsell_ids( $context ) ),
			'cross_sell_ids'        => wp_parse_id_list( $object->get_cross_sell_ids( $context ) ),
			'parent_id'             => $object->get_parent_id( $context ),
			'purchase_note'         => $object->get_purchase_note( $context ),
			'categories'            => $this->prepare_taxonomy_terms( $object ),
			'tags'                  => $this->prepare_taxonomy_terms( $object, 'tag' ),
			'images'                => $this->prepare_images( $object ),
			'attributes'            => $this->prepare_attributes( $object ),
			'default_attributes'    => $this->prepare_default_attributes( $object ),
			'variations'            => array(),
			'grouped_products'      => array(),
			'menu_order'            => $object->get_menu_order( $context ),
			'meta_data'             => $object->get_meta_data(),
		);

		// Add variations to variable products.
		if ( $object->is_type( 'variable' ) ) {
			$data['variations'] = $object->get_children();
		}

		// Add grouped products data.
		if ( $object->is_type( 'grouped' ) ) {
			$data['grouped_products'] = $object->get_children();
		}

		// Add external product data.
		if ( $object->is_type( 'external' ) ) {
			$data['external_url'] = $object->get_product_url( $context );
			$data['button_text']  = $object->get_button_text( $context );
		}

		if ( 'view' === $context ) {
			$data['description']       = wpautop( do_shortcode( $data['description'] ) );
			$data['short_description'] = apply_filters( 'woocommerce_short_description', $data['short_description'] );
			$data['average_rating']    = wc_format_decimal( $data['average_rating'], 2 );
			$data['purchase_note']     = wpautop( do_shortcode( $data['purchase_note'] ) );
		}

		return $data;
	}

	/**
	 * Get the downloads for a product or product variation.
	 *
	 * @param \WC_Product|\WC_Product_Variation $object Product instance.
	 *
	 * @return array
	 */
	protected function prepare_downloads( $object ) {
		$downloads = array();

		if ( $object->is_downloadable() ) {
			foreach ( $object->get_downloads() as $file_id => $file ) {
				$downloads[] = array(
					'id'   => $file_id, // MD5 hash.
					'name' => $file['name'],
					'file' => $file['file'],
				);
			}
		}

		return $downloads;
	}

	/**
	 * Get taxonomy terms.
	 *
	 * @param \WC_Product $object  Product instance.
	 * @param string      $taxonomy Taxonomy slug.
	 *
	 * @return array
	 */
	protected function prepare_taxonomy_terms( $object, $taxonomy = 'cat' ) {
		$terms = array();

		foreach ( wc_get_object_terms( $object->get_id(), 'product_' . $taxonomy ) as $term ) {
			$terms[] = array(
				'id'   => $term->term_id,
				'name' => $term->name,
				'slug' => $term->slug,
			);
		}

		return $terms;
	}

	/**
	 * Get the images for a product or product variation.
	 *
	 * @param \WC_Product|\WC_Product_Variation $object Product instance.
	 * @return array
	 */
	protected function prepare_images( $object ) {
		$images         = array();
		$attachment_ids = array();

		// Add featured image.
		if ( $object->get_image_id() ) {
			$attachment_ids[] = $object->get_image_id();
		}

		// Add gallery images.
		$attachment_ids = array_merge( $attachment_ids, $object->get_gallery_image_ids() );

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

			$images[] = array(
				'id'                => (int) $attachment_id,
				'date_created'      => wc_rest_prepare_date_response( $attachment_post->post_date, false ),
				'date_created_gmt'  => wc_rest_prepare_date_response( strtotime( $attachment_post->post_date_gmt ) ),
				'date_modified'     => wc_rest_prepare_date_response( $attachment_post->post_modified, false ),
				'date_modified_gmt' => wc_rest_prepare_date_response( strtotime( $attachment_post->post_modified_gmt ) ),
				'src'               => current( $attachment ),
				'name'              => get_the_title( $attachment_id ),
				'alt'               => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
			);
		}

		return $images;
	}

	/**
	 * Get default attributes.
	 *
	 * @param \WC_Product $object Product instance.
	 *
	 * @return array
	 */
	protected function prepare_default_attributes( $object ) {
		$default = array();

		if ( $object->is_type( 'variable' ) ) {
			foreach ( array_filter( (array) $object->get_default_attributes(), 'strlen' ) as $key => $value ) {
				if ( 0 === strpos( $key, 'pa_' ) ) {
					$default[] = array(
						'id'     => wc_attribute_taxonomy_id_by_name( $key ),
						'name'   => $this->get_attribute_taxonomy_name( $key, $object ),
						'option' => $value,
					);
				} else {
					$default[] = array(
						'id'     => 0,
						'name'   => $this->get_attribute_taxonomy_name( $key, $object ),
						'option' => $value,
					);
				}
			}
		}

		return $default;
	}

	/**
	 * Get the attributes for a product or product variation.
	 *
	 * @param \WC_Product|\WC_Product_Variation $object Product instance.
	 *
	 * @return array
	 */
	protected function prepare_attributes( $object ) {
		$attributes = array();

		if ( $object->is_type( 'variation' ) ) {
			$_product = wc_get_product( $object->get_parent_id() );
			foreach ( $object->get_variation_attributes() as $attribute_name => $attribute ) {
				$name = str_replace( 'attribute_', '', $attribute_name );

				if ( empty( $attribute ) && '0' !== $attribute ) {
					continue;
				}

				// Taxonomy-based attributes are prefixed with `pa_`, otherwise simply `attribute_`.
				if ( 0 === strpos( $attribute_name, 'attribute_pa_' ) ) {
					$option_term  = get_term_by( 'slug', $attribute, $name );
					$attributes[] = array(
						'id'     => wc_attribute_taxonomy_id_by_name( $name ),
						'name'   => $this->get_attribute_taxonomy_name( $name, $_product ),
						'option' => $option_term && ! is_wp_error( $option_term ) ? $option_term->name : $attribute,
					);
				} else {
					$attributes[] = array(
						'id'     => 0,
						'name'   => $this->get_attribute_taxonomy_name( $name, $_product ),
						'option' => $attribute,
					);
				}
			}
		} else {
			foreach ( $object->get_attributes() as $attribute ) {
				$attributes[] = array(
					'id'        => $attribute['is_taxonomy'] ? wc_attribute_taxonomy_id_by_name( $attribute['name'] ) : 0,
					'name'      => $this->get_attribute_taxonomy_name( $attribute['name'], $object ),
					'position'  => (int) $attribute['position'],
					'visible'   => (bool) $attribute['is_visible'],
					'variation' => (bool) $attribute['is_variation'],
					'options'   => $this->get_attribute_options( $object->get_id(), $attribute ),
				);
			}
		}

		return $attributes;
	}

	/**
	 * Get product attribute taxonomy name.
	 *
	 * @param string      $slug   Taxonomy name.
	 * @param \WC_Product $object Product data.
	 *
	 * @since  3.0.0
	 * @return string
	 */
	protected function get_attribute_taxonomy_name( $slug, $object ) {
		// Format slug so it matches attributes of the product.
		$slug       = wc_attribute_taxonomy_slug( $slug );
		$attributes = $object->get_attributes();
		$attribute  = false;

		// pa_ attributes.
		if ( isset( $attributes[ wc_attribute_taxonomy_name( $slug ) ] ) ) {
			$attribute = $attributes[ wc_attribute_taxonomy_name( $slug ) ];
		} elseif ( isset( $attributes[ $slug ] ) ) {
			$attribute = $attributes[ $slug ];
		}

		if ( ! $attribute ) {
			return $slug;
		}

		// Taxonomy attribute name.
		if ( $attribute->is_taxonomy() ) {
			$taxonomy = $attribute->get_taxonomy_object();
			return $taxonomy->attribute_label;
		}

		// Custom product attribute name.
		return $attribute->get_name();
	}

	/**
	 * Get attribute options.
	 *
	 * @param int   $object_id Product ID.
	 * @param array $attribute  Attribute data.
	 *
	 * @return array
	 */
	protected function get_attribute_options( $object_id, $attribute ) {
		if ( isset( $attribute['is_taxonomy'] ) && $attribute['is_taxonomy'] ) {
			return wc_get_product_terms(
				$object_id,
				$attribute['name'],
				array(
					'fields' => 'names',
				)
			);
		} elseif ( isset( $attribute['value'] ) ) {
			return array_map( 'trim', explode( '|', $attribute['value'] ) );
		}

		return array();
	}
}
