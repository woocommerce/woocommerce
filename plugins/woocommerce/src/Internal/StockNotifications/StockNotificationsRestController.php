<?php
/**
 * StockNotificationsRestController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications;

use Automattic\WooCommerce\Internal\RestApiControllerBase;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationCancellationSource;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use WP_Error;
use WP_Http;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Read-only REST API controller for Back in Stock notifications.
 *
 * Routes:
 * - GET /wc/v3/stock-notifications
 * - GET /wc/v3/stock-notifications/{id}
 *
 * Registered by StockNotifications::maybe_init_services(), so the routes only exist
 * while the customer_stock_notifications feature is enabled.
 */
class StockNotificationsRestController extends RestApiControllerBase {

	/**
	 * REST API base.
	 *
	 * @var string
	 */
	protected string $rest_base = 'stock-notifications';

	/**
	 * Map of `orderby` values to the columns the data store sorts on.
	 *
	 * @var array<string, string>
	 */
	private const ORDERBY_COLUMNS = array(
		'id'            => 'id',
		'date_created'  => 'date_created_gmt',
		'date_modified' => 'date_modified_gmt',
		'date_notified' => 'date_notified_gmt',
	);

	/**
	 * Get the WooCommerce REST API namespace for the class.
	 *
	 * @return string
	 */
	protected function get_rest_api_namespace(): string {
		return 'stock_notifications';
	}

	/**
	 * Register the routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->route_namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'get_items' ),
					'permission_callback' => fn( $request ) => $this->check_permission( $request, 'manage_woocommerce' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->route_namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'get_item' ),
					'permission_callback' => fn( $request ) => $this->check_permission( $request, 'manage_woocommerce' ),
					'args'                => array(
						'id'      => array(
							'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
							'type'        => 'integer',
						),
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Get a collection of notifications.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response
	 */
	protected function get_items( WP_REST_Request $request ): WP_REST_Response {
		$per_page = (int) $request['per_page'];
		$page     = (int) $request['page'];
		$offset   = $request['offset'] ? (int) $request['offset'] : ( $page - 1 ) * $per_page;
		$order    = 'asc' === strtolower( (string) $request['order'] ) ? 'ASC' : 'DESC';
		$orderby  = self::ORDERBY_COLUMNS[ $request['orderby'] ] ?? 'date_created_gmt';
		$order_by = array( $orderby => $order );
		// A second key keeps pages stable when several rows share the same sort value.
		$order_by['id'] = $order;

		$query_args = array(
			'status'              => (array) $request['status'],
			'product_id'          => (array) $request['product_id'],
			'user_id'             => (int) $request['user_id'],
			'user_email'          => (string) $request['email'],
			'start_date'          => $this->to_gmt_mysql_date( $request['after'] ),
			'end_date'            => $this->to_gmt_mysql_date( $request['before'] ),
			'notified_start_date' => $this->to_gmt_mysql_date( $request['notified_after'] ),
			'notified_end_date'   => $this->to_gmt_mysql_date( $request['notified_before'] ),
		);

		$total         = NotificationQuery::count_notifications( $query_args );
		$notifications = $total > 0 ? NotificationQuery::get_notifications(
			array_merge(
				$query_args,
				array(
					'limit'    => $per_page,
					'offset'   => $offset,
					'order_by' => $order_by,
					'return'   => 'objects',
				)
			)
		) : array();

		$items = array();
		foreach ( $notifications as $notification ) {
			$items[] = rest_ensure_response( $this->prepare_item_for_response( $notification, $request ) )->get_data();
		}

		$max_pages = (int) ceil( $total / $per_page );
		$response  = new WP_REST_Response( $items );
		$response->header( 'X-WP-Total', (string) $total );
		$response->header( 'X-WP-TotalPages', (string) $max_pages );

		$base = add_query_arg( urlencode_deep( $request->get_query_params() ), rest_url( $this->route_namespace . '/' . $this->rest_base ) );
		if ( $page > 1 ) {
			$response->link_header( 'prev', add_query_arg( 'page', min( $page - 1, $max_pages ), $base ) );
		}
		if ( $page < $max_pages ) {
			$response->link_header( 'next', add_query_arg( 'page', $page + 1, $base ) );
		}

		return $response;
	}

	/**
	 * Get a single notification.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response|WP_Error
	 */
	protected function get_item( WP_REST_Request $request ) {
		try {
			$notification = new Notification( (int) $request['id'] );
		} catch ( \Exception $e ) {
			// The data store throws when the row does not exist.
			return new WP_Error( 'woocommerce_rest_stock_notification_invalid_id', __( 'Invalid ID.', 'woocommerce' ), array( 'status' => WP_Http::NOT_FOUND ) );
		}

		return $this->prepare_item_for_response( $notification, $request );
	}

	/**
	 * Prepare a notification for the response.
	 *
	 * @param Notification    $notification The notification.
	 * @param WP_REST_Request $request      The request.
	 * @return WP_REST_Response
	 */
	protected function prepare_item_for_response( Notification $notification, WP_REST_Request $request ): WP_REST_Response {
		$product           = $notification->get_product();
		$posted_attributes = $notification->get_meta( 'posted_attributes' );
		$posted_attributes = is_array( $posted_attributes ) ? wc_clean( $posted_attributes ) : array();
		$is_variation      = $product && $product->is_type( 'variation' );

		$data = array(
			'id'                  => $notification->get_id(),
			'status'              => $notification->get_status(),
			'product_id'          => $notification->get_product_id(),
			'parent_id'           => $product ? ( $is_variation ? $product->get_parent_id() : 0 ) : null,
			'variation_id'        => $product ? ( $is_variation ? $product->get_id() : 0 ) : null,
			'sku'                 => $product ? $product->get_sku() : null,
			'posted_attributes'   => (object) $posted_attributes,
			'user_id'             => $notification->get_user_id(),
			'user_email'          => sanitize_email( $notification->get_user_email() ),
			'cancellation_source' => $notification->get_cancellation_source(),
			'product_permalink'   => $product ? $notification->get_product_permalink() : null,
		);

		foreach ( array( 'date_created', 'date_modified', 'date_confirmed', 'date_notified', 'date_last_attempt', 'date_cancelled' ) as $date_key ) {
			$date                       = $notification->{"get_$date_key"}();
			$data[ $date_key ]          = $date ? wc_rest_prepare_date_response( $date, false ) : null;
			$data[ $date_key . '_gmt' ] = $date ? wc_rest_prepare_date_response( $date ) : null;
		}

		$response = new WP_REST_Response( $data );
		$response->add_links( $this->prepare_links( $notification, $product ) );

		/**
		 * Filter the response for a single stock notification.
		 *
		 * @since 11.3.0
		 *
		 * @param WP_REST_Response $response     The response.
		 * @param Notification     $notification The notification.
		 * @param WP_REST_Request  $request      The request.
		 */
		return apply_filters( 'woocommerce_rest_prepare_stock_notification', $response, $notification, $request );
	}

	/**
	 * Prepare the links for a notification.
	 *
	 * @param Notification      $notification The notification.
	 * @param \WC_Product|false $product      The notification's product, or false when it no longer exists.
	 * @return array
	 */
	private function prepare_links( Notification $notification, $product ): array {
		$links = array(
			'self'       => array( 'href' => rest_url( sprintf( '%s/%s/%d', $this->route_namespace, $this->rest_base, $notification->get_id() ) ) ),
			'collection' => array( 'href' => rest_url( sprintf( '%s/%s', $this->route_namespace, $this->rest_base ) ) ),
		);

		if ( $product ) {
			$product_route = $product->is_type( 'variation' )
				? sprintf( 'products/%d/variations/%d', $product->get_parent_id(), $product->get_id() )
				: sprintf( 'products/%d', $product->get_id() );
			$links['up']   = array( 'href' => rest_url( $this->route_namespace . '/' . $product_route ) );
		}

		return $links;
	}

	/**
	 * Convert a REST date-time argument to a GMT MySQL datetime for the data store.
	 *
	 * @param mixed $value The argument value.
	 * @return string Empty when the argument is absent.
	 */
	private function to_gmt_mysql_date( $value ): string {
		if ( empty( $value ) ) {
			return '';
		}

		$timestamp = wc_string_to_timestamp( (string) $value );

		return $timestamp ? gmdate( 'Y-m-d H:i:s', $timestamp ) : '';
	}

	/**
	 * Get the `context` argument definition.
	 *
	 * @param array $args Overrides.
	 * @return array
	 */
	private function get_context_param( array $args = array() ): array {
		return array_merge(
			array(
				'description' => __( 'Scope under which the request is made; determines fields present in response.', 'woocommerce' ),
				'type'        => 'string',
				'enum'        => array( 'view', 'edit' ),
				'default'     => 'view',
			),
			$args
		);
	}

	/**
	 * Get the query parameters for the collection.
	 *
	 * @return array
	 */
	public function get_collection_params(): array {
		return array(
			'context'         => $this->get_context_param(),
			'page'            => array(
				'description' => __( 'Current page of the collection.', 'woocommerce' ),
				'type'        => 'integer',
				'default'     => 1,
				'minimum'     => 1,
			),
			'per_page'        => array(
				'description' => __( 'Maximum number of items to be returned in result set.', 'woocommerce' ),
				'type'        => 'integer',
				'default'     => 10,
				'minimum'     => 1,
				'maximum'     => 100,
			),
			'offset'          => array(
				'description' => __( 'Offset the result set by a specific number of items.', 'woocommerce' ),
				'type'        => 'integer',
				'minimum'     => 0,
			),
			'order'           => array(
				'description' => __( 'Order sort attribute ascending or descending.', 'woocommerce' ),
				'type'        => 'string',
				'default'     => 'desc',
				'enum'        => array( 'asc', 'desc' ),
			),
			'orderby'         => array(
				'description' => __( 'Sort collection by attribute.', 'woocommerce' ),
				'type'        => 'string',
				'default'     => 'date_created',
				'enum'        => array_keys( self::ORDERBY_COLUMNS ),
			),
			'status'          => array(
				'description' => __( 'Limit result set to notifications with one or more statuses.', 'woocommerce' ),
				'type'        => 'array',
				'items'       => array(
					'type' => 'string',
					'enum' => NotificationStatus::get_valid_statuses(),
				),
				'default'     => array(),
			),
			'product_id'      => array(
				'description' => __( 'Limit result set to notifications for one or more product or variation IDs.', 'woocommerce' ),
				'type'        => 'array',
				'items'       => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
				'default'     => array(),
			),
			'user_id'         => array(
				'description' => __( 'Limit result set to notifications of a registered user.', 'woocommerce' ),
				'type'        => 'integer',
				'minimum'     => 1,
			),
			'email'           => array(
				'description'       => __( 'Limit result set to notifications for an email address (exact match).', 'woocommerce' ),
				'type'              => 'string',
				'format'            => 'email',
				'sanitize_callback' => 'sanitize_email',
			),
			'after'           => array(
				'description' => __( 'Limit response to notifications created after a given ISO8601 compliant date.', 'woocommerce' ),
				'type'        => 'string',
				'format'      => 'date-time',
			),
			'before'          => array(
				'description' => __( 'Limit response to notifications created before a given ISO8601 compliant date.', 'woocommerce' ),
				'type'        => 'string',
				'format'      => 'date-time',
			),
			'notified_after'  => array(
				'description' => __( 'Limit response to notifications sent after a given ISO8601 compliant date.', 'woocommerce' ),
				'type'        => 'string',
				'format'      => 'date-time',
			),
			'notified_before' => array(
				'description' => __( 'Limit response to notifications sent before a given ISO8601 compliant date.', 'woocommerce' ),
				'type'        => 'string',
				'format'      => 'date-time',
			),
		);
	}

	/**
	 * Get the item schema.
	 *
	 * @return array
	 */
	public function get_item_schema(): array {
		$date_props = array();
		foreach ( array(
			'date_created'      => __( 'The date the notification was created', 'woocommerce' ),
			'date_modified'     => __( 'The date the notification was last modified', 'woocommerce' ),
			'date_confirmed'    => __( 'The date the notification was confirmed by the customer', 'woocommerce' ),
			'date_notified'     => __( 'The date the back in stock email was sent', 'woocommerce' ),
			'date_last_attempt' => __( 'The date the back in stock email was last attempted', 'woocommerce' ),
			'date_cancelled'    => __( 'The date the notification was cancelled', 'woocommerce' ),
		) as $key => $description ) {
			$date_props[ $key ]          = array(
				/* translators: %s: what the date records. */
				'description' => sprintf( __( "%s, in the site's timezone.", 'woocommerce' ), $description ),
				'type'        => array( 'string', 'null' ),
				'format'      => 'date-time',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			);
			$date_props[ $key . '_gmt' ] = array(
				/* translators: %s: what the date records. */
				'description' => sprintf( __( '%s, as GMT.', 'woocommerce' ), $description ),
				'type'        => array( 'string', 'null' ),
				'format'      => 'date-time',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			);
		}

		$schema               = $this->get_base_schema();
		$schema['title']      = 'stock_notification';
		$schema['properties'] = array_merge(
			array(
				'id'                  => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'status'              => array(
					'description' => __( 'Notification status.', 'woocommerce' ),
					'type'        => 'string',
					'enum'        => NotificationStatus::get_valid_statuses(),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'product_id'          => array(
					'description' => __( 'ID of the product or variation the customer signed up for.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'parent_id'           => array(
					'description' => __( 'Parent product ID when the product is a variation, otherwise 0. Null if the product no longer exists.', 'woocommerce' ),
					'type'        => array( 'integer', 'null' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'variation_id'        => array(
					'description' => __( 'Variation ID when the product is a variation, otherwise 0. Null if the product no longer exists.', 'woocommerce' ),
					'type'        => array( 'integer', 'null' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'sku'                 => array(
					'description' => __( 'Product SKU. Null if the product no longer exists.', 'woocommerce' ),
					'type'        => array( 'string', 'null' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'posted_attributes'   => array(
					'description' => __( 'Variation attributes the customer selected when signing up, keyed by attribute name (e.g. attribute_pa_color).', 'woocommerce' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'user_id'             => array(
					'description' => __( 'User ID, or 0 for a guest.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'user_email'          => array(
					'description' => __( 'Email address the notification is sent to.', 'woocommerce' ),
					'type'        => 'string',
					'format'      => 'email',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'cancellation_source' => array(
					'description' => __( 'Who or what cancelled the notification. Null unless cancelled.', 'woocommerce' ),
					'type'        => array( 'string', 'null' ),
					'enum'        => array_merge( NotificationCancellationSource::get_valid_cancellation_sources(), array( null ) ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'product_permalink'   => array(
					'description' => __( 'Product URL, with the posted attributes preselected for variations. Null if the product no longer exists.', 'woocommerce' ),
					'type'        => array( 'string', 'null' ),
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			),
			$date_props
		);

		return $schema;
	}
}
