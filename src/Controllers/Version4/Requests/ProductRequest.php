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
 * ProductRequest class.
 */
class ProductRequest extends AbstractObjectRequest {

	/**
	 * Convert request to object.
	 *
	 * @return \WC_Product_Simple|\WC_Product_Grouped|\WC_Product_Variable|\WC_Product_External
	 */
	public function prepare_object() {
		$object = $this->get_product_object();

		$this->set_common_props( $object );
		$this->set_meta_data( $object );

		switch ( $object->get_type() ) {
			case 'grouped':
				$this->set_grouped_props( $object );
				break;
			case 'variable':
				$this->set_variable_props( $object );
				break;
			case 'external':
				$this->set_external_props( $object );
				break;
		}

		if ( $object->get_downloadable() ) {
			$this->set_downloadable_props( $object );
		}

		return $object;
	}

	/**
	 * Get product object from request args.
	 *
	 * @throws \WC_REST_Exception Will throw an exception if the resulting product object is invalid.
	 * @return \WC_Product_Simple|\WC_Product_Grouped|\WC_Product_Variable|\WC_Product_External
	 */
	protected function get_product_object() {
		$id   = (int) $this->get_param( 'id', 0 );
		$type = $this->get_param( 'type', '' );

		if ( $type ) {
			$classname = \WC_Product_Factory::get_classname_from_product_type( $type );
			if ( $classname && class_exists( '\\' . $classname ) ) {
				$classname = '\\' . $classname;
			} else {
				$classname = '\WC_Product_Simple';
			}
			$object = new $classname( $id );
		} elseif ( $id ) {
			$object = wc_get_product( $id );
		} else {
			$object = new \WC_Product_Simple();
		}

		if ( ! $object ) {
			throw new \WC_REST_Exception( 'woocommerce_rest_invalid_product_id', __( 'Invalid product.', 'woocommerce' ), 404 );
		}

		if ( $object->is_type( 'variation' ) ) {
			throw new \WC_REST_Exception( 'woocommerce_rest_invalid_product_id', __( 'To manipulate product variations you should use the /products/&lt;product_id&gt;/variations/&lt;id&gt; endpoint.', 'woocommerce' ), 404 );
		}

		return $object;
	}

	/**
	 * Set common product props.
	 *
	 * @param \WC_Product_Simple|\WC_Product_Grouped|\WC_Product_Variable|\WC_Product_External $object Product object reference.
	 */
	protected function set_common_props( &$object ) {
		$props = [
			'name',
			'sku',
			'description',
			'short_description',
			'slug',
			'menu_order',
			'reviews_allowed',
			'virtual',
			'tax_status',
			'tax_class',
			'catalog_visibility',
			'purchase_note',
			'status',
			'featured',
			'regular_price',
			'sale_price',
			'date_on_sale_from',
			'date_on_sale_from_gmt',
			'date_on_sale_to',
			'date_on_sale_to_gmt',
			'parent_id',
			'sold_individually',
			'manage_stock',
			'backorders',
			'stock_status',
			'stock_quantity',
			'downloadable',
			'date_created',
			'date_created_gmt',
			'upsell_ids',
			'cross_sell_ids',
			'images',
			'categories',
			'tags',
			'attributes',
			'weight',
			'dimensions',
			'shipping_class',
		];

		$request_props = array_intersect_key( $this->request, array_flip( $props ) );
		$prop_values   = [];

		foreach ( $request_props as $prop => $value ) {
			switch ( $prop ) {
				case 'date_created':
				case 'date_created_gmt':
					$prop_values[ $prop ] = rest_parse_date( $value );
					break;
				case 'upsell_ids':
				case 'cross_sell_ids':
					$prop_values[ $prop ] = wp_parse_id_list( $value );
					break;
				case 'images':
					$images      = $this->parse_images_field( $value, $object );
					$prop_values = array_merge( $prop_values, $images );
					break;
				case 'categories':
					$prop_values['category_ids'] = wp_list_pluck( $value, 'id' );
					break;
				case 'tags':
					$prop_values['tag_ids'] = wp_list_pluck( $value, 'id' );
					break;
				case 'attributes':
					$prop_values['attributes'] = $this->parse_attributes_field( $value );
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
	 * Set grouped product props.
	 *
	 * @param \WC_Product_Grouped $object Product object reference.
	 */
	protected function set_grouped_props( &$object ) {
		$children = $this->get_param( 'grouped_products', null );

		if ( ! is_null( $children ) ) {
			$object->set_children( $children );
		}
	}

	/**
	 * Set variable product props.
	 *
	 * @param \WC_Product_Variable $object Product object reference.
	 */
	protected function set_variable_props( &$object ) {
		$default_attributes = $this->get_param( 'default_attributes', null );

		if ( ! is_null( $default_attributes ) ) {
			$object->set_default_attributes( $this->parse_default_attributes( $default_attributes, $object ) );
		}
	}

	/**
	 * Set external product props.
	 *
	 * @param \WC_Product_External $object Product object reference.
	 */
	protected function set_external_props( &$object ) {
		$button_text  = $this->get_param( 'button_text', null );
		$external_url = $this->get_param( 'external_url', null );

		if ( ! is_null( $button_text ) ) {
			$object->set_button_text( $button_text );
		}

		if ( ! is_null( $external_url ) ) {
			$object->set_product_url( $external_url );
		}
	}

	/**
	 * Set downloadable product props.
	 *
	 * @param \WC_Product_Simple|\WC_Product_Grouped|\WC_Product_Variable|\WC_Product_External $object Product object reference.
	 */
	protected function set_downloadable_props( &$object ) {
		$download_limit  = $this->get_param( 'download_limit', null );
		$download_expiry = $this->get_param( 'download_expiry', null );
		$downloads       = $this->get_param( 'downloads', null );

		if ( ! is_null( $download_limit ) ) {
			$object->set_download_limit( $download_limit );
		}

		if ( ! is_null( $download_expiry ) ) {
			$object->set_download_expiry( $download_expiry );
		}

		if ( ! is_null( $downloads ) ) {
			$object->set_downloads( $this->parse_downloads_field( $downloads ) );
		}
	}

	/**
	 * Set product object's attributes.
	 *
	 * @param array $raw_attributes Attribute data from request.
	 * @return array
	 */
	protected function parse_attributes_field( $raw_attributes ) {
		$attributes = array();

		foreach ( $raw_attributes as $attribute ) {
			if ( ! empty( $attribute['id'] ) ) {
				$attribute_id   = absint( $attribute['id'] );
				$attribute_name = wc_attribute_taxonomy_name_by_id( $attribute_id );
			} elseif ( ! empty( $attribute['name'] ) ) {
				$attribute_id   = 0;
				$attribute_name = wc_clean( $attribute['name'] );
			}

			if ( ! $attribute_name || ! isset( $attribute['options'] ) ) {
				continue;
			}

			if ( ! is_array( $attribute['options'] ) ) {
				$attribute['options'] = explode( \WC_DELIMITER, $attribute['options'] );
			}

			if ( $attribute_id ) {
				$attribute['options'] = array_filter( array_map( 'wc_sanitize_term_text_based', $attribute['options'] ), 'strlen' );
			}

			$attribute_object = new \WC_Product_Attribute();
			$attribute_object->set_id( $attribute_id );
			$attribute_object->set_name( $attribute_name );
			$attribute_object->set_options( $attribute['options'] );
			$attribute_object->set_position( isset( $attribute['position'] ) ? (string) absint( $attribute['position'] ) : '0' );
			$attribute_object->set_visible( ! empty( $attribute['visible'] ) ? 1 : 0 );
			$attribute_object->set_variation( ! empty( $attribute['variation'] ) ? 1 : 0 );
			$attributes[] = $attribute_object;
		}
		return $attributes;
	}

	/**
	 * Set product images.
	 *
	 * @throws \WC_REST_Exception REST API exceptions.
	 * @param array                                                                            $images  Images data.
	 * @param \WC_Product_Simple|\WC_Product_Grouped|\WC_Product_Variable|\WC_Product_External $object Product object.
	 * @return array
	 */
	protected function parse_images_field( $images, $object ) {
		$response = [
			'image_id'          => '',
			'gallery_image_ids' => [],
		];

		$images = is_array( $images ) ? array_filter( $images ) : [];

		if ( empty( $images ) ) {
			return $response;
		}

		foreach ( $images as $index => $image ) {
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

			if ( 0 === $index ) {
				$response['image_id'] = $attachment->id;
			} else {
				$response['gallery_image_ids'][] = $attachment->id;
			}
		}

		return $response;
	}

	/**
	 * Parse dimensions.
	 *
	 * @param array $dimensions Product dimensions.
	 * @return array
	 */
	protected function parse_dimensions_fields( $dimensions ) {
		$response = [];

		if ( isset( $dimensions['length'] ) ) {
			$response['length'] = $dimensions['length'];
		}

		if ( isset( $dimensions['width'] ) ) {
			$response['width'] = $dimensions['width'];
		}

		if ( isset( $dimensions['height'] ) ) {
			$response['height'] = $dimensions['height'];
		}

		return $response;
	}

	/**
	 * Parse shipping class.
	 *
	 * @param string                                                                           $shipping_class Shipping class slug.
	 * @param \WC_Product_Simple|\WC_Product_Grouped|\WC_Product_Variable|\WC_Product_External $object Product object.
	 * @return int
	 */
	protected function parse_shipping_class( $shipping_class, $object ) {
		$data_store = $object->get_data_store();
		return $data_store->get_shipping_class_id_by_slug( wc_clean( $shipping_class ) );
	}

	/**
	 * Parse downloadable files.
	 *
	 * @param array $downloads  Downloads data.
	 * @return array
	 */
	protected function parse_downloads_field( $downloads ) {
		$files = array();
		foreach ( $downloads as $key => $file ) {
			if ( empty( $file['file'] ) ) {
				continue;
			}

			$download = new \WC_Product_Download();
			$download->set_id( ! empty( $file['id'] ) ? $file['id'] : wp_generate_uuid4() );
			$download->set_name( $file['name'] ? $file['name'] : wc_get_filename_from_url( $file['file'] ) );
			$download->set_file( $file['file'] );
			$files[] = $download;
		}
		return $files;
	}

	/**
	 * Save default attributes.
	 *
	 * @param array                $raw_default_attributes Default attributes.
	 * @param \WC_Product_Variable $object Product object reference.
	 * @return array
	 */
	protected function parse_default_attributes( $raw_default_attributes, $object ) {
		$attributes         = $object->get_attributes();
		$default_attributes = array();

		foreach ( $raw_default_attributes as $attribute ) {
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

			if ( isset( $attributes[ $attribute_name ] ) ) {
				$_attribute = $attributes[ $attribute_name ];

				if ( $_attribute['is_variation'] ) {
					$value = isset( $attribute['option'] ) ? wc_clean( stripslashes( $attribute['option'] ) ) : '';

					if ( ! empty( $_attribute['is_taxonomy'] ) ) {
						// If dealing with a taxonomy, we need to get the slug from the name posted to the API.
						$term = get_term_by( 'name', $value, $attribute_name );

						if ( $term && ! is_wp_error( $term ) ) {
							$value = $term->slug;
						} else {
							$value = sanitize_title( $value );
						}
					}

					if ( $value ) {
						$default_attributes[ $attribute_name ] = $value;
					}
				}
			}
		}

		return $default_attributes;
	}
}
