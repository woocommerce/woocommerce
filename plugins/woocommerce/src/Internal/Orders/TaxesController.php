<?php

namespace Automattic\WooCommerce\Internal\Orders;

/**
 * Class with methods for handling order taxes.
 */
class TaxesController {

	/**
	 * Calculate line taxes via Ajax call.
	 */
	public function calc_line_taxes_via_ajax(): void {
		check_ajax_referer( 'calc-totals', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['order_id'], $_POST['items'] ) ) {
			wp_die( -1 );
		}

		$order = $this->calc_line_taxes( $_POST );

		include __DIR__ . '/../../../includes/admin/meta-boxes/views/html-order-items.php';
		wp_die();
	}

	/**
	 * Calculate line taxes programmatically.
	 *
	 * @param array $post_variables Contents of the $_POST array that would be passed in an Ajax call.
	 * @return object The retrieved order object.
	 */
	public function calc_line_taxes( array $post_variables ): object {
		$order_id           = absint( $post_variables['order_id'] );
		$calculate_tax_args = array(
			'country'  => isset( $post_variables['country'] ) ? wc_strtoupper( wc_clean( wp_unslash( $post_variables['country'] ) ) ) : '',
			'state'    => isset( $post_variables['state'] ) ? wc_strtoupper( wc_clean( wp_unslash( $post_variables['state'] ) ) ) : '',
			'postcode' => isset( $post_variables['postcode'] ) ? wc_strtoupper( wc_clean( wp_unslash( $post_variables['postcode'] ) ) ) : '',
			'city'     => isset( $post_variables['city'] ) ? wc_strtoupper( wc_clean( wp_unslash( $post_variables['city'] ) ) ) : '',
		);

		// Parse the jQuery serialized items.
		$items = array();
		parse_str( wp_unslash( $post_variables['items'] ), $items );

		// Save order items first.
		wc_save_order_items( $order_id, $items );

		// Grab the order and recalculate taxes.
		$order = wc_get_order( $order_id );

		// When prices include tax and we want fixed prices regardless of location,
		// recalculate line item subtotals based on the customer's tax rate.
		$this->maybe_recalculate_line_item_subtotals( $order, $calculate_tax_args );

		$order->calculate_taxes( $calculate_tax_args );
		$order->calculate_totals( false );

		return $order;
	}

	/**
	 * Recalculate line item subtotals when prices include tax and the
	 * woocommerce_adjust_non_base_location_prices filter is false.
	 *
	 * This ensures that when a merchant wants to charge the same total price
	 * regardless of customer location, the net price is recalculated based on
	 * the customer's tax rate rather than the shop's base rate.
	 *
	 * @param \WC_Order $order              The order to recalculate.
	 * @param array     $calculate_tax_args Tax location arguments (country, state, postcode, city).
	 */
	private function maybe_recalculate_line_item_subtotals( \WC_Order $order, array $calculate_tax_args ): void {
		// Only applies when prices include tax and we don't want to adjust for location.
		if ( ! wc_prices_include_tax() || apply_filters( 'woocommerce_adjust_non_base_location_prices', true ) ) {
			return;
		}

		// Need a country to calculate tax rates.
		if ( empty( $calculate_tax_args['country'] ) ) {
			return;
		}

		// Temporarily set billing address so wc_get_price_excluding_tax can use it.
		$order->set_billing_country( $calculate_tax_args['country'] );
		$order->set_billing_state( $calculate_tax_args['state'] ?? '' );
		$order->set_billing_postcode( $calculate_tax_args['postcode'] ?? '' );
		$order->set_billing_city( $calculate_tax_args['city'] ?? '' );

		foreach ( $order->get_items( 'line_item' ) as $item ) {
			$product = $item->get_product();
			if ( ! $product || ! $product->is_taxable() ) {
				continue;
			}

			$new_subtotal = wc_get_price_excluding_tax(
				$product,
				array(
					'qty'   => $item->get_quantity(),
					'order' => $order,
				)
			);

			$item->set_subtotal( $new_subtotal );
			$item->set_total( $new_subtotal );
			$item->save();
		}
	}
}
