<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WooCommerce Webhook class.
 *
 * This class handles storing and retrieving webhook data from the associated.
 * `shop_webhook` custom post type, as well as delivery logs from the `webhook_delivery`.
 * comment type.
 *
 * Webhooks are enqueued to their associated actions, delivered, and logged.
 *
 * @author      WooThemes
 * @category    Webhooks
 * @package     WooCommerce/Webhooks
 * @since       2.2
 */
class WC_Webhook {

	/** @var int webhook ID (post ID) */
	public $id;

	/**
	 * Setup webhook & load post data.
	 *
	 * @since 2.2
	 * @param string|int $id
	 * @return \WC_Webhook
	 */
	public function __construct( $id ) {

		$id = absint( $id );

		if ( ! $id ) {
			return;
		}

		$this->id = $id;
		$this->post_data = get_post( $id );
	}


	/**
	 * Magic isset as a wrapper around metadata_exists().
	 *
	 * @since 2.2
	 * @param string $key
	 * @return bool true if $key isset, false otherwise
	 */
	public function __isset( $key ) {
		if ( ! $this->id ) {
			return false;
		}
		return metadata_exists( 'post', $this->id, '_' . $key );
	}


	/**
	 * Magic get, wraps get_post_meta() for all keys except $status.
	 *
	 * @since 2.2
	 * @param string $key
	 * @return mixed value
	 */
	public function __get( $key ) {

		if ( 'status' === $key ) {
			$value = $this->get_status();
		} else {
			$value = get_post_meta( $this->id, '_' . $key, true );
		}

		return $value;
	}


	/**
	 * Enqueue the hooks associated with the webhook.
	 *
	 * @since 2.2
	 */
	public function enqueue() {
		$hooks = $this->get_hooks();
		$url   = $this->get_delivery_url();

		if ( is_array( $hooks ) && ! empty( $url ) ) {
			foreach ( $hooks as $hook ) {
				add_action( $hook, array( $this, 'process' ) );
			}
		}
	}


	/**
	 * Process the webhook for delivery by verifying that it should be delivered.
	 * and scheduling the delivery (in the background by default, or immediately).
	 *
	 * @since 2.2
	 * @param mixed $arg the first argument provided from the associated hooks
	 */
	public function process( $arg ) {

		// verify that webhook should be processed for delivery
		if ( ! $this->should_deliver( $arg ) ) {
			return;
		}

		// webhooks are processed in the background by default
		// so as to avoid delays or failures in delivery from affecting the
		// user who triggered it
		if ( apply_filters( 'woocommerce_webhook_deliver_async', true, $this ) ) {

			// deliver in background
			wp_schedule_single_event( time(), 'woocommerce_deliver_webhook_async', array( $this->id, is_scalar( $arg ) ? $arg : 0 ) );

		} else {

			// deliver immediately
			$this->deliver( $arg );
		}
	}

	/**
	 * Helper to check if the webhook should be delivered, as some hooks.
	 * (like `wp_trash_post`) will fire for every post type, not just ours.
	 *
	 * @since 2.2
	 * @param mixed $arg first hook argument
	 * @return bool true if webhook should be delivered, false otherwise
	 */
	private function should_deliver( $arg ) {

		// only active webhooks can be delivered
		if ( 'active' != $this->get_status() ) {
			return false;
		}

		$current_action = current_action();

		// only deliver deleted event for coupons, orders, and products
		if ( 'delete_post' == $current_action && ! in_array( $GLOBALS['post_type'], array( 'shop_coupon', 'shop_order', 'product' ) ) ) {
			return false;

		} elseif ( 'delete_user' == $current_action ) {
			$user = get_userdata( absint( $arg ) );

			// only deliver deleted customer event for users with customer role
			if ( ! $user || ! in_array( 'customer', (array) $user->roles ) ) {
				return false;
			}

		// check if the custom order type has chosen to exclude order webhooks from triggering along with its own webhooks.
		} elseif ( 'order' == $this->get_resource() && ! in_array( get_post_type( absint( $arg ) ), wc_get_order_types( 'order-webhooks' ) ) ) {
			return false;

		} elseif ( 0 === strpos( $current_action, 'woocommerce_process_shop' ) ) {
			// the `woocommerce_process_shop_*` hook fires for both updates
			// and creation so check the post creation date to determine the actual event
			$resource = get_post( absint( $arg ) );

			// a resource is considered created when the hook is executed within 10 seconds of the post creation date
			$resource_created = ( ( time() - 10 ) <= strtotime( $resource->post_date_gmt ) );

			if ( 'created' == $this->get_event() && ! $resource_created ) {
				return false;
			} elseif ( 'updated' == $this->get_event() && $resource_created ) {
				return false;
			}
		}

		return true;
	}


	/**
	 * Deliver the webhook payload using wp_safe_remote_request().
	 *
	 * @since 2.2
	 * @param mixed $arg first hook argument
	 */
	public function deliver( $arg ) {

		$payload = $this->build_payload( $arg );

		// setup request args
		$http_args = array(
			'method'      => 'POST',
			'timeout'     => MINUTE_IN_SECONDS,
			'redirection' => 0,
			'httpversion' => '1.0',
			'blocking'    => true,
			'user-agent'  => sprintf( 'WooCommerce/%s Hookshot (WordPress/%s)', WC_VERSION, $GLOBALS['wp_version'] ),
			'body'        => trim( json_encode( $payload ) ),
			'headers'     => array( 'Content-Type' => 'application/json' ),
			'cookies'     => array(),
		);

		$http_args = apply_filters( 'woocommerce_webhook_http_args', $http_args, $arg, $this->id );

		// add custom headers
		$http_args['headers']['X-WC-Webhook-Topic']       = $this->get_topic();
		$http_args['headers']['X-WC-Webhook-Resource']    = $this->get_resource();
		$http_args['headers']['X-WC-Webhook-Event']       = $this->get_event();
		$http_args['headers']['X-WC-Webhook-Signature']   = $this->generate_signature( $http_args['body'] );
		$http_args['headers']['X-WC-Webhook-ID']          = $this->id;
		$http_args['headers']['X-WC-Webhook-Delivery-ID'] = $delivery_id = $this->get_new_delivery_id();

		$start_time = microtime( true );

		// webhook away!
		$response = wp_safe_remote_request( $this->get_delivery_url(), $http_args );

		$duration = round( microtime( true ) - $start_time, 5 );

		$this->log_delivery( $delivery_id, $http_args, $response, $duration );

		do_action( 'woocommerce_webhook_delivery', $http_args, $response, $duration, $arg, $this->id );
	}


	/**
	 * Build the payload data for the webhook.
	 *
	 * @since 2.2
	 * @param mixed $resource_id first hook argument, typically the resource ID
	 * @return mixed payload data
	 */
	private function build_payload( $resource_id ) {

		// build the payload with the same user context as the user who created
		// the webhook -- this avoids permission errors as background processing
		// runs with no user context
		$current_user = get_current_user_id();
		wp_set_current_user( $this->get_user_id() );

		$resource = $this->get_resource();
		$event    = $this->get_event();

		// if a resource has been deleted, just include the ID
		if ( 'deleted' == $event ) {

			$payload = array(
				'id' => $resource_id,
			);

		} else {

			// include & load API classes
			WC()->api->includes();
			WC()->api->register_resources( new WC_API_Server( '/' ) );

			switch( $resource ) {

				case 'coupon':
					$payload = WC()->api->WC_API_Coupons->get_coupon( $resource_id );
					break;

				case 'customer':
					$payload = WC()->api->WC_API_Customers->get_customer( $resource_id );
					break;

				case 'order':
					$payload = WC()->api->WC_API_Orders->get_order( $resource_id );
					break;

				case 'product':
					$payload = WC()->api->WC_API_Products->get_product( $resource_id );
					break;

				// custom topics include the first hook argument
				case 'action':
					$payload = array(
						'action' => current( $this->get_hooks() ),
						'arg'    => $resource_id,
					);
					break;

				default:
					$payload = array();
			}
		}

		// restore the current user
		wp_set_current_user( $current_user );

		return apply_filters( 'woocommerce_webhook_payload', $payload, $resource, $resource_id, $this->id );
	}


	/**
	 * Generate a base64-encoded HMAC-SHA256 signature of the payload body so the.
	 * recipient can verify the authenticity of the webhook. Note that the signature.
	 * is calculated after the body has already been encoded (JSON by default).
	 *
	 * @since 2.2
	 * @param string $payload payload data to hash
	 * @return string hash
	 */
	public function generate_signature( $payload ) {

		$hash_algo = apply_filters( 'woocommerce_webhook_hash_algorithm', 'sha256', $payload, $this->id );

		return base64_encode( hash_hmac( $hash_algo, $payload, $this->get_secret(), true ) );
	}


	/**
	 * Create a new comment for log the delivery request/response and.
	 * return the ID for inclusion in the webhook request.
	 *
	 * @since 2.2
	 * @return int delivery (comment) ID
	 */
	public function get_new_delivery_id() {

		$comment_data = apply_filters( 'woocommerce_new_webhook_delivery_data', array(
			'comment_author'       => __( 'WooCommerce', 'woocommerce' ),
			'comment_author_email' => sanitize_email( sprintf( '%s@%s', strtolower( __( 'WooCommerce', 'woocommerce' ) ), isset( $_SERVER['HTTP_HOST'] ) ? str_replace( 'www.', '', $_SERVER['HTTP_HOST'] ) : 'noreply.com' ) ),
			'comment_post_ID'      => $this->id,
			'comment_agent'        => 'WooCommerce Hookshot',
			'comment_type'         => 'webhook_delivery',
			'comment_parent'       => 0,
			'comment_approved'     => 1,
		), $this->id );

		$comment_id = wp_insert_comment( $comment_data );

		return $comment_id;
	}


	/**
	 * Log the delivery request/response.
	 *
	 * @since 2.2
	 * @param int $delivery_id previously created comment ID
	 * @param array $request request data
	 * @param array $response response data
	 * @param float $duration request duration
	 */
	public function log_delivery( $delivery_id, $request, $response, $duration ) {

		// save request data
		add_comment_meta( $delivery_id, '_request_method', $request['method'] );
		add_comment_meta( $delivery_id, '_request_headers', array_merge( array( 'User-Agent' => $request['user-agent'] ), $request['headers'] ) );
		add_comment_meta( $delivery_id, '_request_body', $request['body'] );

		// parse response
		if ( is_wp_error( $response ) ) {
			$response_code    = $response->get_error_code();
			$response_message = $response->get_error_message();
			$response_headers = $response_body = array();

		} else {
			$response_code    = wp_remote_retrieve_response_code( $response );
			$response_message = wp_remote_retrieve_response_message( $response );
			$response_headers = wp_remote_retrieve_headers( $response );
			$response_body    = wp_remote_retrieve_body( $response );
		}

		// save response data
		add_comment_meta( $delivery_id, '_response_code', $response_code );
		add_comment_meta( $delivery_id, '_response_message', $response_message );
		add_comment_meta( $delivery_id, '_response_headers', $response_headers );
		add_comment_meta( $delivery_id, '_response_body', $response_body );

		// save duration
		add_comment_meta( $delivery_id, '_duration', $duration );

		// set a summary for quick display
		$args = array(
			'comment_ID' => $delivery_id,
			'comment_content' => sprintf( 'HTTP %s %s: %s', $response_code, $response_message, $response_body ),
		);

		wp_update_comment( $args );

		// track failures
		if ( intval( $response_code ) >= 200 && intval( $response_code ) < 300 ) {
			delete_post_meta( $this->id, '_failure_count' );
		} else {
			$this->failed_delivery();
		}

		// keep the 25 most recent delivery logs
		$log = wp_count_comments( $this->id );
		if ( $log->total_comments > apply_filters( 'woocommerce_max_webhook_delivery_logs', 25 ) ) {
			global $wpdb;

			$comment_id = $wpdb->get_var( $wpdb->prepare( "SELECT comment_ID FROM {$wpdb->comments} WHERE comment_post_ID = %d ORDER BY comment_date_gmt ASC LIMIT 1", $this->id ) );

			if ( $comment_id ) {
				wp_delete_comment( $comment_id, true );
			}
		}
	}

	/**
	 * Track consecutive delivery failures and automatically disable the webhook.
	 * if more than 5 consecutive failures occur. A failure is defined as a.
	 * non-2xx response.
	 *
	 * @since 2.2
	 */
	private function failed_delivery() {

		$failures = $this->get_failure_count();

		if ( $failures > apply_filters( 'woocommerce_max_webhook_delivery_failures', 5 ) ) {

			$this->update_status( 'disabled' );

		} else {

			update_post_meta( $this->id, '_failure_count', ++$failures );
		}
	}


	/**
	 * Get the delivery logs for this webhook.
	 *
	 * @since 2.2
	 * @return array
	 */
	public function get_delivery_logs() {

		$args = array(
			'post_id' => $this->id,
			'status'  => 'approve',
			'type'    => 'webhook_delivery',
		);

		remove_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_webhook_comments' ), 10, 1 );

		$logs = get_comments( $args );

		add_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_webhook_comments' ), 10, 1 );

		$delivery_logs = array();

		foreach ( $logs as $log ) {

			$log = $this->get_delivery_log( $log->comment_ID );

			$delivery_logs[] = ( ! empty( $log )  ? $log : array() );
		}

		return $delivery_logs;
	}


	/**
	 * Get the delivery log specified by the ID. The delivery log includes:
	 *
	 * + duration
	 * + summary
	 * + request method/url
	 * + request headers/body
	 * + response code/message/headers/body
	 *
	 * @since 2.2
	 * @param int $delivery_id
	 * @return bool|array false if invalid delivery ID, array of log data otherwise
	 */
	public function get_delivery_log( $delivery_id ) {

		$log = get_comment( $delivery_id );

		// valid comment and ensure delivery log belongs to this webhook
		if ( is_null( $log ) || $log->comment_post_ID != $this->id ) {
			return false;
		}

		$delivery_log = array(
			'id'               => intval( $delivery_id ),
			'duration'         => get_comment_meta( $delivery_id, '_duration', true ),
			'summary'          => $log->comment_content,
			'request_method'   => get_comment_meta( $delivery_id, '_request_method', true ),
			'request_url'      => $this->get_delivery_url(),
			'request_headers'  => get_comment_meta( $delivery_id, '_request_headers', true ),
			'request_body'     => get_comment_meta( $delivery_id, '_request_body', true ),
			'response_code'    => get_comment_meta( $delivery_id, '_response_code', true ),
			'response_message' => get_comment_meta( $delivery_id, '_response_message', true ),
			'response_headers' => get_comment_meta( $delivery_id, '_response_headers', true ),
			'response_body'    => get_comment_meta( $delivery_id, '_response_body', true ),
			'comment'          => $log,
		);

		return apply_filters( 'woocommerce_webhook_delivery_log', $delivery_log, $delivery_id, $this->id );
	}

	/**
	 * Set the webhook topic and associated hooks. The topic resource & event.
	 * are also saved separately.
	 *
	 * @since 2.2
	 * @param string $topic
	 */
	public function set_topic( $topic ) {

		$topic = strtolower( $topic );

		list( $resource, $event ) = explode( '.', $topic );

		update_post_meta( $this->id, '_topic', $topic );
		update_post_meta( $this->id, '_resource', $resource );
		update_post_meta( $this->id, '_event', $event );

		// custom topics are mapped to a single hook
		if ( 'action' === $resource ) {

			update_post_meta( $this->id, '_hooks', array( $event ) );

		} else {

			// API topics have multiple hooks
			update_post_meta( $this->id, '_hooks', $this->get_topic_hooks( $topic ) );
		}
	}

	/**
	 * Get the associated hook names for a topic.
	 *
	 * @since 2.2
	 * @param string $topic
	 * @return array hook names
	 */
	private function get_topic_hooks( $topic ) {

		$topic_hooks = array(
			'coupon.created' => array(
				'woocommerce_process_shop_coupon_meta',
				'woocommerce_api_create_coupon',
			),
			'coupon.updated' => array(
				'woocommerce_process_shop_coupon_meta',
				'woocommerce_api_edit_coupon',
			),
			'coupon.deleted' => array(
				'wp_trash_post',
			),
			'customer.created' => array(
				'woocommerce_created_customer',
			),
			'customer.updated' => array(
				'profile_update',
				'woocommerce_api_edit_customer',
			),
			'customer.deleted' => array(
				'delete_user',
			),
			'order.created'    => array(
				'woocommerce_checkout_order_processed',
				'woocommerce_process_shop_order_meta',
				'woocommerce_api_create_order',
			),
			'order.updated' => array(
				'woocommerce_process_shop_order_meta',
				'woocommerce_api_edit_order',
				'woocommerce_order_edit_status',
				'woocommerce_order_status_changed'
			),
			'order.deleted' => array(
				'wp_trash_post',
			),
			'product.created' => array(
				'woocommerce_process_product_meta',
				'woocommerce_api_create_product',
			),
			'product.updated' => array(
				'woocommerce_process_product_meta',
				'woocommerce_api_edit_product',
			),
			'product.deleted' => array(
				'wp_trash_post',
			),
		);

		$topic_hooks = apply_filters( 'woocommerce_webhook_topic_hooks', $topic_hooks, $this );

		return isset( $topic_hooks[ $topic ] ) ? $topic_hooks[ $topic ] : array();
	}

	/**
	 * Send a test ping to the delivery URL, sent when the webhook is first created.
	 *
	 * @since 2.2
	 * @return bool|WP_Error
	 */
	public function deliver_ping() {
		$args = array(
			'user-agent' => sprintf( 'WooCommerce/%s Hookshot (WordPress/%s)', WC_VERSION, $GLOBALS['wp_version'] ),
			'body'       => "webhook_id={$this->id}",
		);

		$test          = wp_safe_remote_post( $this->get_delivery_url(), $args );
		$response_code = wp_remote_retrieve_response_code( $test );

		if ( is_wp_error( $test ) ) {
			return new WP_Error( 'error', sprintf( __( 'Error: Delivery URL cannot be reached: %s', 'woocommerce' ), $test->get_error_message() ) );
		}

		if ( 200 !== $response_code ) {
			return new WP_Error( 'error', sprintf( __( 'Error: Delivery URL returned response code: %s', 'woocommerce' ), absint( $response_code ) ) );
		}

		return true;
	}

	/**
	 * Get the webhook status:
	 *
	 * + `active` - delivers payload.
	 * + `paused` - does not deliver payload, paused by admin.
	 * + `disabled` - does not delivery payload, paused automatically due to.
	 * consecutive failures.
	 *
	 * @since 2.2
	 * @return string status
	 */
	public function get_status() {

		switch ( $this->get_post_data()->post_status ) {

			case 'publish':
				$status = 'active';
				break;

			case 'draft':
				$status = 'paused';
				break;

			case 'pending':
				$status = 'disabled';
				break;

			default:
				$status = 'paused';
		}

		return apply_filters( 'woocommerce_webhook_status', $status, $this->id );
	}

	/**
	 * Get the webhook i18n status.
	 *
	 * @return string
	 */
	public function get_i18n_status() {
		$status   = $this->get_status();
		$statuses = wc_get_webhook_statuses();

		return isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status;
	}

	/**
	 * Update the webhook status, see get_status() for valid statuses.
	 *
	 * @since 2.2
	 * @param $status
	 */
	public function update_status( $status ) {
		global $wpdb;

		switch ( $status ) {

			case 'active' :
				$post_status = 'publish';
				break;

			case 'paused' :
				$post_status = 'draft';
				break;

			case 'disabled' :
				$post_status = 'pending';
				break;

			default :
				$post_status = 'draft';
				break;
		}

		$wpdb->update( $wpdb->posts, array( 'post_status' => $post_status ), array( 'ID' => $this->id ) );
		clean_post_cache( $this->id );
	}

	/**
	 * Set the delivery URL.
	 *
	 * @since 2.2
	 * @param string $url
	 */
	public function set_delivery_url( $url ) {
		if ( update_post_meta( $this->id, '_delivery_url', esc_url_raw( $url, array( 'http', 'https' ) ) ) ) {
			update_post_meta( $this->id, '_webhook_pending_delivery', true );
		}
	}

	/**
	 * Get the delivery URL.
	 *
	 * @since 2.2
	 * @return string
	 */
	public function get_delivery_url() {

		return apply_filters( 'woocommerce_webhook_delivery_url', $this->delivery_url, $this->id );
	}

	/**
	 * Set the secret used for generating the HMAC-SHA256 signature.
	 *
	 * @since 2.2
	 * @param string $secret
	 */
	public function set_secret( $secret ) {

		update_post_meta( $this->id, '_secret', $secret );
	}

	/**
	 * Get the secret used for generating the HMAC-SHA256 signature.
	 *
	 * @since 2.2
	 * @return string
	 */
	public function get_secret() {
		return apply_filters( 'woocommerce_webhook_secret', $this->secret, $this->id );
	}

	/**
	 * Get the friendly name for the webhook.
	 *
	 * @since 2.2
	 * @return string
	 */
	public function get_name() {
		return apply_filters( 'woocommerce_webhook_name', $this->get_post_data()->post_title, $this->id );
	}

	/**
	 * Get the webhook topic, e.g. `order.created`.
	 *
	 * @since 2.2
	 * @return string
	 */
	public function get_topic() {
		return apply_filters( 'woocommerce_webhook_topic', $this->topic, $this->id );
	}

	/**
	 * Get the hook names for the webhook.
	 *
	 * @since 2.2
	 * @return array hook names
	 */
	public function get_hooks() {
		return apply_filters( 'woocommerce_webhook_hooks', $this->hooks, $this->id );
	}

	/**
	 * Get the resource for the webhook, e.g. `order`.
	 *
	 * @since 2.2
	 * @return string
	 */
	public function get_resource() {
		return apply_filters( 'woocommerce_webhook_resource', $this->resource, $this->id );
	}

	/**
	 * Get the event for the webhook, e.g. `created`.
	 *
	 * @since 2.2
	 * @return string
	 */
	public function get_event() {
		return apply_filters( 'woocommerce_webhook_event', $this->event, $this->id );
	}

	/**
	 * Get the failure count.
	 *
	 * @since 2.2
	 * @return int
	 */
	public function get_failure_count() {
		return intval( $this->failure_count );
	}

	/**
	 * Get the user ID for this webhook.
	 *
	 * @since 2.2
	 * @return int|string user ID
	 */
	public function get_user_id() {
		return $this->get_post_data()->post_author;
	}

	/**
	 * Get the post data for the webhook.
	 *
	 * @since 2.2
	 * @return null|WP_Post
	 */
	public function get_post_data() {
		return $this->post_data;
	}

}
