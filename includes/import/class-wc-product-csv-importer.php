<?php
/**
 * WooCommerce Product CSV importer
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
 * WC_Product_CSV_Importer Class.
 */
class WC_Product_CSV_Importer implements WC_Importer_Interface {

	/**
	 * CSV file.
	 *
	 * @var string
	 */
	protected $file = '';

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
	 * Initialize importer.
	 *
	 * @param string $file File to read.
	 * @param array  $args Arguments for the parser.
	 */
	public function __construct( $file, $params = array() ) {
		$default_args = array(
			'start_pos' => 0,       // File pointer start.
			'end_pos'   => -1,      // File pointer end.
			'lines'     => -1,      // Max lines to read.
			'mapping'   => array(), // Column mapping. csv_heading => schema_heading.
			'parse'     => false,   // Whether to sanitize and format data.
			'delimiter' => ',',     // CSV delimiter.
		);

		$this->params = wp_parse_args( $params, $default_args );
		$this->file   = $file;

		$this->read_file();
	}

	/**
	 * Process importer.
	 *
	 * @return array
	 */
	public function import() {
		$data = array(
			'imported' => array(),
			'failed'   => array(),
		);

		foreach ( $this->parsed_data as $parsed_data ) {
			$result = $this->process_item( $data );

			if ( is_wp_error( $result ) ) {
				$data['failed'][] = $result;
			} else {
				$data['imported'][] = $result;

				// Clean cache for updated products.
				wc_delete_product_transients( $result->get_id() );
				wp_cache_delete( 'product-' . $result->get_id(), 'products' );
			}
		}

		return $data;
	}

	/**
	 * Process each item and save.
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

			return $object->get_id();
		} catch ( WC_Data_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		} catch ( Exception $e ) {
			return new WP_Error( 'woocommerce_product_csv_importer_error', $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

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
		return apply_filters( 'woocommerce_csv_product_parsed_data', $this->parsed_data, $this->get_raw_data() );
	}

	/**
	 * Read file.
	 *
	 * @return array
	 */
	protected function read_file() {
		if ( false !== ( $handle = fopen( $this->file, 'r' ) ) ) {
			$this->raw_keys = fgetcsv( $handle, 0, $this->params['delimiter'] );

			if ( 0 !== $this->params['start_pos'] ) {
				fseek( $handle, (int) $this->params['start_pos'] );
			}

			while ( false !== ( $row = fgetcsv( $handle, 0, $this->params['delimiter'] ) ) ) {
				$this->raw_data[] = $row;

	            if ( ( $this->params['end_pos'] > 0 && ftell( $handle ) >= $this->params['end_pos'] ) || 0 === --$this->params['lines'] ) {
	            	break;
				}
			}
		}

		if ( ! empty( $this->params['mapping'] ) ) {
			$this->set_mapped_keys();
		}

		if ( $this->params['parse'] ) {
			$this->set_parsed_data();
		}
	}

	/**
	 * Set file mapped keys.
	 *
	 * @return array
	 */
	protected function set_mapped_keys() {
		$mapping = $this->params['mapping'];

		foreach ( $this->raw_keys as $key ) {
			$this->mapped_keys[] = isset( $mapping[ $key ] ) ? $mapping[ $key ] : $key;
		}
	}

	/**
	 * Parse a comma-delineated field from a CSV.
	 *
	 * @param string $field
	 * @return array
	 */
	protected function parse_comma_field( $field ) {
		if ( empty( $field ) ) {
			return array();
		}

		return array_map( 'esc_attr', array_map( 'trim', explode( ',', $field ) ) );
	}

	/**
	 * Parse a field that is generally '1' or '0' but can be something else.
	 *
	 * @param string $field
	 * @return bool|string
	 */
	protected function parse_bool_field( $field ) {
		if ( '0' === $field ) {
			return false;
		}

		if ( '1' === $field ) {
			return true;
		}

		// Don't return explicit true or false for empty fields or values like 'notify'.
		return esc_attr( $field );
	}

	/**
	 * Parse a float value field.
	 *
	 * @param string $field
	 * @return float|string
	 */
	protected function parse_float_field( $field ) {
		if ( '' === $field ) {
			return $field;
		}

		return floatval( $field );
	}

	/**
	 * Parse a category field from a CSV.
	 * Categories are separated by commas and subcategories are "parent > subcategory".
	 *
	 * @param string $field
	 * @return array of arrays with "parent" and "name" keys.
	 */
	protected function parse_categories( $field ) {
		if ( empty( $field ) ) {
			return array();
		}

		$sections = array_map( 'trim', explode( ',', $field ) );
		$categories = array();

		foreach ( $sections as $section ) {

			// Top level category.
			if ( false === strpos( $section, '>' ) ) {
				$categories[] = array(
					'parent' => false,
					'name'   => esc_attr( $section ),
				);

			// Subcategory.
			} else {
				$chunks = array_map( 'trim', explode( '>', $section ) );
				$categories[] = array(
					'parent' => esc_attr( reset( $chunks ) ),
					'name'   => esc_attr( end( $chunks ) ),
				);
			}
		}

		return $categories;
	}

	/**
	 * Map and format raw data to known fields.
	 *
	 * @return array
	 */
	protected function set_parsed_data() {

		/**
		 * Columns not mentioned here will get parsed with 'esc_attr'.
		 * column_name => callback.
		 */
		$data_formatting = array(
			'id'                => 'absint',
			'status'            => array( $this, 'parse_bool_field' ),
			'featured'          => array( $this, 'parse_bool_field' ),
			'date_on_sale_from' => 'strtotime',
			'date_on_sale_to'   => 'strtotime',
			'manage_stock'      => array( $this, 'parse_bool_field' ),
			'backorders'        => array( $this, 'parse_bool_field' ),
			'sold_individually' => array( $this, 'parse_bool_field' ),
			'width'             => array( $this, 'parse_float_field' ),
			'length'            => array( $this, 'parse_float_field' ),
			'height'            => array( $this, 'parse_float_field' ),
			'weight'            => array( $this, 'parse_float_field' ),
			'reviews_allowed'   => array( $this, 'parse_bool_field' ),
			'purchase_note'     => 'wp_kses_post',
			'price'             => 'wc_format_decimal',
			'regular_price'     => 'wc_format_decimal',
			'stock_quantity'    => 'absint',
			'category_ids'      => array( $this, 'parse_categories' ),
			'tag_ids'           => array( $this, 'parse_comma_field' ),
			'images'            => array( $this, 'parse_comma_field' ),
			'upsell_ids'        => array( $this, 'parse_comma_field' ),
			'cross_sell_ids'    => array( $this, 'parse_comma_field' ),
			'download_limit'    => 'absint',
			'download_expiry'   => 'absint',
		);

		/**
		 * @todo switch these to some standard, slug format.
		 */
		$regex_match_data_formatting = array(
			'/Attribute * Value\(s\)/' => array( $this, 'parse_comma_field' ),
			'/Attribute * Visible/'    => array( $this, 'parse_bool_field' ),
			'/Download * URL/'         => 'esc_url',
		);

		$headers         = ! empty( $this->mapped_keys ) ? $this->mapped_keys : $this->raw_keys;
		$parse_functions = array();

		// Figure out the parse function for each column.
		foreach ( $headers as $index => $heading ) {

			$parse_function = 'esc_attr';
			if ( isset( $data_formatting[ $heading ] ) ) {
				$parse_function = $data_formatting[ $heading ];
			} else {
				foreach ( $regex_match_data_formatting as $regex => $callback ) {
					if ( preg_match( $regex, $heading ) ) {
						$parse_function = $callback;
						break;
					}
				}
			}

			$parse_functions[] = $parse_function;
		}

		// Parse the data.
		foreach ( $this->raw_data as $row ) {
			$item = array();
			foreach ( $row as $index => $field ) {
				$item[ $headers[ $index ] ] = call_user_func( $parse_functions[ $index ], $field );
			}
			$this->parsed_data[] = $item;
		}
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
			return new WP_Error( 'variation_not_supported', __( 'Variations are not supported yet.', 'woocommerce' ), array( 'status' => 401 ) );
		}

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
			$product->set_status( get_post_status_object( $data['status'] ) ? $data['status'] : 'draft' );
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
			$product = $this->set_product_images( $product, $data['images'] );
		}

		// Allow set meta_data.
		if ( isset( $data['meta_data'] ) && is_array( $data['meta_data'] ) ) {
			foreach ( $data['meta_data'] as $meta ) {
				$product->update_meta_data( $meta['key'], $meta['value'], isset( $meta['id'] ) ? $meta['id'] : '' );
			}
		}

		return apply_filters( 'woocommerce_product_csv_import_pre_insert_product_object', $product, $data );
	}

	/**
	 * Set product images.
	 *
	 * @param WC_Product $product Product instance.
	 * @param array      $images  Images data.
	 * @return WC_Product
	 */
	protected function set_product_images( $product, $images ) {
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
			if ( isset( $data['dimensions']['height'] ) ) {
				$product->set_height( $data['dimensions']['height'] );
			}

			// Width.
			if ( isset( $data['dimensions']['width'] ) ) {
				$product->set_width( $data['dimensions']['width'] );
			}

			// Length.
			if ( isset( $data['dimensions']['length'] ) ) {
				$product->set_length( $data['dimensions']['length'] );
			}
		}

		// Shipping class.
		if ( isset( $data['shipping_class'] ) ) {
			$shipping_class_term = get_term_by( 'slug', wc_clean( $data['shipping_class'] ), 'product_shipping_class' );

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
	 * @param WP_REST_Request $request Request data.
	 * @return WC_Product
	 */
	protected function save_default_attributes( $product, $request ) {
		if ( isset( $request['default_attributes'] ) && is_array( $request['default_attributes'] ) ) {

			$attributes         = $product->get_attributes();
			$default_attributes = array();

			foreach ( $request['default_attributes'] as $attribute ) {
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
