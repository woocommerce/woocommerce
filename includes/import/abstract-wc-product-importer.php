<?php
/**
 * Abstract Product importer
 *
 * @author   Automattic
 * @category Admin
 * @package  WooCommerce/Import
 * @version  3.1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Include dependencies.
 */
if ( ! class_exists( 'WC_Importer_Interface', false ) ) {
	include_once( WC_ABSPATH . 'includes/interfaces/class-wc-importer-interface.php' );
}

/**
 * WC_Product_Importer Class.
 */
abstract class WC_Product_Importer implements WC_Importer_Interface {

	/**
	 * CSV file.
	 *
	 * @var string
	 */
	protected $file = '';

	/**
	 * The file position after the last read.
	 *
	 * @var int
	 */
	protected $file_position = 0;

	/**
	 * Importer parameters.
	 *
	 * @var array
	 */
	protected $params = array();

	/**
	 * Raw keys - CSV raw headers.
	 *
	 * @var array
	 */
	protected $raw_keys = array();

	/**
	 * Mapped keys - CSV headers.
	 *
	 * @var array
	 */
	protected $mapped_keys = array();

	/**
	 * Raw data.
	 *
	 * @var array
	 */
	protected $raw_data = array();

	/**
	 * Parsed data.
	 *
	 * @var array
	 */
	protected $parsed_data = array();

	/**
	 * Get file raw headers.
	 *
	 * @return array
	 */
	public function get_raw_keys() {
		return $this->raw_keys;
	}

	/**
	 * Get file mapped headers.
	 *
	 * @return array
	 */
	public function get_mapped_keys() {
		return $this->mapped_keys;
	}

	/**
	 * Get raw data.
	 *
	 * @return array
	 */
	public function get_raw_data() {
		return $this->raw_data;
	}

	/**
	 * Get parsed data.
	 *
	 * @return array
	 */
	public function get_parsed_data() {
		return apply_filters( 'woocommerce_product_parsed_data', $this->parsed_data, $this->get_raw_data() );
	}

	/**
	 * Get file pointer position from the last read.
	 *
	 * @return int
	 */
	public function get_file_position() {
		return $this->file_position;
	}

	/**
	 * Get file pointer position as a percentage of file size.
	 *
	 * @return int
	 */
	public function get_percent_complete() {
		$size = filesize( $this->file );
		if ( ! $size ) {
			return 0;
		}

		return min( round( ( $this->file_position / $size ) * 100 ), 100 );
	}

	/**
	 * Process a single item and save.
	 *
	 * @param  array $data Raw CSV data.
	 * @return WC_Product|WC_Error
	 */
	protected function process_item( $data ) {
		// Ignore IDs and create new products.
		// @todo Mike said that we should have something to force create.
		$force_create = false;

		try {
			$object = $this->prepare_product( $data, $force_create );

			if ( is_wp_error( $object ) ) {
				return $object;
			}

			$object->save();

			// Clean cache for updated products.
			$this->clear_cache( $object );

			return $object->get_id();
		} catch ( WC_Data_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		} catch ( Exception $e ) {
			return new WP_Error( 'woocommerce_product_csv_importer_error', $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Clear product cache.
	 *
	 * @param WC_Product $object Product instance.
	 */
	protected function clear_cache( $object ) {
		$id = $object->get_id();

		if ( 'variation' === $object->get_type() ) {
			$id = $object->get_parent_id();
		}

		wc_delete_product_transients( $id );
		wp_cache_delete( 'product-' . $id, 'products' );
	}

	/**
	 * Prepare a single product for create or update.
	 *
	 * @param  array $data     Row data.
	 * @param  bool  $creating If should force create a new product.
	 * @return WC_Product|WP_Error
	 */
	protected function prepare_product( $data, $force_create = false ) {
		$id = ! $force_create && isset( $data['id'] ) ? absint( $data['id'] ) : 0;

		// Type is the most important part here because we need to be using the correct class and methods.
		if ( isset( $data['type'] ) ) {
			$product_type = 'simple';
			$types        = array_filter( array_map( 'trim', explode( ',', $data['type'] ) ) );

			foreach ( $types as $key => $type ) {
				if ( 'downloadable' === $type ) {
					$data['downloadable'] = true;
				} elseif ( 'virtual' === $type ) {
					$data['virtual'] = true;
				} elseif ( in_array( $type, array_keys( wc_get_product_types() ), true ) || 'variation' === $type ) {
					$product_type = $type;
				} else {
					return new WP_Error( 'woocommerce_product_csv_importer_invalid_type', __( 'Invalid product type.', 'woocommerce' ), array( 'status' => 401 ) );
				}
			}

			$classname = WC_Product_Factory::get_classname_from_product_type( $data['type'] );

			if ( ! class_exists( $classname ) ) {
				$classname = 'WC_Product_Simple';
			}

			$product = new $classname( $id );
		} elseif ( isset( $data['id'] ) ) {
			$product = wc_get_product( $id );
		} else {
			$product = new WC_Product_Simple();
		}

		// @todo need to check first how we'll handle variations.
		if ( 'variation' === $product->get_type() ) {
			$product = $this->save_variation_data( $product, $data );
		} else {
			$product = $this->save_product_data( $product, $data );
		}

		return apply_filters( 'woocommerce_product_csv_import_pre_insert_product_object', $product, $data );
	}

	/**
	 * Set product data.
	 *
	 * @param WC_Product $product Product instance.
	 * @param array      $data    Row data.
	 *
	 * @return WC_Product
	 */
	protected function save_product_data( $product, $data ) {

		// Post title.
		if ( isset( $data['name'] ) ) {
			$product->set_name( wp_filter_post_kses( $data['name'] ) );
		}

		// Post content.
		if ( isset( $data['description'] ) ) {
			$product->set_description( wp_filter_post_kses( $data['description'] ) );
		}

		// Post excerpt.
		if ( isset( $data['short_description'] ) ) {
			$product->set_short_description( wp_filter_post_kses( $data['short_description'] ) );
		}

		// Post status.
		if ( isset( $data['status'] ) ) {
			$product->set_status( $data['status'] ? 'publish' : 'draft' );
		}

		// Post slug.
		if ( isset( $data['slug'] ) ) {
			$product->set_slug( $data['slug'] );
		}

		// Menu order.
		if ( isset( $data['menu_order'] ) ) {
			$product->set_menu_order( $data['menu_order'] );
		}

		// Comment status.
		if ( isset( $data['reviews_allowed'] ) ) {
			$product->set_reviews_allowed( $data['reviews_allowed'] );
		}

		// Virtual.
		if ( isset( $data['virtual'] ) ) {
			$product->set_virtual( $data['virtual'] );
		}

		// Tax status.
		if ( isset( $data['tax_status'] ) ) {
			$product->set_tax_status( $data['tax_status'] );
		}

		// Tax Class.
		if ( isset( $data['tax_class'] ) ) {
			$product->set_tax_class( $data['tax_class'] );
		}

		// Catalog Visibility.
		if ( isset( $data['catalog_visibility'] ) ) {
			$product->set_catalog_visibility( $data['catalog_visibility'] );
		}

		// Purchase Note.
		if ( isset( $data['purchase_note'] ) ) {
			$product->set_purchase_note( wc_clean( $data['purchase_note'] ) );
		}

		// Featured Product.
		if ( isset( $data['featured'] ) ) {
			$product->set_featured( $data['featured'] );
		}

		// Shipping data.
		$product = $this->save_product_shipping_data( $product, $data );

		// SKU.
		if ( isset( $data['sku'] ) ) {
			$product->set_sku( wc_clean( $data['sku'] ) );
		}

		// Attributes.
		if ( isset( $data['attributes'] ) ) {
			$attributes = array();

			foreach ( $data['attributes'] as $attribute ) {
				$attribute_id   = 0;
				$attribute_name = '';

				// Check ID for global attributes or name for product attributes.
				if ( ! empty( $attribute['id'] ) ) {
					$attribute_id   = absint( $attribute['id'] );
					$attribute_name = wc_attribute_taxonomy_name_by_id( $attribute_id );
				} elseif ( ! empty( $attribute['name'] ) ) {
					$attribute_name = wc_clean( $attribute['name'] );
				}

				if ( ! $attribute_id && ! $attribute_name ) {
					continue;
				}

				if ( $attribute_id ) {

					if ( isset( $attribute['options'] ) ) {
						$options = $attribute['options'];

						if ( ! is_array( $attribute['options'] ) ) {
							// Text based attributes - Posted values are term names.
							$options = explode( WC_DELIMITER, $options );
						}

						$values = array_map( 'wc_sanitize_term_text_based', $options );
						$values = array_filter( $values, 'strlen' );
					} else {
						$values = array();
					}

					if ( ! empty( $values ) ) {
						// Add attribute to array, but don't set values.
						$attribute_object = new WC_Product_Attribute();
						$attribute_object->set_id( $attribute_id );
						$attribute_object->set_name( $attribute_name );
						$attribute_object->set_options( $values );
						$attribute_object->set_position( isset( $attribute['position'] ) ? (string) absint( $attribute['position'] ) : '0' );
						$attribute_object->set_visible( ( isset( $attribute['visible'] ) && $attribute['visible'] ) ? 1 : 0 );
						$attribute_object->set_variation( ( isset( $attribute['variation'] ) && $attribute['variation'] ) ? 1 : 0 );
						$attributes[] = $attribute_object;
					}
				} elseif ( isset( $attribute['options'] ) ) {
					// Custom attribute - Add attribute to array and set the values.
					if ( is_array( $attribute['options'] ) ) {
						$values = $attribute['options'];
					} else {
						$values = explode( WC_DELIMITER, $attribute['options'] );
					}
					$attribute_object = new WC_Product_Attribute();
					$attribute_object->set_name( $attribute_name );
					$attribute_object->set_options( $values );
					$attribute_object->set_position( isset( $attribute['position'] ) ? (string) absint( $attribute['position'] ) : '0' );
					$attribute_object->set_visible( ( isset( $attribute['visible'] ) && $attribute['visible'] ) ? 1 : 0 );
					$attribute_object->set_variation( ( isset( $attribute['variation'] ) && $attribute['variation'] ) ? 1 : 0 );
					$attributes[] = $attribute_object;
				}
			}
			$product->set_attributes( $attributes );
		}

		// Sales and prices.
		if ( in_array( $product->get_type(), array( 'variable', 'grouped' ), true ) ) {
			$product->set_regular_price( '' );
			$product->set_sale_price( '' );
			$product->set_date_on_sale_to( '' );
			$product->set_date_on_sale_from( '' );
			$product->set_price( '' );
		} else {
			// Regular Price.
			if ( isset( $data['regular_price'] ) ) {
				$product->set_regular_price( $data['regular_price'] );
			}

			// Sale Price.
			if ( isset( $data['sale_price'] ) ) {
				$product->set_sale_price( $data['sale_price'] );
			}

			if ( isset( $data['date_on_sale_from'] ) ) {
				$product->set_date_on_sale_from( $data['date_on_sale_from'] );
			}

			if ( isset( $data['date_on_sale_from_gmt'] ) ) {
				$product->set_date_on_sale_from( $data['date_on_sale_from_gmt'] ? strtotime( $data['date_on_sale_from_gmt'] ) : null );
			}

			if ( isset( $data['date_on_sale_to'] ) ) {
				$product->set_date_on_sale_to( $data['date_on_sale_to'] );
			}

			if ( isset( $data['date_on_sale_to_gmt'] ) ) {
				$product->set_date_on_sale_to( $data['date_on_sale_to_gmt'] ? strtotime( $data['date_on_sale_to_gmt'] ) : null );
			}
		}

		// Product parent ID for groups.
		if ( isset( $data['parent_id'] ) ) {
			$product->set_parent_id( $data['parent_id'] );
		}

		// Sold individually.
		if ( isset( $data['sold_individually'] ) ) {
			$product->set_sold_individually( $data['sold_individually'] );
		}

		// Stock status.
		if ( isset( $data['in_stock'] ) ) {
			$stock_status = true === $data['in_stock'] ? 'instock' : 'outofstock';
		} else {
			$stock_status = $product->get_stock_status();
		}

		// Stock data.
		if ( 'yes' === get_option( 'woocommerce_manage_stock' ) ) {
			// Manage stock.
			if ( isset( $data['manage_stock'] ) ) {
				$product->set_manage_stock( $data['manage_stock'] );
			}

			// Backorders.
			if ( isset( $data['backorders'] ) ) {
				$product->set_backorders( $data['backorders'] );
			}

			if ( $product->is_type( 'grouped' ) ) {
				$product->set_manage_stock( 'no' );
				$product->set_backorders( 'no' );
				$product->set_stock_quantity( '' );
				$product->set_stock_status( $stock_status );
			} elseif ( $product->is_type( 'external' ) ) {
				$product->set_manage_stock( 'no' );
				$product->set_backorders( 'no' );
				$product->set_stock_quantity( '' );
				$product->set_stock_status( 'instock' );
			} elseif ( $product->get_manage_stock() ) {
				// Stock status is always determined by children so sync later.
				if ( ! $product->is_type( 'variable' ) ) {
					$product->set_stock_status( $stock_status );
				}

				// Stock quantity.
				if ( isset( $data['stock_quantity'] ) ) {
					$product->set_stock_quantity( wc_stock_amount( $data['stock_quantity'] ) );
				} elseif ( isset( $data['inventory_delta'] ) ) {
					$stock_quantity  = wc_stock_amount( $product->get_stock_quantity() );
					$stock_quantity += wc_stock_amount( $data['inventory_delta'] );
					$product->set_stock_quantity( wc_stock_amount( $stock_quantity ) );
				}
			} else {
				// Don't manage stock.
				$product->set_manage_stock( 'no' );
				$product->set_stock_quantity( '' );
				$product->set_stock_status( $stock_status );
			}
		} elseif ( ! $product->is_type( 'variable' ) ) {
			$product->set_stock_status( $stock_status );
		}

		// Upsells.
		if ( isset( $data['upsell_ids'] ) ) {
			$upsells = array();
			$ids     = $data['upsell_ids'];

			if ( ! empty( $ids ) ) {
				foreach ( $ids as $id ) {
					if ( $id && $id > 0 ) {
						$upsells[] = $id;
					}
				}
			}

			$product->set_upsell_ids( $upsells );
		}

		// Cross sells.
		if ( isset( $data['cross_sell_ids'] ) ) {
			$crosssells = array();
			$ids        = $data['cross_sell_ids'];

			if ( ! empty( $ids ) ) {
				foreach ( $ids as $id ) {
					if ( $id && $id > 0 ) {
						$crosssells[] = $id;
					}
				}
			}

			$product->set_cross_sell_ids( $crosssells );
		}

		// Product categories.
		if ( isset( $data['categories'] ) && is_array( $data['categories'] ) ) {
			$product = $this->save_taxonomy_terms( $product, $data['categories'] );
		}

		// Product tags.
		if ( isset( $data['tags'] ) && is_array( $data['tags'] ) ) {
			$product = $this->save_taxonomy_terms( $product, $data['tags'], 'tag' );
		}

		// Downloadable.
		if ( isset( $data['downloadable'] ) ) {
			$product->set_downloadable( $data['downloadable'] );
		}

		// Downloadable options.
		if ( $product->get_downloadable() ) {

			// Downloadable files.
			if ( isset( $data['downloads'] ) && is_array( $data['downloads'] ) ) {
				$product = $this->save_downloadable_files( $product, $data['downloads'] );
			}

			// Download limit.
			if ( isset( $data['download_limit'] ) ) {
				$product->set_download_limit( $data['download_limit'] );
			}

			// Download expiry.
			if ( isset( $data['download_expiry'] ) ) {
				$product->set_download_expiry( $data['download_expiry'] );
			}
		}

		// Product url and button text for external products.
		if ( $product->is_type( 'external' ) ) {
			if ( isset( $data['external_url'] ) ) {
				$product->set_product_url( $data['external_url'] );
			}

			if ( isset( $data['button_text'] ) ) {
				$product->set_button_text( $data['button_text'] );
			}
		}

		// Save default attributes for variable products.
		if ( $product->is_type( 'variable' ) ) {
			$product = $this->save_default_attributes( $product, $data );
		}

		// Check for featured/gallery images, upload it and set it.
		if ( isset( $data['images'] ) ) {
			$product = $this->save_product_images( $product, $data['images'] );
		}

		// Allow set meta_data.
		if ( isset( $data['meta_data'] ) && is_array( $data['meta_data'] ) ) {
			foreach ( $data['meta_data'] as $meta ) {
				$product->update_meta_data( $meta['key'], $meta['value'], isset( $meta['id'] ) ? $meta['id'] : '' );
			}
		}

		return $product;
	}

	/**
	 * Set variation data.
	 *
	 * @param WC_Product $variation Product instance.
	 * @param array      $data    Row data.
	 *
	 * @return WC_Product
	 */
	protected function save_variation_data( $variation, $data ) {
		if ( isset( $data['product_id'] ) ) {
			$variation->set_parent_id( absint( $data['product_id'] ) );
		} else {
			return new WP_Error( 'woocommerce_product_importer_missing_variation_parent_id', __( 'Missing variation product parent ID', 'woocommerce' ), array( 'status' => 401 ) );
		}

		// Status.
		if ( isset( $data['status'] ) ) {
			$variation->set_status( false === $data['status'] ? 'private' : 'publish' );
		}

		// SKU.
		if ( isset( $data['sku'] ) ) {
			$variation->set_sku( wc_clean( $data['sku'] ) );
		}

		// Thumbnail.
		if ( isset( $data['image'] ) ) {
			if ( is_array( $data['image'] ) ) {
				$image = $data['image'];
				if ( is_array( $image ) ) {
					$image['position'] = 0;
				}

				$variation = $this->save_product_images( $variation, array( $image ) );
			} else {
				$variation->set_image_id( '' );
			}
		}

		// Virtual variation.
		if ( isset( $data['virtual'] ) ) {
			$variation->set_virtual( $data['virtual'] );
		}

		// Downloadable variation.
		if ( isset( $data['downloadable'] ) ) {
			$variation->set_downloadable( $data['downloadable'] );
		}

		// Downloads.
		if ( $variation->get_downloadable() ) {
			// Downloadable files.
			if ( isset( $data['downloads'] ) && is_array( $data['downloads'] ) ) {
				$variation = $this->save_downloadable_files( $variation, $data['downloads'] );
			}

			// Download limit.
			if ( isset( $data['download_limit'] ) ) {
				$variation->set_download_limit( $data['download_limit'] );
			}

			// Download expiry.
			if ( isset( $data['download_expiry'] ) ) {
				$variation->set_download_expiry( $data['download_expiry'] );
			}
		}

		// Shipping data.
		$variation = $this->save_product_shipping_data( $variation, $data );

		// Stock handling.
		if ( isset( $data['manage_stock'] ) ) {
			$variation->set_manage_stock( $data['manage_stock'] );
		}

		if ( isset( $data['in_stock'] ) ) {
			$variation->set_stock_status( true === $data['in_stock'] ? 'instock' : 'outofstock' );
		}

		if ( isset( $data['backorders'] ) ) {
			$variation->set_backorders( $data['backorders'] );
		}

		if ( $variation->get_manage_stock() ) {
			if ( isset( $data['stock_quantity'] ) ) {
				$variation->set_stock_quantity( $data['stock_quantity'] );
			} elseif ( isset( $data['inventory_delta'] ) ) {
				$stock_quantity  = wc_stock_amount( $variation->get_stock_quantity() );
				$stock_quantity += wc_stock_amount( $data['inventory_delta'] );
				$variation->set_stock_quantity( $stock_quantity );
			}
		} else {
			$variation->set_backorders( 'no' );
			$variation->set_stock_quantity( '' );
		}

		// Regular Price.
		if ( isset( $data['regular_price'] ) ) {
			$variation->set_regular_price( $data['regular_price'] );
		}

		// Sale Price.
		if ( isset( $data['sale_price'] ) ) {
			$variation->set_sale_price( $data['sale_price'] );
		}

		if ( isset( $data['date_on_sale_from'] ) ) {
			$variation->set_date_on_sale_from( $data['date_on_sale_from'] );
		}

		if ( isset( $data['date_on_sale_from_gmt'] ) ) {
			$variation->set_date_on_sale_from( $data['date_on_sale_from_gmt'] ? strtotime( $data['date_on_sale_from_gmt'] ) : null );
		}

		if ( isset( $data['date_on_sale_to'] ) ) {
			$variation->set_date_on_sale_to( $data['date_on_sale_to'] );
		}

		if ( isset( $data['date_on_sale_to_gmt'] ) ) {
			$variation->set_date_on_sale_to( $data['date_on_sale_to_gmt'] ? strtotime( $data['date_on_sale_to_gmt'] ) : null );
		}

		// Tax class.
		if ( isset( $data['tax_class'] ) ) {
			$variation->set_tax_class( $data['tax_class'] );
		}

		// Description.
		if ( isset( $data['description'] ) ) {
			$variation->set_description( wp_kses_post( $data['description'] ) );
		}

		// Update taxonomies.
		if ( isset( $data['attributes'] ) ) {
			$attributes        = array();
			$parent            = wc_get_product( $variation->get_parent_id() );
			$parent_attributes = $parent->get_attributes();

			foreach ( $data['attributes'] as $attribute ) {
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

			$variation->set_attributes( $attributes );
		}

		// Menu order.
		if ( isset( $data['menu_order'] ) ) {
			$variation->set_menu_order( $data['menu_order'] );
		}

		// Meta data.
		if ( isset( $data['meta_data'] ) && is_array( $data['meta_data'] ) ) {
			foreach ( $data['meta_data'] as $meta ) {
				$variation->update_meta_data( $meta['key'], $meta['value'], isset( $meta['id'] ) ? $meta['id'] : '' );
			}
		}

		return $variation;
	}

	/**
	 * Set product images.
	 *
	 * @param WC_Product $product Product instance.
	 * @param array      $images  Images data.
	 * @return WC_Product
	 */
	protected function save_product_images( $product, $images ) {
		if ( is_array( $images ) ) {
			$gallery = array();

			foreach ( $images as $image ) {
				$attachment_id = isset( $image['id'] ) ? absint( $image['id'] ) : 0;

				if ( 0 === $attachment_id && isset( $image['src'] ) ) {
					$upload = wc_rest_upload_image_from_url( esc_url_raw( $image['src'] ) );

					if ( is_wp_error( $upload ) ) {
						if ( ! apply_filters( 'woocommerce_rest_suppress_image_upload_error', false, $upload, $product->get_id(), $images ) ) {
							throw new Exception( $upload->get_error_message(), 400 );
						} else {
							continue;
						}
					}

					$attachment_id = wc_rest_set_uploaded_image_as_attachment( $upload, $product->get_id() );
				}

				if ( ! wp_attachment_is_image( $attachment_id ) ) {
					throw new Exception( sprintf( __( '#%s is an invalid image ID.', 'woocommerce' ), $attachment_id ), 400 );
				}

				if ( isset( $image['position'] ) && 0 === absint( $image['position'] ) ) {
					$product->set_image_id( $attachment_id );
				} else {
					$gallery[] = $attachment_id;
				}

				// Set the image alt if present.
				if ( ! empty( $image['alt'] ) ) {
					update_post_meta( $attachment_id, '_wp_attachment_image_alt', wc_clean( $image['alt'] ) );
				}

				// Set the image name if present.
				if ( ! empty( $image['name'] ) ) {
					wp_update_post( array( 'ID' => $attachment_id, 'post_title' => $image['name'] ) );
				}
			}

			if ( ! empty( $gallery ) ) {
				$product->set_gallery_image_ids( $gallery );
			}
		} else {
			$product->set_image_id( '' );
			$product->set_gallery_image_ids( array() );
		}

		return $product;
	}

	/**
	 * Save product shipping data.
	 *
	 * @param WC_Product $product Product instance.
	 * @param array      $data    Shipping data.
	 * @return WC_Product
	 */
	protected function save_product_shipping_data( $product, $data ) {
		// Virtual.
		if ( isset( $data['virtual'] ) && true === $data['virtual'] ) {
			$product->set_weight( '' );
			$product->set_height( '' );
			$product->set_length( '' );
			$product->set_width( '' );
		} else {
			if ( isset( $data['weight'] ) ) {
				$product->set_weight( $data['weight'] );
			}

			// Height.
			if ( isset( $data['height'] ) ) {
				$product->set_height( $data['height'] );
			}

			// Width.
			if ( isset( $data['width'] ) ) {
				$product->set_width( $data['width'] );
			}

			// Length.
			if ( isset( $data['length'] ) ) {
				$product->set_length( $data['length'] );
			}
		}

		// Shipping class.
		if ( isset( $data['shipping_class_id'] ) ) {
			$shipping_class_term = get_term_by( 'id', wc_clean( $data['shipping_class_id'] ), 'product_shipping_class' );

			if ( $shipping_class_term ) {
				$product->set_shipping_class_id( $shipping_class_term->term_id );
			}
		}

		return $product;
	}

	/**
	 * Save downloadable files.
	 *
	 * @param WC_Product $product    Product instance.
	 * @param array      $downloads  Downloads data.
	 * @param int        $deprecated Deprecated since 3.0.
	 * @return WC_Product
	 */
	protected function save_downloadable_files( $product, $downloads ) {
		$files = array();
		foreach ( $downloads as $key => $file ) {
			if ( empty( $file['file'] ) ) {
				continue;
			}

			$download = new WC_Product_Download();
			$download->set_id( $key );
			$download->set_name( $file['name'] ? $file['name'] : wc_get_filename_from_url( $file['file'] ) );
			$download->set_file( apply_filters( 'woocommerce_file_download_path', $file['file'], $product, $key ) );
			$files[]  = $download;
		}
		$product->set_downloads( $files );

		return $product;
	}

	/**
	 * Save taxonomy terms.
	 *
	 * @param WC_Product $product  Product instance.
	 * @param array      $terms    Terms data.
	 * @param string     $taxonomy Taxonomy name.
	 * @return WC_Product
	 */
	protected function save_taxonomy_terms( $product, $terms, $taxonomy = 'cat' ) {
		$term_ids = wp_list_pluck( $terms, 'id' );

		if ( 'cat' === $taxonomy ) {
			$product->set_category_ids( $term_ids );
		} elseif ( 'tag' === $taxonomy ) {
			$product->set_tag_ids( $term_ids );
		}

		return $product;
	}

	/**
	 * Save default attributes.
	 *
	 * @since 3.0.0
	 *
	 * @param WC_Product      $product Product instance.
	 * @param array $data    Row data.
	 * @return WC_Product
	 */
	protected function save_default_attributes( $product, $data ) {
		if ( isset( $data['default_attributes'] ) && is_array( $data['default_attributes'] ) ) {

			$attributes         = $product->get_attributes();
			$default_attributes = array();

			foreach ( $data['default_attributes'] as $attribute ) {
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

			$product->set_default_attributes( $default_attributes );
		}

		return $product;
	}
}
