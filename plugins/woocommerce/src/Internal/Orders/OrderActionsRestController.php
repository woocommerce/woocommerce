<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Orders;

use Automattic\WooCommerce\Internal\Traits\AccessiblePrivateMethods;
use Automattic\WooCommerce\Internal\RestApiControllerBase;
use WP_Error;
use WP_REST_Request;

/**
 * Controller for the REST endpoint to run actions on orders.
 *
 * This first version only supports sending the order details to the customer (`send_order_details`).
 */
class OrderActionsRestController extends RestApiControllerBase {
	use AccessiblePrivateMethods;

	/**
	 * Get the WooCommerce REST API namespace for the class.
	 *
	 * @return string
	 */
	protected function get_rest_api_namespace(): string {
		return 'order-actions';
	}

	/**
	 * Register the REST API endpoints handled by this controller.
	 */
	public function register_routes() {
		register_rest_route(
			$this->route_namespace,
			'/orders/(?P<id>[\d]+)/actions',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'order_actions' ),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
					'args'                => $this->get_args_for_order_actions(),
					'schema'              => $this->get_schema_for_order_actions(),
				),
			)
		);
	}

	/**
	 * Permission check for REST API endpoint.
	 *
	 * @param WP_REST_Request $request The request for which the permission is checked.
	 * @return bool|WP_Error True if the current user has the capability, otherwise a WP_Error object.
	 */
	private function check_permissions( WP_REST_Request $request ) {
		$order_id = $request->get_param( 'id' );
		$order    = wc_get_order( $order_id );

		if ( ! $order ) {
			return new WP_Error( 'woocommerce_rest_not_found', __( 'Order not found', 'woocommerce' ), array( 'status' => 404 ) );
		}

		return $this->check_permission( $request, 'read_shop_order', $order_id );
	}

	/**
	 * Get the accepted arguments for the POST request.
	 *
	 * @return array[]
	 */
	private function get_args_for_order_actions(): array {
		return array(
			'id'     => array(
				'description' => __( 'Unique identifier of the order.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'action' => array(
				'description' => __( 'The action to run on the order.', 'woocommerce' ),
				'type'        => 'string',
				'enum'        => array( 'send_order_details' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'required'    => true,
			),
		);
	}

	/**
	 * Get the schema for both the GET and the POST requests.
	 *
	 * @return array[]
	 */
	private function get_schema_for_order_actions(): array {
		$schema['properties'] = array(
			'message' => array(
				'description' => __( 'A message indication whether the email was sent.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
		);
		return $schema;
	}

	/**
	 * Handle the POST /orders/{id}/actions.
	 *
	 * @param WP_REST_Request $request The received request.
	 * @return array|WP_Error Request response or an error.
	 */
	public function order_actions( WP_REST_Request $request ) {
		$action = $request->get_param( 'action' );
		if ( 'send_order_details' !== $action ) {
			return new WP_Error( 'invalid_action', __( 'Invalid action.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		$order_id = $request->get_param( 'id' );
		$order    = wc_get_order( $order_id );
		if ( ! $order ) {
			return new WP_Error( 'invalid_order', __( 'Invalid order ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		// phpcs:disable WooCommerce.Commenting.CommentHooks.MissingSinceComment
		/** This action is documented in includes/admin/meta-boxes/class-wc-meta-box-order-actions.php */
		do_action( 'woocommerce_before_resend_order_emails', $order, 'customer_invoice' );

		WC()->mailer()->customer_invoice( $order );

		$order->add_order_note( __( 'Order details sent to customer via REST API.', 'woocommerce' ), false, true );

		// phpcs:disable WooCommerce.Commenting.CommentHooks.MissingSinceComment
		/** This action is documented in includes/admin/meta-boxes/class-wc-meta-box-order-actions.php */
		do_action( 'woocommerce_after_resend_order_email', $order, 'customer_invoice' );

		return array(
			'message' => __( 'Order details email sent to customer.', 'woocommerce' ),
		);
	}
}
