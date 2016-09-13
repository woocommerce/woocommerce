<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Order Line Item (product).
 *
 * @version     2.7.0
 * @since       2.7.0
 * @package     WooCommerce/Classes
 * @author      WooThemes
 */
class WC_Order_Item_Product extends WC_Item_Product {

	/**
	 * May store an order to prevent retriving it multiple times.
	 * @var object
	 */
	protected $_order;

	/**
	 * Stores meta in cache for future reads.
	 * A group must be set to to enable caching.
	 * @var string
	 */
	protected $_cache_group = 'order_itemmeta';

	/**
	 * Meta type. This should match up with
	 * the types avaiable at https://codex.wordpress.org/Function_Reference/add_metadata.
	 * WP defines 'post', 'user', 'comment', and 'term'.
	 */
	protected $_meta_type = 'order_item';

	/**
	 * Data array.
	 * @since 2.7.0
	 * @var array
	 */
	 protected $_data = array(
		'order_id'     => 0,
		'name'         => '',
		'product_id'   => 0,
		'variation_id' => 0,
		'quantity'     => 1,
		'tax_class'    => '',
		'subtotal'     => 0,
		'subtotal_tax' => 0,
		'total'        => 0,
		'total_tax'    => 0,
		'taxes'        => array(
			'subtotal' => array(),
			'total'    => array(),
		),
	);

	/**
	 * offsetGet for ArrayAccess/Backwards compatibility.
	 * @deprecated Add deprecation notices in future release.
	 * @param string $offset
	 * @return mixed
	 */
	public function offsetGet( $offset ) {
		if ( 'line_subtotal' === $offset ) {
			$offset = 'subtotal';
		} elseif ( 'line_subtotal_tax' === $offset ) {
			$offset = 'subtotal_tax';
		} elseif ( 'line_total' === $offset ) {
			$offset = 'total';
		} elseif ( 'line_tax' === $offset ) {
			$offset = 'total_tax';
		} elseif ( 'line_tax_data' === $offset ) {
			$offset = 'taxes';
		}
		return parent::offsetGet( $offset );
	}

	/**
	 * offsetSet for ArrayAccess/Backwards compatibility.
	 * @deprecated Add deprecation notices in future release.
	 * @param string $offset
	 * @param mixed $value
	 */
	public function offsetSet( $offset, $value ) {
		if ( 'line_subtotal' === $offset ) {
			$offset = 'subtotal';
		} elseif ( 'line_subtotal_tax' === $offset ) {
			$offset = 'subtotal_tax';
		} elseif ( 'line_total' === $offset ) {
			$offset = 'total';
		} elseif ( 'line_tax' === $offset ) {
			$offset = 'total_tax';
		} elseif ( 'line_tax_data' === $offset ) {
			$offset = 'taxes';
		}
		parent::offsetSet( $offset, $value );
	}

	/**
	 * offsetExists for ArrayAccess
	 * @param string $offset
	 * @return bool
	 */
	public function offsetExists( $offset ) {
		if ( in_array( $offset, array( 'line_subtotal', 'line_subtotal_tax', 'line_total', 'line_tax', 'line_tax_data', 'item_meta_array', 'item_meta' ) ) ) {
			return true;
		}
		return parent::offsetExists( $offset );
	}

	/**
	 * Internal meta keys we don't want exposed as part of meta_data.
	 * @return array()
	 */
	protected function get_internal_meta_keys() {
		return array( '_product_id', '_variation_id', '_qty', '_tax_class', '_line_subtotal', '_line_subtotal_tax', '_line_total', '_line_tax', '_line_tax_data' );
	}

	/**
	 * Get the associated product.
	 * @return WC_Product|bool
	 */
	public function get_product() {
		$product = parent::get_product();

		// Backwards compatible filter from WC_Order::get_product_from_item()
		if ( has_filter( 'woocommerce_get_product_from_item' ) ) {
			$product = apply_filters( 'woocommerce_get_product_from_item', $product, $this, wc_get_order( $this->get_order_id() ) );
		}

		return apply_filters( 'woocommerce_order_item_product', $product, $this );
	}

	/**
	 * Get the Download URL.
	 * @param  int $download_id
	 * @return string
	 */
	public function get_item_download_url( $download_id ) {
		$order = $this->get_order();

		return $order ? add_query_arg( array(
			'download_file' => $this->get_variation_id() ? $this->get_variation_id() : $this->get_product_id(),
			'order'         => $order->get_order_key(),
			'email'         => urlencode( $order->get_billing_email() ),
			'key'           => $download_id,
		), trailingslashit( home_url() ) ) : '';
	}

	/**
	 * Get any associated downloadable files.
	 * @return array
	 */
	public function get_item_downloads() {
		global $wpdb;

		$files   = array();
		$product = $this->get_product();
		$order   = $this->get_order();

		if ( $product && $order && $product->is_downloadable() && $order->is_download_permitted() ) {
			$download_ids        = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT download_id FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions WHERE user_email = %s AND order_key = %s AND product_id = %d ORDER BY permission_id",
					$order->get_billing_email(),
					$order->get_order_key(),
					$this->get_variation_id() ? $this->get_variation_id() : $this->get_product_id()
				)
			);

			foreach ( $download_ids as $download_id ) {
				if ( $product->has_file( $download_id ) ) {
					$files[ $download_id ]                 = $product->get_file( $download_id );
					$files[ $download_id ]['download_url'] = $this->get_item_download_url( $download_id );
				}
			}
		}

		return apply_filters( 'woocommerce_get_item_downloads', $files, $this, $order );
	}

	/**
	 * Set meta data for backordered products.
	 */
	public function set_backorder_meta() {
		if ( $this->get_product()->backorders_require_notification() && $this->get_product()->is_on_backorder( $this->get_quantity() ) ) {
			$this->add_meta_data( apply_filters( 'woocommerce_backordered_item_meta_name', __( 'Backordered', 'woocommerce' ) ), $this->get_quantity() - max( 0, $this->get_product()->get_total_stock() ), true );
		}
	}

	/**
	 * Expands things like term slugs before return.
	 * @param string $hideprefix (default: _)
	 * @return array
	 */
	public function get_formatted_meta_data( $hideprefix = '_' ) {
		$formatted_meta = array();
		$meta_data      = $this->get_meta_data();

		foreach ( $meta_data as $meta ) {
			if ( "" === $meta->value || is_serialized( $meta->value ) || ( ! empty( $hideprefix ) && substr( $meta->key, 0, 1 ) === $hideprefix ) ) {
				continue;
			}

			$meta->key     = rawurldecode( $meta->key );
			$meta->value   = rawurldecode( $meta->value );
			$attribute_key = str_replace( 'attribute_', '', $meta->key );
			$display_key   = wc_attribute_label( $attribute_key, is_callable( array( $this, 'get_product' ) ) ? $this->get_product() : false );
			$display_value = $meta->value;

			if ( taxonomy_exists( $attribute_key ) ) {
				$term = get_term_by( 'slug', $meta->value, $attribute_key );
				if ( ! is_wp_error( $term ) && is_object( $term ) && $term->name ) {
					$display_value = $term->name;
				}
			}

			$formatted_meta[ $meta->id ] = (object) array(
				'key'           => $meta->key,
				'value'         => $meta->value,
				'display_key'   => apply_filters( 'woocommerce_order_item_display_meta_key', $display_key ),
				'display_value' => apply_filters( 'woocommerce_order_item_display_meta_value', wpautop( make_clickable( $display_value ) ) ),
			);
		}

		return $formatted_meta;
	}

	/*
	|--------------------------------------------------------------------------
	| CRUD methods
	|--------------------------------------------------------------------------
	|
	| Methods which create, read, update and delete data from the database.
	|
	*/

	/**
	 * Insert data into the database.
	 * @since 2.7.0
	 */
	public function create() {
		global $wpdb;

		$wpdb->insert( $wpdb->prefix . 'woocommerce_order_items', array(
			'order_item_name' => $this->get_name(),
			'order_item_type' => $this->get_type(),
			'order_id'        => $this->get_order_id(),
		) );
		$this->set_id( $wpdb->insert_id );

		do_action( 'woocommerce_new_order_item', $this->get_id(), $this, $this->get_order_id() );
	}

	/**
	 * Get data either from the passed object, or the DB, or return false;
	 * @param  mixed $item
	 * @return object|bool
	 */
	private function get_item_data( $item ) {
		global $wpdb;

		if ( is_numeric( $item ) && ! empty( $item ) ) {
			$data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_id = %d LIMIT 1;", $item ) );
		} elseif ( ! empty( $item->order_item_id ) ) {
			$data = $item;
		} else {
			$data = false;
		}

		return $data;
	}

	/**
	 * Read from the database.
	 * @since 2.7.0
	 * @param int|object $item ID of object to read, or already queried object.
	 */
	public function read( $item ) {
		$this->set_defaults();

		if ( ! $data = $this->get_item_data( $item ) ) {
			return;
		}

		$this->set_id( $data->order_item_id );
		$this->set_name( $data->order_item_name );
		$this->read_meta_data();
		$this->set_props( array(
			'product_id'   => get_metadata( 'order_item', $this->get_id(), '_product_id', true ),
			'variation_id' => get_metadata( 'order_item', $this->get_id(), '_variation_id', true ),
			'quantity'     => get_metadata( 'order_item', $this->get_id(), '_qty', true ),
			'tax_class'    => get_metadata( 'order_item', $this->get_id(), '_tax_class', true ),
			'subtotal'     => get_metadata( 'order_item', $this->get_id(), '_line_subtotal', true ),
			'total'        => get_metadata( 'order_item', $this->get_id(), '_line_total', true ),
			'taxes'        => get_metadata( 'order_item', $this->get_id(), '_line_tax_data', true ),
		) );
	}

	/**
	 * Save properties specific to this order item.
	 * @return int Item ID
	 */
	public function save() {
		$this->get_id() ? $this->update() : $this->create();
		$this->save_meta_data();
		wc_update_order_item_meta( $this->get_id(), '_product_id', $this->get_product_id() );
		wc_update_order_item_meta( $this->get_id(), '_variation_id', $this->get_variation_id() );
		wc_update_order_item_meta( $this->get_id(), '_qty', $this->get_quantity() );
		wc_update_order_item_meta( $this->get_id(), '_tax_class', $this->get_tax_class() );
		wc_update_order_item_meta( $this->get_id(), '_line_subtotal', $this->get_subtotal() );
		wc_update_order_item_meta( $this->get_id(), '_line_subtotal_tax', $this->get_subtotal_tax() );
		wc_update_order_item_meta( $this->get_id(), '_line_total', $this->get_total() );
		wc_update_order_item_meta( $this->get_id(), '_line_tax', $this->get_total_tax() );
		wc_update_order_item_meta( $this->get_id(), '_line_tax_data', $this->get_taxes() );

		return $this->get_id();
	}

	/**
	 * Delete data from the database.
	 * @since 2.7.0
	 */
	public function delete() {
		if ( $this->get_id() ) {
			global $wpdb;
			do_action( 'woocommerce_before_delete_order_item', $this->get_id() );
			$wpdb->delete( $wpdb->prefix . 'woocommerce_order_items', array( 'order_item_id' => $this->get_id() ) );
			$wpdb->delete( $wpdb->prefix . 'woocommerce_order_itemmeta', array( 'order_item_id' => $this->get_id() ) );
			do_action( 'woocommerce_delete_order_item', $this->get_id() );
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set order ID.
	 * @param int $value
	 * @throws WC_Data_Exception
	 */
	public function set_order_id( $value ) {
		$this->_data['order_id'] = absint( $value );
	}

	/**
	 * Line subtotal (before discounts).
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_subtotal( $value ) {
		$this->_data['subtotal'] = wc_format_decimal( $value );
	}

	/**
	 * Line total (after discounts).
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_total( $value ) {
		$this->_data['total'] = wc_format_decimal( $value );

		// Subtotal cannot be less than total
		if ( ! $this->get_subtotal() || $this->get_subtotal() < $this->get_total() ) {
			$this->set_subtotal( $value );
		}
	}

	/**
	 * Line subtotal tax (before discounts).
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	protected function set_subtotal_tax( $value ) {
		$this->_data['subtotal_tax'] = wc_format_decimal( $value );
	}

	/**
	 * Line total tax (after discounts).
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	protected function set_total_tax( $value ) {
		$this->_data['total_tax'] = wc_format_decimal( $value );
	}

	/**
	 * Set line taxes and totals for passed in taxes.
	 * @param array $raw_tax_data
	 * @throws WC_Data_Exception
	 */
	public function set_taxes( $raw_tax_data ) {
		$raw_tax_data = maybe_unserialize( $raw_tax_data );
		$tax_data     = array(
			'total'    => array(),
			'subtotal' => array(),
		);
		if ( ! empty( $raw_tax_data['total'] ) && ! empty( $raw_tax_data['subtotal'] ) ) {
			$tax_data['subtotal'] = array_map( 'wc_format_decimal', $raw_tax_data['subtotal'] );
			$tax_data['total']    = array_map( 'wc_format_decimal', $raw_tax_data['total'] );

			// Subtotal cannot be less than total!
			if ( array_sum( $tax_data['subtotal'] ) < array_sum( $tax_data['total'] ) ) {
				$tax_data['subtotal'] = $tax_data['total'];
			}
		}
		$this->_data['taxes'] = $tax_data;
		$this->set_total_tax( array_sum( $tax_data['total'] ) );
		$this->set_subtotal_tax( array_sum( $tax_data['subtotal'] ) );
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get parent order object.
	 * @return object
	 */
	public function get_order() {
		return $this->_order ? $this->_order : $this->_order = wc_get_order( $this->get_order_id() );
	}

	/**
	 * Get order ID this meta belongs to.
	 * @return int
	 */
	public function get_order_id() {
		return $this->_data['order_id'];
	}

	/**
	 * Get subtotal.
	 * @return string
	 */
	public function get_subtotal() {
		return wc_format_decimal( $this->_data['subtotal'] );
	}

	/**
	 * Get subtotal tax.
	 * @return string
	 */
	public function get_subtotal_tax() {
		return wc_format_decimal( $this->_data['subtotal_tax'] );
	}

	/**
	 * Get total.
	 * @return string
	 */
	public function get_total() {
		return wc_format_decimal( $this->_data['total'] );
	}

	/**
	 * Get total tax.
	 * @return string
	 */
	public function get_total_tax() {
		return wc_format_decimal( $this->_data['total_tax'] );
	}

	/**
	 * Get fee taxes.
	 * @return array
	 */
	public function get_taxes() {
		return $this->_data['taxes'];
	}
}
