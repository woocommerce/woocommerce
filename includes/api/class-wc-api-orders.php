<?php
/**
 * WooCommerce API Orders Class
 *
 * Handles requests to the /orders endpoint
 *
 * @author      WooThemes
 * @category    API
 * @package     WooCommerce/API
 * @since       2.1
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_API_Orders extends WC_API_Resource {

	/** @var string $base the route base */
	protected $base = '/orders';

	/**
	 * Register the routes for this class
	 *
	 * GET|POST /orders
	 * GET /orders/count
	 * GET|PUT|DELETE /orders/<id>
	 * GET /orders/<id>/notes
	 *
	 * @since 2.1
	 * @param array $routes
	 * @return array
	 */
	public function registerRoutes( $routes ) {

		# GET|POST /orders
		$routes[ $this->base ] = array(
			array( array( $this, 'getOrders' ),     WC_API_Server::READABLE ),
			array( array( $this, 'createOrder' ),   WC_API_Server::CREATABLE | WC_API_Server::ACCEPT_DATA ),
		);

		# GET /orders/count
		$routes[ $this->base . '/count'] = array(
			array( array( $this, 'getOrdersCount' ), WC_API_Server::READABLE ),
		);

		# GET|PUT|DELETE /orders/<id>
		$routes[ $this->base . '/(?P<id>\d+)' ] = array(
			array( array( $this, 'getOrder' ),  WC_API_Server::READABLE ),
			array( array( $this, 'editOrder' ), WC_API_Server::EDITABLE | WC_API_Server::ACCEPT_DATA ),
			array( array( $this, 'deleteOrder' ), WC_API_Server::DELETABLE ),
		);

		# GET /orders/<id>/notes
		$routes[ $this->base . '/(?P<id>\d+)/notes' ] = array(
			array( array( $this, 'getOrderNotes' ), WC_API_Server::READABLE ),
		);

		return $routes;
	}

	/**
	 * Get all orders
	 *
	 * @since 2.1
	 * @param array $fields
	 * @param string $status
	 * @param string $created_at_min
	 * @param string $created_at_max
	 * @param string $updated_at_min
	 * @param string $updated_at_max
	 * @param string $q search terms
	 * @param int $limit coupons per response
	 * @param int $offset
	 * @return array
	 */
	public function getOrders( $fields = array(), $status = null, $created_at_min = null, $created_at_max = null, $updated_at_min = null, $updated_at_max = null, $q = null, $limit = null, $offset = null ) {

		$request_args = array(
			'status' => $status,
			'created_at_min' => $created_at_min,
			'created_at_max' => $created_at_max,
			'updated_at_min' => $updated_at_min,
			'updated_at_max' => $updated_at_max,
			'q'              => $q,
			'limit'          => $limit,
			'offset'         => $offset,
		);

		$query = $this->queryOrders( $request_args );

		$orders = array();

		foreach( $query->posts as $order_id ) {

			$orders[] = $this->getOrder( $order_id, $fields );
		}

		return array( 'orders' => $orders );
	}


	/**
	 * Get the order for the given ID
	 *
	 * @since 2.1
	 * @param int $id the order ID
	 * @param array $fields
	 * @return array
	 */
	public function getOrder( $id, $fields = null ) {

		$id = absint( $id );

		if ( empty( $id ) )
			return new WP_Error( 'woocommerce_api_invalid_id', __( 'Invalid order ID', 'woocommerce' ), array( 'status' => 404 ) );

		// invalid IDs return a valid WC_Order object with customer_user equal to a blank string
		$order = new WC_Order( $id );

		// TODO: check post type instead or abstract into generic object/permissions check in base class @see self::getOrderNotes()
		if ( '' === $order->customer_user )
			return new WP_Error( 'woocommerce_api_invalid_order', __( 'Invalid order', 'woocommerce' ), array( 'status' => 404 ) );

		$order_data = array(
			'id'                        => $order->id,
			'order_number'              => $order->get_order_number(),
			'created_at'                => $order->order_date,
			'updated_at'                => $order->modified_date,
			'completed_at'              => $order->completed_date,
			'status'                    => $order->status,
			'currency'                  => $order->order_currency,
			'total'                     => $order->get_total(),
			'total_line_items_quantity' => $order->get_item_count(),
			'total_tax'                 => $order->get_total_tax(),
			'total_shipping'            => $order->get_total_shipping(),
			'cart_tax'                  => $order->get_cart_tax(),
			'shipping_tax'              => $order->get_shipping_tax(),
			'total_discount'            => $order->get_total_discount(),
			'cart_discount'             => $order->get_cart_discount(),
			'order_discount'            => $order->get_order_discount(),
			'shipping_methods'          => $order->get_shipping_method(),
			'payment_details' => array(
				'method_id'    => $order->payment_method,
				'method_title' => $order->payment_method_title,
				'paid'         => isset( $order->paid_date ),
			),
			'billing_address' => array(
				'first_name' => $order->billing_first_name,
				'last_name'  => $order->billing_last_name,
				'company'    => $order->billing_company,
				'address_1'  => $order->billing_address_1,
				'address_2'  => $order->billing_address_2,
				'city'       => $order->billing_city,
				'state'      => $order->billing_state,
				'postcode'   => $order->billing_postcode,
				'country'    => $order->billing_country,
				'email'      => $order->billing_email,
				'phone'      => $order->billing_phone,
			),
			'shipping_address' => array(
				'first_name' => $order->shipping_first_name,
				'last_name'  => $order->shipping_last_name,
				'company'    => $order->shipping_company,
				'address_1'  => $order->shipping_address_1,
				'address_2'  => $order->shipping_address_2,
				'city'       => $order->shipping_city,
				'state'      => $order->shipping_state,
				'postcode'   => $order->shipping_postcode,
				'country'    => $order->shipping_country,
			),
			'note'                      => $order->customer_note,
			'customer_ip'               => $order->customer_ip_address,
			'customer_user_agent'       => $order->customer_user_agent,
			'customer_id'               => $order->customer_user,
			'view_order_url'            => $order->get_view_order_url(),
		);

		// add line items
		foreach( $order->get_items() as $item_id => $item ) {

			$product = $order->get_product_from_item( $item );

			$order_data['line_items'][] = array(
				'id'         => $item_id,
				'subtotal'   => $order->get_line_subtotal( $item ),
				'total'      => $order->get_line_total( $item ),
				'total_tax'  => $order->get_line_tax( $item ),
				'quantity'   => $item['qty'],
				'tax_class'  => ( ! empty( $item['tax_class'] ) ) ? $item['tax_class'] : null,
				'name'       => $item['name'],
				'product_id' => ( isset( $product->variation_id ) ) ? $product->variation_id : $product->id,
				'sku'        => $product->get_sku(),
			);
		}

		// add shipping
		foreach ( $order->get_shipping_methods() as $shipping_item_id => $shipping_item ) {

			$order_data['shipping_lines'][] = array(
				'id'           => $shipping_item_id,
				'method_id'    => $shipping_item['method_id'],
				'method_title' => $shipping_item['name'],
				'total'        => $shipping_item['cost'],
			);
		}

		// add taxes
		foreach ( $order->get_tax_totals() as $tax_code => $tax ) {

			$order_data['tax_lines'][] = array(
				'code'     => $tax_code,
				'title'    => $tax->label,
				'total'    => $tax->amount,
				'compound' => (bool) $tax->is_compound,
			);
		}

		// add fees
		foreach ( $order->get_fees() as $fee_item_id => $fee_item ) {

			$order_data['fee_lines'] = array(
				'id'        => $fee_item_id,
				'title'     => $fee_item['name'],
				'tax_class' => ( ! empty( $fee_item['tax_class'] ) ) ? $fee_item['tax_class'] : null,
				'total'     => $order->get_line_total( $fee_item ),
				'total_tax' => $order->get_line_tax( $fee_item ),
			);
		}

		// add coupons
		foreach ( $order->get_items( 'coupon' ) as $coupon_item_id => $coupon_item ) {

			$order_data['coupon_lines'] = array(
				'id'     => $coupon_item_id,
				'code'   => $coupon_item['name'],
				'amount' => $coupon_item['discount_amount'],
			);
		}

		// ensure line properties exist in response
		foreach ( array( 'line_items', 'shipping_lines', 'tax_lines', 'fee_lines', 'coupon_lines' ) as $line ) {

			if ( ! isset( $order_data[ $line ] ) )
				$order_data[ $line ] = array();
		}

		return apply_filters( 'woocommerce_api_order_response', $order_data, $order, $fields );
	}

	/**
	 * Get the total number of orders
	 *
	 * @since 2.1
	 * @param string $status
	 * @param string $created_at_min
	 * @param string $created_at_max
	 * @param string $updated_at_min
	 * @param string $updated_at_max
	 * @return array
	 */
	public function getOrdersCount( $status = null, $created_at_min = null, $created_at_max = null, $updated_at_min = null, $updated_at_max = null ) {

		$request_args = array(
			'status' => $status,
			'created_at_min' => $created_at_min,
			'created_at_max' => $created_at_max,
			'updated_at_min' => $updated_at_min,
			'updated_at_max' => $updated_at_max,
		);

		$query = $this->queryOrders( $request_args );

		return array( 'count' => $query->found_posts );
	}

	/**
	 * Create an order
	 *
	 * @since 2.1
	 * @param array $data
	 * @return array
	 */
	public function createOrder( $data ) {

		// TODO: implement - a woocommerce_create_new_order() function would be great

		return array();
	}

	/**
	 * Edit an order
	 *
	 * @since 2.1
	 * @param int $id the order ID
	 * @param array $data
	 * @return array
	 */
	public function editOrder( $id, $data ) {

		// TODO: implement

		return $this->getOrder( $id );
	}

	/**
	 * Delete an order
	 *
	 * @since 2.1
	 * @param int $id the order ID
	 * @param bool $force true to permanently delete order, false to move to trash
	 * @return array
	 */
	public function deleteOrder( $id, $force = false ) {

		return $this->deleteResource( $id, 'order',  ( 'true' === $force ) );
	}

	/**
	 * Get the admin order notes for an order
	 * @param $id
	 * @return mixed
	 */
	public function getOrderNotes( $id ) {

		$id = absint( $id );

		if ( empty( $id ) )
			return new WP_Error( 'woocommerce_api_invalid_id', __( 'Invalid order ID', 'woocommerce' ), array( 'status' => 404 ) );

		$post = get_post( $id, ARRAY_A );

		if ( 'shop_order' !== $post['post_type'] )
			return new WP_Error( 'woocommerce_api_invalid_order', __( 'Invalid order', 'woocommerce' ), array( 'status' => 404 ) );

		$args = array(
			'post_id' => $id,
			'approve' => 'approve',
			'type'    => 'order_note'
		);


		remove_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments', 10, 1 ) );

		$notes = get_comments( $args );

		add_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ), 10, 1 );

		$order_notes = array();

		foreach ( $notes as $note ) {

			$order_notes[] = array(
				'created_at'    => $note->comment_date,
				'note'          => $note->comment_content,
				'customer_note' => get_comment_meta( $note->comment_ID, 'is_customer_note', true ) ? true : false,
			);
		}

		return array( 'order_notes' => $order_notes );
	}

	/**
	 * Helper method to get order post objects
	 *
	 * @since 2.1
	 * @param array $args request arguments for filtering query
	 * @return array
	 */
	private function queryOrders( $args ) {

		// set base query arguments
		$query_args = array(
			'fields'      => 'ids',
			'post_type'   => 'shop_order',
			'post_status' => 'publish',
		);

		// add status argument
		if ( ! empty( $args['status'] ) ) {

			$statuses = explode( ',', $args['status'] );

			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'shop_order_status',
					'field' => 'slug',
					'terms' => $statuses,
				),
			);
		}

		$query_args = $this->mergeQueryArgs( $query_args, $args );

		// TODO: navigation/total count headers for pagination

		return new WP_Query( $query_args );
	}

}
