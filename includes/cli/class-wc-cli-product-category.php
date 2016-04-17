<?php

/**
 * Manage Product Categories.
 *
 * @since    2.5.0
 * @package  WooCommerce/CLI
 * @category CLI
 * @author   WooThemes
 */
//@formatter:off
class WC_CLI_Product_Category extends WC_CLI_Command {

	/**
	 * Get product category.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : Product category ID.
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole product category fields, returns the value of a single fields.
	 *
	 * [--fields=<fields>]
	 * : Get a specific subset of the product category's fields.
	 *
	 * [--format=<format>]
	 * : Accepted values: table, json, csv. Default: table.
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * * id
	 * * name
	 * * slug
	 * * parent
	 * * description
	 * * display
	 * * image
	 * * count
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc product category get 123
	 *
	 * @since 2.5.0
	 */
	public function get( $args, $assoc_args ) {
		try {
			$product_category = $this->get_product_category( $args[0] );

			$formatter = $this->get_formatter( $assoc_args );
			$formatter->display_item( $product_category );
		} catch ( WC_CLI_Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}
	}

	/**
	 * List of product categories.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : Filter products based on product property.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each product.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific product fields.
	 *
	 * [--format=<format>]
	 * : Acceptec values: table, csv, json, count, ids. Default: table.
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * * id
	 * * name
	 * * slug
	 * * parent
	 * * description
	 * * display
	 * * image
	 * * count
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc product category list
	 *
	 *     wp wc product category list --fields=id,name --format=json
	 *
	 * @subcommand list
	 * @since      2.5.0
	 */
	public function list_( $__, $assoc_args ) {
		try {
			$product_categories = array();
			$terms              = get_terms( 'product_cat', array( 'hide_empty' => false, 'fields' => 'ids' ) );

			foreach ( $terms as $term_id ) {
				$product_categories[] = $this->get_product_category( $term_id );
			}

			$formatter = $this->get_formatter( $assoc_args );
			$formatter->display_items( $product_categories );
		} catch ( WC_CLI_Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}
	}

	/**
	 * Get product category properties from given term ID.
	 *
	 * @since  2.5.0
	 * @param  int $term_id Category term ID
	 * @return array
	 * @throws WC_CLI_Exception
	 */
	protected function get_product_category( $term_id ) {
		$term_id = absint( $term_id );
		$term    = get_term( $term_id, 'product_cat' );

		if ( is_wp_error( $term ) || is_null( $term ) ) {
			throw new WC_CLI_Exception( 'woocommerce_cli_invalid_product_category_id', sprintf( __( 'Invalid product category ID "%s"', 'woocommerce' ), $term_id ) );
		}

		$term_id = intval( $term->term_id );

		// Get category display type.
		$display_type = get_woocommerce_term_meta( $term_id, 'display_type' );

		// Get category image.
		$image = '';
		if ( $image_id = get_woocommerce_term_meta( $term_id, 'thumbnail_id' ) ) {
			$image = wp_get_attachment_url( $image_id );
		}

		return array(
			'id'          => $term_id,
			'name'        => $term->name,
			'slug'        => $term->slug,
			'parent'      => $term->parent,
			'description' => $term->description,
			'display'     => $display_type ? $display_type : 'default',
			'image'       => $image ? esc_url( $image ) : '',
			'count'       => intval( $term->count )
		);
	}

	protected function get_product_category_by_name( $term_name ) {

		$term_meta = get_term_by( 'slug', $term_name, 'product_cat', OBJECT, 'woocommerce_cli_get_product_term_by_slug' );
		return $this->get_product_category( $term_meta->term_id );
	}

	protected function get_product_category_by_slug( $term_slug ) {

		$term_meta = get_term_by( 'name', $term_slug, 'product_cat', OBJECT, 'woocommerce_cli_get_product_term_by_name' );
		return $this->get_product_category( $term_meta->term_id );
	}

	/**
	 * Get default format fields that will be used in `list` and `get` subcommands.
	 *
	 * @since  2.5.0
	 * @return string
	 */
	protected function get_default_format_fields() {
		return 'id,name,slug,parent,description,display,image,count';
	}

	/**
	 * Create a new product category
	 *
	 * // display type = (products, subcategories, both)
	 *
	 * @param string $term
	 *
	 * @since 2.6.0
	 * @return array
	 */
	public function create( $__, $assoc_args ) {

		try {

			// Create and validate the term creation
			$product_category_meta = ( empty( $assoc_args ) ) ? wp_insert_term( $assoc_args[ 'name' ], 'product_cat' ) :
				wp_insert_term( $assoc_args[ 'name' ], 'product_cat', $assoc_args );

			if( is_object( $product_category_meta ) && ( 'WP_Error' == get_class( $product_category_meta ) ) ) {

				$error = array_pop( $product_category_meta->errors );
				throw new WC_CLI_Exception( $error[0], key( $error ) );
			}

			// Log successful creation
			WP_CLI::log( 'Product Category ' . $assoc_args[ 'name' ] . ' was created successfully.' );

			if ( array_key_exists( 'parent', $assoc_args ) ) {

				$term = $this->get_product_category( $assoc_args[ 'parent' ] );
				WP_CLI::log( "parent term: " . var_dump( $term ) );
				$assoc_args[ 'parent' ] = $term[ 'id' ];

			}

			// Read for default values
			$default_meta_values = $this->get_default_product_category_meta();

			$assignable_meta_values = array_merge( $default_meta_values, $assoc_args );

            foreach( $assignable_meta_values as $meta_key => $meta_value ) {

                add_term_meta( $product_category_meta[ 'term_id' ], $meta_key, $assignable_meta_values[ $meta_key ] );
            }

			return true;
		}
		catch ( WC_CLI_Exception $ex ) {

			WP_CLI::error( $ex->getErrorCode() );
		}
	}

	protected function get_default_product_category_meta() {

		return array (
			'display_type' => 'both',
			'thumbnail_id' => 0,
		);
	}

	protected function get_product_category_by_mixed( $term_identifier ) {

		$term = false;

		if ( is_int( $term_identifier ) ) {
			$term = $this->get_product_category( $term_identifier );
		}

		if ( ! $term ) {
			$term = $this->get_product_category_by_slug( $term_identifier );
		}

		if ( ! $term ) {
			$term = $this->get_product_category_by_name( $term_identifier );
		}

		return $term;
	}

}
