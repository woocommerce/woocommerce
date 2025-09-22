<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Handles order data updates from the request.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\RestApi\Routes\V4\Orders;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\RestApi\Routes\V4\Orders\Schema\OrderSchema;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\CostOfGoodsSold\CogsAwareTrait;
use Automattic\WooCommerce\Utilities\ArrayUtil;
use Automattic\WooCommerce\Utilities\StringUtil;
use Automattic\WooCommerce\Internal\Utilities\Users;
use WC_REST_Exception;
use WC_Order;
use WP_REST_Request;
use WP_Http;
use WC_Order_Item_Product;
use WC_Order_Item_Shipping;
use WC_Order_Item_Fee;
use WC_Order_Item_Coupon;

/**
 * UpdateUtils class.
 */
class UpdateUtils {
	use CogsAwareTrait;

	/**
	 * The order schema.
	 *
	 * @var OrderSchema
	 */
	private $order_schema;

	/**
	 * Initialize the update utils.
	 *
	 * @internal
	 * @param OrderSchema $order_schema The order schema.
	 */
	final public function init( OrderSchema $order_schema ) {
		$this->order_schema = $order_schema;
	}

	/**
	 * Update an order from the request.
	 *
	 * @throws WC_REST_Exception When fails to set any item, \WC_Data_Exception When fails to set any item.
	 * @param WC_Order        $order Order object.
	 * @param WP_REST_Request $request Request object.
	 * @param bool            $creating True when creating object, false when updating.
	 * @return void
	 */
	public function update_order_from_request( WC_Order $order, WP_REST_Request $request, bool $creating = false ) {
		// Get data that can be edited from schema.
		$ignore_keys = array( 'created_via', 'status', 'customer_id', 'set_paid' );
		$data_keys   = array_diff( array_keys( $this->order_schema->get_writable_item_schema_properties() ), $ignore_keys );

		// Make sure gateways are loaded so hooks from gateways fire on save/create.
		WC()->payment_gateways();

		// Handle all writable props.
		foreach ( $data_keys as $key ) {
			$value = $request[ $key ];

			if ( is_null( $value ) ) {
				continue;
			}

			if ( 'billing' === $key || 'shipping' === $key ) {
				$this->update_address( $order, $key, (array) $value );
			} elseif ( 'coupon_lines' === $key ) {
				$this->update_line_items( $order, (array) $value, 'coupon' );
			} elseif ( 'line_items' === $key ) {
				$this->update_line_items( $order, (array) $value, 'line_item' );
			} elseif ( 'shipping_lines' === $key ) {
				$this->update_line_items( $order, (array) $value, 'shipping' );
			} elseif ( 'fee_lines' === $key ) {
				$this->update_line_items( $order, (array) $value, 'fee' );
			} elseif ( 'meta_data' === $key ) {
				$this->update_meta_data( $order, (array) $value );
			} elseif ( is_callable( array( $order, "set_{$key}" ) ) ) {
				$order->{"set_{$key}"}( $value );
			}
		}

		if ( ! is_null( $request['customer_id'] ) && 0 !== $request['customer_id'] ) {
			// The customer must exist, and in a multisite context must be visible to the current user.
			if ( is_wp_error( Users::get_user_in_current_site( $request['customer_id'] ) ) ) {
				throw new WC_REST_Exception( 'woocommerce_rest_invalid_customer_id', esc_html__( 'Customer ID is invalid.', 'woocommerce' ), (int) WP_Http::BAD_REQUEST );
			}

			// Make sure customer is part of blog.
			if ( is_multisite() && ! is_user_member_of_blog( $request['customer_id'] ) ) {
				add_user_to_blog( get_current_blog_id(), $request['customer_id'], 'customer' );
			}

			$order->set_customer_id( (int) $request['customer_id'] );
		}

		// Save before calculating totals to ensure all line items are up to date.
		$order->save();

		// If items have changed, recalculate order totals.
		if ( isset( $request['billing'] ) || isset( $request['shipping'] ) || isset( $request['line_items'] ) || isset( $request['shipping_lines'] ) || isset( $request['fee_lines'] ) ) {
			$order->calculate_totals( true );
		}

		if ( isset( $request['coupon_lines'] ) ) {
			$order->recalculate_coupons();
		}

		if ( ! empty( $request['status'] ) ) {
			$order->set_status( $request['status'], '', true );
			$order->save();
		}

		// Actions for after the order is saved.
		if ( true === $request['set_paid'] ) {
			if ( $creating || $order->needs_payment() ) {
				$order->payment_complete( $request['transaction_id'] );
			}
		}
	}


	/**
	 * Update address.
	 *
	 * @param WC_Order $order  Order data.
	 * @param string   $type   Type of address; 'billing' or 'shipping'.
	 * @param array    $request_data Posted data.
	 */
	protected function update_address( WC_Order $order, string $type, array $request_data ) {
		foreach ( $request_data as $key => $value ) {
			if ( is_callable( array( $order, "set_{$type}_{$key}" ) ) ) {
				$order->{"set_{$type}_{$key}"}( $value );
			}
		}
	}

	/**
	 * Update meta data.
	 *
	 * @param WC_Order $order  Order data.
	 * @param array    $meta_data Posted data.
	 */
	protected function update_meta_data( WC_Order $order, array $meta_data ) {
		foreach ( $meta_data as $meta ) {
			$order->update_meta_data( $meta['key'], $meta['value'], isset( $meta['id'] ) ? $meta['id'] : '' );
		}
	}

	/**
	 * Update line items from an array of line item data for an order. Non-posted line items are removed.
	 *
	 * @throws WC_REST_Exception If line items type is invalid.
	 * @param WC_Order $order The order to update the line items for.
	 * @param array    $line_items The line items to update.
	 * @param string   $line_items_type The type of line items to update.
	 */
	protected function update_line_items( WC_Order $order, array $line_items, string $line_items_type = 'line_item' ) {
		if ( ! in_array( $line_items_type, array( 'line_item', 'shipping', 'fee', 'coupon' ), true ) ) {
			throw new WC_REST_Exception( 'woocommerce_rest_invalid_line_items_type', esc_html__( 'Invalid line items type.', 'woocommerce' ), 400 );
		}

		// Get existing items from the order. Any items that are not in the $line_items array will be removed.
		$existing_items     = $order->get_items( $line_items_type );
		$processed_item_ids = array();
		foreach ( $line_items as $line_item_data ) {
			if ( ! is_array( $line_item_data ) ) {
				continue;
			}
			if ( $this->item_is_null_or_zero( $line_item_data ) ) {
				if ( $line_item_data['id'] ) {
					$this->remove_item_from_order( $order, $line_items_type, (int) $line_item_data['id'] );
				}
				continue;
			}
			$processed_item_ids[] = $this->update_line_item( $order, $line_items_type, $line_item_data );
		}

		// Remove any pre-existing items that were not posted.
		foreach ( $existing_items as $existing_item ) {
			if ( ! in_array( $existing_item->get_id(), $processed_item_ids, true ) ) {
				$this->remove_item_from_order( $order, $line_items_type, $existing_item->get_id() );
			}
		}
	}

	/**
	 * Wrapper method to create/update order items.
	 * When updating, the item ID provided is checked to ensure it is associated with the order.
	 *
	 * @throws WC_REST_Exception If item ID is not associated with order.
	 * @param WC_Order $order order object.
	 * @param string   $line_items_type The item type.
	 * @param array    $line_item_data item provided in the request body.
	 * @return int The ID of the updated or created item.
	 */
	protected function update_line_item( WC_Order $order, string $line_items_type, array $line_item_data ) {
		global $wpdb;

		$action = empty( $line_item_data['id'] ) ? 'create' : 'update';
		$method = 'prepare_' . $line_items_type . '_data';
		$item   = null;

		// Verify provided line item ID is associated with order.
		if ( 'update' === $action ) {
			$item = $order->get_item( absint( $line_item_data['id'] ), false );

			if ( ! $item ) {
				throw new WC_REST_Exception( 'woocommerce_rest_invalid_item_id', esc_html__( 'Order item ID provided is not associated with order.', 'woocommerce' ), 400 );
			}
		}

		// Prepare item data.
		$item = $this->$method( $line_item_data, $action, $item );

		/**
		 * Allow extensions be notified before the item is saved.
		 *
		 * @param WC_Order_Item $item The item object.
		 * @param array         $request_data The item data.
		 *
		 * @since 4.5.0.
		 */
		do_action( 'woocommerce_rest_set_order_item', $item, $line_item_data );

		// If creating the order, add the item to it.
		if ( 'create' === $action ) {
			$order->add_item( $item );
		} else {
			$item->save();
		}

		// Maybe update product stock quantity.
		if ( 'line_item' === $line_items_type && in_array( $order->get_status(), array( OrderStatus::PROCESSING, OrderStatus::COMPLETED, OrderStatus::ON_HOLD ), true ) ) {
			require_once WC_ABSPATH . 'includes/admin/wc-admin-functions.php';
			$changed_stock = wc_maybe_adjust_line_item_product_stock( $item );
			if ( $changed_stock && ! is_wp_error( $changed_stock ) ) {
				$order->add_order_note(
					sprintf(
						// translators: %s item name.
						__( 'Adjusted stock: %s', 'woocommerce' ),
						sprintf(
							'%1$s (%2$s&rarr;%3$s)',
							$item->get_name(),
							$changed_stock['from'],
							$changed_stock['to']
						)
					),
					false,
					true
				);
			}
		}

		return $item->get_id();
	}

	/**
	 * Helper method to check if the resource ID associated with the provided item is null.
	 * Items can be deleted by setting the resource ID to null.
	 *
	 * @param array $item Item provided in the request body.
	 * @return bool True if the item resource ID is null, false otherwise.
	 */
	protected function item_is_null_or_zero( $item ) {
		$keys = array( 'product_id', 'method_id', 'method_title', 'name', 'code' );

		foreach ( $keys as $key ) {
			if ( array_key_exists( $key, $item ) && is_null( $item[ $key ] ) ) {
				return true;
			}
		}

		if ( array_key_exists( 'quantity', $item ) && 0 === $item['quantity'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Wrapper method to remove order items.
	 * When updating, the item ID provided is checked to ensure it is associated with the order.
	 *
	 * @param WC_Order $order     The order to remove the item from.
	 * @param string   $line_items_type The item type.
	 * @param int      $item_id   The ID of the item to remove.
	 *
	 * @return void
	 * @throws WC_REST_Exception If item ID is not associated with order.
	 */
	protected function remove_item_from_order( WC_Order $order, string $line_items_type, int $item_id ): void {
		$item = $order->get_item( $item_id );

		if ( ! $item ) {
			throw new WC_REST_Exception(
				'woocommerce_rest_invalid_item_id',
				esc_html__( 'Order item ID provided is not associated with order.', 'woocommerce' ),
				400
			);
		}

		if ( 'line_item' === $line_items_type ) {
			require_once WC_ABSPATH . 'includes/admin/wc-admin-functions.php';
			wc_maybe_adjust_line_item_product_stock( $item, 0 );
		}

		/**
		 * Allow extensions be notified before the item is removed.
		 *
		 * @param WC_Order_Item $item The item object.
		 *
		 * @since 9.3.0.
		 */
		do_action( 'woocommerce_rest_remove_order_item', $item );

		$order->remove_item( $item_id );
	}

	/**
	 * Gets the product ID from the SKU or posted ID.
	 *
	 * @throws WC_REST_Exception When SKU or ID is not valid.
	 * @param array  $request_data Request data.
	 * @param string $action 'create' to add line item or 'update' to update it.
	 * @return int
	 */
	protected function get_product_id_from_line_item( $request_data, $action = 'create' ) {
		if ( ! empty( $request_data['sku'] ) ) {
			$product_id = (int) wc_get_product_id_by_sku( $request_data['sku'] );
		} elseif ( ! empty( $request_data['product_id'] ) && empty( $request_data['variation_id'] ) ) {
			$product_id = (int) $request_data['product_id'];
		} elseif ( ! empty( $request_data['variation_id'] ) ) {
			$product_id = (int) $request_data['variation_id'];
		} elseif ( 'update' === $action ) {
			$product_id = 0;
		} else {
			throw new WC_REST_Exception( 'woocommerce_rest_required_product_reference', esc_html__( 'Product ID or SKU is required.', 'woocommerce' ), 400 );
		}
		return $product_id;
	}

	/**
	 * Create or update a line item, overridden to add COGS data as needed.
	 *
	 * @param array  $request_data Line item data.
	 * @param string $action 'create' to add line item or 'update' to update it.
	 * @param object $item Passed when updating an item. Null during creation.
	 * @return WC_Order_Item_Product
	 * @throws WC_REST_Exception Invalid data, server error.
	 */
	protected function prepare_line_item_data( $request_data, $action = 'create', $item = null ) {
		$item    = is_null( $item ) ? new WC_Order_Item_Product( ! empty( $request_data['id'] ) ? $request_data['id'] : '' ) : $item;
		$product = wc_get_product( $this->get_product_id_from_line_item( $request_data, $action ) );

		if ( $product && $product !== $item->get_product() ) {
			$item->set_product( $product );

			if ( 'create' === $action ) {
				$quantity = isset( $request_data['quantity'] ) ? $request_data['quantity'] : 1;
				$total    = wc_get_price_excluding_tax( $product, array( 'qty' => $quantity ) );
				$item->set_total( $total );
				$item->set_subtotal( $total );
			}
		}

		$this->maybe_set_item_props( $item, array( 'name', 'quantity', 'total', 'subtotal', 'tax_class' ), $request_data );
		$this->maybe_set_item_meta_data( $item, $request_data );

		if ( ! $item->has_cogs() || ! $this->cogs_is_enabled() ) {
			return $item;
		}

		$cogs_value = $request_data['cost_of_goods_sold']['total_value'] ?? null;
		if ( ! is_null( $cogs_value ) ) {
			$item->set_cogs_value( (float) $cogs_value );
		}

		return $item;
	}

	/**
	 * Create or update an order shipping method.
	 *
	 * @param array  $request_data $shipping Item data.
	 * @param string $action 'create' to add shipping or 'update' to update it.
	 * @param object $item Passed when updating an item. Null during creation.
	 * @return WC_Order_Item_Shipping
	 * @throws WC_REST_Exception Invalid data, server error.
	 */
	protected function prepare_shipping_data( $request_data, $action = 'create', $item = null ) {
		$item = is_null( $item ) ? new WC_Order_Item_Shipping( ! empty( $request_data['id'] ) ? $request_data['id'] : '' ) : $item;

		if ( 'create' === $action && empty( $request_data['method_id'] ) ) {
			throw new WC_REST_Exception( 'woocommerce_rest_invalid_shipping_item', esc_html__( 'Shipping method ID is required.', 'woocommerce' ), 400 );
		}

		$this->maybe_set_item_props( $item, array( 'method_id', 'method_title', 'total', 'instance_id' ), $request_data );
		$this->maybe_set_item_meta_data( $item, $request_data );

		return $item;
	}

	/**
	 * Create or update an order fee.
	 *
	 * @param array  $request_data Item data.
	 * @param string $action 'create' to add fee or 'update' to update it.
	 * @param object $item Passed when updating an item. Null during creation.
	 * @return WC_Order_Item_Fee
	 * @throws WC_REST_Exception Invalid data, server error.
	 */
	protected function prepare_fee_data( $request_data, $action = 'create', $item = null ) {
		$item = is_null( $item ) ? new WC_Order_Item_Fee( ! empty( $request_data['id'] ) ? $request_data['id'] : '' ) : $item;

		if ( 'create' === $action && empty( $request_data['name'] ) ) {
			throw new WC_REST_Exception( 'woocommerce_rest_invalid_fee_item', esc_html__( 'Fee name is required.', 'woocommerce' ), 400 );
		}

		$this->maybe_set_item_props( $item, array( 'name', 'tax_class', 'tax_status', 'total' ), $request_data );
		$this->maybe_set_item_meta_data( $item, $request_data );

		return $item;
	}

	/**
	 * Create or update an order coupon.
	 *
	 * @param array  $request_data Item data.
	 * @param string $action 'create' to add coupon or 'update' to update it.
	 * @param object $item Passed when updating an item. Null during creation.
	 * @return WC_Order_Item_Coupon
	 * @throws WC_REST_Exception Invalid data, server error.
	 */
	protected function prepare_coupon_data( $request_data, $action = 'create', $item = null ) {
		$item = is_null( $item ) ? new WC_Order_Item_Coupon( ! empty( $request_data['id'] ) ? $request_data['id'] : '' ) : $item;

		if ( 'create' === $action ) {
			$coupon_code = ArrayUtil::get_value_or_default( $request_data, 'code' );
			if ( StringUtil::is_null_or_whitespace( $coupon_code ) ) {
				throw new WC_REST_Exception( 'woocommerce_rest_invalid_coupon_coupon', esc_html__( 'Coupon code is required.', 'woocommerce' ), 400 );
			}
		}

		$this->maybe_set_item_props( $item, array( 'code', 'discount' ), $request_data );
		$this->maybe_set_item_meta_data( $item, $request_data );

		return $item;
	}

	/**
	 * Maybe set an item prop if the value was posted.
	 *
	 * @param WC_Order_Item $item   Order item.
	 * @param string        $prop   Order property.
	 * @param array         $request_data Request data.
	 */
	protected function maybe_set_item_prop( $item, $prop, $request_data ) {
		if ( isset( $request_data[ $prop ] ) && is_callable( array( $item, "set_$prop" ) ) ) {
			$item->{"set_$prop"}( $request_data[ $prop ] );
		}
	}

	/**
	 * Maybe set item props if the values were posted.
	 *
	 * @param WC_Order_Item $item   Order item data.
	 * @param string[]      $props  Properties.
	 * @param array         $request_data Request data.
	 */
	protected function maybe_set_item_props( $item, $props, $request_data ) {
		foreach ( $props as $prop ) {
			$this->maybe_set_item_prop( $item, $prop, $request_data );
		}
	}

	/**
	 * Maybe set item meta if posted.
	 *
	 * @param WC_Order_Item $item   Order item data.
	 * @param array         $request_data Request data.
	 */
	protected function maybe_set_item_meta_data( $item, $request_data ) {
		if ( ! empty( $request_data['meta_data'] ) && is_array( $request_data['meta_data'] ) ) {
			foreach ( $request_data['meta_data'] as $meta ) {
				if ( isset( $meta['key'] ) ) {
					$value = isset( $meta['value'] ) ? $meta['value'] : null;
					$item->update_meta_data( $meta['key'], $value, isset( $meta['id'] ) ? $meta['id'] : '' );
				}
			}
		}
	}
}
