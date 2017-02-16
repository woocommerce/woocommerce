<?php
/**
 * REST API Orders controller
 *
 * Handles requests to the /orders endpoint.
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/API
 * @since    2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Orders controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Orders_V1_Controller
 */
class WC_REST_Orders_Controller extends WC_REST_Orders_V1_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v2';

	/**
	 * Stores the request.
	 * @var array
	 */
	protected $request = array();

	/**
	 * Expands an order item to get its data.
	 * @param WC_Order_item $item
	 * @return array
	 */
	protected function get_order_item_data( $item ) {
		$data           = $item->get_data();
		$format_decimal = array( 'subtotal', 'subtotal_tax', 'total', 'total_tax', 'tax_total', 'shipping_tax_total' );

		// Format decimal values.
		foreach ( $format_decimal as $key ) {
			if ( isset( $data[ $key ] ) ) {
				$data[ $key ] = wc_format_decimal( $data[ $key ], $this->request['dp'] );
			}
		}

		// Add meta, SKU and PRICE to products.
		if ( is_callable( array( $item, 'get_product' ) ) ) {
			$data['sku']   = $item->get_product() ? $item->get_product()->get_sku(): null;
			$data['price'] = $item->get_total() / max( 1, $item->get_quantity() );

			// Format meta data.
			if ( isset( $data['meta_data'] ) ) {
				$hideprefix = 'true' === $this->request['all_item_meta'] ? null : '_';
				$item_meta  = $item->get_formatted_meta_data( $hideprefix );

				foreach ( $item_meta as $key => $values ) {
					// Label was used in previous version of API - set it here.
					$item_meta[ $key ]->label = $values->display_key;
					unset( $item_meta[ $key ]->display_key );
					unset( $item_meta[ $key ]->display_value );
				}

				$data['meta'] = array_values( $item_meta );
			}
		}

		// Format taxes.
		if ( ! empty( $data['taxes']['total'] ) ) {
			$taxes = array();

			foreach ( $data['taxes']['total'] as $tax_rate_id => $tax ) {
				$taxes[] = array(
					'id'       => $tax_rate_id,
					'total'    => $tax,
					'subtotal' => isset( $data['taxes']['subtotal'][ $tax_rate_id ] ) ? $data['taxes']['subtotal'][ $tax_rate_id ] : '',
				);
			}
			$data['taxes'] = $taxes;
		} elseif ( isset( $data['taxes'] ) ) {
			$data['taxes'] = array();
		}

		// Remove props we don't want to expose.
		unset( $data['order_id'] );
		unset( $data['type'] );

		return $data;
	}

	/**
	 * Prepare a single order output for response.
	 *
	 * @param WP_Post $post Post object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $data
	 */
	public function prepare_item_for_response( $post, $request ) {
		$this->request     = $request;
		$statuses          = wc_get_order_statuses();
		$order             = wc_get_order( $post );
		$data              = array_merge( array( 'id' => $order->get_id() ), $order->get_data() );
		$format_decimal    = array( 'discount_total', 'discount_tax', 'shipping_total', 'shipping_tax', 'shipping_total', 'shipping_tax', 'cart_tax', 'total', 'total_tax' );
		$format_date       = array( 'date_created', 'date_modified', 'date_completed', 'date_paid' );
		$format_line_items = array( 'line_items', 'tax_lines', 'shipping_lines', 'fee_lines', 'coupon_lines' );

		// Format decimal values.
		foreach ( $format_decimal as $key ) {
			$data[ $key ] = wc_format_decimal( $data[ $key ], $this->request['dp'] );
		}

		// Format date values.
		foreach ( $format_date as $key ) {
			$data[ $key ] = $data[ $key ] ? wc_rest_prepare_date_response( get_gmt_from_date( date( 'Y-m-d H:i:s', $data[ $key ] ) ) ) : false;
		}

		// Format the order status.
		$data['status'] = 'wc-' === substr( $data['status'], 0, 3 ) ? substr( $data['status'], 3 ) : $data['status'];

		// Format line items.
		foreach ( $format_line_items as $key ) {
			$data[ $key ] = array_values( array_map( array( $this, 'get_order_item_data' ), $data[ $key ] ) );
		}

		// Refunds.
		$data['refunds'] = array();
		foreach ( $order->get_refunds() as $refund ) {
			$data['refunds'][] = array(
				'id'     => $refund->get_id(),
				'refund' => $refund->get_reason() ? $refund->get_reason() : '',
				'total'  => '-' . wc_format_decimal( $refund->get_amount(), $this->request['dp'] ),
			);
		}

		$context  = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $order, $request ) );

		/**
		 * Filter the data for a response.
		 *
		 * The dynamic portion of the hook name, $this->post_type, refers to post_type of the post being
		 * prepared for the response.
		 *
		 * @param WP_REST_Response   $response   The response object.
		 * @param WP_Post            $post       Post object.
		 * @param WP_REST_Request    $request    Request object.
		 */
		return apply_filters( "woocommerce_rest_prepare_{$this->post_type}", $response, $post, $request );
	}

	/**
	 * Prepare a single order for create.
	 *
	 * @param  WP_REST_Request $request Request object.
	 * @return WP_Error|WC_Order $data Object.
	 */
	protected function prepare_item_for_database( $request ) {
		$id        = isset( $request['id'] ) ? absint( $request['id'] ) : 0;
		$order     = new WC_Order( $id );
		$schema    = $this->get_item_schema();
		$data_keys = array_keys( array_filter( $schema['properties'], array( $this, 'filter_writable_props' ) ) );

		// Handle all writable props
		foreach ( $data_keys as $key ) {
			$value = $request[ $key ];

			if ( ! is_null( $value ) ) {
				switch ( $key ) {
					case 'billing' :
					case 'shipping' :
						$this->update_address( $order, $value, $key );
						break;
					case 'line_items' :
					case 'shipping_lines' :
					case 'fee_lines' :
					case 'coupon_lines' :
						if ( is_array( $value ) ) {
							foreach ( $value as $item ) {
								if ( is_array( $item ) ) {
									if ( $this->item_is_null( $item ) || ( isset( $item['quantity'] ) && 0 === $item['quantity'] ) ) {
										$order->remove_item( $item['id'] );
									} else {
										$this->set_item( $order, $key, $item );
									}
								}
							}
						}
						break;
					case 'meta_data' :
						if ( is_array( $value ) ) {
							foreach ( $value as $meta ) {
								$order->update_meta_data( $meta['key'], $meta['value'], $meta['id'] );
							}
						}
						break;
					default :
						if ( is_callable( array( $order, "set_{$key}" ) ) ) {
							$order->{"set_{$key}"}( $value );
						}
						break;
				}
			}
		}

		/**
		 * Filter the data for the insert.
		 *
		 * The dynamic portion of the hook name, $this->post_type, refers to post_type of the post being
		 * prepared for the response.
		 *
		 * @param WC_Order           $order      The prder object.
		 * @param WP_REST_Request    $request    Request object.
		 */
		return apply_filters( "woocommerce_rest_pre_insert_{$this->post_type}", $order, $request );
	}

	/**
	 * Maybe set item meta if posted.
	 * @param WC_Order_Item $item
	 * @param array $posted Request data.
	 */
	protected function maybe_set_item_meta_data( $item, $posted ) {
		if ( ! empty( $posted['meta_data'] ) && is_array( $posted['meta_data'] ) ) {
			foreach ( $posted['meta_data'] as $meta ) {
				if ( isset( $meta['key'], $meta['value'] ) ) {
					$item->update_meta_data( $meta['key'], $meta['value'], isset( $meta['id'] ) ? $meta['id'] : '' );
				}
			}
		}
	}

	/**
	 * Create or update a line item.
	 *
	 * @param array $posted Line item data.
	 * @param string $action 'create' to add line item or 'update' to update it.
	 * @throws WC_REST_Exception Invalid data, server error.
	 */
	protected function prepare_line_items( $posted, $action = 'create' ) {
		$item    = new WC_Order_Item_Product( ! empty( $posted['id'] ) ? $posted['id'] : '' );
		$product = wc_get_product( $this->get_product_id( $posted ) );

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
	 * @param $posted $shipping Item data.
	 * @param string $action 'create' to add shipping or 'update' to update it.
	 * @throws WC_REST_Exception Invalid data, server error.
	 */
	protected function prepare_shipping_lines( $posted, $action ) {
		$item = new WC_Order_Item_Shipping( ! empty( $posted['id'] ) ? $posted['id'] : '' );

		if ( 'create' === $action ) {
			if ( empty( $posted['method_id'] ) ) {
				throw new WC_REST_Exception( 'woocommerce_rest_invalid_shipping_item', __( 'Shipping method ID is required.', 'woocommerce' ), 400 );
			}
		}

		$this->maybe_set_item_props( $item, array( 'method_id', 'method_title', 'total' ), $posted );
		$this->maybe_set_item_meta_data( $item, $posted );

		return $item;
	}

	/**
	 * Create or update an order fee.
	 *
	 * @param array $posted Item data.
	 * @param string $action 'create' to add fee or 'update' to update it.
	 * @throws WC_REST_Exception Invalid data, server error.
	 */
	protected function prepare_fee_lines( $posted, $action ) {
		$item = new WC_Order_Item_Fee( ! empty( $posted['id'] ) ? $posted['id'] : '' );

		if ( 'create' === $action ) {
			if ( empty( $posted['name'] ) ) {
				throw new WC_REST_Exception( 'woocommerce_rest_invalid_fee_item', __( 'Fee name is required.', 'woocommerce' ), 400 );
			}
		}

		$this->maybe_set_item_props( $item, array( 'name', 'tax_class', 'tax_status', 'total' ), $posted );
		$this->maybe_set_item_meta_data( $item, $posted );

		return $item;
	}

	/**
	 * Create or update an order coupon.
	 *
	 * @param array $posted Item data.
	 * @param string $action 'create' to add coupon or 'update' to update it.
	 * @throws WC_REST_Exception Invalid data, server error.
	 */
	protected function prepare_coupon_lines( $posted, $action ) {
		$item = new WC_Order_Item_Coupon( ! empty( $posted['id'] ) ? $posted['id'] : '' );

		if ( 'create' === $action ) {
			if ( empty( $posted['code'] ) ) {
				throw new WC_REST_Exception( 'woocommerce_rest_invalid_coupon_coupon', __( 'Coupon code is required.', 'woocommerce' ), 400 );
			}
		}

		$this->maybe_set_item_props( $item, array( 'code', 'discount' ), $posted );
		$this->maybe_set_item_meta_data( $item, $posted );

		return $item;
	}

	/**
	 * Get the Order's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->post_type,
			'type'       => 'object',
			'properties' => array(
				'id' => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'parent_id' => array(
					'description' => __( 'Parent order ID.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'status' => array(
					'description' => __( 'Order status.', 'woocommerce' ),
					'type'        => 'string',
					'default'     => 'pending',
					'enum'        => $this->get_order_statuses(),
					'context'     => array( 'view', 'edit' ),
				),
				'order_key' => array(
					'description' => __( 'Order key.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'currency' => array(
					'description' => __( 'Currency the order was created with, in ISO format.', 'woocommerce' ),
					'type'        => 'string',
					'default'     => get_woocommerce_currency(),
					'enum'        => array_keys( get_woocommerce_currencies() ),
					'context'     => array( 'view', 'edit' ),
				),
				'version' => array(
					'description' => __( 'Version of WooCommerce when the order was made.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'prices_include_tax' => array(
					'description' => __( 'True the prices included tax during checkout.', 'woocommerce' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created' => array(
					'description' => __( "The date the order was created, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_modified' => array(
					'description' => __( "The date the order was last modified, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'customer_id' => array(
					'description' => __( 'User ID who owns the order. 0 for guests.', 'woocommerce' ),
					'type'        => 'integer',
					'default'     => 0,
					'context'     => array( 'view', 'edit' ),
				),
				'discount_total' => array(
					'description' => __( 'Total discount amount for the order.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'discount_tax' => array(
					'description' => __( 'Total discount tax amount for the order.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'shipping_total' => array(
					'description' => __( 'Total shipping amount for the order.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'shipping_tax' => array(
					'description' => __( 'Total shipping tax amount for the order.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'cart_tax' => array(
					'description' => __( 'Sum of line item taxes only.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'total' => array(
					'description' => __( 'Grand total.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'total_tax' => array(
					'description' => __( 'Sum of all taxes.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'billing' => array(
					'description' => __( 'Billing address.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'first_name' => array(
							'description' => __( 'First name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'last_name' => array(
							'description' => __( 'Last name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'company' => array(
							'description' => __( 'Company name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_1' => array(
							'description' => __( 'Address 1', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_2' => array(
							'description' => __( 'Address 2', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'city' => array(
							'description' => __( 'City name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'state' => array(
							'description' => __( 'ISO code or name of the state, province or district.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'postcode' => array(
							'description' => __( 'Postal code.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'country' => array(
							'description' => __( 'Country code in ISO 3166-1 alpha-2 format.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'email' => array(
							'description' => __( 'Email address.', 'woocommerce' ),
							'type'        => 'string',
							'format'      => 'email',
							'context'     => array( 'view', 'edit' ),
						),
						'phone' => array(
							'description' => __( 'Phone number.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
				'shipping' => array(
					'description' => __( 'Shipping address.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'first_name' => array(
							'description' => __( 'First name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'last_name' => array(
							'description' => __( 'Last name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'company' => array(
							'description' => __( 'Company name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_1' => array(
							'description' => __( 'Address 1', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_2' => array(
							'description' => __( 'Address 2', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'city' => array(
							'description' => __( 'City name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'state' => array(
							'description' => __( 'ISO code or name of the state, province or district.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'postcode' => array(
							'description' => __( 'Postal code.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'country' => array(
							'description' => __( 'Country code in ISO 3166-1 alpha-2 format.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
				'payment_method' => array(
					'description' => __( 'Payment method ID.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'payment_method_title' => array(
					'description' => __( 'Payment method title.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'transaction_id' => array(
					'description' => __( 'Unique transaction ID.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'customer_ip_address' => array(
					'description' => __( "Customer's IP address.", 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'customer_user_agent' => array(
					'description' => __( 'User agent of the customer.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'created_via' => array(
					'description' => __( 'Shows where the order was created.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'customer_note' => array(
					'description' => __( 'Note left by customer during checkout.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'date_completed' => array(
					'description' => __( "The date the order was completed, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'date_paid' => array(
					'description' => __( "The date the order has been paid, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'cart_hash' => array(
					'description' => __( 'MD5 hash of cart items to ensure orders are not modified.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'number' => array(
					'description' => __( 'Order number.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'meta_data' => array(
					'description' => __( 'Meta data.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id' => array(
								'description' => __( 'Meta ID.', 'woocommerce' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'key' => array(
								'description' => __( 'Meta key.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'value' => array(
								'description' => __( 'Meta value.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),
				'line_items' => array(
					'description' => __( 'Line items data.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id' => array(
								'description' => __( 'Item ID.', 'woocommerce' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'name' => array(
								'description' => __( 'Product name.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'product_id' => array(
								'description' => __( 'Product ID.', 'woocommerce' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'variation_id' => array(
								'description' => __( 'Variation ID, if applicable.', 'woocommerce' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'quantity' => array(
								'description' => __( 'Quantity ordered.', 'woocommerce' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'tax_class' => array(
								'description' => __( 'Tax class of product.', 'woocommerce' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'subtotal' => array(
								'description' => __( 'Line subtotal (before discounts).', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'subtotal_tax' => array(
								'description' => __( 'Line subtotal tax (before discounts).', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'total' => array(
								'description' => __( 'Line total (after discounts).', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'total_tax' => array(
								'description' => __( 'Line total tax (after discounts).', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'taxes' => array(
								'description' => __( 'Line taxes.', 'woocommerce' ),
								'type'        => 'array',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
										'id' => array(
											'description' => __( 'Tax rate ID.', 'woocommerce' ),
											'type'        => 'integer',
											'context'     => array( 'view', 'edit' ),
										),
										'total' => array(
											'description' => __( 'Tax total.', 'woocommerce' ),
											'type'        => 'string',
											'context'     => array( 'view', 'edit' ),
										),
										'subtotal' => array(
											'description' => __( 'Tax subtotal.', 'woocommerce' ),
											'type'        => 'string',
											'context'     => array( 'view', 'edit' ),
										),
									),
								),
							),
							'meta_data' => array(
								'description' => __( 'Meta data.', 'woocommerce' ),
								'type'        => 'array',
								'context'     => array( 'view', 'edit' ),
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
										'id' => array(
											'description' => __( 'Meta ID.', 'woocommerce' ),
											'type'        => 'integer',
											'context'     => array( 'view', 'edit' ),
											'readonly'    => true,
										),
										'key' => array(
											'description' => __( 'Meta key.', 'woocommerce' ),
											'type'        => 'string',
											'context'     => array( 'view', 'edit' ),
										),
										'value' => array(
											'description' => __( 'Meta value.', 'woocommerce' ),
											'type'        => 'string',
											'context'     => array( 'view', 'edit' ),
										),
									),
								),
							),
							'sku' => array(
								'description' => __( 'Product SKU.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'price' => array(
								'description' => __( 'Product price.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'meta' => array(
								'description' => __( 'Meta data (formatted).', 'woocommerce' ),
								'type'        => 'array',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
										'key' => array(
											'description' => __( 'Meta key.', 'woocommerce' ),
											'type'        => 'string',
											'context'     => array( 'view', 'edit' ),
											'readonly'    => true,
										),
										'label' => array(
											'description' => __( 'Meta label.', 'woocommerce' ),
											'type'        => 'string',
											'context'     => array( 'view', 'edit' ),
											'readonly'    => true,
										),
										'value' => array(
											'description' => __( 'Meta value.', 'woocommerce' ),
											'type'        => 'string',
											'context'     => array( 'view', 'edit' ),
											'readonly'    => true,
										),
									),
								),
							),
						),
					),
				),
				'tax_lines' => array(
					'description' => __( 'Tax lines data.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id' => array(
								'description' => __( 'Item ID.', 'woocommerce' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'rate_code' => array(
								'description' => __( 'Tax rate code.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'rate_id' => array(
								'description' => __( 'Tax rate ID.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'label' => array(
								'description' => __( 'Tax rate label.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'compound' => array(
								'description' => __( 'Show if is a compound tax rate.', 'woocommerce' ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'tax_total' => array(
								'description' => __( 'Tax total (not including shipping taxes).', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'shipping_tax_total' => array(
								'description' => __( 'Shipping tax total.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'meta_data' => array(
								'description' => __( 'Meta data.', 'woocommerce' ),
								'type'        => 'array',
								'context'     => array( 'view', 'edit' ),
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
										'id' => array(
											'description' => __( 'Meta ID.', 'woocommerce' ),
											'type'        => 'integer',
											'context'     => array( 'view', 'edit' ),
											'readonly'    => true,
										),
										'key' => array(
											'description' => __( 'Meta key.', 'woocommerce' ),
											'type'        => 'string',
											'context'     => array( 'view', 'edit' ),
										),
										'value' => array(
											'description' => __( 'Meta value.', 'woocommerce' ),
											'type'        => 'string',
											'context'     => array( 'view', 'edit' ),
										),
									),
								),
							),
						),
					),
				),
				'shipping_lines' => array(
					'description' => __( 'Shipping lines data.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id' => array(
								'description' => __( 'Item ID.', 'woocommerce' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'method_title' => array(
								'description' => __( 'Shipping method name.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'method_id' => array(
								'description' => __( 'Shipping method ID.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'total' => array(
								'description' => __( 'Line total (after discounts).', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'total_tax' => array(
								'description' => __( 'Line total tax (after discounts).', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'taxes' => array(
								'description' => __( 'Line taxes.', 'woocommerce' ),
								'type'        => 'array',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
										'id' => array(
											'description' => __( 'Tax rate ID.', 'woocommerce' ),
											'type'        => 'integer',
											'context'     => array( 'view', 'edit' ),
											'readonly'    => true,
										),
										'total' => array(
											'description' => __( 'Tax total.', 'woocommerce' ),
											'type'        => 'string',
											'context'     => array( 'view', 'edit' ),
											'readonly'    => true,
										),
									),
								),
							),
							'meta_data' => array(
								'description' => __( 'Meta data.', 'woocommerce' ),
								'type'        => 'array',
								'context'     => array( 'view', 'edit' ),
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
										'id' => array(
											'description' => __( 'Meta ID.', 'woocommerce' ),
											'type'        => 'integer',
											'context'     => array( 'view', 'edit' ),
											'readonly'    => true,
										),
										'key' => array(
											'description' => __( 'Meta key.', 'woocommerce' ),
											'type'        => 'string',
											'context'     => array( 'view', 'edit' ),
										),
										'value' => array(
											'description' => __( 'Meta value.', 'woocommerce' ),
											'type'        => 'string',
											'context'     => array( 'view', 'edit' ),
										),
									),
								),
							),
						),
					),
				),
				'fee_lines' => array(
					'description' => __( 'Fee lines data.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id' => array(
								'description' => __( 'Item ID.', 'woocommerce' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'name' => array(
								'description' => __( 'Fee name.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'tax_class' => array(
								'description' => __( 'Tax class of fee.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'tax_status' => array(
								'description' => __( 'Tax status of fee.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'enum'        => array( 'taxable', 'none' ),
							),
							'total' => array(
								'description' => __( 'Line total (after discounts).', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'total_tax' => array(
								'description' => __( 'Line total tax (after discounts).', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'taxes' => array(
								'description' => __( 'Line taxes.', 'woocommerce' ),
								'type'        => 'array',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
										'id' => array(
											'description' => __( 'Tax rate ID.', 'woocommerce' ),
											'type'        => 'integer',
											'context'     => array( 'view', 'edit' ),
											'readonly'    => true,
										),
										'total' => array(
											'description' => __( 'Tax total.', 'woocommerce' ),
											'type'        => 'string',
											'context'     => array( 'view', 'edit' ),
											'readonly'    => true,
										),
										'subtotal' => array(
											'description' => __( 'Tax subtotal.', 'woocommerce' ),
											'type'        => 'string',
											'context'     => array( 'view', 'edit' ),
											'readonly'    => true,
										),
									),
								),
							),
							'meta_data' => array(
								'description' => __( 'Meta data.', 'woocommerce' ),
								'type'        => 'array',
								'context'     => array( 'view', 'edit' ),
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
										'id' => array(
											'description' => __( 'Meta ID.', 'woocommerce' ),
											'type'        => 'integer',
											'context'     => array( 'view', 'edit' ),
											'readonly'    => true,
										),
										'key' => array(
											'description' => __( 'Meta key.', 'woocommerce' ),
											'type'        => 'string',
											'context'     => array( 'view', 'edit' ),
										),
										'value' => array(
											'description' => __( 'Meta value.', 'woocommerce' ),
											'type'        => 'string',
											'context'     => array( 'view', 'edit' ),
										),
									),
								),
							),
						),
					),
				),
				'coupon_lines' => array(
					'description' => __( 'Coupons line data.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id' => array(
								'description' => __( 'Item ID.', 'woocommerce' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'code' => array(
								'description' => __( 'Coupon code.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'discount' => array(
								'description' => __( 'Discount total.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'discount_tax' => array(
								'description' => __( 'Discount total tax.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'meta_data' => array(
								'description' => __( 'Meta data.', 'woocommerce' ),
								'type'        => 'array',
								'context'     => array( 'view', 'edit' ),
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
										'id' => array(
											'description' => __( 'Meta ID.', 'woocommerce' ),
											'type'        => 'integer',
											'context'     => array( 'view', 'edit' ),
											'readonly'    => true,
										),
										'key' => array(
											'description' => __( 'Meta key.', 'woocommerce' ),
											'type'        => 'string',
											'context'     => array( 'view', 'edit' ),
										),
										'value' => array(
											'description' => __( 'Meta value.', 'woocommerce' ),
											'type'        => 'string',
											'context'     => array( 'view', 'edit' ),
										),
									),
								),
							),
						),
					),
				),
				'refunds' => array(
					'description' => __( 'List of refunds.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id' => array(
								'description' => __( 'Refund ID.', 'woocommerce' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'reason' => array(
								'description' => __( 'Refund reason.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'total' => array(
								'description' => __( 'Refund total.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
				),
				'set_paid' => array(
					'description' => __( 'Define if the order is paid. It will set the status to processing and reduce stock items.', 'woocommerce' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'edit' ),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['status'] = array(
			'default'           => 'any',
			'description'       => __( 'Limit result set to orders assigned a specific status.', 'woocommerce' ),
			'type'              => 'string',
			'enum'              => array_merge( array( 'any' ), $this->get_order_statuses() ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['customer'] = array(
			'description'       => __( 'Limit result set to orders assigned a specific customer.', 'woocommerce' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['product'] = array(
			'description'       => __( 'Limit result set to orders assigned a specific product.', 'woocommerce' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['dp'] = array(
			'default'           => 2,
			'description'       => __( 'Number of decimal points to use in each resource.', 'woocommerce' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}
}
