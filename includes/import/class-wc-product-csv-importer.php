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
if ( ! class_exists( 'WC_Product_Importer', false ) ) {
	include_once( dirname( __FILE__ ) . '/abstract-wc-product-importer.php' );
}

/**
 * WC_Product_CSV_Importer Class.
 */
class WC_Product_CSV_Importer extends WC_Product_Importer {

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

			$this->file_position = ftell( $handle );
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
	 * Parse relative field and return product ID.
	 * Handle `id:xx` and SKUs.
	 *
	 * @param  string $field Field value.
	 * @return int|string
	 */
	protected function parse_relative_field( $field ) {
		if ( empty( $field ) ) {
			return '';
		}

		if ( preg_match( '/^id:(\d+)$/', $field, $matches ) ) {
			return intval( $matches[1] );
		}

		return wc_get_product_id_by_sku( $field );
	}

	/**
	 * Parse reletive comma-delineated field and return product ID.
	 *
	 * @param string $field Field value.
	 * @return array
	 */
	protected function parse_relative_comma_field( $field ) {
		if ( empty( $field ) ) {
			return array();
		}

		return array_filter( array_map( array( $this, 'parse_relative_field' ), array_map( 'trim', explode( ',', $field ) ) ) );
	}

	/**
	 * Parse a comma-delineated field from a CSV.
	 *
	 * @param string $field Field value.
	 * @return array
	 */
	protected function parse_comma_field( $field ) {
		if ( empty( $field ) ) {
			return array();
		}

		return array_map( 'wc_clean', array_map( 'trim', explode( ',', $field ) ) );
	}

	/**
	 * Parse a field that is generally '1' or '0' but can be something else.
	 *
	 * @param string $field Field value.
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
		return wc_clean( $field );
	}

	/**
	 * Parse a float value field.
	 *
	 * @param string $field Field value.
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
	 * @param string $field Field value.
	 * @return array of arrays with "parent" and "name" keys.
	 */
	protected function parse_categories_field( $field ) {
		if ( empty( $field ) ) {
			return array();
		}

		$row_terms  = array_map( 'trim', explode( ',', $field ) );
		$categories = array();

		foreach ( $row_terms as $row_term ) {
			$parent = null;
			$_terms = array_map( 'trim', explode( '>', $row_term ) );
			$total  = count( $_terms );

			foreach ( $_terms as $index => $_term ) {
				// Check if category exists. Parent must be empty string or null if doesn't exists.
				// @codingStandardsIgnoreStart
				$term = term_exists( $_term, 'product_cat', $parent );
				// @codingStandardsIgnoreEnd

				if ( is_array( $term ) ) {
					$term_id = $term['term_id'];
				} else {
					$term    = wp_insert_term( $_term, 'product_cat', array( 'parent' => intval( $parent ) ) );
					$term_id = $term['term_id'];
				}

				// Only requires assign the last category.
				if ( ( 1 + $index ) === $total ) {
					$categories[] = $term_id;
				} else {
					// Store parent to be able to insert or query categories based in parent ID.
					$parent = $term_id;
				}
			}
		}

		return $categories;
	}

	/**
	 * Parse a tag field from a CSV.
	 *
	 * @param  string $field Field value.
	 * @return array
	 */
	protected function parse_tags_field( $field ) {
		if ( empty( $field ) ) {
			return array();
		}

		$names = array_map( 'trim', explode( ',', $field ) );
		$tags  = array();

		foreach ( $names as $name ) {
			$term = get_term_by( 'name', $name, 'product_tag' );

			if ( ! $term ) {
				$term = (object) wp_insert_term( $name, 'product_tag' );
			}

			$tags[] = $term->term_id;
		}

		return $tags;
	}

	/**
	 * Parse a shipping class field from a CSV.
	 *
	 * @param  string $field Field value.
	 * @return int
	 */
	protected function parse_shipping_class_field( $field ) {
		$term = get_term_by( 'name', $field, 'product_shipping_class' );

		if ( ! $term ) {
			$term = (object) wp_insert_term( $name, 'product_shipping_class' );
		}

		return $term->term_id;
	}

	/**
	 * Parse images list from a CSV.
	 *
	 * @param  string $field Field value.
	 * @return array
	 */
	protected function parse_images_field( $field ) {
		if ( empty( $field ) ) {
			return array();
		}

		return array_map( 'esc_url_raw', array_map( 'trim', explode( ',', $field ) ) );
	}

	/**
	 * Get formatting callback.
	 *
	 * @return array
	 */
	protected function get_formating_callback() {

		/**
		 * Columns not mentioned here will get parsed with 'wc_clean'.
		 * column_name => callback.
		 */
		$data_formatting = array(
			'id'                => 'absint',
			'type'              => array( $this, 'parse_comma_field' ),
			'published'         => array( $this, 'parse_bool_field' ),
			'featured'          => array( $this, 'parse_bool_field' ),
			'date_on_sale_from' => 'strtotime',
			'date_on_sale_to'   => 'strtotime',
			'short_description' => 'wp_kses_post',
			'description'       => 'wp_kses_post',
			'manage_stock'      => array( $this, 'parse_bool_field' ),
			'backorders'        => array( $this, 'parse_bool_field' ),
			'stock_status'      => array( $this, 'parse_bool_field' ),
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
			'category_ids'      => array( $this, 'parse_categories_field' ),
			'tag_ids'           => array( $this, 'parse_tags_field' ),
			'shipping_class_id' => array( $this, 'parse_shipping_class_field' ),
			'image_id'          => array( $this, 'parse_images_field' ),
			'parent_id'         => array( $this, 'parse_relative_field' ),
			'upsell_ids'        => array( $this, 'parse_relative_comma_field' ),
			'cross_sell_ids'    => array( $this, 'parse_relative_comma_field' ),
			'download_limit'    => 'absint',
			'download_expiry'   => 'absint',
			'external_url'      => 'esc_url',
		);

		/**
		 * Match special column names.
		 */
		$regex_match_data_formatting = array(
			'/attributes:value*/'   => array( $this, 'parse_comma_field' ),
			'/attributes:visible*/' => array( $this, 'parse_bool_field' ),
			'/downloads:url*/'      => 'esc_url',
			'/meta:*/'              => 'wp_kses_post', // Allow some HTML in meta fields.
		);

		$callbacks = array();

		// Figure out the parse function for each column.
		foreach ( $this->get_mapped_keys() as $index => $heading ) {
			$callback = 'wc_clean';

			if ( isset( $data_formatting[ $heading ] ) ) {
				$callback = $data_formatting[ $heading ];
			} else {
				foreach ( $regex_match_data_formatting as $regex => $callback ) {
					if ( preg_match( $regex, $heading ) ) {
						$callback = $callback;
						break;
					}
				}
			}

			$callbacks[] = $callback;
		}

		return $callbacks;
	}

	/**
	 * Check if strings starts with determined word.
	 *
	 * @param  string $haystack Complete sentence.
	 * @param  string $needle   Excerpt.
	 * @return bool
	 */
	protected function starts_with( $haystack, $needle ) {
		return substr( $haystack, 0, strlen( $needle ) ) === $needle;
	}

	/**
	 * Expand special and internal data.
	 *
	 * @param  array $data Data to import.
	 * @return array
	 */
	protected function expand_data( $data ) {
		$data = apply_filters( 'woocommerce_product_importer_pre_expand_data', $data );

		// Meta data.
		$data['meta_data']          = array();
		$data['attributes']         = array();
		$data['default_attributes'] = array();
		$data['downloads']          = array();
		$data['gallery_image_ids']  = array();

		// Manage stock.
		if ( isset( $data['stock_quantity'] ) ) {
			$data['manage_stock'] = 0 < $data['stock_quantity'];
		}

		// Images.
		if ( isset( $data['image_id'] ) ) {
			$images           = $data['image_id'];
			$data['image_id'] = array_shift( $images );

			if ( ! empty( $images ) ) {
				$data['gallery_image_ids'] = $images;
			}
		}

		// Type, virtual and downlodable.
		if ( isset( $data['type'] ) ) {
			$data['virtual']      = in_array( 'virtual', $data['type'], true );
			$data['downloadable'] = in_array( 'downloadable', $data['type'], true );

			// Convert type to string.
			$data['type'] = current( array_diff( $data['type'], array( 'virtual', 'downloadable' ) ) );
		}

		// Handle special column names.
		foreach ( $data as $key => $value ) {
			// Attributes.
			if ( $this->starts_with( $key, 'attributes:name' ) ) {
				if ( ! empty( $value ) ) {
					$data['attributes'][ str_replace( 'attributes:name', '', $key ) ]['name'] = $value;
				}

				unset( $data[ $key ] );
			}
			if ( $this->starts_with( $key, 'attributes:value' ) ) {
				if ( ! empty( $value ) ) {
					$data['attributes'][ str_replace( 'attributes:value', '', $key ) ]['value'] = $value;
				}

				unset( $data[ $key ] );
			}
			if ( $this->starts_with( $key, 'attributes:visible' ) ) {
				if ( '' !== $value ) {
					$data['attributes'][ str_replace( 'attributes:visible', '', $key ) ]['visible'] = $value;
				}

				unset( $data[ $key ] );
			}

			// Default attributes
			if ( $this->starts_with( $key, 'attributes:default' ) ) {
				if ( ! empty( $value ) ) {
					$data['default_attributes'][] = $value;
				}

				unset( $data[ $key ] );
			}

			// Downloads.
			if ( $this->starts_with( $key, 'downloads:name' ) ) {
				if ( ! empty( $value ) ) {
					$data['downloads'][ str_replace( 'downloads:name', '', $key ) ]['name'] = $value;
				}

				unset( $data[ $key ] );
			}
			if ( $this->starts_with( $key, 'downloads:url' ) ) {
				if ( ! empty( $value ) ) {
					$data['downloads'][ str_replace( 'downloads:url', '', $key ) ]['url'] = $value;
				}

				unset( $data[ $key ] );
			}

			// Meta data.
			if ( $this->starts_with( $key, 'meta:' ) ) {
				$data['meta_data'][] = array(
					'key'   => str_replace( 'meta:', '', $key ),
					'value' => $value,
				);

				unset( $data[ $key ] );
			}
		}

		return $data;
	}

	/**
	 * Map and format raw data to known fields.
	 *
	 * @return array
	 */
	protected function set_parsed_data() {
		$parse_functions = $this->get_formating_callback();
		$mapped_keys     = $this->get_mapped_keys();

		// Parse the data.
		foreach ( $this->raw_data as $row ) {
			$data = array();

			foreach ( $row as $id => $value ) {
				// Skip ignored columns.
				if ( empty( $mapped_keys[ $id ] ) ) {
					continue;
				}

				$data[ $mapped_keys[ $id ] ] = call_user_func( $parse_functions[ $id ], $value );
			}

			$this->parsed_data[] = apply_filters( 'woocommerce_product_importer_parsed_data', $this->expand_data( $data ) );
		}
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
			$result = $this->process_item( $parsed_data );

			if ( is_wp_error( $result ) ) {
				$data['failed'][] = $result;
			} else {
				$data['imported'][] = $result;
			}
		}

		return $data;
	}
}
