<?php
/**
 * Convert data in the product schema format to a product object.
 *
 * @package Automattic/WooCommerce/RestApi
 */

namespace Automattic\WooCommerce\RestApi\Controllers\Version4\Requests;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\RestApi\Utilities\ImageAttachment;

/**
 * ProductVariationRequest class.
 */
class ProductVariationRequest extends ProductRequest {

	/**
	 * Convert request to object.
	 *
	 * @throws \WC_REST_Exception Will throw an exception if the resulting product object is invalid.
	 * @return \WC_Product_Variation
	 */
	public function prepare_object() {
		$id     = (int) $this->get_param( 'id', 0 );
		$object = new \WC_Product_Variation( $id );
		$object->set_parent_id( (int) $this->get_param( 'product_id', 0 ) );
		$parent = wc_get_product( $object->get_parent_id() );

		if ( ! $parent ) {
			throw new \WC_REST_Exception( 'woocommerce_rest_product_variation_invalid_parent', __( 'Invalid parent product.', 'woocommerce-rest-api' ), 404 );
		}

		$this->set_common_props( $object );
		$this->set_meta_data( $object );

		if ( $object->get_downloadable() ) {
			$this->set_downloadable_props( $object );
		}

		return $object;
	}

	/**
	 * Set common product props.
	 *
	 * @param \WC_Product_Variation $object Product object reference.
	 */
	protected function set_common_props( &$object ) {
		$props = [
			'status',
			'sku',
			'virtual',
			'downloadable',
			'download_limit',
			'download_expiry',
			'manage_stock',
			'stock_status',
			'backorders',
			'regular_price',
			'sale_price',
			'date_on_sale_from',
			'date_on_sale_from_gmt',
			'date_on_sale_to',
			'date_on_sale_to_gmt',
			'tax_class',
			'description',
			'menu_order',
			'stock_quantity',
			'image',
			'downloads',
			'attributes',
			'weight',
			'dimensions',
			'shipping_class',
		];

		$request_props = array_intersect_key( $this->request, array_flip( $props ) );
		$prop_values   = [];

		foreach ( $request_props as $prop => $value ) {
			switch ( $prop ) {
				case 'image':
					$prop_values['image_id'] = $this->parse_image_field( $value, $object );
					break;
				case 'attributes':
					$prop_values['attributes'] = $this->parse_attributes_field( $value, $object );
					break;
				case 'dimensions':
					$dimensions  = $this->parse_dimensions_fields( $value );
					$prop_values = array_merge( $prop_values, $dimensions );
					break;
				case 'shipping_class':
					$prop_values['shipping_class_id'] = $this->parse_shipping_class( $value, $object );
					break;
				default:
					$prop_values[ $prop ] = $value;
			}
		}

		foreach ( $prop_values as $prop => $value ) {
			$object->{"set_$prop"}( $value );
		}
	}

	/**
	 * Set product object's attributes.
	 *
	 * @param array                 $raw_attributes Attribute data from request.
	 * @param \WC_Product_Variation $object Product object.
	 */
	protected function parse_attributes_field( $raw_attributes, $object = null ) {
		$attributes        = array();
		$parent            = wc_get_product( $object->get_parent_id() );
		$parent_attributes = $parent->get_attributes();

		foreach ( $raw_attributes as $attribute ) {
			$attribute_id   = 0;
			$attribute_name = '';

			// Check ID for global attributes or name for product attributes.
			if ( ! empty( $attribute['id'] ) ) {
				$attribute_id   = absint( $attribute['id'] );
				$attribute_name = wc_attribute_taxonomy_name_by_id( $attribute_id );
			} elseif ( ! empty( $attribute['name'] ) ) {
				$attribute_name = sanitize_title( $attribute['name'] );
			}

			if ( ! $attribute_id && ! $attribute_name ) {
				continue;
			}

			if ( ! isset( $parent_attributes[ $attribute_name ] ) || ! $parent_attributes[ $attribute_name ]->get_variation() ) {
				continue;
			}

			$attribute_key   = sanitize_title( $parent_attributes[ $attribute_name ]->get_name() );
			$attribute_value = isset( $attribute['option'] ) ? wc_clean( stripslashes( $attribute['option'] ) ) : '';

			if ( $parent_attributes[ $attribute_name ]->is_taxonomy() ) {
				// If dealing with a taxonomy, we need to get the slug from the name posted to the API.
				$term = get_term_by( 'name', $attribute_value, $attribute_name );

				if ( $term && ! is_wp_error( $term ) ) {
					$attribute_value = $term->slug;
				} else {
					$attribute_value = sanitize_title( $attribute_value );
				}
			}

			$attributes[ $attribute_key ] = $attribute_value;
		}

		return $attributes;
	}

	/**
	 * Set product images.
	 *
	 * @throws \WC_REST_Exception REST API exceptions.
	 * @param array                 $image  Image data.
	 * @param \WC_Product_Variation $object Product object.
	 * @return array
	 */
	protected function parse_image_field( $image, $object ) {
		if ( empty( $image ) ) {
			return '';
		}

		$attachment_id = isset( $image['id'] ) ? absint( $image['id'] ) : 0;
		$attachment    = new ImageAttachment( $attachment_id, $object->get_id() );

		if ( 0 === $attachment->id && ! empty( $image['src'] ) ) {
			$attachment->upload_image_from_src( $image['src'] );
		}

		if ( ! empty( $image['alt'] ) ) {
			$attachment->update_alt_text( $image['alt'] );
		}

		if ( ! empty( $image['name'] ) ) {
			$attachment->update_name( $image['name'] );
		}

		return $attachment->id;
	}
}
