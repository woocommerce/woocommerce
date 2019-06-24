<?php
/**
 * Convert data in the order schema format to a product object.
 *
 * @package Automattic/WooCommerce/RestApi
 */

namespace Automattic\WooCommerce\RestApi\Controllers\Version4\Requests;

defined( 'ABSPATH' ) || exit;

/**
 * OrderRequest class.
 */
class OrderRequest extends AbstractObjectRequest {

	/**
	 * Convert request to object.
	 *
	 * @return \WC_Order
	 */
	public function prepare_object() {
		$id     = (int) $this->get_param( 'id', 0 );
		$object = new \WC_Order( $id );

		$this->set_props( $object );
		$this->set_meta_data( $object );
		$this->set_line_items( $object );
		$this->calculate_coupons( $object );

		return $object;
	}

	/**
	 * Set order props.
	 *
	 * @param \WC_Order $object Order object reference.
	 */
	protected function set_props( &$object ) {
		$props = [
			'parent_id',
			'currency',
			'customer_id',
			'customer_note',
			'payment_method',
			'payment_method_title',
			'transaction_id',
			'billing',
			'shipping',
			'status',
		];

		$request_props = array_intersect_key( $this->request, array_flip( $props ) );
		$prop_values   = [];

		foreach ( $request_props as $prop => $value ) {
			switch ( $prop ) {
				case 'customer_id':
					$prop_values[ $prop ] = $this->parse_customer_id_field( $value );
					break;
				case 'billing':
				case 'shipping':
					$address     = $this->parse_address_field( $value, $object, $prop );
					$prop_values = array_merge( $prop_values, $address );
					break;
				default:
					$prop_values[ $prop ] = $value;
			}
		}

		foreach ( $prop_values as $prop => $value ) {
			$object->{"set_$prop"}( $value );
		}
	}

	/**
	 * Set order line items.
	 *
	 * @param \WC_Order $object Order object reference.
	 */
	protected function set_line_items( &$object ) {
		$types = [
			'line_items',
			'shipping_lines',
			'fee_lines',
		];

		foreach ( $types as $type ) {
			if ( ! isset( $this->request[ $type ] ) || ! is_array( $this->request[ $type ] ) ) {
				continue;
			}
			$items = $this->request[ $type ];

			foreach ( $items as $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}
				if ( $this->item_is_null( $item ) || ( isset( $item['quantity'] ) && 0 === $item['quantity'] ) ) {
					$object->remove_item( $item['id'] );
				} else {
					$this->set_item( $object, $type, $item );
				}
			}
		}
	}

	/**
	 * Helper method to check if the resource ID associated with the provided item is null.
	 * Items can be deleted by setting the resource ID to null.
	 *
	 * @param array $item Item provided in the request body.
	 * @return bool True if the item resource ID is null, false otherwise.
	 */
	protected function item_is_null( $item ) {
		$keys = array( 'product_id', 'method_id', 'method_title', 'name', 'code' );

		foreach ( $keys as $key ) {
			if ( array_key_exists( $key, $item ) && is_null( $item[ $key ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Maybe set an item prop if the value was posted.
	 *
	 * @param \WC_Order_Item $item   Order item.
	 * @param string         $prop   Order property.
	 * @param array          $posted Request data.
	 */
	protected function maybe_set_item_prop( $item, $prop, $posted ) {
		if ( isset( $posted[ $prop ] ) ) {
			$item->{"set_$prop"}( $posted[ $prop ] );
		}
	}

	/**
	 * Maybe set item props if the values were posted.
	 *
	 * @param \WC_Order_Item $item   Order item data.
	 * @param string[]       $props  Properties.
	 * @param array          $posted Request data.
	 */
	protected function maybe_set_item_props( $item, $props, $posted ) {
		foreach ( $props as $prop ) {
			$this->maybe_set_item_prop( $item, $prop, $posted );
		}
	}

	/**
	 * Maybe set item meta if posted.
	 *
	 * @param \WC_Order_Item $item   Order item data.
	 * @param array          $posted Request data.
	 */
	protected function maybe_set_item_meta_data( $item, $posted ) {
		if ( ! empty( $posted['meta_data'] ) && is_array( $posted['meta_data'] ) ) {
			foreach ( $posted['meta_data'] as $meta ) {
				if ( isset( $meta['key'] ) ) {
					$value = isset( $meta['value'] ) ? $meta['value'] : null;
					$item->update_meta_data( $meta['key'], $value, isset( $meta['id'] ) ? $meta['id'] : '' );
				}
			}
		}
	}

	/**
	 * Gets the product ID from the SKU or posted ID.
	 *
	 * @param array $posted Request data.
	 * @return int
	 * @throws \WC_REST_Exception When SKU or ID is not valid.
	 */
	protected function get_product_id_from_line_item( $posted ) {
		if ( ! empty( $posted['sku'] ) ) {
			$product_id = (int) wc_get_product_id_by_sku( $posted['sku'] );
		} elseif ( ! empty( $posted['product_id'] ) && empty( $posted['variation_id'] ) ) {
			$product_id = (int) $posted['product_id'];
		} elseif ( ! empty( $posted['variation_id'] ) ) {
			$product_id = (int) $posted['variation_id'];
		} else {
			throw new \WC_REST_Exception( 'woocommerce_rest_required_product_reference', __( 'Product ID or SKU is required.', 'woocommerce-rest-api' ), 400 );
		}
		return $product_id;
	}

	/**
	 * Create or update a line item.
	 *
	 * @param array  $posted Line item data.
	 * @param string $action 'create' to add line item or 'update' to update it.
	 * @param object $item Passed when updating an item. Null during creation.
	 * @return \WC_Order_Item_Product
	 * @throws \WC_REST_Exception Invalid data, server error.
	 */
	protected function prepare_line_items( $posted, $action = 'create', $item = null ) {
		$item    = is_null( $item ) ? new \WC_Order_Item_Product( ! empty( $posted['id'] ) ? $posted['id'] : '' ) : $item;
		$product = wc_get_product( $this->get_product_id_from_line_item( $posted ) );

		if ( $product !== $item->get_product() ) {
			$item->set_product( $product );

			if ( 'create' === $action ) {
				$quantity = isset( $posted['quantity'] ) ? $posted['quantity'] : 1;
				$total    = wc_get_price_excluding_tax( $product, array( 'qty' => $quantity ) );
				$item->set_total( $total );
				$item->set_subtotal( $total );
			}
		}

		$this->maybe_set_item_props( $item, array( 'name', 'quantity', 'total', 'subtotal', 'tax_class' ), $posted );
		$this->maybe_set_item_meta_data( $item, $posted );

		return $item;
	}

	/**
	 * Create or update an order shipping method.
	 *
	 * @param array  $posted $shipping Item data.
	 * @param string $action 'create' to add shipping or 'update' to update it.
	 * @param object $item Passed when updating an item. Null during creation.
	 * @return \WC_Order_Item_Shipping
	 * @throws \WC_REST_Exception Invalid data, server error.
	 */
	protected function prepare_shipping_lines( $posted, $action = 'create', $item = null ) {
		$item = is_null( $item ) ? new \WC_Order_Item_Shipping( ! empty( $posted['id'] ) ? $posted['id'] : '' ) : $item;

		if ( 'create' === $action ) {
			if ( empty( $posted['method_id'] ) ) {
				throw new \WC_REST_Exception( 'woocommerce_rest_invalid_shipping_item', __( 'Shipping method ID is required.', 'woocommerce-rest-api' ), 400 );
			}
		}

		$this->maybe_set_item_props( $item, array( 'method_id', 'method_title', 'total' ), $posted );
		$this->maybe_set_item_meta_data( $item, $posted );

		return $item;
	}

	/**
	 * Create or update an order fee.
	 *
	 * @param array  $posted Item data.
	 * @param string $action 'create' to add fee or 'update' to update it.
	 * @param object $item Passed when updating an item. Null during creation.
	 * @return \WC_Order_Item_Fee
	 * @throws \WC_REST_Exception Invalid data, server error.
	 */
	protected function prepare_fee_lines( $posted, $action = 'create', $item = null ) {
		$item = is_null( $item ) ? new \WC_Order_Item_Fee( ! empty( $posted['id'] ) ? $posted['id'] : '' ) : $item;

		if ( 'create' === $action ) {
			if ( empty( $posted['name'] ) ) {
				throw new \WC_REST_Exception( 'woocommerce_rest_invalid_fee_item', __( 'Fee name is required.', 'woocommerce-rest-api' ), 400 );
			}
		}

		$this->maybe_set_item_props( $item, array( 'name', 'tax_class', 'tax_status', 'total' ), $posted );
		$this->maybe_set_item_meta_data( $item, $posted );

		return $item;
	}

	/**
	 * Wrapper method to create/update order items.
	 * When updating, the item ID provided is checked to ensure it is associated
	 * with the order.
	 *
	 * @param \WC_Order $order order object.
	 * @param string    $item_type The item type.
	 * @param array     $posted item provided in the request body.
	 * @throws \WC_REST_Exception If item ID is not associated with order.
	 */
	protected function set_item( &$order, $item_type, $posted ) {
		if ( ! empty( $posted['id'] ) ) {
			$action = 'update';
		} else {
			$action = 'create';
		}

		$method = 'prepare_' . $item_type;
		$item   = null;

		// Verify provided line item ID is associated with order.
		if ( 'update' === $action ) {
			$item = $order->get_item( absint( $posted['id'] ), false );

			if ( ! $item ) {
				throw new \WC_REST_Exception( 'woocommerce_rest_invalid_item_id', __( 'Order item ID provided is not associated with order.', 'woocommerce-rest-api' ), 400 );
			}
		}

		// Prepare item data.
		$item = $this->$method( $posted, $action, $item );

		do_action( 'woocommerce_rest_set_order_item', $item, $posted );

		// If creating the order, add the item to it.
		if ( 'create' === $action ) {
			$order->add_item( $item );
		} else {
			$item->save();
		}
	}

	/**
	 * Parse address data.
	 *
	 * @param array     $data  Posted data.
	 * @param \WC_Order $object Order object reference.
	 * @param string    $type   Address type.
	 * @return array
	 */
	protected function parse_address_field( $data, $object, $type = 'billing' ) {
		$address = [];
		foreach ( $data as $key => $value ) {
			if ( is_callable( array( $object, "set_{$type}_{$key}" ) ) ) {
				$address[ "{$type}_{$key}" ] = $value;
			}
		}
		return $address;
	}

	/**
	 * Parse customer ID.
	 *
	 * @throws \WC_REST_Exception Will throw an exception if the customer is invalid.
	 * @param int $customer_id Customer ID to set.
	 * @return int
	 */
	protected function parse_customer_id_field( $customer_id ) {
		if ( 0 !== $customer_id ) {
			// Make sure customer exists.
			if ( false === get_user_by( 'id', $customer_id ) ) {
				throw new \WC_REST_Exception( 'woocommerce_rest_invalid_customer_id', __( 'Customer ID is invalid.', 'woocommerce-rest-api' ), 400 );
			}

			// Make sure customer is part of blog.
			if ( is_multisite() && ! is_user_member_of_blog( $customer_id ) ) {
				add_user_to_blog( get_current_blog_id(), $customer_id, 'customer' );
			}
		}
		return $customer_id;
	}

	/**
	 * Calculate coupons.
	 *
	 * @throws \WC_REST_Exception When fails to set any item.
	 *
	 * @param \WC_Order $order   Order data.
	 * @return bool
	 */
	protected function calculate_coupons( &$order ) {
		$coupon_lines = $this->get_param( 'coupon_lines', false );

		if ( ! is_array( $coupon_lines ) ) {
			return false;
		}

		// Remove all coupons first to ensure calculation is correct.
		foreach ( $order->get_items( 'coupon' ) as $coupon ) {
			$order->remove_coupon( $coupon->get_code() );
		}

		foreach ( $coupon_lines as $item ) {
			if ( is_array( $item ) ) {
				if ( empty( $item['id'] ) ) {
					if ( empty( $item['code'] ) ) {
						throw new \WC_REST_Exception( 'woocommerce_rest_invalid_coupon', __( 'Coupon code is required.', 'woocommerce-rest-api' ), 400 );
					}

					$results = $order->apply_coupon( wc_clean( $item['code'] ) );

					if ( is_wp_error( $results ) ) {
						throw new \WC_REST_Exception( 'woocommerce_rest_' . $results->get_error_code(), $results->get_error_message(), 400 );
					}
				}
			}
		}

		return true;
	}
}
