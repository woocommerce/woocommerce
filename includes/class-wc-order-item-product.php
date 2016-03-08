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
class WC_Order_Item_Product extends WC_Order_Item {

	/**
	 * Data properties of this order item object.
	 * @since 2.7.0
	 * @var array
	 */
	protected $_data = array(
		'order_id'      => 0,
		'order_item_id' => 0,
		'name'          => '',
		'product_id'    => 0,
		'variation_id'  => 0,
		'qty'           => 0,
		'tax_class'     => '',
		'subtotal'      => 0,
		'subtotal_tax'  => 0,
		'total'         => 0,
		'total_tax'     => 0,
		'taxes'         => array(
			'subtotal' => array(),
			'total'    => array()
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
		if ( $this->get_id() ) {
			$this->set_product_id( get_metadata( 'order_item', $this->get_id(), '_product_id', true ) );
			$this->set_variation_id( get_metadata( 'order_item', $this->get_id(), '_variation_id', true ) );
			$this->set_qty( get_metadata( 'order_item', $this->get_id(), '_qty', true ) );
			$this->set_tax_class( get_metadata( 'order_item', $this->get_id(), '_tax_class', true ) );
			$this->set_subtotal( get_metadata( 'order_item', $this->get_id(), '_line_subtotal', true ) );
			$this->set_subtotal_tax( get_metadata( 'order_item', $this->get_id(), '_line_subtotal_tax', true ) );
			$this->set_total( get_metadata( 'order_item', $this->get_id(), '_line_total', true ) );
			$this->set_total_tax( get_metadata( 'order_item', $this->get_id(), '_line_tax', true ) );
			$this->set_taxes( get_metadata( 'order_item', $this->get_id(), '_line_tax_data', true ) );
		}
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
			wc_update_order_item_meta( $this->get_id(), '_qty', $this->get_qty() );
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
	 * Get any associated downloadable files.
	 * @return array
	 */
	public function get_item_downloads() {
		global $wpdb;

		$files   = array();
		$product = $this->get_product();
		$order   = wc_get_order( $this->get_order_id() );

		if ( $product && $order && $product->is_downloadable() && $order->is_download_permitted() ) {
			$download_file_id = $this->get_variation_id() ? $this->get_variation_id() : $this->get_product_id();
			$download_ids     = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT download_id FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions WHERE user_email = %s AND order_key = %s AND product_id = %d ORDER BY permission_id",
					$order->get_billing_email(),
					$order->get_order_key(),
					$download_file_id
				)
			);

			foreach ( $download_ids as $download_id ) {
				if ( $product->has_file( $download_id ) ) {
					$files[ $download_id ] = $product->get_file( $download_id );
					$files[ $download_id ]['download_url'] = add_query_arg( array(
						'download_file' => $download_file_id,
						'order'         => $order->get_order_key(),
						'email'         => urlencode( $order->get_billing_email() ),
						'key'           => $download_id
					), trailingslashit( home_url() ) );
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

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set qty.
	 * @param int $value
	 */
	public function set_qty( $value ) {
		$this->_data['qty'] = wc_stock_amount( $value );
	}

	/**
	 * Set tax class.
	 * @param string $value
	 */
	public function set_tax_class( $value ) {
		$this->_data['tax_class'] = $value;
	}

	/**
	 * Set Product ID
	 * @param int $value
	 */
	public function set_product_id( $value ) {
		$this->_data['product_id'] = absint( $value );
	}

	/**
	 * Set variation ID.
	 * @param int $value
	 */
	public function set_variation_id( $value ) {
		$this->_data['variation_id'] = absint( $value );
	}

	/**
	 * Line subtotal (before discounts).
	 * @param string $value
	 */
	public function set_subtotal( $value ) {
		$this->_data['subtotal'] = wc_format_decimal( $value );
	}

	/**
	 * Line total (after discounts).
	 * @param string $value
	 */
	public function set_total( $value ) {
		$this->_data['total'] = wc_format_decimal( $value );
	}

	/**
	 * Line subtotal tax (before discounts).
	 * @param string $value
	 */
	public function set_subtotal_tax( $value ) {
		$this->_data['subtotal_tax'] = wc_format_decimal( $value );
	}

	/**
	 * Line total tax (after discounts).
	 * @param string $value
	 */
	public function set_total_tax( $value ) {
		$this->_data['total_tax'] = wc_format_decimal( $value );
	}

	/**
	 * Set line taxes.
	 * @param array $raw_tax_data
	 */
	public function set_taxes( $raw_tax_data ) {
		$raw_tax_data = maybe_unserialize( $raw_tax_data );
		$tax_data     = array(
			'total'    => array(),
			'subtotal' => array()
		);
		if ( ! empty( $raw_tax_data['total'] ) && ! empty( $raw_tax_data['subtotal'] ) ) {
			$tax_data['total']    = array_map( 'wc_format_decimal', $raw_tax_data['total'] );
			$tax_data['subtotal'] = array_map( 'wc_format_decimal', $raw_tax_data['subtotal'] );
		}
		$this->_data['taxes'] = $tax_data;
	}

	/**
	 * Set variation data (stored as meta data - write only).
	 * @param array $data Key/Value pairs
	 */
	public function set_variation( $data ) {
		foreach ( $data as $key => $value ) {
			$this->_meta_data[ str_replace( 'attribute_', '', $key ) ] = $value;
		}
	}

	/**
	 * Set properties based on passed in product object.
	 * @param WC_Product $product
	 */
	public function set_product( $product ) {
		if ( $product ) {
			$this->set_product_id( $product->get_id() );
			$this->set_name( $product->get_title() );
			$this->set_tax_class( $product->get_tax_class() );
			$this->set_variation_id( is_callable( array( $product, 'get_variation_id' ) ) ? $product->get_variation_id() : 0 );
			$this->set_variation( is_callable( array( $product, 'get_variation_attributes' ) ) ? $product->get_variation_attributes() : array() );
		}
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
	 * Get qty.
	 * @return int
	 */
	public function get_qty() {
		return wc_stock_amount( $this->_data['qty'] );
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
}
