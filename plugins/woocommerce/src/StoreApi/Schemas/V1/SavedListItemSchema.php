<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Schemas\V1;

/**
 * SavedListItemSchema class.
 *
 * Response shape for a single item stored in a saved list (e.g. save-for-later).
 * The server-side `cart_item_data` payload is intentionally omitted from the public schema
 * because it may contain plugin internals; it is still preserved in user meta and replayed
 * when the item is moved back to the cart.
 */
class SavedListItemSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'saved-list-item';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'saved-list-item';

	/**
	 * Item schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return array(
			'key'          => array(
				'description' => __( 'Unique identifier for the item within the saved list.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'product_id'   => array(
				'description' => __( 'The ID of the saved product.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'variation_id' => array(
				'description' => __( 'The ID of the saved product variation, or 0 if the product is not a variation.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'quantity'     => array(
				'description' => __( 'Quantity saved for later.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'variation'    => array(
				'description' => __( 'Chosen attributes for the variation, keyed by attribute name.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'attribute' => array(
							'description' => __( 'The attribute name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'value'     => array(
							'description' => __( 'The attribute value.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
			),
			'saved_at'     => array(
				'description' => __( 'Unix timestamp (seconds) when the item was saved.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
		);
	}

	/**
	 * Format a stored saved-list item for response.
	 *
	 * @param array $item Stored item payload from SavedListsStore.
	 * @return array
	 */
	public function get_item_response( $item ) {
		$variation = array();
		foreach ( (array) ( $item['variation'] ?? array() ) as $attribute => $value ) {
			$variation[] = array(
				'attribute' => (string) $attribute,
				'value'     => (string) $value,
			);
		}

		return array(
			'key'          => (string) ( $item['key'] ?? '' ),
			'product_id'   => (int) ( $item['product_id'] ?? 0 ),
			'variation_id' => (int) ( $item['variation_id'] ?? 0 ),
			'quantity'     => (int) ( $item['quantity'] ?? 0 ),
			'variation'    => $variation,
			'saved_at'     => (int) ( $item['saved_at'] ?? 0 ),
		);
	}
}
