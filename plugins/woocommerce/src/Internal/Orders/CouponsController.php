<?php

namespace Automattic\WooCommerce\Internal\Orders;

use Automattic\WooCommerce\Utilities\ArrayUtil;
use Automattic\WooCommerce\Utilities\StringUtil;
use Exception;

/**
 * Class with methods for handling order coupons.
 */
class CouponsController {

	/**
	 * Add order discount via Ajax.
	 *
	 * @throws Exception If order or coupon is invalid.
	 */
	public function add_coupon_discount_via_ajax(): void {
		check_ajax_referer( 'order-item', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( -1 );
		}

		$response = array();

		try {
			$order = $this->add_coupon_discount( $_POST );

			ob_start();
			include __DIR__ . '/../../../includes/admin/meta-boxes/views/html-order-items.php';
			$response['html'] = ob_get_clean();

			ob_start();
			$notes = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );
			include __DIR__ . '/../../../includes/admin/meta-boxes/views/html-order-notes.php';
			$response['notes_html'] = ob_get_clean();
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'error' => $e->getMessage() ) );
		}

		// wp_send_json_success must be outside the try block not to break phpunit tests.
		wp_send_json_success( $response );
	}

	/**
	 * Add order discount programmatically.
	 *
	 * @param array $post_variables Contents of the $_POST array that would be passed in an Ajax call.
	 * @return object The retrieved order object.
	 * @throws \Exception Invalid order or coupon.
	 */
	public function add_coupon_discount( array $post_variables ): object {
		$order_id           = isset( $post_variables['order_id'] ) ? absint( $post_variables['order_id'] ) : 0;
		$order              = wc_get_order( $order_id );
		$calculate_tax_args = array(
			'country'  => isset( $post_variables['country'] ) ? wc_strtoupper( wc_clean( wp_unslash( $post_variables['country'] ) ) ) : '',
			'state'    => isset( $post_variables['state'] ) ? wc_strtoupper( wc_clean( wp_unslash( $post_variables['state'] ) ) ) : '',
			'postcode' => isset( $post_variables['postcode'] ) ? wc_strtoupper( wc_clean( wp_unslash( $post_variables['postcode'] ) ) ) : '',
			'city'     => isset( $post_variables['city'] ) ? wc_strtoupper( wc_clean( wp_unslash( $post_variables['city'] ) ) ) : '',
		);

		if ( ! $order ) {
			throw new Exception( __( 'Invalid order', 'woocommerce' ) );
		}

		$coupon = ArrayUtil::get_value_or_default( $post_variables, 'coupon' );
		if ( StringUtil::is_null_or_whitespace( $coupon ) ) {
			throw new Exception( __( 'Invalid coupon', 'woocommerce' ) );
		}

		// Add user ID and/or email so validation for coupon limits works.
		$user_id_arg    = isset( $post_variables['user_id'] ) ? absint( $post_variables['user_id'] ) : 0;
		$user_email_arg = isset( $post_variables['user_email'] ) ? sanitize_email( wp_unslash( $post_variables['user_email'] ) ) : '';

		if ( $user_id_arg ) {
			$order->set_customer_id( $user_id_arg );
		}
		if ( $user_email_arg ) {
			$order->set_billing_email( $user_email_arg );
		}

		$order->calculate_taxes( $calculate_tax_args );
		$order->calculate_totals( false );

		$code = wc_format_coupon_code( wp_unslash( $coupon ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		// A line total differing from its subtotal is treated as a manual price edit here. That is
		// the editor's best guess: a difference recorded by REST or an extension is adopted the same way.
		$result = $order->apply_coupon_using_edited_totals( $code );

		if ( is_wp_error( $result ) ) {
			throw new Exception( html_entity_decode( wp_strip_all_tags( $result->get_error_message() ) ) );
		}

		// translators: %s coupon code.
		$order->add_order_note( esc_html( sprintf( __( 'Coupon applied: "%s".', 'woocommerce' ), $code ) ), 0, true, array( 'note_group' => OrderNoteGroup::ORDER_UPDATE ) );

		return $order;
	}

	/**
	 * Get who coupon usage is recorded against: the customer ID, or the billing email for guests.
	 *
	 * @since 11.3.0
	 * @param \WC_Order $order Order.
	 * @return string
	 */
	public function get_usage_identity( \WC_Order $order ): string {
		return (string) ( $order->get_user_id() ? $order->get_user_id() : $order->get_billing_email() );
	}

	/**
	 * Adjust coupon usage counts after the coupons of an order changed outside of apply_coupon() and remove_coupon().
	 *
	 * An order that has not counted its coupons yet is counted through wc_update_coupon_usage_counts(), which
	 * only does so when the order status allows it.
	 *
	 * @since 11.3.0
	 * @param \WC_Order $order                  The saved order, with its current coupons.
	 * @param string[]  $previous_codes         Coupon codes the order had before the change.
	 * @param string    $previous_usage_identity Value of get_usage_identity() before the change. Removed coupons release usage held under it.
	 */
	public function sync_usage_counts( \WC_Order $order, array $previous_codes, string $previous_usage_identity ): void {
		/**
		 * Order data store.
		 *
		 * @var \WC_Order_Data_Store_Interface $data_store
		 */
		$data_store = $order->get_data_store();

		if ( ! $data_store->get_recorded_coupon_usage_counts( $order ) ) {
			wc_update_coupon_usage_counts( $order->get_id() );
			return;
		}

		$previous = $this->normalize_codes( $previous_codes );
		$current  = $this->normalize_codes( $order->get_coupon_codes() );
		$used_by  = $this->get_usage_identity( $order );

		foreach ( array_diff_key( $current, $previous ) as $code ) {
			$coupon = new \WC_Coupon( $code );
			if ( $coupon->get_id() ) {
				$coupon->increase_usage_count( $used_by, $order );
			}
		}

		foreach ( array_diff_key( $previous, $current ) as $code ) {
			$coupon = new \WC_Coupon( $code );
			if ( $coupon->get_id() ) {
				$coupon->decrease_usage_count( $previous_usage_identity );
			}
		}

		if ( ! $current ) {
			$data_store->set_recorded_coupon_usage_counts( $order, false );
		}
	}

	/**
	 * Drop blank codes and duplicates, keyed by the lowercase code.
	 *
	 * @param string[] $codes Coupon codes.
	 * @return array<string, string>
	 */
	private function normalize_codes( array $codes ): array {
		$normalized = array();
		foreach ( $codes as $code ) {
			if ( ! StringUtil::is_null_or_whitespace( $code ) ) {
				$normalized[ wc_strtolower( $code ) ] = $code;
			}
		}
		return $normalized;
	}
}
