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
 * @version     3.0.0
 * @package     WooCommerce/Classes
 * @category    Class
 * @author      WooThemes
 */
class WC_Product_Variation extends WC_Product_Simple {

	/**
	 * Post type.
	 * @var string
	 */
	public $post_type = 'product_variation';

	/**
	 * Parent data.
	 * @var array
	 */
	protected $parent_data = array(
		'title'             => '',
		'sku'               => '',
		'manage_stock'      => '',
		'backorders'        => '',
		'stock_quantity'    => '',
		'weight'            => '',
		'length'            => '',
		'width'             => '',
		'height'            => '',
		'tax_class'         => '',
		'shipping_class_id' => '',
		'image_id'          => '',
	);

	/**
	 * Prefix for action and filter hooks on data.
	 *
	 * @since  3.0.0
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
	 * @since  3.0.0
	 * @return int
	 */
	public function get_stock_managed_by_id() {
		return 'parent' === $this->get_manage_stock() ? $this->get_parent_id() : $this->get_id();
	}

	/**
	 * Get the product's title. For variations this is the parent product name.
	 *
	 * @return string
	 */
	public function get_title() {
		return apply_filters( 'woocommerce_product_title', $this->parent_data['title'], $this );
	}

	/**
	 * Get variation attribute values. Keys are prefixed with attribute_, as stored.
	 *
	 * @return array of attributes and their values for this variation
	 */
	public function get_variation_attributes() {
		$attributes           = $this->get_attributes();
		$variation_attributes = array();
		foreach ( $attributes as $key => $value ) {
			$variation_attributes[ 'attribute_' . $key ] = $value;
		}
		return $variation_attributes;
	}

	/**
	 * Wrapper for get_permalink. Adds this variations attributes to the URL.
	 *
	 * @param  $item_object item array If a cart or order item is passed, we can get a link containing the exact attributes selected for the variation, rather than the default attributes.
	 * @return string
	 */
	public function get_permalink( $item_object = null ) {
		$url = get_permalink( $this->get_parent_id() );

		if ( ! empty( $item_object['variation'] ) ) {
			$data = $item_object['variation'];
		} elseif ( ! empty( $item_object['item_meta_array'] ) ) {
			$data_keys    = array_map( 'wc_variation_attribute_name', wp_list_pluck( $item_object['item_meta_array'], 'key' ) );
			$data_values  = wp_list_pluck( $item_object['item_meta_array'], 'value' );
			$data         = array_intersect_key( array_combine( $data_keys, $data_values ), $this->get_variation_attributes() );
		} else {
			$data         = $this->get_variation_attributes();
		}

		return add_query_arg( array_map( 'urlencode', array_filter( $data ) ), $url );
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
		if ( 'view' === $context && 'parent' === $value ) {
			$value = $this->parent_data['tax_class'];
		}
		return $value;
	}

	/**
	 * Return if product manage stock.
	 *
	 * @since 3.0.0
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
	 * @since 3.0.0
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

	/**
	 * Get main image ID.
	 *
	 * @since 3.0.0
	 * @param  string $context
	 * @return string
	 */
	public function get_image_id( $context = 'view' ) {
		$image_id = $this->get_prop( 'image_id', $context );

		if ( 'view' === $context && ! $image_id ) {
			$image_id = $this->parent_data['image_id'];
		}

		return $image_id;
	}

	/**
	 * Get shipping class ID.
	 *
	 * @since 3.0.0
	 * @param  string $context
	 * @return int
	 */
	public function get_shipping_class_id( $context = 'view' ) {
		$shipping_class_id = $this->get_prop( 'shipping_class_id', $context );

		if ( 'view' === $context && ! $shipping_class_id ) {
			$shipping_class_id = $this->parent_data['shipping_class_id'];
		}

		return $shipping_class_id;
	}

	/*
	|--------------------------------------------------------------------------
	| CRUD methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set the parent data array for this variation.
	 *
	 * @since 3.0.0
	 * @param array
	 */
	public function set_parent_data( $parent_data ) {
		$this->parent_data = $parent_data;
	}

	/**
	 * Get the parent data array for this variation.
	 *
	 * @since  3.0.0
	 * @return array
	 */
	public function get_parent_data() {
		return $this->parent_data;
	}

	/**
	 * Set attributes. Unlike the parent product which uses terms, variations are assigned
	 * specific attributes using name value pairs.
	 * @param array $raw_attributes
	 */
	public function set_attributes( $raw_attributes ) {
		$raw_attributes = (array) $raw_attributes;
		$attributes     = array();

		foreach ( $raw_attributes as $key => $value ) {
			// Remove attribute prefix which meta gets stored with.
			if ( 0 === strpos( $key, 'attribute_' ) ) {
				$key = substr( $key, 10 );
			}
			$attributes[ $key ] = $value;
		}
		$this->set_prop( 'attributes', $attributes );
	}

	/**
	 * Returns array of attribute name value pairs. Keys are prefixed with attribute_, as stored.
	 *
	 * @param  string $context
	 * @return array
	 */
	public function get_attributes( $context = 'view' ) {
		return $this->get_prop( 'attributes', $context );
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
