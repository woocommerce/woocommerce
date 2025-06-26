<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockBindings;

use Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation\AbstractOrderConfirmationBlock;

/**
 * Order Status Block Bindings class.
 *
 * Provides Block Bindings sources for order confirmation status blocks.
 *
 * @since 9.8.0
 * @internal
 */
class OrderStatusBindings {

	/**
	 * Initialize the block bindings.
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_block_bindings_sources' ) );
	}

	/**
	 * Register Block Bindings sources for order status.
	 */
	public function register_block_bindings_sources() {
		if ( ! function_exists( 'register_block_bindings_source' ) ) {
			return;
		}

		register_block_bindings_source(
			'woocommerce/order-status-title',
			array(
				'label'              => __( 'Order Status Title', 'woocommerce' ),
				'uses_context'       => array( 'queryId', 'postId', 'postType' ),
				'get_value_callback' => array( $this, 'get_order_status_title' ),
			)
		);

		register_block_bindings_source(
			'woocommerce/order-status-description',
			array(
				'label'              => __( 'Order Status Description', 'woocommerce' ),
				'uses_context'       => array( 'queryId', 'postId', 'postType' ),
				'get_value_callback' => array( $this, 'get_order_status_description' ),
			)
		);
	}

	/**
	 * Get order status title for Block Bindings.
	 *
	 * @param array    $source_args    Source arguments.
	 * @param WP_Block $block_instance Block instance.
	 * @return string Order status title.
	 */
	public function get_order_status_title( $source_args, $block_instance ) {
		$order = $this->get_current_order();

		if ( ! $order ) {
			return $this->get_default_title( null );
		}

		$permission = $this->get_view_order_permissions( $order );

		if ( ! $permission ) {
			return $this->get_default_title( null );
		}

		return $this->get_status_title( $order );
	}

	/**
	 * Get order status description for Block Bindings.
	 *
	 * @param array    $source_args    Source arguments.
	 * @param WP_Block $block_instance Block instance.
	 * @return string Order status description.
	 */
	public function get_order_status_description( $source_args, $block_instance ) {
		$order = $this->get_current_order();

		if ( ! $order ) {
			return $this->get_default_description( null );
		}

		$permission = $this->get_view_order_permissions( $order );

		if ( ! $permission ) {
			return $this->get_default_description( null );
		}

		return $this->get_status_description( $order );
	}

	/**
	 * Get current order from query vars.
	 *
	 * @return \WC_Order|null
	 */
	private function get_current_order() {
		$order_id = absint( get_query_var( 'order-received' ) );

		if ( $order_id ) {
			return wc_get_order( $order_id );
		}

		return null;
	}

	/**
	 * Get view order permissions (copied from AbstractOrderConfirmationBlock).
	 *
	 * @param \WC_Order|null $order Order object.
	 * @return string|false Returns "full" if the user can view all order details. False if they can view no details.
	 */
	private function get_view_order_permissions( $order ) {
		if ( ! $order || ! $this->has_valid_order_key( $order ) ) {
			return false;
		}

		// For customers with accounts, verify the order belongs to the current user or disallow access.
		if ( $this->is_customer_order( $order ) ) {
			return $this->current_user_can_view_order( $order ) ? 'full' : false;
		}

		// For guest orders, check email verification.
		if ( $this->email_verification_permitted( $order ) ) {
			return $this->email_verification_required( $order ) ? false : 'full';
		}

		return 'full';
	}

	/**
	 * Check if order has valid order key.
	 *
	 * @param \WC_Order $order Order object.
	 * @return bool
	 */
	private function has_valid_order_key( $order ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order_key = wc_clean( $_GET['key'] ?? '' );
		return hash_equals( $order->get_order_key(), $order_key );
	}

	/**
	 * Check if order belongs to a customer account.
	 *
	 * @param \WC_Order $order Order object.
	 * @return bool
	 */
	private function is_customer_order( $order ) {
		return $order->get_customer_id() > 0;
	}

	/**
	 * Check if current user can view the order.
	 *
	 * @param \WC_Order $order Order object.
	 * @return bool
	 */
	private function current_user_can_view_order( $order ) {
		return get_current_user_id() === $order->get_customer_id();
	}

	/**
	 * Check if email verification is permitted.
	 *
	 * @param \WC_Order $order Order object.
	 * @return bool
	 */
	private function email_verification_permitted( $order ) {
		return 'yes' === get_option( 'woocommerce_order_email_verification' );
	}

	/**
	 * Check if email verification is required.
	 *
	 * @param \WC_Order $order Order object.
	 * @return bool
	 */
	private function email_verification_required( $order ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$submitted_email = sanitize_email( $_POST['email'] ?? '' );
		return empty( $submitted_email ) || ! hash_equals( strtolower( $order->get_billing_email() ), strtolower( $submitted_email ) );
	}

	/**
	 * Get default status title.
	 *
	 * @param \WC_Order|null $order Order object or null.
	 * @return string
	 */
	private function get_default_title( $order ) {
		return apply_filters(
			'woocommerce_thankyou_order_received_title',
			__( 'Order received', 'woocommerce' ),
			$order
		);
	}

	/**
	 * Get default status description.
	 *
	 * @param \WC_Order|null $order Order object or null.
	 * @return string
	 */
	private function get_default_description( $order ) {
		return apply_filters(
			'woocommerce_thankyou_order_received_text',
			__( 'Thank you. Your order has been received.', 'woocommerce' ),
			$order
		);
	}

	/**
	 * Get status-specific title text.
	 *
	 * @param \WC_Order $order Order object.
	 * @return string
	 */
	private function get_status_title( $order ) {
		$status = $order->get_status();

		switch ( $status ) {
			case 'cancelled':
				return apply_filters(
					'woocommerce_thankyou_order_received_title',
					__( 'Order cancelled', 'woocommerce' ),
					$order
				);

			case 'refunded':
				return apply_filters(
					'woocommerce_thankyou_order_received_title',
					__( 'Order refunded', 'woocommerce' ),
					$order
				);

			case 'completed':
				return apply_filters(
					'woocommerce_thankyou_order_received_title',
					__( 'Order completed', 'woocommerce' ),
					$order
				);

			case 'failed':
				return apply_filters(
					'woocommerce_thankyou_order_received_title',
					__( 'Order failed', 'woocommerce' ),
					$order
				);

			default:
				return $this->get_default_title( $order );
		}
	}

	/**
	 * Get status-specific description text.
	 *
	 * @param \WC_Order $order Order object.
	 * @return string
	 */
	private function get_status_description( $order ) {
		$status = $order->get_status();

		switch ( $status ) {
			case 'cancelled':
				return apply_filters(
					'woocommerce_thankyou_order_received_text',
					__( 'Your order has been cancelled.', 'woocommerce' ),
					$order
				);

			case 'refunded':
				return sprintf(
					apply_filters(
						'woocommerce_thankyou_order_received_text',
						// translators: %s: date and time of the order refund.
						__( 'Your order was refunded %s.', 'woocommerce' ),
						$order
					),
					wc_format_datetime( $order->get_date_modified() )
				);

			case 'completed':
				return apply_filters(
					'woocommerce_thankyou_order_received_text',
					__( 'Thank you. Your order has been fulfilled.', 'woocommerce' ),
					$order
				);

			case 'failed':
				return apply_filters(
					'woocommerce_thankyou_order_received_text',
					__( 'Your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'woocommerce' ),
					$order
				);

			default:
				return $this->get_default_description( $order );
		}
	}
}
