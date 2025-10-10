<?php
/**
 * EmailSettingsSchema class.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\Email\Schema;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractSchema;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * EmailSettingsSchema class.
 */
class EmailSettingsSchema extends AbstractSchema {
	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'email_settings';

	/**
	 * Return all properties for the item schema.
	 *
	 * @return array
	 */
	public function get_item_schema_properties(): array {
		return array(
			'id'          => array(
				'description' => __( 'Unique identifier for the settings group.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'title'       => array(
				'description' => __( 'Settings title.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'description' => array(
				'description' => __( 'Settings description.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'values'      => array(
				'description' => __( 'Flat key-value mapping of all setting field values.', 'woocommerce' ),
				'type'        => 'object',
				'context'     => self::VIEW_EDIT_CONTEXT,
			),
			'groups'      => array(
				'description'          => __( 'Collection of setting groups.', 'woocommerce' ),
				'type'                 => 'object',
				'context'              => self::VIEW_EDIT_CONTEXT,
				'additionalProperties' => array(
					'type'        => 'object',
					'description' => __( 'Settings group.', 'woocommerce' ),
					'properties'  => array(
						'title'       => array(
							'description' => __( 'Group title.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => self::VIEW_EDIT_CONTEXT,
						),
						'description' => array(
							'description' => __( 'Group description.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => self::VIEW_EDIT_CONTEXT,
						),
						'order'       => array(
							'description' => __( 'Display order for the group.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => self::VIEW_EDIT_CONTEXT,
							'readonly'    => true,
						),
						'fields'      => array(
							'description' => __( 'Settings fields.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => self::VIEW_EDIT_CONTEXT,
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
				'id'          => array(
					'description' => __( 'Setting field ID.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => self::VIEW_EDIT_CONTEXT,
				),
				'label'       => array(
					'description' => __( 'Setting field label.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => self::VIEW_EDIT_CONTEXT,
				),
				'type'        => array(
					'description' => __( 'Setting field type.', 'woocommerce' ),
					'type'        => 'string',
					'enum'        => array( 'text', 'email', 'boolean', 'number' ),
					'context'     => self::VIEW_EDIT_CONTEXT,
				),
				'description' => array(
					'description' => __( 'Setting field description.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => self::VIEW_EDIT_CONTEXT,
				),
			),
		);
	}

	/**
	 * Get email settings data by transforming email settings into REST API format.
	 *
	 * @param mixed           $item             Settings array from WC_Settings_Emails.
	 * @param WP_REST_Request $request          Request object.
	 * @param array           $include_fields   Fields to include.
	 * @return array
	 */
	public function get_item_response( $item, WP_REST_Request $request, array $include_fields = array() ): array {
		$settings = is_array( $item ) ? $item : array();

		// Filter out non-editable field types.
		$editable_settings = array_filter(
			$settings,
			static function ( $setting ) {
				$type = $setting['type'] ?? '';
				return isset( $setting['id'] ) && ! in_array( $type, array( 'title', 'sectionend', 'email_notification', 'email_notification_block_emails', 'email_preview', 'email_image_url', 'email_font_family', 'email_color_palette', 'previewing_new_templates', 'email_improvements_button' ), true );
			}
		);

		// Build fields array from settings.
		$fields = array();
		$values = array();

		foreach ( $editable_settings as $setting ) {
			$setting_id   = $setting['id'];
			$setting_type = $setting['type'] ?? 'text';

			// Map WooCommerce field types to REST API types.
			$api_type = $this->map_setting_type_to_api_type( $setting_type );

			// Build field definition.
			$field = array(
				'id'    => $setting_id,
				'label' => $setting['title'] ?? $setting_id,
				'type'  => $api_type,
			);

			// Add description if available.
			if ( ! empty( $setting['desc'] ) ) {
				$field['description'] = $setting['desc'];
			}

			$fields[] = $field;

			// Get current value.
			$default_value = $setting['default'] ?? '';
			$current_value = get_option( $setting_id, $default_value );

			// Convert checkbox values to boolean for API.
			if ( 'checkbox' === $setting_type ) {
				$current_value = 'yes' === $current_value;
			}

			$values[ $setting_id ] = $current_value;
		}

		$response = array(
			'id'          => 'email',
			'title'       => __( 'Email design', 'woocommerce' ),
			'description' => __( 'Customize the look and feel of all you notification emails.', 'woocommerce' ),
			'values'      => $values,
			'groups'      => array(
				'sender_details' => array(
					'title'       => __( 'Sender details', 'woocommerce' ),
					'description' => __( 'This is how your sender name and email address would appear in outgoing emails.', 'woocommerce' ),
					'order'       => 1,
					'fields'      => $fields,
				),
			),
		);

		if ( ! empty( $include_fields ) ) {
			$response = array_intersect_key( $response, array_flip( $include_fields ) );
		}

		return $response;
	}

	/**
	 * Map WooCommerce setting type to REST API type.
	 *
	 * @param string $setting_type WooCommerce setting type.
	 * @return string REST API type.
	 */
	private function map_setting_type_to_api_type( string $setting_type ): string {
		$type_map = array(
			'text'     => 'text',
			'email'    => 'email',
			'checkbox' => 'boolean',
			'number'   => 'number',
		);

		return $type_map[ $setting_type ] ?? 'text';
	}
}
