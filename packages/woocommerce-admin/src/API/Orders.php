<?php
/**
 * REST API Orders Controller
 *
 * Handles requests to /orders/*
 */

namespace Automattic\WooCommerce\Admin\API;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\Controller as ReportsController;

/**
 * Orders controller.
 *
 * @extends WC_REST_Orders_Controller
 */
class Orders extends \WC_REST_Orders_Controller {
	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-analytics';

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();
		// This needs to remain a string to support extensions that filter Order Number.
		$params['number'] = array(
			'description'       => __( 'Limit result set to orders matching part of an order number.', 'woocommerce' ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		);
		// Fix the default 'status' value until it can be patched in core.
		$params['status']['default'] = array( 'any' );

		// Analytics settings may affect the allowed status list.
		$params['status']['items']['enum'] = ReportsController::get_order_statuses();

		return $params;
	}

	/**
	 * Prepare objects query.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		global $wpdb;
		$args = parent::prepare_objects_query( $request );

		// Search by partial order number.
		if ( ! empty( $request['number'] ) ) {
			$partial_number = trim( $request['number'] );
			$limit          = intval( $args['posts_per_page'] );
			$order_ids      = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT ID
					FROM {$wpdb->prefix}posts
					WHERE post_type = 'shop_order'
					AND ID LIKE %s
					LIMIT %d",
					$wpdb->esc_like( absint( $partial_number ) ) . '%',
					$limit
				)
			);

			// Force WP_Query return empty if don't found any order.
			$order_ids        = empty( $order_ids ) ? array( 0 ) : $order_ids;
			$args['post__in'] = $order_ids;
		}

		return $args;
	}

	/**
	 * Get formatted item data.
	 *
	 * @param  WC_Data $object WC_Data instance.
	 * @return array
	 */
	protected function get_formatted_item_data( $object ) {
		$fields = false;
		// Determine if the response fields were specified.
		if ( ! empty( $this->request['_fields'] ) ) {
			$fields = wp_parse_list( $this->request['_fields'] );

			if ( 0 === count( $fields ) ) {
				$fields = false;
			} else {
				$fields = array_map( 'trim', $fields );
			}
		}

		// Initially skip line items if we can.
		$using_order_class_override = is_a( $object, '\Automattic\WooCommerce\Admin\Overrides\Order' );
		if ( $using_order_class_override ) {
			$data = $object->get_data_without_line_items();
		} else {
			$data = $object->get_data();
		}

		$format_decimal    = array( 'discount_total', 'discount_tax', 'shipping_total', 'shipping_tax', 'shipping_total', 'shipping_tax', 'cart_tax', 'total', 'total_tax' );
		$format_date       = array( 'date_created', 'date_modified', 'date_completed', 'date_paid' );
		$format_line_items = array( 'line_items', 'tax_lines', 'shipping_lines', 'fee_lines', 'coupon_lines' );

		// Format decimal values.
		foreach ( $format_decimal as $key ) {
			$data[ $key ] = wc_format_decimal( $data[ $key ], $this->request['dp'] );
		}

		// Format date values.
		foreach ( $format_date as $key ) {
			$datetime              = $data[ $key ];
			$data[ $key ]          = wc_rest_prepare_date_response( $datetime, false );
			$data[ $key . '_gmt' ] = wc_rest_prepare_date_response( $datetime );
		}

		// Format the order status.
		$data['status'] = 'wc-' === substr( $data['status'], 0, 3 ) ? substr( $data['status'], 3 ) : $data['status'];

		// Format requested line items.
		$formatted_line_items = array();

		foreach ( $format_line_items as $key ) {
			if ( false === $fields || in_array( $key, $fields ) ) {
				if ( $using_order_class_override ) {
					$line_item_data = $object->get_line_item_data( $key );
				} else {
					$line_item_data = $data[ $key ];
				}
				$formatted_line_items[ $key ] = array_values( array_map( array( $this, 'get_order_item_data' ), $line_item_data ) );
			}
		}

		// Refunds.
		$data['refunds'] = array();
		foreach ( $object->get_refunds() as $refund ) {
			$data['refunds'][] = array(
				'id'     => $refund->get_id(),
				'reason' => $refund->get_reason() ? $refund->get_reason() : '',
				'total'  => '-' . wc_format_decimal( $refund->get_amount(), $this->request['dp'] ),
			);
		}

		return array_merge(
			array(
				'id'                   => $object->get_id(),
				'parent_id'            => $data['parent_id'],
				'number'               => $data['number'],
				'order_key'            => $data['order_key'],
				'created_via'          => $data['created_via'],
				'version'              => $data['version'],
				'status'               => $data['status'],
				'currency'             => $data['currency'],
				'date_created'         => $data['date_created'],
				'date_created_gmt'     => $data['date_created_gmt'],
				'date_modified'        => $data['date_modified'],
				'date_modified_gmt'    => $data['date_modified_gmt'],
				'discount_total'       => $data['discount_total'],
				'discount_tax'         => $data['discount_tax'],
				'shipping_total'       => $data['shipping_total'],
				'shipping_tax'         => $data['shipping_tax'],
				'cart_tax'             => $data['cart_tax'],
				'total'                => $data['total'],
				'total_tax'            => $data['total_tax'],
				'prices_include_tax'   => $data['prices_include_tax'],
				'customer_id'          => $data['customer_id'],
				'customer_ip_address'  => $data['customer_ip_address'],
				'customer_user_agent'  => $data['customer_user_agent'],
				'customer_note'        => $data['customer_note'],
				'billing'              => $data['billing'],
				'shipping'             => $data['shipping'],
				'payment_method'       => $data['payment_method'],
				'payment_method_title' => $data['payment_method_title'],
				'transaction_id'       => $data['transaction_id'],
				'date_paid'            => $data['date_paid'],
				'date_paid_gmt'        => $data['date_paid_gmt'],
				'date_completed'       => $data['date_completed'],
				'date_completed_gmt'   => $data['date_completed_gmt'],
				'cart_hash'            => $data['cart_hash'],
				'meta_data'            => $data['meta_data'],
				'refunds'              => $data['refunds'],
			),
			$formatted_line_items
		);
	}
}
