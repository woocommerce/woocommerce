<?php
/**
 * ActionController class.
 *
 * @package WooCommerce\RestApi
 * @internal This file is for internal use only and should not be used by external code.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Orders;

defined( 'ABSPATH' ) || exit;

use WC_REST_Exception;
use WP_REST_Request;
use WP_Http;
use WP_Error;
use WC_Order;

/**
 * ActionController class.
 *
 * Actions that can be performed on orders.
 *
 * @internal This class is for internal use only and should not be used by external code.
 */
class ActionController {

	/**
	 * Get endpoint args for the actions.
	 *
	 * @return array
	 */
	public function get_endpoint_args_for_actions(): array {
		return array(
			'mark_as_paid'                    => array(
				'description' => __( 'Mark the order as paid. It will update the order status and reduce line item stock.', 'woocommerce' ),
				'type'        => 'boolean',
				'default'     => false,
			),
			'regenerate_download_permissions' => array(
				'description' => __( 'Regenerate the download permissions for the order.', 'woocommerce' ),
				'type'        => 'boolean',
				'default'     => false,
			),
			'send_order_details'              => array(
				'description' => __( 'Send the order details to the customer.', 'woocommerce' ),
				'type'        => 'boolean',
				'default'     => false,
			),
		);
	}

	/**
	 * Run the actions for the order.
	 *
	 * @throws WC_REST_Exception If an error occurs.
	 * @param WC_Order        $order The order object.
	 * @param WP_REST_Request $request The request object.
	 * @return void
	 */
	public function run_actions( WC_Order $order, WP_REST_Request $request ) {
		$valid_actions = array(
			'mark_as_paid'                    => 'action_mark_as_paid',
			'regenerate_download_permissions' => 'action_regenerate_download_permissions',
			'send_order_details'              => 'action_send_order_details',
		);

		foreach ( $valid_actions as $action => $callback ) {
			if ( null !== $request->get_param( $action ) ) {
				$result = call_user_func( array( $this, $callback ), $request->get_param( $action ), $order, $request );

				if ( is_wp_error( $result ) ) {
					throw new WC_REST_Exception( 'woocommerce_rest_invalid_action', esc_html( $result->get_error_message() ) );
				}
			}
		}
	}

	/**
	 * Send the order details to the customer.
	 *
	 * @param bool     $action_value The action value.
	 * @param WC_Order $order The order object.
	 * @return true|WP_Error
	 */
	private function action_send_order_details( $action_value, WC_Order $order ) {
		if ( ! $action_value ) {
			return true;
		}

		if ( ! $order->get_billing_email() ) {
			return new WP_Error( 'woocommerce_rest_missing_email', __( 'Order does not have an email address.', 'woocommerce' ) );
		}

		// phpcs:disable WooCommerce.Commenting.CommentHooks.MissingSinceComment
		/** This action is documented in includes/admin/meta-boxes/class-wc-meta-box-order-actions.php */
		do_action( 'woocommerce_before_resend_order_emails', $order, 'customer_invoice' );

		WC()->payment_gateways();
		WC()->shipping();
		WC()->mailer()->customer_invoice( $order );

		$order->add_order_note(
			sprintf(
			// translators: %s is the customer email.
				esc_html__( 'Order details emailed to %s.', 'woocommerce' ),
				esc_html( $order->get_billing_email() ),
			),
			false,
			true
		);

		// phpcs:disable WooCommerce.Commenting.CommentHooks.MissingSinceComment
		/** This action is documented in includes/admin/meta-boxes/class-wc-meta-box-order-actions.php */
		do_action( 'woocommerce_after_resend_order_email', $order, 'customer_invoice' );

		return true;
	}

	/**
	 * Regenerate the download permissions for the order.
	 *
	 * @param bool     $action_value The action value.
	 * @param WC_Order $order The order object.
	 * @return true|WP_Error
	 */
	private function action_regenerate_download_permissions( $action_value, WC_Order $order ) {
		if ( ! $action_value ) {
			return true;
		}
		$data_store = \WC_Data_Store::load( 'customer-download' );

		if ( $data_store ) {
			$data_store->delete_by_order_id( $order->get_id() );
		}

		wc_downloadable_product_permissions( $order->get_id(), true );

		$order->add_order_note(
			esc_html__( 'Downloadable product permissions regenerated.', 'woocommerce' ),
			false,
			true
		);

		return true;
	}

	/**
	 * Mark the order as paid.
	 *
	 * @param bool            $action_value The action value.
	 * @param WC_Order        $order The order object.
	 * @param WP_REST_Request $request The request object.
	 * @return true|WP_Error
	 */
	private function action_mark_as_paid( $action_value, WC_Order $order, WP_REST_Request $request ) {
		if ( $action_value ) {
			$order->payment_complete( $request['transaction_id'] );
		}
		return true;
	}
}
