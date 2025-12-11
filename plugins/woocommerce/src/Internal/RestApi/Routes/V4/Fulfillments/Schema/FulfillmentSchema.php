<?php
/**
 * FulfillmentSchema class.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Fulfillments\Schema;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\Fulfillments\Fulfillment;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractSchema;
use WP_REST_Request;

/**
 * FulfillmentSchema class.
 */
class FulfillmentSchema extends AbstractSchema {
	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'fulfillment';

	/**
	 * Return all properties for the item schema.
	 *
	 * Note that context determines under which context data should be visible. For example, edit would be the context
	 * used when getting records with the intent of editing them. embed context allows the data to be visible when the
	 * item is being embedded in another response.
	 *
	 * @return array
	 */
	public function get_item_schema_properties(): array {
		return array(
			'id'           => array(
				'description' => __( 'Unique identifier for the fulfillment.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'entity_type'  => array(
				'description' => __( 'The type of entity for which the fulfillment is created.', 'woocommerce' ),
				'type'        => 'string',
				'required'    => true,
				'context'     => self::VIEW_EDIT_CONTEXT,
			),
			'entity_id'    => array(
				'description' => __( 'Unique identifier for the entity.', 'woocommerce' ),
				'type'        => 'string',
				'required'    => true,
				'context'     => self::VIEW_EDIT_CONTEXT,
			),
			'status'       => array(
				'description' => __( 'The status of the fulfillment.', 'woocommerce' ),
				'type'        => 'string',
				'default'     => 'unfulfilled',
				'required'    => true,
				'context'     => self::VIEW_EDIT_CONTEXT,
			),
			'is_fulfilled' => array(
				'description' => __( 'Whether the fulfillment is fulfilled.', 'woocommerce' ),
				'type'        => 'boolean',
				'default'     => false,
				'required'    => true,
				'context'     => self::VIEW_EDIT_CONTEXT,
			),
			'date_updated' => array(
				'description' => __( 'The date the fulfillment was last updated.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
				'required'    => true,
			),
			'date_deleted' => array(
				'description' => __( 'The date the fulfillment was deleted.', 'woocommerce' ),
				'anyOf'       => array(
					array(
						'type' => 'string',
					),
					array(
						'type' => 'null',
					),
				),
				'default'     => null,
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
				'required'    => true,
			),
			'meta_data'    => array(
				'description' => __( 'Meta data for the fulfillment.', 'woocommerce' ),
				'type'        => 'array',
				'required'    => true,
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'    => array(
							'description' => __( 'The unique identifier for the meta data. Set `0` for new records.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => self::VIEW_EDIT_CONTEXT,
							'readonly'    => true,
						),
						'key'   => array(
							'description' => __( 'The key of the meta data.', 'woocommerce' ),
							'type'        => 'string',
							'required'    => true,
							'context'     => self::VIEW_EDIT_CONTEXT,
						),
						'value' => array(
							'description' => __( 'The value of the meta data.', 'woocommerce' ),
							'type'        => array( 'string', 'number', 'boolean', 'object', 'array', 'null' ),
							'required'    => true,
							'context'     => self::VIEW_EDIT_CONTEXT,
						),
					),
					'required'   => true,
					'context'    => self::VIEW_EDIT_CONTEXT,
					'readonly'   => true,
				),
			),
		);
	}

	/**
	 * Get the item response.
	 *
	 * @param Fulfillment     $fulfillment Fulfillment object.
	 * @param WP_REST_Request $request Request object.
	 * @param array           $include_fields Fields to include in the response.
	 * @return array The item response.
	 */
	public function get_item_response( $fulfillment, WP_REST_Request $request, array $include_fields = array() ): array {
		$date_deleted = $fulfillment->get_date_deleted();

		return array(
			'id'           => $fulfillment->get_id(),
			'entity_type'  => $fulfillment->get_entity_type(),
			'entity_id'    => (string) $fulfillment->get_entity_id(),
			'status'       => $fulfillment->get_status(),
			'is_fulfilled' => $fulfillment->get_is_fulfilled(),
			'date_updated' => wc_rest_prepare_date_response( $fulfillment->get_date_updated() ),
			'date_deleted' => $date_deleted ? wc_rest_prepare_date_response( $date_deleted ) : null,
			'meta_data'    => $fulfillment->get_meta_data(),
		);
	}
}
