<?php
/**
 * LegacyFieldsSchema class.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Products\LegacyFields\Schema;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractSchema;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * Schema for legacy field definitions captured from WC helper function hooks.
 *
 * @since 9.9.0
 */
class LegacyFieldsSchema extends AbstractSchema {

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'legacy_fields';

	/**
	 * Return all properties for the item schema.
	 *
	 * @return array
	 */
	public function get_item_schema_properties(): array {
		return array(
			'fields' => array(
				'description'          => __( 'Field definitions grouped by hook name.', 'woocommerce' ),
				'type'                 => 'object',
				'context'              => self::VIEW_EDIT_CONTEXT,
				'readonly'             => true,
				'additionalProperties' => array(
					'type'  => 'array',
					'items' => $this->get_field_definition_schema(),
				),
			),
		);
	}

	/**
	 * Get the schema for a single field definition.
	 *
	 * @return array
	 */
	private function get_field_definition_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'id'                => array(
					'description' => __( 'Field identifier.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => self::VIEW_EDIT_CONTEXT,
				),
				'type'              => array(
					'description' => __( 'Helper function type.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => self::VIEW_EDIT_CONTEXT,
				),
				'input_type'        => array(
					'description' => __( 'HTML input type.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => self::VIEW_EDIT_CONTEXT,
				),
				'label'             => array(
					'description' => __( 'Field label.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => self::VIEW_EDIT_CONTEXT,
				),
				'meta_key'          => array(
					'description' => __( 'Post meta key for persistence.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => self::VIEW_EDIT_CONTEXT,
				),
				'placeholder'       => array(
					'description' => __( 'Placeholder text.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => self::VIEW_EDIT_CONTEXT,
				),
				'description'       => array(
					'description' => __( 'Field description.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => self::VIEW_EDIT_CONTEXT,
				),
				'default_value'     => array(
					'description' => __( 'Default value.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => self::VIEW_EDIT_CONTEXT,
				),
				'wrapper_class'     => array(
					'description' => __( 'CSS classes on the wrapper element.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => self::VIEW_EDIT_CONTEXT,
				),
				'custom_attributes' => array(
					'description' => __( 'Custom HTML attributes.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => self::VIEW_EDIT_CONTEXT,
				),
				'options'           => array(
					'description' => __( 'Options for select and radio fields.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => self::VIEW_EDIT_CONTEXT,
				),
				'hidden'            => array(
					'description' => __( 'Whether the field is a hidden input.', 'woocommerce' ),
					'type'        => 'boolean',
					'context'     => self::VIEW_EDIT_CONTEXT,
				),
			),
		);
	}

	/**
	 * Get the item response.
	 *
	 * @param mixed                                 $item           Captured field definitions grouped by hook name.
	 * @param WP_REST_Request<array<string, mixed>> $request        Request object.
	 * @param array                                 $include_fields Fields to include in the response.
	 * @return array
	 */
	public function get_item_response( $item, WP_REST_Request $request, array $include_fields = array() ): array {
		$response = array(
			'fields' => $item,
		);

		if ( ! empty( $include_fields ) ) {
			$response = array_intersect_key( $response, array_flip( $include_fields ) );
		}

		return $response;
	}
}
