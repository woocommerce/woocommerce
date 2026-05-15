<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Schemas\V1;

use Automattic\WooCommerce\Internal\ShopperLists\ShopperList;
use Automattic\WooCommerce\Internal\ShopperLists\ShopperListItem;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\SchemaController;

/**
 * ShopperListSchema class.
 *
 * Represents a single shopper list, including its saved items.
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
	 * Item schema instance.
	 *
	 * @var ShopperListItemSchema
	 */
	protected $item_schema;

	/**
	 * Constructor.
	 *
	 * @throws \RuntimeException When the ShopperListItemSchema is not registered.
	 *
	 * @param ExtendSchema     $extend Rest Extending instance.
	 * @param SchemaController $controller Schema Controller instance.
	 */
	public function __construct( ExtendSchema $extend, SchemaController $controller ) {
		parent::__construct( $extend, $controller );
		$schema = $this->controller->get( ShopperListItemSchema::IDENTIFIER );
		if ( ! $schema instanceof ShopperListItemSchema ) {
			throw new \RuntimeException( 'ShopperListItemSchema is not registered in SchemaController.' );
		}
		$this->item_schema = $schema;
	}

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
			'items'            => array(
				'description' => __( 'List of saved items.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'items'       => array(
					'type'       => 'object',
					'properties' => $this->force_schema_readonly( $this->item_schema->get_properties() ),
				),
			),
		);
	}

	/**
	 * Serialize the shopper list.
	 *
	 * @param ShopperList $shopper_list The list.
	 * @return array
	 */
	public function get_item_response( $shopper_list ) {
		$items = array_values( $shopper_list->get_items() );

		$product_ids = array_filter(
			array_map(
				static function ( ShopperListItem $item ): int {
					$variation_id = $item->get_variation_id();
					return $variation_id > 0 ? $variation_id : $item->get_product_id();
				},
				$items
			)
		);
		if ( ! empty( $product_ids ) ) {
			_prime_post_caches( array_unique( $product_ids ) );
		}

		return array(
			'slug'             => $shopper_list->get_slug(),
			'date_created_gmt' => wc_rest_prepare_date_response( $shopper_list->get_date_created_gmt() ),
			'item_count'       => count( $items ),
			'items'            => array_values(
				array_map(
					fn( ShopperListItem $item ) => $this->item_schema->get_item_response( $item ),
					$items
				)
			),
		);
	}
}
