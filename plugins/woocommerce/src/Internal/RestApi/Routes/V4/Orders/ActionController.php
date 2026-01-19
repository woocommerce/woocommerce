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
use WP_Error;
use WC_Order;
use Automattic\WooCommerce\Internal\Orders\OrderNoteGroup;

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
			'payment_complete'           => array(
				'description' => __( 'Marks the order as paid. Updates the order status and reduces line item stock if necessary.', 'woocommerce' ),
				'type'        => 'boolean',
				'default'     => false,
			),
			'reset_download_permissions' => array(
				'description' => __( 'Resets any download permissions linked to the order.', 'woocommerce' ),
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
		$valid_actions = array_keys( $this->get_endpoint_args_for_actions() );

		foreach ( $valid_actions as $action ) {
			$callback = 'action_' . $action;
			$param    = $request->get_param( $action );
			if ( null !== $param && is_callable( array( $this, $callback ) ) ) {
				$result = call_user_func( array( $this, $callback ), $param, $order, $request );

				if ( is_wp_error( $result ) ) {
					throw new WC_REST_Exception( 'woocommerce_rest_invalid_action', esc_html( $result->get_error_message() ) );
				}
			}
		}
	}

	/**
	 * Regenerate the download permissions for the order.
	 *
	 * @param bool            $action_value The action value.
	 * @param WC_Order        $order The order object.
	 * @param WP_REST_Request $request The request object.
	 * @return bool
	 */
	private function action_reset_download_permissions( $action_value, WC_Order $order, WP_REST_Request $request ) {
		if ( ! $action_value ) {
			return false;
		}

		$data_store = \WC_Data_Store::load( 'customer-download' );

		if ( $data_store ) {
			$data_store->delete_by_order_id( $order->get_id() );
		}

		wc_downloadable_product_permissions( $order->get_id(), true );

		$user_agent = esc_html( $request->get_header( 'User-Agent' ) );
		$order->add_order_note(
			esc_html__( 'Download permissions were reset manually.', 'woocommerce' ),
			false,
			true,
			array(
				'user_agent' => $user_agent ? $user_agent : 'REST API',
				'note_title' => __( 'Download permissions', 'woocommerce' ),
				'note_group' => OrderNoteGroup::ORDER_UPDATE,
			)
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
	private function action_payment_complete( $action_value, WC_Order $order, WP_REST_Request $request ) {
		if ( $action_value ) {
			$result = $order->payment_complete( $request['transaction_id'] ?? '' );

			if ( ! $result ) {
				return new WP_Error( 'woocommerce_rest_payment_complete_failed', __( 'Could not mark the order as paid.', 'woocommerce' ) );
			}
		}
		return true;
	}
}
