<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product Variation Class. @todo needs new getters/setters/changes code
 *
 * @todo removed filters need to be mapped via add_action to the product actions of similar naming.
 *       woocommerce_variation_is_in_stock
 *       woocommerce_variation_sale_price_html
 *       woocommerce_variation_price_html
 *       woocommerce_variation_free_price_html
 *       woocommerce_get_variation_price_html
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
	 * Post type.
	 * @var string
	 */
	protected $post_type = 'product_variation';

	/**
	 * Initialize simple product.
	 *
	 * @param mixed $product
	 */
	public function __construct( $product = 0 ) {
		$this->internal_meta_keys[] = '_variation_description';
		parent::__construct( $product );
	}

	/**
	 * Get internal type.
	 * @return string
	 */
	public function get_type() {
		return 'variation';
	}

	/**
	 * Get variation attribute values. @todo needed?
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
			$data         = array_intersect_key( array_combine( $data_keys, $data_values ), $this->get_attributes() );
		} else {
			$data = $this->get_attributes();
		}
		return add_query_arg( array_map( 'urlencode', array_filter( $data ) ), $this->get_permalink() );
	}

	/**
	 * Get the add to url used mainly in loops.
	 *
	 * @return string
	 */
	public function add_to_cart_url() {
		$variation_data = array_map( 'urlencode', $this->get_attributes() );
		$url            = $this->is_purchasable() ? remove_query_arg( 'added-to-cart', add_query_arg( array( 'variation_id' => $this->get_id(), 'add-to-cart' => $this->get_parent_id() ), $this->get_permalink() ) ) : $this->get_permalink();
		return apply_filters( 'woocommerce_product_add_to_cart_url', $url, $this );
	}

	/*
	|--------------------------------------------------------------------------
	| CRUD methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Callback to remove unwanted meta data.
	 *
	 * @param object $meta
	 * @return bool false if excluded.
	 */
	protected function exclude_internal_meta_keys( $meta ) {
		return ! in_array( $meta->meta_key, $this->get_internal_meta_keys() ) && 0 !== stripos( $meta->meta_key, 'attribute_' );
	}

	/**
	 * Set attributes. Unlike the parent product which uses terms, variations are assigned
	 * specific attributes using name value pairs.
	 * @param array
	 */
	public function set_attributes( $attributes ) {
		$this->data['attributes'] = (array) $attributes;
	}

	/**
	 * Returns array of attribute name value pairs.
	 * @return array
	 */
	public function get_attributes() {
		return $this->data['attributes'];
	}

	/**
	 * Reads a product from the database and sets its data to the class.
	 *
	 * @since 2.7.0
	 * @param int $id Variation ID.
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
			'name'              => get_the_title( $post_object ),
			'slug'              => $post_object->post_name,
			'status'            => $post_object->post_status,
			'date_created'      => $post_object->post_date,
			'date_modified'     => $post_object->post_modified,
			'description'       => get_post_meta( $id, '_variation_description', true ),
			'regular_price'     => get_post_meta( $id, '_regular_price', true ),
			'sale_price'        => get_post_meta( $id, '_sale_price', true ),
			'date_on_sale_from' => get_post_meta( $id, '_sale_price_dates_from', true ),
			'date_on_sale_to'   => get_post_meta( $id, '_sale_price_dates_to', true ),
			'tax_status'        => get_post_meta( $id, '_tax_status', true ),
			'manage_stock'      => get_post_meta( $id, '_manage_stock', true ),
			'stock_quantity'    => get_post_meta( $id, '_stock', true ),
			'stock_status'      => get_post_meta( $id, '_stock_status', true ),
			'menu_order'        => $post_object->menu_order,
			'shipping_class_id' => current( $this->get_term_ids( 'product_shipping_class' ) ),
			'virtual'           => get_post_meta( $id, '_virtual', true ),
			'downloadable'      => get_post_meta( $id, '_downloadable', true ),
			'downloads'         => array_filter( (array) get_post_meta( $id, '_downloadable_files', true ) ),
			'gallery_image_ids' => array_filter( explode( ',', get_post_meta( $id, '_product_image_gallery', true ) ) ),
			'download_limit'    => get_post_meta( $id, '_download_limit', true ),
			'download_expiry'   => get_post_meta( $id, '_download_expiry', true ),
			'image_id'          => get_post_thumbnail_id( $id ),
			'backorders'        => get_post_meta( $this->get_id(), '_backorders', true ),
		) );

		// Data that can be inherited from the parent product.
		$inherit_on_empty = array(
			'_sku'    => 'sku',
			'_stock'  => 'stock_quantity',  // @todo test this
			'_weight' => 'weight',
			'_length' => 'length',
			'_width'  => 'width',
			'_height' => 'height',
		);
		$parent_props     = array();

		foreach ( $inherit_on_empty as $meta_key => $prop ) {
			$value = get_post_meta( $this->get_id(), $meta_key, true );
			if ( '' !== $value ) {
				$inherit_props[ $prop ] = $value;
			} else {
				$inherit_props[ $prop ] = get_post_meta( $this->get_parent_id(), $meta_key, true );
			}
		}

		$tax_class = get_post_meta( $this->get_id(), '_tax_class', true );

		if ( 'parent' === $tax_class || ! metadata_exists( 'post', $this->get_id(), '_tax_class' ) ) {
			$inherit_props['tax_class'] = get_post_meta( $this->get_parent_id(), '_tax_class', true );
		} else {
			$inherit_props['tax_class'] = $tax_class;
		}

		$this->set_props( $inherit_props );

		if ( $this->is_on_sale() ) {
			$this->set_price( $this->get_sale_price() );
		} else {
			$this->set_price( $this->get_regular_price() );
		}

		$this->read_meta_data();
		$this->read_attributes();
	}

	/**
	 * Create a new product.
	 *
	 * @since 2.7.0
	 */
	public function create() {
		$this->set_date_created( current_time( 'timestamp' ) );

		$id = wp_insert_post( apply_filters( 'woocommerce_new_product_variation_data', array(
			'post_type'      => $this->post_type,
			'post_status'    => $this->get_status() ? $this->get_status() : 'publish',
			'post_author'    => get_current_user_id(),
			'post_title'     => get_the_title( $this->get_parent_id() ) . ' &ndash;' . wc_get_formatted_variation( $this->get_attributes(), true ),
			'post_content'   => '',
			'post_parent'    => $this->get_parent_id(),
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
			'menu_order'     => $this->get_menu_order(),
			'post_date'      => date( 'Y-m-d H:i:s', $this->get_date_created() ),
			'post_date_gmt'  => get_gmt_from_date( date( 'Y-m-d H:i:s', $this->get_date_created() ) ),
		) ), true );

		if ( $id && ! is_wp_error( $id ) ) {
			$this->set_id( $id );
			$this->update_post_meta();
			$this->update_terms();
			$this->update_attributes();
			$this->save_meta_data();
			do_action( 'woocommerce_create_' . $this->post_type, $id );
		}
	}

	/**
	 * Updates an existing product.
	 *
	 * @since 2.7.0
	 */
	public function update() {
		$post_data = array(
			'ID'             => $this->get_id(),
			'post_title'     => get_the_title( $this->get_parent_id() ) . ' &ndash;' . wc_get_formatted_variation( $this->get_attributes(), true ),
			'post_parent'    => $this->get_parent_id(),
			'comment_status' => 'closed',
			'post_status'    => $this->get_status() ? $this->get_status() : 'publish',
			'menu_order'     => $this->get_menu_order(),
		);
		wp_update_post( $post_data );
		$this->update_post_meta();
		$this->update_terms();
		$this->update_attributes();
		$this->save_meta_data();
		do_action( 'woocommerce_update_' . $this->post_type, $this->get_id() );
	}

	/**
	 * Helper method that updates all the post meta for a product based on it's settings in the WC_Product class.
	 *
	 * @since 2.7.0
	 */
	public function update_post_meta() {
		update_post_meta( $this->get_id(), '_variation_description', $this->get_description() );
		parent::update_post_meta();
	}

	/**
	 * Save data (either create or update depending on if we are working on an existing product).
	 *
	 * @since 2.7.0
	 */
	public function save() {
		if ( $this->get_id() ) {
			$this->update();
		} else {
			$this->create();
		}
		WC_Product_Variable::sync( $this->get_parent_id() );
		return $this->get_id();
	}

	/**
	 * For all stored terms in all taxonomies, save them to the DB.
	 *
	 * @since 2.7.0
	 */
	protected function update_terms() {
		wp_set_post_terms( $this->get_id(), array( $this->data['shipping_class_id'] ), 'product_shipping_class', false );
	}

	/**
	 * Read attributes from post meta.
	 *
	 * @since 2.7.0
	 */
	protected function read_attributes() {
		$this->set_attributes( wc_get_product_variation_attributes( $this->get_id() ) );
	}

	/**
	 * Update attribute meta values.
	 * @since 2.7.0
	 */
	protected function update_attributes() {
		global $wpdb;
		$attributes             = $this->get_attributes();
		$updated_attribute_keys = array();
		foreach ( $attributes as $key => $value ) {
			update_post_meta( $this->get_id(), 'attribute_' . $key, $value );
			$updated_attribute_keys[] = 'attribute_' . $key;
		}

		// Remove old taxonomies attributes so data is kept up to date - first get attribute key names.
		$delete_attribute_keys = $wpdb->get_col( $wpdb->prepare( "SELECT meta_key FROM {$wpdb->postmeta} WHERE meta_key LIKE 'attribute_%%' AND meta_key NOT IN ( '" . implode( "','", array_map( 'esc_sql', $updated_attribute_keys ) ) . "' ) AND post_id = %d;", $this->get_id() ) );

		foreach ( $delete_attribute_keys as $key ) {
			delete_post_meta( $this->get_id(), $key );
		}
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
