<?php
/**
 * REST API Product Settings Schema
 *
 * Handles schema definition for product settings.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\Products\Schema;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\Admin\Settings\ReactSettingsSchema;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractSchema;
use WC_Settings_Products;
use WP_REST_Request;

/**
 * Product Settings Schema Class.
 */
class ProductSettingsSchema extends AbstractSchema {
	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'product_settings';

	/**
	 * Return all properties for the item schema.
	 *
	 * @return array The schema properties.
	 */
	public function get_item_schema_properties(): array {
		return array(
			'id'          => array(
				'description' => __( 'Unique identifier for the settings group.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'title'       => array(
				'description' => __( 'Settings title.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'description' => array(
				'description' => __( 'Settings description.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'values'      => array(
				'description'          => __( 'Flat key-value mapping of all setting field values.', 'woocommerce' ),
				'type'                 => 'object',
				'context'              => array( 'view', 'edit' ),
				'additionalProperties' => array(
					'description' => __( 'Setting field value.', 'woocommerce' ),
					'type'        => array( 'string', 'number', 'array', 'boolean' ),
				),
			),
			'groups'      => array(
				'description'          => __( 'Collection of setting groups.', 'woocommerce' ),
				'type'                 => 'object',
				'context'              => array( 'view', 'edit' ),
				'additionalProperties' => array(
					'type'        => 'object',
					'description' => __( 'Settings group.', 'woocommerce' ),
					'properties'  => array(
						'title'       => array(
							'description' => __( 'Group title.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'description' => array(
							'description' => __( 'Group description.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'order'       => array(
							'description' => __( 'Display order for the group.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'fields'      => array(
							'description' => __( 'Settings fields.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => array( 'view', 'edit' ),
							'items'       => $this->get_field_schema(),
						),
					),
				),
			),
		);
	}

	/**
	 * Get the schema for individual setting fields.
	 *
	 * @return array
	 */
	private function get_field_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'id'      => array(
					'description' => __( 'Setting field ID.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'label'   => array(
					'description' => __( 'Setting field label.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'type'    => array(
					'description' => __( 'Setting field type.', 'woocommerce' ),
					'type'        => 'string',
					'enum'        => array( 'text', 'number', 'select', 'multiselect', 'checkbox' ),
					'context'     => array( 'view', 'edit' ),
				),
				'options' => array(
					'description' => __( 'Available options for select/multiselect fields.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
				),
				'desc'    => array(
					'description' => __( 'Description for the setting field.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
			),
		);
	}

	/**
	 * Get product settings data by transforming WC_Settings_Products data into REST API format.
	 *
	 * Delegates the actual transform to ReactSettingsSchema::build_response() so
	 * there's one canonical transformer shared with the modernised admin UI
	 * preloader.
	 *
	 * @param mixed           $item             Settings products instance.
	 * @param WP_REST_Request $request          Request object.
	 * @param array           $include_fields   Fields to include.
	 * @return array
	 */
	public function get_item_response( $item, WP_REST_Request $request, array $include_fields = array() ): array {
		$raw_settings = is_array( $item ) ? $item : array();

		$response = ReactSettingsSchema::build_response(
			'products',
			'',
			$raw_settings,
			new WC_Settings_Products()
		);

		// Preserve the REST-specific title/description.
		$response['id']          = 'products';
		$response['title']       = __( 'Products', 'woocommerce' );
		$response['description'] = __( 'Manage product settings including dimensions, weight units, and display options.', 'woocommerce' );

		if ( ! empty( $include_fields ) ) {
			$response = array_intersect_key( $response, array_flip( $include_fields ) );
		}

		return $response;
	}

}
