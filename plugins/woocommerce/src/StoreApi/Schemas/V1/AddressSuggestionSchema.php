<?php
namespace Automattic\WooCommerce\StoreApi\Schemas\V1;

use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Utilities\QuantityLimits;

/**
 * ProductSchema class.
 */
class AddressSuggestionSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'address-suggestion';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'address-suggestion';

	/**
	 * Product schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return [
			'hits' => [
				'description' => __( 'Results based on address query.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'id'    => [
							'type' => 'string',
						],
						'label' => [
							'type' => 'string',
						],
					],
				],
			],
		];
	}

	/**
	 * Get the schema properties.
	 *
	 * @param array $hits Address suggestions.
	 * @return array
	 */
	public function get_item_response( $hits ) {
		return array_map(
			function ( $key, $value ) {
				return [
					'id'    => $key,
					'label' => $value,
				];
			},
			array_keys( $hits ),
			array_values( $hits )
		);
	}
}
