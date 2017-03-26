<?php

/**
 * Manage Product Categories.
 *
 * @since    2.5.0
 * @package  WooCommerce/CLI
 * @category CLI
 * @author   WooThemes
 */
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

	/**
	 * Get default format fields that will be used in `list` and `get` subcommands.
	 *
	 * @since  2.5.0
	 * @return string
	 */
	protected function get_default_format_fields() {
		return 'id,name,slug,parent,description,display,image,count';
	}
}
