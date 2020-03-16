<?php
/**
 * Checkout schema for the Store API.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\RestApi\StoreApi\Schemas;

defined( 'ABSPATH' ) || exit;

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
			'order' => [
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
	 * @todo we need to determine the fields we want to return after processing e.g. redirect URLs.
	 *
	 * @param object $checkout_result Result from checkout action.
	 * @return array
	 */
	public function get_item_response( $checkout_result ) {
		$order_schema = new OrderSchema();
		return [
			'note'  => 'This is a placeholder',
			'order' => $order_schema->get_item_response( $checkout_result['order'] ),
		];
	}
}
