<?php
/**
 * Product Data
 *
 * Displays the product data box, tabbed, with several panels covering price, stock etc.
 *
 * @author   WooThemes
 * @category Admin
 * @package  WooCommerce/Admin/Meta Boxes
 * @version  3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Meta_Box_Product_Data Class.
 */
class WC_Meta_Box_Product_Data {

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
	public static function output( $post ) {
		global $post, $thepostid, $product_object;

		$thepostid      = $post->ID;
		$product_object = $thepostid ? wc_get_product( $thepostid ) : new WC_Product;

		include( 'views/html-product-data-panel.php' );
	}

	/**
	 * Show tab content/settings.
	 */
	private static function output_tabs() {
		global $post, $thepostid, $product_object;

		include( 'views/html-product-data-general.php' );
		include( 'views/html-product-data-inventory.php' );
		include( 'views/html-product-data-shipping.php' );
		include( 'views/html-product-data-linked-products.php' );
		include( 'views/html-product-data-attributes.php' );
		include( 'views/html-product-data-advanced.php' );
	}

	/**
	 * Return array of product type options.
	 * @return array
	 */
	private static function get_product_type_options() {
		return apply_filters( 'product_type_options', array(
			'virtual' => array(
				'id'            => '_virtual',
				'wrapper_class' => 'show_if_simple',
				'label'         => __( 'Virtual', 'woocommerce' ),
				'description'   => __( 'Virtual products are intangible and aren\'t shipped.', 'woocommerce' ),
				'default'       => 'no',
			),
			'downloadable' => array(
				'id'            => '_downloadable',
				'wrapper_class' => 'show_if_simple',
				'label'         => __( 'Downloadable', 'woocommerce' ),
				'description'   => __( 'Downloadable products give access to a file upon purchase.', 'woocommerce' ),
				'default'       => 'no',
			),
		) );
	}

	/**
	 * Return array of tabs to show.
	 * @return array
	 */
	private static function get_product_data_tabs() {
		return apply_filters( 'woocommerce_product_data_tabs', array(
			'general' => array(
				'label'  => __( 'General', 'woocommerce' ),
				'target' => 'general_product_data',
				'class'  => array( 'hide_if_grouped' ),
			),
			'inventory' => array(
				'label'  => __( 'Inventory', 'woocommerce' ),
				'target' => 'inventory_product_data',
				'class'  => array( 'show_if_simple', 'show_if_variable', 'show_if_grouped', 'show_if_external' ),
			),
			'shipping' => array(
				'label'  => __( 'Shipping', 'woocommerce' ),
				'target' => 'shipping_product_data',
				'class'  => array( 'hide_if_virtual', 'hide_if_grouped', 'hide_if_external' ),
			),
			'linked_product' => array(
				'label'  => __( 'Linked Products', 'woocommerce' ),
				'target' => 'linked_product_data',
				'class'  => array(),
			),
			'attribute' => array(
				'label'  => __( 'Attributes', 'woocommerce' ),
				'target' => 'product_attributes',
				'class'  => array(),
			),
			'variations' => array(
				'label'  => __( 'Variations', 'woocommerce' ),
				'target' => 'variable_product_options',
				'class'  => array( 'variations_tab', 'show_if_variable' ),
			),
			'advanced' => array(
				'label'  => __( 'Advanced', 'woocommerce' ),
				'target' => 'advanced_product_data',
				'class'  => array(),
			),
		) );
	}

	/**
	 * Filter callback for finding variation attributes.
	 * @param  WC_Product_Attribute $attribute
	 * @return bool
	 */
	private static function filter_variation_attributes( $attribute ) {
		return true === $attribute->get_variation();
	}

	/**
	 * Show options for the variable product type.
	 */
	public static function output_variations() {
		global $post, $wpdb, $product_object;

		$variation_attributes   = array_filter( $product_object->get_attributes(), array( __CLASS__, 'filter_variation_attributes' ) );
		$default_attributes     = $product_object->get_default_attributes();
		$variations_count       = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM $wpdb->posts WHERE post_parent = %d AND post_type = 'product_variation' AND post_status IN ('publish', 'private')", $post->ID ) ) );
		$variations_per_page    = absint( apply_filters( 'woocommerce_admin_meta_boxes_variations_per_page', 15 ) );
		$variations_total_pages = ceil( $variations_count / $variations_per_page );

		include( 'views/html-product-data-variations.php' );
	}

	/**
	 * Prepare downloads for save.
	 * @return array
	 */
	private static function prepare_downloads( $file_names, $file_urls, $file_hashes ) {
		$downloads = array();

		if ( ! empty( $file_urls ) ) {
			$file_url_size = sizeof( $file_urls );

			for ( $i = 0; $i < $file_url_size; $i ++ ) {
				if ( ! empty( $file_urls[ $i ] ) ) {
					$downloads[] = array(
						'name'          => wc_clean( $file_names[ $i ] ),
						'file'          => wp_unslash( trim( $file_urls[ $i ] ) ),
						'previous_hash' => wc_clean( $file_hashes[ $i ] ),
					);
				}
			}
		}
		return $downloads;
	}

	/**
	 * Prepare children for save.
	 * @return array
	 */
	private static function prepare_children() {
		return isset( $_POST['grouped_products'] ) ? array_filter( array_map( 'intval', (array) $_POST['grouped_products'] ) ) : array();
	}

	/**
	 * Prepare attributes for save.
	 * @return array
	 */
	public static function prepare_attributes( $data = false ) {
		$attributes = array();

		if ( ! $data ) {
			$data = $_POST;
		}

		if ( isset( $data['attribute_names'], $data['attribute_values'] ) ) {
			$attribute_names         = $data['attribute_names'];
			$attribute_values        = $data['attribute_values'];
			$attribute_visibility    = isset( $data['attribute_visibility'] ) ? $data['attribute_visibility'] : array();
			$attribute_variation     = isset( $data['attribute_variation'] ) ? $data['attribute_variation'] : array();
			$attribute_position      = $data['attribute_position'];
			$attribute_names_max_key = max( array_keys( $attribute_names ) );

			for ( $i = 0; $i <= $attribute_names_max_key; $i++ ) {
				if ( empty( $attribute_names[ $i ] ) || ! isset( $attribute_values[ $i ] ) ) {
					continue;
				}
				$attribute_name = wc_clean( $attribute_names[ $i ] );
				$attribute_id   = wc_attribute_taxonomy_id_by_name( $attribute_name );
				$options        = isset( $attribute_values[ $i ] ) ? $attribute_values[ $i ] : '';

				if ( is_array( $options ) ) {
					// Term ids sent as array.
					$options = wp_parse_id_list( $options );
				} else {
					// Terms or text sent in textarea.
					$options = 0 < $attribute_id ? wc_sanitize_textarea( wc_sanitize_term_text_based( $options ) ) : wc_sanitize_textarea( $options );
					$options = wc_get_text_attributes( $options );
				}

				if ( empty( $options ) ) {
					continue;
				}

				$attribute = new WC_Product_Attribute();
				$attribute->set_id( $attribute_id );
				$attribute->set_name( $attribute_name );
				$attribute->set_options( $options );
				$attribute->set_position( $attribute_position[ $i ] );
				$attribute->set_visible( isset( $attribute_visibility[ $i ] ) );
				$attribute->set_variation( isset( $attribute_variation[ $i ] ) );
				$attributes[] = $attribute;
			}
		}
		return $attributes;
	}

	/**
	 * Prepare attributes for a specific variation or defaults.
	 * @param  array $all_attributes
	 * @param  string $key_prefix
	 * @param  int $index
	 * @return array
	 */
	private static function prepare_set_attributes( $all_attributes, $key_prefix = 'attribute_', $index = null ) {
		$attributes = array();

		if ( $all_attributes ) {
			foreach ( $all_attributes as $attribute ) {
				if ( $attribute->get_variation() ) {
					$attribute_key = sanitize_title( $attribute->get_name() );

					if ( ! is_null( $index ) ) {
						$value = isset( $_POST[ $key_prefix . $attribute_key ][ $index ] ) ? stripslashes( $_POST[ $key_prefix . $attribute_key ][ $index ] ) : '';
					} else {
						$value = isset( $_POST[ $key_prefix . $attribute_key ] ) ? stripslashes( $_POST[ $key_prefix . $attribute_key ] ) : '';
					}

					$value                        = $attribute->is_taxonomy() ? sanitize_title( $value ) : wc_clean( $value ); // Don't use wc_clean as it destroys sanitized characters in terms.
					$attributes[ $attribute_key ] = $value;
				}
			}
		}

		return $attributes;
	}

	/**
	 * Save meta box data.
	 */
	public static function save( $post_id, $post ) {
		// Process product type first so we have the correct class to run setters.
		$product_type = empty( $_POST['product-type'] ) ? WC_Product_Factory::get_product_type( $post_id ) : sanitize_title( stripslashes( $_POST['product-type'] ) );
		$classname    = WC_Product_Factory::get_product_classname( $post_id, $product_type ? $product_type : 'simple' );
		$product      = new $classname( $post_id );
		$attributes   = self::prepare_attributes();
		$errors       = $product->set_props( array(
			'sku'                => isset( $_POST['_sku'] ) ? wc_clean( $_POST['_sku'] ) : null,
			'purchase_note'      => wp_kses_post( stripslashes( $_POST['_purchase_note'] ) ),
			'downloadable'       => isset( $_POST['_downloadable'] ),
			'virtual'            => isset( $_POST['_virtual'] ),
			'featured'           => isset( $_POST['_featured'] ),
			'catalog_visibility' => wc_clean( $_POST['_visibility'] ),
			'tax_status'         => isset( $_POST['_tax_status'] ) ? wc_clean( $_POST['_tax_status'] ) : null,
			'tax_class'          => isset( $_POST['_tax_class'] ) ? wc_clean( $_POST['_tax_class'] ) : null,
			'weight'             => wc_clean( $_POST['_weight'] ),
			'length'             => wc_clean( $_POST['_length'] ),
			'width'              => wc_clean( $_POST['_width'] ),
			'height'             => wc_clean( $_POST['_height'] ),
			'shipping_class_id'  => absint( $_POST['product_shipping_class'] ),
			'sold_individually'  => ! empty( $_POST['_sold_individually'] ),
			'upsell_ids'         => isset( $_POST['upsell_ids'] ) ? array_map( 'intval', (array) $_POST['upsell_ids'] ) : array(),
			'cross_sell_ids'     => isset( $_POST['crosssell_ids'] ) ? array_map( 'intval', (array) $_POST['crosssell_ids'] ) : array(),
			'regular_price'      => wc_clean( $_POST['_regular_price'] ),
			'sale_price'         => wc_clean( $_POST['_sale_price'] ),
			'date_on_sale_from'  => wc_clean( $_POST['_sale_price_dates_from'] ),
			'date_on_sale_to'    => wc_clean( $_POST['_sale_price_dates_to'] ),
			'manage_stock'       => ! empty( $_POST['_manage_stock'] ),
			'backorders'         => wc_clean( $_POST['_backorders'] ),
			'stock_status'       => wc_clean( $_POST['_stock_status'] ),
			'stock_quantity'     => wc_stock_amount( $_POST['_stock'] ),
			'download_limit'     => '' === $_POST['_download_limit'] ? '' : absint( $_POST['_download_limit'] ),
			'download_expiry'    => '' === $_POST['_download_expiry'] ? '' : absint( $_POST['_download_expiry'] ),
			'downloads'          => self::prepare_downloads(
				isset( $_POST['_wc_file_names'] ) ? $_POST['_wc_file_names'] : array(),
				isset( $_POST['_wc_file_urls'] ) ? $_POST['_wc_file_urls'] : array(),
				isset( $_POST['_wc_file_hashes'] ) ? $_POST['_wc_file_hashes'] : array()
			),
			'product_url'        => esc_url_raw( $_POST['_product_url'] ),
			'button_text'        => wc_clean( $_POST['_button_text'] ),
			'children'           => 'grouped' === $product_type ? self::prepare_children() : null,
			'reviews_allowed'    => ! empty( $_POST['_reviews_allowed'] ),
			'attributes'         => $attributes,
			'default_attributes' => self::prepare_set_attributes( $attributes, 'default_attribute_' ),
		) );

		if ( is_wp_error( $errors ) ) {
			WC_Admin_Meta_Boxes::add_error( $errors->get_error_message() );
		}

		/**
		 * @since 3.0.0 to set props before save.
		 */
		do_action( 'woocommerce_admin_process_product_object', $product );

		$product->save();

		if ( $product->is_type( 'variable' ) ) {
			$product->get_data_store()->sync_variation_names( $product, wc_clean( $_POST['original_post_title'] ), wc_clean( $_POST['post_title'] ) );
		}

		do_action( 'woocommerce_process_product_meta_' . $product_type, $post_id );
	}

	/**
	 * Save meta box data.
	 *
	 * @param int $post_id
	 * @param WP_Post $post
	 */
	public static function save_variations( $post_id, $post ) {
		if ( isset( $_POST['variable_post_id'] ) ) {
			$parent     = wc_get_product( $post_id );
			$parent->set_default_attributes( self::prepare_set_attributes( $parent->get_attributes(), 'default_attribute_' ) );
			$parent->save();

			$max_loop   = max( array_keys( $_POST['variable_post_id'] ) );
			$data_store = $parent->get_data_store();
			$data_store->sort_all_product_variations( $parent->get_id() );

			for ( $i = 0; $i <= $max_loop; $i ++ ) {

				if ( ! isset( $_POST['variable_post_id'][ $i ] ) ) {
					continue;
				}
				$variation_id = absint( $_POST['variable_post_id'][ $i ] );
				$variation    = new WC_Product_Variation( $variation_id );
				$errors       = $variation->set_props( array(
					'status'            => isset( $_POST['variable_enabled'][ $i ] ) ? 'publish' : 'private',
					'menu_order'        => wc_clean( $_POST['variation_menu_order'][ $i ] ),
					'regular_price'     => wc_clean( $_POST['variable_regular_price'][ $i ] ),
					'sale_price'        => wc_clean( $_POST['variable_sale_price'][ $i ] ),
					'virtual'           => isset( $_POST['variable_is_virtual'][ $i ] ),
					'downloadable'      => isset( $_POST['variable_is_downloadable'][ $i ] ),
					'date_on_sale_from' => wc_clean( $_POST['variable_sale_price_dates_from'][ $i ] ),
					'date_on_sale_to'   => wc_clean( $_POST['variable_sale_price_dates_to'][ $i ] ),
					'description'       => wp_kses_post( $_POST['variable_description'][ $i ] ),
					'download_limit'    => wc_clean( $_POST['variable_download_limit'][ $i ] ),
					'download_expiry'   => wc_clean( $_POST['variable_download_expiry'][ $i ] ),
					'downloads'         => self::prepare_downloads(
						isset( $_POST['_wc_variation_file_names'][ $variation_id ] ) ? $_POST['_wc_variation_file_names'][ $variation_id ] : array(),
						isset( $_POST['_wc_variation_file_urls'][ $variation_id ] ) ? $_POST['_wc_variation_file_urls'][ $variation_id ] : array(),
						isset( $_POST['_wc_variation_file_hashes'][ $variation_id ] ) ? $_POST['_wc_variation_file_hashes'][ $variation_id ] : array()
					),
					'manage_stock'      => isset( $_POST['variable_manage_stock'][ $i ] ),
					'stock_quantity'    => wc_clean( $_POST['variable_stock'][ $i ] ),
					'backorders'        => wc_clean( $_POST['variable_backorders'][ $i ] ),
					'stock_status'      => wc_clean( $_POST['variable_stock_status'][ $i ] ),
					'image_id'          => wc_clean( $_POST['upload_image_id'][ $i ] ),
					'attributes'        => self::prepare_set_attributes( $parent->get_attributes(), 'attribute_', $i ),
					'sku'               => isset( $_POST['variable_sku'][ $i ] ) ? wc_clean( $_POST['variable_sku'][ $i ] )       : '',
					'weight'            => isset( $_POST['variable_weight'][ $i ] ) ? wc_clean( $_POST['variable_weight'][ $i ] ) : '',
					'length'            => isset( $_POST['variable_length'][ $i ] ) ? wc_clean( $_POST['variable_length'][ $i ] ) : '',
					'width'             => isset( $_POST['variable_width'][ $i ] ) ? wc_clean( $_POST['variable_width'][ $i ] )   : '',
					'height'            => isset( $_POST['variable_height'][ $i ] ) ? wc_clean( $_POST['variable_height'][ $i ] ) : '',
					'shipping_class_id' => wc_clean( $_POST['variable_shipping_class'][ $i ] ),
					'tax_class'         => isset( $_POST['variable_tax_class'][ $i ] ) ? wc_clean( $_POST['variable_tax_class'][ $i ] ) : null,
				) );

				if ( is_wp_error( $errors ) ) {
					WC_Admin_Meta_Boxes::add_error( $errors->get_error_message() );
				}

				$variation->save();

				do_action( 'woocommerce_save_product_variation', $variation_id, $i );
			}
		}
	}
}
