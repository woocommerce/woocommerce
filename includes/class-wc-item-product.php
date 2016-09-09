<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Order Line Item.
 *
 * @version     2.7.0
 * @since       2.7.0
 * @package     WooCommerce/Classes
 * @author      WooThemes
 */
class WC_Item_Product extends WC_Item {

	/**
	 * Data array.
	 * @since 2.7.0
	 * @var array
	 */
	 protected $_data = array(
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
	 * Read/populate data properties specific to this order item.
	 */
	public function read( $id ) {
		parent::read( $id );

		if ( ! $this->get_id() ) {
			return;
		}

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
		parent::save();
		if ( $this->get_id() ) {
			wc_update_order_item_meta( $this->get_id(), '_product_id', $this->get_product_id() );
			wc_update_order_item_meta( $this->get_id(), '_variation_id', $this->get_variation_id() );
			wc_update_order_item_meta( $this->get_id(), '_qty', $this->get_quantity() );
			wc_update_order_item_meta( $this->get_id(), '_tax_class', $this->get_tax_class() );
			wc_update_order_item_meta( $this->get_id(), '_line_subtotal', $this->get_subtotal() );
			wc_update_order_item_meta( $this->get_id(), '_line_subtotal_tax', $this->get_subtotal_tax() );
			wc_update_order_item_meta( $this->get_id(), '_line_total', $this->get_total() );
			wc_update_order_item_meta( $this->get_id(), '_line_tax', $this->get_total_tax() );
			wc_update_order_item_meta( $this->get_id(), '_line_tax_data', $this->get_taxes() );
		}

		return $this->get_id();
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
		if ( $this->get_variation_id() ) {
			$product = wc_get_product( $this->get_variation_id() );
		} else {
			$product = wc_get_product( $this->get_product_id() );
		}

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
	 * Get tax status.
	 * @return string
	 */
	public function get_tax_status() {
		$product = $this->get_product();
		return $product ? $product->get_tax_status() : 'taxable';
	}

	/**
	 * Set meta data for backordered products.
	 */
	public function set_backorder_meta() {
		if ( $this->get_product()->backorders_require_notification() && $this->get_product()->is_on_backorder( $this->get_quantity() ) ) {
			$this->add_meta_data( apply_filters( 'woocommerce_backordered_item_meta_name', __( 'Backordered', 'woocommerce' ) ), $this->get_quantity() - max( 0, $this->get_product()->get_total_stock() ), true );
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set quantity.
	 * @param int $value
	 * @throws WC_Data_Exception
	 */
	public function set_quantity( $value ) {
		if ( 0 >= $value ) {
			$this->error( 'order_item_product_invalid_quantity', __( 'Quantity must be positive', 'woocommerce' ) );
		}
		$this->_data['quantity'] = wc_stock_amount( $value );
	}

	/**
	 * Set tax class.
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_tax_class( $value ) {
		if ( $value && ! in_array( $value, WC_Tax::get_tax_classes() ) ) {
			$this->error( 'order_item_product_invalid_tax_class', __( 'Invalid tax class', 'woocommerce' ) );
		}
		$this->_data['tax_class'] = $value;
	}

	/**
	 * Set Product ID
	 * @param int $value
	 * @throws WC_Data_Exception
	 */
	public function set_product_id( $value ) {
		if ( $value > 0 && 'product' !== get_post_type( absint( $value ) ) ) {
			$this->error( 'order_item_product_invalid_product_id', __( 'Invalid product ID', 'woocommerce' ) );
		}
		$this->_data['product_id'] = absint( $value );
	}

	/**
	 * Set variation ID.
	 * @param int $value
	 * @throws WC_Data_Exception
	 */
	public function set_variation_id( $value ) {
		if ( $value > 0 && 'product_variation' !== get_post_type( $value ) ) {
			$this->error( 'order_item_product_invalid_variation_id', __( 'Invalid variation ID', 'woocommerce' ) );
		}
		$this->_data['variation_id'] = absint( $value );
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

	/**
	 * Set variation data (stored as meta data - write only).
	 * @param array $data Key/Value pairs
	 */
	public function set_variation( $data ) {
		foreach ( $data as $key => $value ) {
			$this->add_meta_data( str_replace( 'attribute_', '', $key ), $value, true );
		}
	}

	/**
	 * Set properties based on passed in product object.
	 * @param WC_Product $product
	 * @throws WC_Data_Exception
	 */
	public function set_product( $product ) {
		if ( ! is_a( $product, 'WC_Product' ) ) {
			$this->error( 'order_item_product_invalid_product', __( 'Invalid product', 'woocommerce' ) );
		}
		$this->set_product_id( $product->get_id() );
		$this->set_name( $product->get_title() );
		$this->set_tax_class( $product->get_tax_class() );
		$this->set_variation_id( is_callable( array( $product, 'get_variation_id' ) ) ? $product->get_variation_id() : 0 );
		$this->set_variation( is_callable( array( $product, 'get_variation_attributes' ) ) ? $product->get_variation_attributes() : array() );
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get order item type.
	 * @return string
	 */
	public function get_type() {
		return 'line_item';
	}

	/**
	 * Get product ID.
	 * @return int
	 */
	public function get_product_id() {
		return absint( $this->_data['product_id'] );
	}

	/**
	 * Get variation ID.
	 * @return int
	 */
	public function get_variation_id() {
		return absint( $this->_data['variation_id'] );
	}

	/**
	 * Get quantity.
	 * @return int
	 */
	public function get_quantity() {
		return wc_stock_amount( $this->_data['quantity'] );
	}

	/**
	 * Get tax class.
	 * @return string
	 */
	public function get_tax_class() {
		return $this->_data['tax_class'];
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






	/**
	 * Expands things like term slugs before return. @todo find correct place for this
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
}
