<?php
declare( strict_types = 1);

namespace Automattic\WooCommerce\Admin\API\Reports;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trait to contain shared methods for reports Controllers that use order and orders statuses.
 *
 * If your analytics controller needs to work with orders,
 * you will most probably need to use at least {@see get_order_statuses() get_order_statuses()}
 * to filter only "actionable" statuses to produce consistent results among other analytics.
 *
 * @see GenericController
 */
trait OrderAwareControllerTrait {

	/**
	 * Get the order number for an order. If no filter is present for `woocommerce_order_number`, we can just return the ID.
	 * Returns the parent order number if the order is actually a refund.
	 *
	 * @param  int $order_id Order ID.
	 * @return string|null The Order Number or null if the order doesn't exist.
	 */
	protected function get_order_number( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $this->is_valid_order( $order ) ) {
			return null;
		}

		if ( 'shop_order_refund' === $order->get_type() ) {
			$order = wc_get_order( $order->get_parent_id() );

			// If the parent order doesn't exist, return null.
			if ( ! $this->is_valid_order( $order ) ) {
				return null;
			}
		}

		if ( ! has_filter( 'woocommerce_order_number' ) ) {
			return $order->get_id();
		}

		return $order->get_order_number();
	}

	/**
	 * Whether the order is valid.
	 *
	 * @param bool|WC_Order|WC_Order_Refund $order Order object.
	 * @return bool True if the order is valid, false otherwise.
	 */
	protected function is_valid_order( $order ) {
		return $order instanceof \WC_Order || $order instanceof \WC_Order_Refund;
	}

	/**
	 * Get the order total with the related currency formatting.
	 * Returns the parent order total if the order is actually a refund.
	 *
	 * @param  int $order_id Order ID.
	 * @return string|null The Order Number or null if the order doesn't exist.
	 */
	protected function get_total_formatted( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $this->is_valid_order( $order ) ) {
			return null;
		}

		if ( 'shop_order_refund' === $order->get_type() ) {
			$order = wc_get_order( $order->get_parent_id() );

			if ( ! $this->is_valid_order( $order ) ) {
				return null;
			}
		}

		return wp_strip_all_tags( html_entity_decode( $order->get_formatted_order_total() ), true );
	}

	/**
	 * Get order statuses without prefixes.
	 * Includes unregistered statuses that have been marked "actionable".
	 *
	 * @return array
	 */
	public static function get_order_statuses() {
		// Allow all statuses selected as "actionable" - this may include unregistered statuses.
		// See: https://github.com/woocommerce/woocommerce-admin/issues/5592.
		$actionable_statuses = get_option( 'woocommerce_actionable_order_statuses', array() );

		// See WC_REST_Orders_V2_Controller::get_collection_params() re: any/trash statuses.
		$registered_statuses = array_merge( array( 'any', 'trash' ), array_keys( self::get_order_status_labels() ) );

		// Merge the status arrays (using flip to avoid array_unique()).
		$allowed_statuses = array_keys( array_merge( array_flip( $registered_statuses ), array_flip( $actionable_statuses ) ) );

		return $allowed_statuses;
	}

	/**
	 * Get order statuses (and labels) without prefixes.
	 *
	 * @internal
	 * @return array
	 */
	public static function get_order_status_labels() {
		$order_statuses = array();

		foreach ( wc_get_order_statuses() as $key => $label ) {
			$new_key                    = str_replace( 'wc-', '', $key );
			$order_statuses[ $new_key ] = $label;
		}

		return $order_statuses;
	}
}
