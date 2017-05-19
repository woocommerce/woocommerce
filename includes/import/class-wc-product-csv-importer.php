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
					'name'   => wc_clean( $section ),
				);

			// Subcategory.
			} else {
				$chunks = array_map( 'trim', explode( '>', $section ) );
				$categories[] = array(
					'parent' => wc_clean( reset( $chunks ) ),
					'name'   => wc_clean( end( $chunks ) ),
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
			'category_ids'      => array( $this, 'parse_categories' ),
			'tag_ids'           => array( $this, 'parse_comma_field' ),
			'image_id'          => array( $this, 'parse_comma_field' ),
			'parent_id'         => array( $this, 'parse_relative_field' ),
			'upsell_ids'        => array( $this, 'parse_relative_comma_field' ),
			'cross_sell_ids'    => array( $this, 'parse_relative_comma_field' ),
			'download_limit'    => 'absint',
			'download_expiry'   => 'absint',
		);

		/**
		 * Match special column names.
		 */
		$regex_match_data_formatting = array(
			'/attributes:value*/' => array( $this, 'parse_comma_field' ),
			'/downloads:url*/'    => 'esc_url',
		);

		$headers         = ! empty( $this->mapped_keys ) ? $this->mapped_keys : $this->raw_keys;
		$parse_functions = array();

		// Figure out the parse function for each column.
		foreach ( $headers as $index => $heading ) {
			$parse_function = 'wc_clean';

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
				// Skip ignored columns.
				if ( empty( $headers[ $index ] ) ) {
					continue;
				}

				$item[ $headers[ $index ] ] = call_user_func( $parse_functions[ $index ], $field );
			}

			$this->parsed_data[] = $item;
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
