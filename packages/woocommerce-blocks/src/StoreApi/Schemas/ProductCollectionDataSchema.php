<?php
/**
 * Product Collection Data Schema.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\StoreApi\Schemas;

defined( 'ABSPATH' ) || exit;

/**
 * ProductCollectionDataSchema class.
 *
 * @since 2.5.0
 */
class ProductCollectionDataSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'product-collection-data';

	/**
	 * Product collection data schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return [
			'price_range'      => [
				'description' => __( 'Min and max prices found in collection of products, provided using the smallest unit of the currency.', 'woocommerce' ),
				'type'        => [ 'object', 'null' ],
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
				'properties'  => array_merge(
					$this->get_store_currency_properties(),
					[
						'min_price' => [
							'description' => __( 'Min price found in collection of products.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'max_price' => [
							'description' => __( 'Max price found in collection of products.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
					]
				),
			],
			'attribute_counts' => [
				'description' => __( 'Returns number of products within attribute terms.', 'woocommerce' ),
				'type'        => [ 'array', 'null' ],
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'term'  => [
							'description' => __( 'Term ID', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'count' => [
							'description' => __( 'Number of products.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
					],
				],
			],
			'rating_counts'    => [
				'description' => __( 'Returns number of products with each average rating.', 'woocommerce' ),
				'type'        => [ 'array', 'null' ],
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'rating' => [
							'description' => __( 'Average rating', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
						'count'  => [
							'description' => __( 'Number of products.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => [ 'view', 'edit' ],
							'readonly'    => true,
						],
					],
				],
			],
		];
	}

	/**
	 * Format data.
	 *
	 * @param array $data Collection data to format and return.
	 * @return array
	 */
	public function get_item_response( $data ) {
		return [
			'price_range'      => ! is_null( $data['min_price'] ) && ! is_null( $data['max_price'] ) ? (object) array_merge(
				$this->get_store_currency_response(),
				[
					'min_price' => $this->prepare_money_response( $data['min_price'], wc_get_price_decimals() ),
					'max_price' => $this->prepare_money_response( $data['max_price'], wc_get_price_decimals() ),
				]
			) : null,
			'attribute_counts' => $data['attribute_counts'],
			'rating_counts'    => $data['rating_counts'],
		];
	}
}
