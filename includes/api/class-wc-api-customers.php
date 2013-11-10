<?php
/**
 * WooCommerce API Customers Class
 *
 * Handles requests to the /customers endpoint
 *
 * @author      WooThemes
 * @category    API
 * @package     WooCommerce/API
 * @since       2.1
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_API_Customers extends WC_API_Resource {

	/** @var string $base the route base */
	protected $base = '/customers';

	/** @var string $created_at_min for date filtering */
	private $created_at_min = null;

	/** @var string $created_at_max for date filtering */
	private $created_at_max = null;

	/**
	 * Setup class, overridden to provide customer data to order response
	 *
	 * @since 2.1
	 * @param WC_API_Server $server
	 * @return WC_API_Customers
	 */
	public function __construct( WC_API_Server $server ) {

		parent::__construct( $server );

		// add customer data to order responses
		add_filter( 'woocommerce_api_order_response', array( $this, 'addCustomerData' ), 10, 2 );
		// modify WP_User_Query to support created_at date filtering
		add_action( 'pre_user_query', array( $this, 'modify_user_query' ) );
	}

	/**
	 * Register the routes for this class
	 *
	 * GET|POST /customers
	 * GET /customers/count
	 * GET|PUT|DELETE /customers/<id>
	 * GET /customers/<id>/orders
	 *
	 * @since 2.1
	 * @param array $routes
	 * @return array
	 */
	public function registerRoutes( $routes ) {

		# GET|POST /customers
		$routes[ $this->base ] = array(
			array( array( $this, 'getCustomers' ),     WC_API_SERVER::READABLE ),
			array( array( $this, 'createCustomer' ),   WC_API_SERVER::CREATABLE | WC_API_SERVER::ACCEPT_DATA ),
		);

		# GET /customers/count
		$routes[ $this->base . '/count'] = array(
			array( array( $this, 'getCustomersCount' ), WC_API_SERVER::READABLE ),
		);

		# GET|PUT|DELETE /customers/<id>
		$routes[ $this->base . '/(?P<id>\d+)' ] = array(
			array( array( $this, 'getCustomer' ),  WC_API_SERVER::READABLE ),
			array( array( $this, 'editCustomer' ), WC_API_SERVER::EDITABLE | WC_API_SERVER::ACCEPT_DATA ),
			array( array( $this, 'deleteCustomer' ), WC_API_SERVER::DELETABLE ),
		);

		# GET /customers/<id>/orders
		$routes[ $this->base . '/(?P<id>\d+)/orders' ] = array(
			array( array( $this, 'getCustomerOrders' ), WC_API_SERVER::READABLE ),
		);

		return $routes;
	}

	/**
	 * Get all customers
	 *
	 * @TODO support created_at_min/created_at_max with pre_user_query filter
	 *
	 * @since 2.1
	 * @param array $fields
	 * @param string $q search terms
	 * @param int $limit coupons per response
	 * @param int $offset
	 * @return array
	 */
	public function getCustomers( $fields = null, $q = null, $limit = null, $offset = null ) {

		$request_args = array(
			'q'              => $q,
			'limit'          => $limit,
			'offset'         => $offset,
		);

		$query = $this->queryCustomers( $request_args );

		$customers = array();

		foreach( $query->results as $user_id ) {

			$customers[] = $this->getCustomer( $user_id, $fields );
		}

		return array( 'customers' => $customers );
	}


	/**
	 * Get the customer for the given ID
	 *
	 * @TODO: implement customer meta
	 *
	 * @since 2.1
	 * @param int $id the customer ID
	 * @param string $fields
	 * @return array
	 */
	public function getCustomer( $id, $fields = null ) {
		global $wpdb;

		$id = absint( $id );

		if ( empty( $id ) )
			return new WP_Error( 'woocommerce_api_invalid_id', __( 'Invalid customer ID', 'woocommerce' ), array( 'status' => 404 ) );

		// non-existent IDs return a valid WP_User object with the user ID = 0
		$customer = new WP_User( $id );

		if ( 0 === $customer->ID )
			return new WP_Error( 'woocommerce_api_invalid_customer', __( 'Invalid customer', 'woocommerce' ), array( 'status' => 404 ) );

		// get info about user's last order
		$last_order = $wpdb->get_row( "SELECT id, post_date
						FROM $wpdb->posts AS posts
						LEFT JOIN {$wpdb->postmeta} AS meta on posts.ID = meta.post_id
						WHERE meta.meta_key = '_customer_user'
						AND   meta.meta_value = {$customer->ID}
						AND   posts.post_type = 'shop_order'
						AND   posts.post_status = 'publish'
					" );

		$customer_data = array(
			'id'               => $customer->ID,
			'created_at'       => $customer->user_registered,
			'email'            => $customer->user_email,
			'first_name'       => $customer->first_name,
			'last_name'        => $customer->last_name,
			'username'         => $customer->user_login,
			'last_order_id'    => is_object( $last_order ) ? $last_order->id : null,
			'last_order_date'  => is_object( $last_order ) ? $last_order->post_date : null,
			'orders_count'     => $customer->_order_count,
			'total_spent'      => $customer->_money_spent,
			'avatar_url'       => $this->get_avatar_url( $customer->customer_email ),
			'billing_address'  => array(
				'first_name' => $customer->billing_first_name,
				'last_name'  => $customer->billing_last_name,
				'company'    => $customer->billing_company,
				'address_1'  => $customer->billing_address_1,
				'address_2'  => $customer->billing_address_2,
				'city'       => $customer->billing_city,
				'state'      => $customer->billing_state,
				'postcode'   => $customer->billing_postcode,
				'country'    => $customer->billing_country,
				'email'      => $customer->billing_email,
				'phone'      => $customer->billing_phone,
			),
			'shipping_address' => array(
				'first_name' => $customer->shipping_first_name,
				'last_name'  => $customer->shipping_last_name,
				'company'    => $customer->shipping_company,
				'address_1'  => $customer->shipping_address_1,
				'address_2'  => $customer->shipping_address_2,
				'city'       => $customer->shipping_city,
				'state'      => $customer->shipping_state,
				'postcode'   => $customer->shipping_postcode,
				'country'    => $customer->shipping_country,
			),
		);

		return apply_filters( 'woocommerce_api_customer_response', $customer_data, $customer, $fields );
	}

	/**
	 * Get the total number of customers
	 *
	 * @TODO support created_at_min/created_at_max with pre_user_query filter
	 *
	 * @since 2.1
	 * @return array
	 */
	public function getCustomersCount() {

		$query = $this->queryCustomers();

		return array( 'count' => $query->get_total() );
	}


	/**
	 * Create a customer
	 *
	 * @since 2.1
	 * @param array $data
	 * @return array
	 */
	public function createCustomer( $data ) {

		// TODO: implement - what's the minimum set of data required?
		// woocommerce_create_new_customer()

		return array();
	}

	/**
	 * Edit a customer
	 *
	 * @since 2.1
	 * @param int $id the customer ID
	 * @param array $data
	 * @return array
	 */
	public function editCustomer( $id, $data ) {

		// TODO: implement
		return $this->getCustomer( $id );
	}

	/**
	 * Delete a customer
	 *
	 * @since 2.1
	 * @param int $id the customer ID
	 * @return array
	 */
	public function deleteCustomer( $id ) {

		return $this->deleteResource( $id, 'customer' );
	}

	/**
	 * Get the orders for a customer
	 *
	 * @TODO should this support the same parameters as getOrders call? e.g. fields, created_at, pagination, etc
	 *
	 * @since 2.1
	 * @param int $id the customer ID
	 * @return array
	 */
	public function getCustomerOrders( $id ) {
		global $wpdb;

		// TODO: DRY this along with duplicate code in getCustomer()
		$id = absint( $id );

		if ( empty( $id ) )
			return new WP_Error( 'woocommerce_api_invalid_id', __( 'Invalid customer ID', 'woocommerce' ), array( 'status' => 404 ) );

		// non-existent IDs return a valid WP_User object with the user ID = 0
		$customer = new WP_User( $id );

		if ( 0 === $customer->ID )
			return new WP_Error( 'woocommerce_api_invalid_customer', __( 'Invalid customer', 'woocommerce' ), array( 'status' => 404 ) );

		$order_ids = $wpdb->get_col( "SELECT id
						FROM $wpdb->posts AS posts
						LEFT JOIN {$wpdb->postmeta} AS meta on posts.ID = meta.post_id
						WHERE meta.meta_key = '_customer_user'
						AND   meta.meta_value = {$id}
						AND   posts.post_type = 'shop_order'
						AND   posts.post_status = 'publish'
					" );

		if ( empty( $order_ids ) )
			return array( 'orders' => array() );

		$orders = array();

		foreach ( $order_ids as $order_id ) {
			$orders[] = WC()->api->WC_API_Orders->getOrder( $order_id );
		}

		return array( 'orders' => $orders );
	}

	/**
	 * Helper method to get customer user objects
	 *
	 * @since 2.1
	 * @param array $args request arguments for filtering query
	 * @return array
	 */
	private function queryCustomers( $args = array() ) {

		// set base query arguments
		$query_args = array(
			'fields'  => 'ID',
			'role'    => 'customer',
			'orderby' => 'registered',
			'order'   => 'DESC',
		);

		// TODO: refactor WP_API_Base::mergeQueryVars to support user query args

		if ( ! empty( $args['q'] ) )
			$query_args['search'] = $args['q'];

		if ( ! empty( $args['limit'] ) )
			$query_args['number'] = $args['limit'];

		if ( ! empty( $args['offset'] ) )
			$query_args['offset'] = $args['offset'];

		// TODO: navigation/total count headers for pagination
		if ( ! empty( $args['created_at_min'] ) )
			$this->created_at_min = $args['created_at_min'];

		if ( ! empty( $args['created_at_max'] ) )
			$this->created_at_max = $args['created_at_max'];

		// TODO: support page argument - requires custom implementation as WP_User_Query has no built-in pagination like WP_Query

		return new WP_User_Query( $query_args );
	}

	/**
	 * Add customer data to orders
	 *
	 * @TODO should guest orders return more than 'guest'?
	 *
	 * @since 2.1
	 * @param $order_data
	 * @param $order
	 *
	 */
	public function addCustomerData( $order_data, $order ) {

		if ( 0 == $order->customer_user ) {

			$order_data['customer'] = 'guest';

		} else {

			$order_data['customer'] = $this->getCustomer( $order->customer_user );
		}

		return $order_data;
	}

	/**
	 * Modify the WP_User_Query to support filtering on the date the customer was created
	 *
	 * @since 2.1
	 * @param WP_User_Query $query
	 */
	public function modify_user_query( $query ) {

		if ( $this->created_at_min )
			$query->query_where .= sprintf( " AND DATE(user_registered) >= '%s'", date( 'Y-m-d H:i:s', strtotime( $this->created_at_min ) ) ); // TODO: date formatting

		if ( $this->created_at_max )
			$query->query_where .= sprintf( " AND DATE(user_registered) <= '%s'", date( 'Y-m-d H:i:s', strtotime( $this->created_at_max ) ) ); // TODO: date formatting
	}

	/**
	 * Wrapper for @see get_avatar() which doesn't simply return
	 * the URL so we need to pluck it from the HTML img tag
	 *
	 * @param string $email the customer's email
	 * @return string the URL to the customer's avatar
	 */
	private function get_avatar_url( $email ) {

		$dom = new DOMDocument();

		$dom->loadHTML( get_avatar( $email ) );

		$url = $dom->getElementsByTagName('img')->item(0)->getAttribute('src');

		return ( ! empty( $url ) ) ? $url : null;
	}

}
