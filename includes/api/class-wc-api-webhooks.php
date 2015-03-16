<?php
/**
 * WooCommerce API Webhooks class
 *
 * Handles requests to the /webhooks endpoint
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/API
 * @since    2.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_API_Webhooks extends WC_API_Resource {

	/** @var string $base the route base */
	protected $base = '/webhooks';

	/**
	 * Register the routes for this class
	 *
	 * @since 2.2
	 * @param array $routes
	 * @return array
	 */
	public function register_routes( $routes ) {

		# GET|POST /webhooks
		$routes[ $this->base ] = array(
			array( array( $this, 'get_webhooks' ),     WC_API_Server::READABLE ),
			array( array( $this, 'create_webhook' ),   WC_API_Server::CREATABLE | WC_API_Server::ACCEPT_DATA ),
		);

		# GET /webhooks/count
		$routes[ $this->base . '/count'] = array(
			array( array( $this, 'get_webhooks_count' ), WC_API_Server::READABLE ),
		);

		# GET|PUT|DELETE /webhooks/<id>
		$routes[ $this->base . '/(?P<id>\d+)' ] = array(
			array( array( $this, 'get_webhook' ),  WC_API_Server::READABLE ),
			array( array( $this, 'edit_webhook' ), WC_API_Server::EDITABLE | WC_API_Server::ACCEPT_DATA ),
			array( array( $this, 'delete_webhook' ), WC_API_Server::DELETABLE ),
		);

		# GET /webhooks/<id>/deliveries
		$routes[ $this->base . '/(?P<webhook_id>\d+)/deliveries' ] = array(
			array( array( $this, 'get_webhook_deliveries' ), WC_API_Server::READABLE ),
		);

		# GET /webhooks/<webhook_id>/deliveries/<id>
		$routes[ $this->base . '/(?P<webhook_id>\d+)/deliveries/(?P<id>\d+)' ] = array(
			array( array( $this, 'get_webhook_delivery' ), WC_API_Server::READABLE ),
		);

		return $routes;
	}

	/**
	 * Get all webhooks
	 *
	 * @since 2.2
	 * @param array $fields
	 * @param array $filter
	 * @param int $page
	 * @return array
	 */
	public function get_webhooks( $fields = null, $filter = array(), $status = null, $page = 1 ) {

		if ( ! empty( $status ) ) {
			$filter['status'] = $status;
		}

		$filter['page'] = $page;

		$query = $this->query_webhooks( $filter );

		$webhooks = array();

		foreach ( $query->posts as $webhook_id ) {

			if ( ! $this->is_readable( $webhook_id ) ) {
				continue;
			}

			$webhooks[] = current( $this->get_webhook( $webhook_id, $fields ) );
		}

		$this->server->add_pagination_headers( $query );

		return array( 'webhooks' => $webhooks );
	}

	/**
	 * Get the webhook for the given ID
	 *
	 * @since 2.2
	 * @param int $id webhook ID
	 * @param array $fields
	 * @return array
	 */
	public function get_webhook( $id, $fields = null ) {

		// ensure webhook ID is valid & user has permission to read
		$id = $this->validate_request( $id, 'shop_webhook', 'read' );

		if ( is_wp_error( $id ) ) {
			return $id;
		}

		$webhook = new WC_Webhook( $id );

		$webhook_data = array(
			'id'           => $webhook->id,
			'name'         => $webhook->get_name(),
			'status'       => $webhook->get_status(),
			'topic'        => $webhook->get_topic(),
			'resource'     => $webhook->get_resource(),
			'event'        => $webhook->get_event(),
			'hooks'        => $webhook->get_hooks(),
			'delivery_url' => $webhook->get_delivery_url(),
			'created_at'   => $this->server->format_datetime( $webhook->get_post_data()->post_date_gmt ),
			'updated_at'   => $this->server->format_datetime( $webhook->get_post_data()->post_modified_gmt ),
		);

		return array( 'webhook' => apply_filters( 'woocommerce_api_webhook_response', $webhook_data, $webhook, $fields, $this ) );
	}

	/**
	 * Get the total number of webhooks
	 *
	 * @since 2.2
	 * @param string $status
	 * @param array $filter
	 * @return array
	 */
	public function get_webhooks_count( $status = null, $filter = array() ) {
		try {
			if ( ! current_user_can( 'read_private_shop_webhooks' ) ) {
				throw new WC_API_Exception( 'woocommerce_api_user_cannot_read_webhooks_count', __( 'You do not have permission to read the webhooks count', 'woocommerce' ), 401 );
			}

			if ( ! empty( $status ) ) {
				$filter['status'] = $status;
			}

			$query = $this->query_webhooks( $filter );

			return array( 'count' => (int) $query->found_posts );
		} catch ( WC_API_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Create an webhook
	 *
	 * @since 2.2
	 * @param array $data parsed webhook data
	 * @return array
	 */
	public function create_webhook( $data ) {

		$data = isset( $data['webhook'] ) ? $data['webhook'] : array();

		try {

			// permission check
			if ( ! current_user_can( 'publish_shop_webhooks' ) ) {
				throw new WC_API_Exception( 'woocommerce_api_user_cannot_create_webhooks', __( 'You do not have permission to create webhooks', 'woocommerce' ), 401 );
			}

			$data = apply_filters( 'woocommerce_api_create_webhook_data', $data, $this );

			// validate topic
			if ( empty( $data['topic'] ) || ! wc_is_webhook_valid_topic( strtolower( $data['topic'] ) ) ) {
				throw new WC_API_Exception( 'woocommerce_api_invalid_webhook_topic', __( 'Webhook topic is required and must be valid', 'woocommerce' ), 400 );
			}

			// validate delivery URL
			if ( empty( $data['delivery_url'] ) || ! wc_is_valid_url( $data['delivery_url'] ) ) {
				throw new WC_API_Exception( 'woocommerce_api_invalid_webhook_delivery_url', __( 'Webhook delivery URL must be a valid URL starting with http:// or https://', 'woocommerce' ), 400 );
			}

			$webhook_data = apply_filters( 'woocommerce_new_webhook_data', array(
				'post_type'     => 'shop_webhook',
				'post_status'   => 'publish',
				'ping_status'   => 'closed',
				'post_author'   => get_current_user_id(),
				'post_password' => strlen( ( $password = uniqid( 'webhook_' ) ) ) > 20 ? substr( $password, 0, 20 ) : $password,
				'post_title'    => ! empty( $data['name'] ) ? $data['name'] : sprintf( __( 'Webhook created on %s', 'woocommerce' ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Webhook created on date parsed by strftime', 'woocommerce' ) ) ),
			), $data, $this );

			$webhook_id = wp_insert_post( $webhook_data );

			if ( is_wp_error( $webhook_id ) || ! $webhook_id ) {
				throw new WC_API_Exception( 'woocommerce_api_cannot_create_webhook', sprintf( __( 'Cannot create webhook: %s', 'woocommerce' ), is_wp_error( $webhook_id ) ? implode( ', ', $webhook_id->get_error_messages() ) : '0' ), 500 );
			}

			$webhook = new WC_Webhook( $webhook_id );

			// set topic, delivery URL, and optional secret
			$webhook->set_topic( $data['topic'] );
			$webhook->set_delivery_url( $data['delivery_url'] );

			// set secret if provided, defaults to API users consumer secret
			$webhook->set_secret( ! empty( $data['secret'] ) ? $data['secret'] : get_user_meta( get_current_user_id(), 'woocommerce_api_consumer_secret', true ) );

			// send ping
			$webhook->deliver_ping();

			// HTTP 201 Created
			$this->server->send_status( 201 );

			do_action( 'woocommerce_api_create_webhook', $webhook->id, $this );

			delete_transient( 'woocommerce_webhook_ids' );

			return $this->get_webhook( $webhook->id );

		} catch ( WC_API_Exception $e ) {

			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Edit a webhook
	 *
	 * @since 2.2
	 * @param int $id webhook ID
	 * @param array $data parsed webhook data
	 * @return array
	 */
	public function edit_webhook( $id, $data ) {

		$data = isset( $data['webhook'] ) ? $data['webhook'] : array();

		try {

			$id = $this->validate_request( $id, 'shop_webhook', 'edit' );

			if ( is_wp_error( $id ) ) {
				return $id;
			}

			$data = apply_filters( 'woocommerce_api_edit_webhook_data', $data, $id, $this );

			$webhook = new WC_Webhook( $id );

			// update topic
			if ( ! empty( $data['topic'] ) ) {

				if ( wc_is_webhook_valid_topic( strtolower( $data['topic'] ) ) ) {

					$webhook->set_topic( $data['topic'] );

				} else {
					throw new WC_API_Exception( 'woocommerce_api_invalid_webhook_topic', __( 'Webhook topic must be valid', 'woocommerce' ), 400 );
				}
			}

			// update delivery URL
			if ( ! empty( $data['delivery_url'] ) ) {
				if ( wc_is_valid_url( $data['delivery_url'] ) ) {

					$webhook->set_delivery_url( $data['delivery_url'] );

				} else {
					throw new WC_API_Exception( 'woocommerce_api_invalid_webhook_delivery_url', __( 'Webhook delivery URL must be a valid URL starting with http:// or https://', 'woocommerce' ), 400 );
				}
			}

			// update secret
			if ( ! empty( $data['secret'] ) ) {
				$webhook->set_secret( $data['secret'] );
			}

			// update status
			if ( ! empty( $data['status'] ) ) {
				$webhook->update_status( $data['status'] );
			}

			// update user ID
			$webhook_data = array(
				'ID'          => $webhook->id,
				'post_author' => get_current_user_id()
			);

			// update name
			if ( ! empty( $data['name'] ) ) {
				$webhook_data['post_title'] = $data['name'];
			}

			// update post
			wp_update_post( $webhook_data );

			do_action( 'woocommerce_api_edit_webhook', $webhook->id, $this );

			delete_transient( 'woocommerce_webhook_ids' );

			return $this->get_webhook( $id );

		} catch ( WC_API_Exception $e ) {

			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Delete a webhook
	 *
	 * @since 2.2
	 * @param int $id webhook ID
	 * @return array
	 */
	public function delete_webhook( $id ) {

		$id = $this->validate_request( $id, 'shop_webhook', 'delete' );

		if ( is_wp_error( $id ) ) {
			return $id;
		}

		do_action( 'woocommerce_api_delete_webhook', $id, $this );

		delete_transient( 'woocommerce_webhook_ids' );

		// no way to manage trashed webhooks at the moment, so force delete
		return $this->delete( $id, 'webhook', true );
	}

	/**
	 * Helper method to get webhook post objects
	 *
	 * @since 2.2
	 * @param array $args request arguments for filtering query
	 * @return WP_Query
	 */
	private function query_webhooks( $args ) {

		// Set base query arguments
		$query_args = array(
			'fields'      => 'ids',
			'post_type'   => 'shop_webhook',
		);

		// Add status argument
		if ( ! empty( $args['status'] ) ) {

			switch ( $args['status'] ) {
				case 'active':
					$query_args['post_status'] = 'publish';
					break;
				case 'paused':
					$query_args['post_status'] = 'draft';
					break;
				case 'disabled':
					$query_args['post_status'] = 'pending';
					break;
				default:
					$query_args['post_status'] = 'publish';
			}
			unset( $args['status'] );
		}

		$query_args = $this->merge_query_args( $query_args, $args );

		return new WP_Query( $query_args );
	}

	/**
	 * Get deliveries for a webhook
	 *
	 * @since 2.2
	 * @param string $webhook_id webhook ID
	 * @param string|null $fields fields to include in response
	 * @return array
	 */
	public function get_webhook_deliveries( $webhook_id, $fields = null ) {

		// Ensure ID is valid webhook ID
		$webhook_id = $this->validate_request( $webhook_id, 'shop_webhook', 'read' );

		if ( is_wp_error( $webhook_id ) ) {
			return $webhook_id;
		}

		$webhook       = new WC_Webhook( $webhook_id );
		$logs          = $webhook->get_delivery_logs();
		$delivery_logs = array();

		foreach ( $logs as $log ) {

			// Add timestamp
			$log['created_at'] = $this->server->format_datetime( $log['comment']->comment_date_gmt );

			// Remove comment object
			unset( $log['comment'] );

			$delivery_logs[] = $log;
		}

		return array( 'webhook_deliveries' => $delivery_logs );
	}

	/**
	 * Get the delivery log for the given webhook ID and delivery ID
	 *
	 * @since 2.2
	 * @param string $webhook_id webhook ID
	 * @param string $id delivery log ID
	 * @param string|null $fields fields to limit response to
	 * @return array
	 */
	public function get_webhook_delivery( $webhook_id, $id, $fields = null ) {
		try {
			// Validate webhook ID
			$webhook_id = $this->validate_request( $webhook_id, 'shop_webhook', 'read' );

			if ( is_wp_error( $webhook_id ) ) {
				return $webhook_id;
			}

			$id = absint( $id );

			if ( empty( $id ) ) {
				throw new WC_API_Exception( 'woocommerce_api_invalid_webhook_delivery_id', __( 'Invalid webhook delivery ID', 'woocommerce' ), 404 );
			}

			$webhook = new WC_Webhook( $webhook_id );

			$log = $webhook->get_delivery_log( $id );

			if ( ! $log ) {
				throw new WC_API_Exception( 'woocommerce_api_invalid_webhook_delivery_id', __( 'Invalid webhook delivery', 'woocommerce' ), 400 );
			}

			$delivery_log = $log;

			// Add timestamp
			$delivery_log['created_at'] = $this->server->format_datetime( $log['comment']->comment_date_gmt );

			// Remove comment object
			unset( $delivery_log['comment'] );

			return array( 'webhook_delivery' => apply_filters( 'woocommerce_api_webhook_delivery_response', $delivery_log, $id, $fields, $log, $webhook_id, $this ) );
		} catch ( WC_API_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

}
