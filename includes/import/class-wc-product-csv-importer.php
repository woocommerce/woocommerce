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
			'start_pos'       => 0, // File pointer start.
			'end_pos'         => -1, // File pointer end.
			'lines'           => -1, // Max lines to read.
			'mapping'         => array(), // Column mapping. csv_heading => schema_heading.
			'parse'           => false, // Whether to sanitize and format data.
			'update_existing' => false, // Whether to update existing items.
			'delimiter'       => ',', // CSV delimiter.
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

		$parse_functions   = array();
		$this->parsed_data = array();

		// If we have no mapped data, abort.
		if ( empty( $this->mapped_keys ) ) {
			return;
		}

		// Figure out the parse function for each column.
		foreach ( $this->mapped_keys as $index => $heading ) {

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
				$item[ $this->mapped_keys[ $index ] ] = call_user_func( $parse_functions[ $index ], $field );
			}
			$this->parsed_data[] = $item;
		}
	}

	/**
	 * Get a string to identify the row from parsed data.
	 *
	 * @param  array $parsed_data
	 * @return string
	 */
	protected function get_row_id( $parsed_data ) {
		$id       = isset( $parsed_data['id'] ) ? absint( $parsed_data['id'] ) : 0;
		$sku      = isset( $parsed_data['sku'] ) ? esc_attr( $parsed_data['sku'] ) : '';
		$name     = isset( $parsed_data['name'] ) ? esc_attr( $parsed_data['name'] ) : '';
		$row_data = array();

		if ( $name ) {
			$row_data[] = $name;
		}
		if ( $id ) {
			$row_data[] = __( 'ID: ', 'woocommerce' ) . $id;
		}
		if ( $sku ) {
			$row_data[] = __( 'SKU: ', 'woocommerce' ) . $sku;
		}

		return implode( ', ', $row_data );
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
			'updated'  => array(),
			'skipped'  => array(),
		);

		foreach ( $this->parsed_data as $parsed_data_key => $parsed_data ) {

			// Don't import products with IDs or SKUs that already exist if option is true.
			if ( ! $this->params['update_existing'] ) {
				$id  = isset( $parsed_data['id'] ) ? absint( $parsed_data['id'] )     : 0;
				$sku = isset( $parsed_data['sku'] ) ? esc_attr( $parsed_data['sku'] ) : '';

				if ( $id && wc_get_product( $id ) ) {
					$data['skipped'][] = new WP_Error( 'woocommerce_product_csv_importer_error', __( 'A product with this ID already exists.', 'woocommerce' ), array( 'id' => $id, 'row' => $this->get_row_id( $parsed_data ) ) );
					continue;
				} elseif ( $sku && wc_get_product_id_by_sku( $sku ) ) {
					$data['skipped'][] = new WP_Error( 'woocommerce_product_csv_importer_error', __( 'A product with this SKU already exists.', 'woocommerce' ), array( 'sku' => $sku, 'row' => $this->get_row_id( $parsed_data ) ) );
					continue;
				}
			}

			$result = $this->process_item( $parsed_data );

			if ( is_wp_error( $result ) ) {
				$result->add_data( array( 'row' => $this->get_row_id( $parsed_data ) ) );
				$data['failed'][]   = $result;
			} elseif ( ! empty( $parsed_data['id'] ) ) {
				$data['updated'][]  = $result;
			} else {
				$data['imported'][] = $result;
			}
		}

		return $data;
	}
}
