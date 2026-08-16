<?php
/**
 * FulfillmentSchema class.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Fulfillments\Schema;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\Features\Fulfillments\Fulfillment;
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
		$date_updated = $fulfillment->get_date_updated();
		$date_deleted = $fulfillment->get_date_deleted();

		return array(
			'id'           => $fulfillment->get_id(),
			'entity_type'  => $fulfillment->get_entity_type(),
			'entity_id'    => (string) $fulfillment->get_entity_id(),
			'status'       => $fulfillment->get_status(),
			'is_fulfilled' => $fulfillment->get_is_fulfilled(),
			'date_updated' => $this->format_utc_iso8601( $date_updated ),
			'date_deleted' => $this->format_utc_iso8601( $date_deleted ),
			'meta_data'    => $this->prepare_meta_data_for_response( $fulfillment->get_raw_meta_data() ),
		);
	}

	/**
	 * Format a UTC 'Y-m-d H:i:s' string as ISO 8601 with explicit 'Z' suffix.
	 *
	 * @since 10.8.0
	 * @param string|null $date UTC datetime string.
	 * @return string|null
	 */
	private function format_utc_iso8601( ?string $date ): ?string {
		if ( null === $date || '' === $date ) {
			return null;
		}
		$formatted = wc_rest_prepare_date_response( $date );
		return null === $formatted ? null : $formatted . 'Z';
	}

	/**
	 * Format `_date_fulfilled` entries in a meta data array as ISO 8601 with 'Z'
	 * suffix so V4 clients see the same UTC contract as V3 instead of the raw
	 * 'Y-m-d H:i:s' storage form. Other entries pass through unchanged.
	 *
	 * @since 10.8.0
	 *
	 * @param array<int, mixed> $meta_data Raw meta data array.
	 * @return array<int, mixed>
	 */
	private function prepare_meta_data_for_response( array $meta_data ): array {
		foreach ( $meta_data as &$meta ) {
			if ( is_array( $meta ) && isset( $meta['key'], $meta['value'] ) && '_date_fulfilled' === $meta['key'] && is_string( $meta['value'] ) ) {
				$meta['value'] = $this->format_utc_iso8601( $meta['value'] );
			}
		}
		unset( $meta );

		return $meta_data;
	}
}
