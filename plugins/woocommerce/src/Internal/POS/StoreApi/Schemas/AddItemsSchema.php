<?php
/**
 * AddItemsSchema class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\Schemas;

use Automattic\WooCommerce\StoreApi\Schemas\V1\AbstractSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;

/**
 * Response schema for the POS cart/add-items route.
 *
 * A multi-item add can partially succeed — some items land in the cart while
 * others fail validation — so the response is an envelope: the full resulting
 * cart (the shared Store API CartSchema, composed, not forked) plus a
 * per-item outcome the client can surface for each requested item. The cart
 * is always included so the client can re-render without a follow-up fetch.
 *
 * @internal Just for internal use.
 *
 * @since 11.0.0
 */
class AddItemsSchema extends AbstractSchema {

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'pos-cart-add-items';

	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'pos_cart_add_items';

	/**
	 * Envelope properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return array(
			'cart'  => array(
				'description' => __( 'The full cart after the add operation, in the standard Store API cart shape.', 'woocommerce' ),
				'type'        => 'object',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'properties'  => $this->get_cart_schema()->get_properties(),
			),
			'items' => array(
				'description' => __( 'Per-item outcome for each requested item, in request order.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'       => array(
							'description' => __( 'The requested product or variation ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'quantity' => array(
							'description' => __( 'The requested quantity.', 'woocommerce' ),
							'type'        => 'number',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'added'    => array(
							'description' => __( 'Whether this item was added to the cart.', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'key'      => array(
							'description' => __( 'The cart item key, when the item was added.', 'woocommerce' ),
							'type'        => array( 'string', 'null' ),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'error'    => array(
							'description' => __( 'Why the item could not be added, when it was not.', 'woocommerce' ),
							'type'        => array( 'object', 'null' ),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
							'properties'  => array(
								'code'    => array(
									'description' => __( 'The error code the equivalent single-item web request would have produced.', 'woocommerce' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'message' => array(
									'description' => __( 'Human-readable reason the item was not added.', 'woocommerce' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
							),
						),
					),
				),
			),
		);
	}

	/**
	 * Build the envelope response.
	 *
	 * @param array $data Array with `cart` (the cart from CartController::get_cart_for_response)
	 *                    and `items` (per-item outcome arrays from the route).
	 * @return array
	 */
	public function get_item_response( $data ) {
		return array(
			'cart'  => $this->get_cart_schema()->get_item_response( $data['cart'] ),
			'items' => array_values( $data['items'] ),
		);
	}

	/**
	 * The shared cart schema this envelope composes.
	 *
	 * @return CartSchema
	 */
	private function get_cart_schema(): CartSchema {
		/**
		 * The registry-shared cart schema instance.
		 *
		 * @var CartSchema $cart_schema
		 */
		$cart_schema = $this->controller->get( CartSchema::IDENTIFIER );

		return $cart_schema;
	}
}
