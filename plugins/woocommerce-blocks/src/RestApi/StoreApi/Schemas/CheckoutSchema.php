<?php
/**
 * Checkout schema for the Store API.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\RestApi\StoreApi\Schemas;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\Payments\PaymentResult;

/**
 * CheckoutSchema class.
 */
class CheckoutSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'checkout';

	/**
	 * Checkout schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return [
			'payment_status'  => [
				'description' => __( 'Status of the payment returned by the gateway. One of success, pending, failure, error.', 'woo-gutenberg-products-block' ),
				'readonly'    => true,
				'type'        => 'string',
			],
			'payment_details' => [
				'description' => __( 'An array of data being returned from the payment gateway.', 'woo-gutenberg-products-block' ),
				'readonly'    => true,
				'type'        => 'array',
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'key'   => [
							'type' => 'string',
						],
						'value' => [
							'type' => 'string',
						],
					],
				],
			],
			'order'           => [
				'description' => __( 'The order that was processed.', 'woo-gutenberg-products-block' ),
				'type'        => 'object',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
				'properties'  => $this->force_schema_readonly( ( new OrderSchema() )->get_properties() ),
			],
		];
	}

	/**
	 * Return the response for checkout.
	 *
	 * @throws Exception On invalid response.
	 *
	 * @param array $item Results from checkout action.
	 * @return array
	 */
	public function get_item_response( $item ) {
		$order          = wc_get_order( absint( $item['order_id'] ) );
		$payment_result = $item['payment_result'] instanceof PaymentResult ? $item['payment_result'] : false;

		if ( ! $order || ! $payment_result ) {
			throw new Exception( 'Invalid response.' );
		}

		return [
			'payment_status'  => $payment_result->status,
			'payment_details' => $payment_result->payment_details,
			'order'           => ( new OrderSchema() )->get_item_response( $order ),
		];
	}
}
