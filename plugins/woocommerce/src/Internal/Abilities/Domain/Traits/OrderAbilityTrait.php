<?php
/**
 * Order ability trait file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain\Traits;

defined( 'ABSPATH' ) || exit;

/**
 * Shared order helpers for WooCommerce domain ability definitions.
 */
trait OrderAbilityTrait {

	/**
	 * Get an order note output schema.
	 *
	 * @return array
	 */
	protected static function get_order_note_output_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'note_id' => array( 'type' => 'integer' ),
				'order'   => array(
					'type'                 => 'object',
					'additionalProperties' => true,
				),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get an order from ability input.
	 *
	 * @param array $input Ability input.
	 * @return \WC_Order|\WP_Error
	 */
	protected static function get_order_from_input( array $input ) {
		if ( empty( $input['id'] ) ) {
			return new \WP_Error(
				'woocommerce_order_id_required',
				__( 'Order ID is required.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$order = wc_get_order( absint( $input['id'] ) );

		if ( ! $order instanceof \WC_Order ) {
			return new \WP_Error(
				'woocommerce_order_not_found',
				__( 'Order not found.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		return $order;
	}

	/**
	 * Format an order for ability output.
	 *
	 * @param \WC_Order $order              Order object.
	 * @param bool      $include_line_items Whether to include line items.
	 * @return array
	 */
	protected static function format_order_for_response( \WC_Order $order, bool $include_line_items ): array {
		$data = array(
			'id'                   => $order->get_id(),
			'status'               => $order->get_status(),
			'currency'             => $order->get_currency(),
			'total'                => $order->get_total(),
			'customer_id'          => $order->get_customer_id(),
			'billing_email'        => $order->get_billing_email(),
			'payment_method'       => $order->get_payment_method(),
			'payment_method_title' => $order->get_payment_method_title(),
			'date_created'         => wc_rest_prepare_date_response( $order->get_date_created(), false ),
			'date_modified'        => wc_rest_prepare_date_response( $order->get_date_modified(), false ),
		);

		if ( $include_line_items ) {
			$data['line_items'] = array();

			foreach ( $order->get_items() as $item ) {
				if ( ! $item instanceof \WC_Order_Item_Product ) {
					continue;
				}

				$data['line_items'][] = array(
					'id'           => $item->get_id(),
					'name'         => $item->get_name(),
					'product_id'   => $item->get_product_id(),
					'variation_id' => $item->get_variation_id(),
					'quantity'     => $item->get_quantity(),
					'subtotal'     => $item->get_subtotal(),
					'total'        => $item->get_total(),
				);
			}
		}

		return $data;
	}
}
