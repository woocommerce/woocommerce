<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product Variation Class.
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
	 * Parent data.
	 * @var array
	 */
	protected $parent_data = array(
		'sku'            => '',
		'manage_stock'   => '',
		'stock_quantity' => '',
		'weight'         => '',
		'length'         => '',
		'width'          => '',
		'height'         => '',
		'tax_class'      => '',
	);

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
	 * Prefix for action and filter hooks on data.
	 *
	 * @since  2.7.0
	 * @return string
	 */
	protected function get_hook_prefix() {
		return 'woocommerce_product_variation_get_';
	}

	/**
	 * Get internal type.
	 * @return string
	 */
	public function get_type() {
		return 'variation';
	}

	/**
	 * If the stock level comes from another product ID.
	 * @since  2.7.0
	 * @return int
	 */
	public function get_stock_managed_by_id() {
		return 'parent' === $this->get_manage_stock() ? $this->get_parent_id() : $this->get_id();
	}

	/**
	 * Get variation attribute values.
	 *
	 * @return array of attributes and their values for this variation
	 */
	public function get_variation_attributes() {
		return $this->get_attributes();
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

	/**
	 * Get SKU (Stock-keeping unit) - product unique ID.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_sku( $context = 'view' ) {
		$value = $this->get_prop( 'sku', $context );

		// Inherit value from parent.
		if ( 'view' === $context && empty( $value ) ) {
			$value = $this->parent_data['sku'];
		}
		return $value;
	}

	/**
	 * Returns the product's weight.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_weight( $context = 'view' ) {
		$value = $this->get_prop( 'weight', $context );

		// Inherit value from parent.
		if ( 'view' === $context && empty( $value ) ) {
			$value = $this->parent_data['weight'];
		}
		return $value;
	}

	/**
	 * Returns the product length.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_length( $context = 'view' ) {
		$value = $this->get_prop( 'length', $context );

		// Inherit value from parent.
		if ( 'view' === $context && empty( $value ) ) {
			$value = $this->parent_data['length'];
		}
		return $value;
	}

	/**
	 * Returns the product width.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_width( $context = 'view' ) {
		$value = $this->get_prop( 'width', $context );

		// Inherit value from parent.
		if ( 'view' === $context && empty( $value ) ) {
			$value = $this->parent_data['width'];
		}
		return $value;
	}

	/**
	 * Returns the product height.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_height( $context = 'view' ) {
		$value = $this->get_prop( 'height', $context );

		// Inherit value from parent.
		if ( 'view' === $context && empty( $value ) ) {
			$value = $this->parent_data['height'];
		}
		return $value;
	}

	/**
	 * Returns the tax class.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_tax_class( $context = 'view' ) {
		$value = $this->get_prop( 'tax_class', $context );

		// Inherit value from parent.
		if ( 'view' === $context && empty( $value ) ) {
			$value = $this->parent_data['tax_class'];
		}
		return $value;
	}

	/**
	 * Return if product manage stock.
	 *
	 * @since 2.7.0
	 * @param  string $context
	 * @return boolean|string true, false, or parent.
	 */
	public function get_manage_stock( $context = 'view' ) {
		$value = $this->get_prop( 'manage_stock', $context );

		// Inherit value from parent.
		if ( 'view' === $context && false === $value && true === wc_string_to_bool( $this->parent_data['manage_stock'] ) ) {
			$value = 'parent';
		}
		return $value;
	}

	/**
	 * Returns number of items available for sale.
	 *
	 * @param  string $context
	 * @return int|null
	 */
	public function get_stock_quantity( $context = 'view' ) {
		$value = $this->get_prop( 'stock_quantity', $context );

		// Inherit value from parent.
		if ( 'view' === $context && 'parent' === $this->get_manage_stock() ) {
			$value = $this->parent_data['stock_quantity'];
		}
		return $value;
	}

	/**
	 * Get backorders.
	 *
	 * @param  string $context
	 * @since 2.7.0
	 * @return string yes no or notify
	 */
	public function get_backorders( $context = 'view' ) {
		$value = $this->get_prop( 'backorders', $context );

		// Inherit value from parent.
		if ( 'view' === $context && 'parent' === $this->get_manage_stock() ) {
			$value = $this->parent_data['backorders'];
		}
		return $value;
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
		$this->set_prop( 'attributes', (array) $attributes );
	}

	/**
	 * Returns array of attribute name value pairs.
	 *
	 * @param  string $context
	 * @return array
	 */
	public function get_attributes( $context = 'view' ) {
		return $this->get_prop( 'attributes', $context );
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

		$this->set_props( array(
			'name'              => get_the_title( $post_object ),
			'slug'              => $post_object->post_name,
			'date_created'      => $post_object->post_date,
			'date_modified'     => $post_object->post_modified,
			'status'            => $post_object->post_status,
			'menu_order'        => $post_object->menu_order,
			'reviews_allowed'   => 'open' === $post_object->comment_status,
		) );
		$this->read_product_data();
		$this->read_meta_data();
		$this->read_attributes();

		// Set object_read true once all data is read.
		$this->set_object_read( true );
	}

	/**
	 * Read post data. Can be overridden by child classes to load other props.
	 *
	 * @since 2.7.0
	 */
	protected function read_product_data() {
		$id = $this->get_id();
		$this->set_props( array(
			'description'       => get_post_meta( $id, '_variation_description', true ),
			'regular_price'     => get_post_meta( $id, '_regular_price', true ),
			'sale_price'        => get_post_meta( $id, '_sale_price', true ),
			'date_on_sale_from' => get_post_meta( $id, '_sale_price_dates_from', true ),
			'date_on_sale_to'   => get_post_meta( $id, '_sale_price_dates_to', true ),
			'tax_status'        => get_post_meta( $id, '_tax_status', true ),
			'manage_stock'      => get_post_meta( $id, '_manage_stock', true ),
			'stock_status'      => get_post_meta( $id, '_stock_status', true ),
			'shipping_class_id' => current( $this->get_term_ids( 'product_shipping_class' ) ),
			'virtual'           => get_post_meta( $id, '_virtual', true ),
			'downloadable'      => get_post_meta( $id, '_downloadable', true ),
			'downloads'         => array_filter( (array) get_post_meta( $id, '_downloadable_files', true ) ),
			'gallery_image_ids' => array_filter( explode( ',', get_post_meta( $id, '_product_image_gallery', true ) ) ),
			'download_limit'    => get_post_meta( $id, '_download_limit', true ),
			'download_expiry'   => get_post_meta( $id, '_download_expiry', true ),
			'image_id'          => get_post_thumbnail_id( $id ),
			'backorders'        => get_post_meta( $id, '_backorders', true ),
			'sku'               => get_post_meta( $id, '_sku', true ),
			'stock_quantity'    => get_post_meta( $id, '_stock', true ),
			'weight'            => get_post_meta( $id, '_weight', true ),
			'length'            => get_post_meta( $id, '_length', true ),
			'width'             => get_post_meta( $id, '_width', true ),
			'height'            => get_post_meta( $id, '_height', true ),
			'tax_class'         => get_post_meta( $id, '_tax_class', true ),
		) );

		if ( $this->is_on_sale() ) {
			$this->set_price( $this->get_sale_price() );
		} else {
			$this->set_price( $this->get_regular_price() );
		}

		$this->parent_data = array(
			'sku'            => get_post_meta( $this->get_parent_id(), '_sku', true ),
			'manage_stock'   => get_post_meta( $this->get_parent_id(), '_manage_stock', true ),
			'backorders'     => get_post_meta( $this->get_parent_id(), '_backorders', true ),
			'stock_quantity' => get_post_meta( $this->get_parent_id(), '_stock', true ),
			'weight'         => get_post_meta( $this->get_parent_id(), '_weight', true ),
			'length'         => get_post_meta( $this->get_parent_id(), '_length', true ),
			'width'          => get_post_meta( $this->get_parent_id(), '_width', true ),
			'height'         => get_post_meta( $this->get_parent_id(), '_height', true ),
			'tax_class'      => get_post_meta( $this->get_parent_id(), '_tax_class', true ),
		);
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
		parent::save();

		wc_delete_product_transients( $this->get_parent_id() );
		wp_schedule_single_event( time(), 'woocommerce_deferred_product_sync', array( 'product_id' => $this->get_parent_id() ) );

		return $this->get_id();
	}

	/**
	 * For all stored terms in all taxonomies, save them to the DB.
	 *
	 * @since 2.7.0
	 */
	protected function update_terms() {
		wp_set_post_terms( $this->get_id(), array( $this->get_shipping_class_id( 'edit' ) ), 'product_shipping_class', false );
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
