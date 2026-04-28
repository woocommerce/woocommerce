<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Schemas\V1;

/**
 * ShopperListSchema class.
 *
 * Represents the metadata for a single shopper list. Items are exposed via a
 * separate endpoint and serialised by ShopperListItemSchema.
 */
class ShopperListSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'shopper_list';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'shopper-list';

	/**
	 * Schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return array(
			'slug'             => array(
				'description' => __( 'Stable slug for the list.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'name'             => array(
				'description' => __( 'Human-readable list name.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'is_public'        => array(
				'description' => __( 'Whether the list is shareable.', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'date_created_gmt' => array(
				'description' => __( 'The date the list was created, as GMT.', 'woocommerce' ),
				'type'        => 'string',
				'format'      => 'date-time',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'item_count'       => array(
				'description' => __( 'Number of items currently in the list.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
		);
	}

	/**
	 * Convert a stored list record into the response shape.
	 *
	 * @param array $shopper_list List array (as returned by ShopperList::to_array()).
	 * @return array
	 */
	public function get_item_response( $shopper_list ) {
		$items = isset( $shopper_list['items'] ) && is_array( $shopper_list['items'] ) ? $shopper_list['items'] : array();

		return array(
			'slug'             => $shopper_list['slug'] ?? '',
			'name'             => $shopper_list['name'] ?? '',
			'is_public'        => $shopper_list['is_public'] ?? false,
			'date_created_gmt' => wc_rest_prepare_date_response( $shopper_list['date_created_gmt'] ?? current_time( 'mysql', true ) ),
			'item_count'       => count( $items ),
		);
	}
}
