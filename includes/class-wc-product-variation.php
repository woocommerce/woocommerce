<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product Variation Class.
 *
 * @todo removed filters need to be mapped via add_action to the product actions of similar naming.
 *       woocommerce_variation_is_in_stock
 *       woocommerce_variation_sale_price_html
 *       woocommerce_variation_price_html
 *       woocommerce_variation_free_price_html
 *       woocommerce_get_variation_price_html
 *
 * The WooCommerce product variation class handles product variation data.
 *
 * @class       WC_Product_Variation
 * @version     2.7.0
 * @package     WooCommerce/Classes
 * @category    Class
 * @author      WooThemes
 */
class WC_Product_Variation extends WC_Product_Simple {

	/**
	 * Get internal type.
	 * @return string
	 */
	public function get_type() {
		return 'variation';
	}

	/**
	 * Get product name with SKU or ID. Used within admin.
	 *
	 * @return string Formatted product name
	 */
	public function get_formatted_name() {
		$formatted_attributes = wc_get_formatted_variation( $this->get_variation_attributes(), true );
		return parent::get_formatted_name() . ' &ndash; ' . $formatted_attributes . ' &ndash; ' . wc_price( $this->get_price() );
	}

	/**
	 * Get variation attribute values.
	 *
	 * @return array of attributes and their values for this variation
	 */
	public function get_variation_attributes() {
		return wc_get_product_variation_attributes( $this->get_id() );
	}

	/**
	 * Wrapper for get_permalink. Adds this variations attributes to the URL.
	 *
	 * @param  $item_object item array If a cart or order item is passed, we can get a link containing the exact attributes selected for the variation, rather than the default attributes.
	 * @return string
	 */
	public function get_permalink( $item_object = null ) {
		if ( ! empty( $item_object['variation'] ) ) {
			$data = $item_object['variation'];
		} elseif ( ! empty( $item_object['item_meta_array'] ) ) {
			$data_keys    = array_map( 'wc_variation_attribute_name', wp_list_pluck( $item_object['item_meta_array'], 'key' ) );
			$data_values  = wp_list_pluck( $item_object['item_meta_array'], 'value' );
			$data         = array_intersect_key( array_combine( $data_keys, $data_values ), $this->get_variation_attributes() );
		} else {
			$data = $this->get_variation_attributes();
		}
		return add_query_arg( array_map( 'urlencode', array_filter( $data ) ), get_permalink( $this->get_id() ) );
	}

	/**
	 * Get the add to url used mainly in loops.
	 *
	 * @return string
	 */
	public function add_to_cart_url() {
		$variation_data = array_map( 'urlencode', $this->get_variation_attributes() );
		$url            = $this->is_purchasable() ? remove_query_arg( 'added-to-cart', add_query_arg( array( 'variation_id' => $this->get_id(), 'add-to-cart' => $this->get_parent_id() ), $this->get_permalink() ) ) : $this->get_permalink();
		return apply_filters( 'woocommerce_product_add_to_cart_url', $url, $this );
	}

	/*
	|--------------------------------------------------------------------------
	| CRUD methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Reads a product from the database and sets its data to the class.
	 *
	 * @since 2.7.0
	 * @param int $id Product ID.
	 */
	public function read( $id ) {
		$this->set_defaults();

		if ( ! $id || ! ( $post_object = get_post( $id ) ) ) {
			return;
		}

		$this->set_id( $id );
		$this->set_parent_id( $post_object->post_parent );

		// The post doesn't have a parent id, therefore its invalid and we should prevent this being created.
		if ( empty( $this->get_parent_id() ) ) {
			throw new Exception( sprintf( 'No parent product set for variation #%d', $this->get_id() ), 422 );
		}

		// The post parent is not a valid variable product so we should prevent this being created.
		if ( 'product' !== get_post_type( $this->get_parent_id() ) ) {
			throw new Exception( sprintf( 'Invalid parent for variation #%d', $this->get_id() ), 422 );
		}

		// Variation data.
		$this->set_props( array(
			'name'                   => get_the_title( $post_object ),
			'slug'                   => $post_object->post_name,
			'permalink'              => get_permalink( $post_object ), // @todo Needed? Not used in getters and setters
			'status'                 => $post_object->post_status,
			'date_created'           => $post_object->post_date,
			'date_modified'          => $post_object->post_modified,
			'description'            => get_post_meta( $id, '_variation_description', true ),
			'regular_price'          => get_post_meta( $id, '_regular_price', true ),
			'sale_price'             => get_post_meta( $id, '_sale_price', true ),
			'date_on_sale_from'      => get_post_meta( $id, '_sale_price_dates_from', true ),
			'date_on_sale_to'        => get_post_meta( $id, '_sale_price_dates_to', true ),
			'tax_status'             => get_post_meta( $id, '_tax_status', true ),
			'manage_stock'           => get_post_meta( $id, '_manage_stock', true ),
			'stock_quantity'         => get_post_meta( $id, '_stock', true ),
			'stock_status'           => get_post_meta( $id, '_stock_status', true ),
			'menu_order'             => $post_object->menu_order,
			'shipping_class_id'      => current( $this->get_term_ids( 'product_shipping_class' ) ),
			'virtual'                => get_post_meta( $id, '_virtual', true ),
			'downloadable'           => get_post_meta( $id, '_downloadable', true ),
			'downloads'              => array_filter( (array) get_post_meta( $id, '_downloadable_files', true ) ),
			'gallery_attachment_ids' => array_filter( explode( ',', get_post_meta( $id, '_product_image_gallery', true ) ) ),
			'download_limit'         => get_post_meta( $id, '_download_limit', true ),
			'download_expiry'        => get_post_meta( $id, '_download_expiry', true ),
			'download_type'          => get_post_meta( $id, '_download_type', true ),
			'thumbnail_id'           => get_post_thumbnail_id( $id ),
		) );

		// Inherited data.
		$this->set_props( array(
			'tax_class'  => metadata_exists( 'post', $this->get_id(), '_tax_class' ) ? get_post_meta( $this->get_id(), '_tax_class', true ) : get_post_meta( $this->get_parent_id(), '_tax_class', true ),
			'backorders' => metadata_exists( 'post', $this->get_id(), '_backorders' ) ? get_post_meta( $this->get_id(), '_tax_class', true ) : get_post_meta( $this->get_parent_id(), '_tax_class', true ),
			'sku'        => metadata_exists( 'post', $this->get_id(), '_sku' ) ? get_post_meta( $this->get_id(), '_sku', true ) : get_post_meta( $this->get_parent_id(), '_sku', true ),
			'weight'     => metadata_exists( 'post', $this->get_id(), '_weight' ) ? get_post_meta( $this->get_id(), '_weight', true ) : get_post_meta( $this->get_parent_id(), '_weight', true ),
			'length'     => metadata_exists( 'post', $this->get_id(), '_length' ) ? get_post_meta( $this->get_id(), '_length', true ) : get_post_meta( $this->get_parent_id(), '_length', true ),
			'width'      => metadata_exists( 'post', $this->get_id(), '_width' ) ? get_post_meta( $this->get_id(), '_width', true ) : get_post_meta( $this->get_parent_id(), '_width', true ),
			'height'     => metadata_exists( 'post', $this->get_id(), '_height' ) ? get_post_meta( $this->get_id(), '_height', true ) : get_post_meta( $this->get_parent_id(), '_height', true ),
		) );

		if ( $this->is_on_sale() ) {
			$this->set_price( $this->get_sale_price() );
		} else {
			$this->set_price( $this->get_regular_price() );
		}

		$this->read_meta_data();
	}

	/*
	|--------------------------------------------------------------------------
	| Conditionals
	|--------------------------------------------------------------------------
	*/

	/**
	 * Returns whether or not the product has enough stock for the order.
	 *
	 * @param mixed $quantity
	 * @return bool
	 */
	public function has_enough_stock( $quantity ) {
		if ( $this->managing_stock() ) {
			return $this->backorders_allowed() || $this->get_stock_quantity() >= $quantity;
		} else {
			$parent = wc_get_product( $this->get_parent_id() );
			return $parent->has_enough_stock( $quantity );
		}
	}

	/**
	 * Returns false if the product cannot be bought.
	 * Override abstract method so that: i) Disabled variations are not be purchasable by admins. ii) Enabled variations are not purchasable if the parent product is not purchasable.
	 *
	 * @return bool
	 */
	public function is_purchasable() {
		return apply_filters( 'woocommerce_variation_is_purchasable', $this->variation_is_visible() && parent::is_purchasable(), $this );
	}

	/**
	 * Controls whether this particular variation will appear greyed-out (inactive) or not (active).
	 * Used by extensions to make incompatible variations appear greyed-out, etc.
	 * Other possible uses: prevent out-of-stock variations from being selected.
	 *
	 * @return bool
	 */
	public function variation_is_active() {
		return apply_filters( 'woocommerce_variation_is_active', true, $this );
	}

	/**
	 * Checks if this particular variation is visible. Invisible variations are enabled and can be selected, but no price / stock info is displayed.
	 * Instead, a suitable 'unavailable' message is displayed.
	 * Invisible by default: Disabled variations and variations with an empty price.
	 *
	 * @return bool
	 */
	public function variation_is_visible() {
		return apply_filters( 'woocommerce_variation_is_visible', 'publish' === get_post_status( $this->get_id() ) && '' !== $this->get_price(), $this->get_id(), $this->get_parent_id(), $this );
	}
}
