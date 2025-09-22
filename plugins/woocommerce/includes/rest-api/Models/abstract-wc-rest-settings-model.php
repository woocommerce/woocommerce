<?php
/**
 * Abstract REST API Settings Model
 *
 * Lightweight base model class for handling individual setting groups in REST API endpoints.
 * Each model represents a specific group of related settings (e.g., Store Address, Currency, etc.).
 *
 * @package WooCommerce\RestApi\Models
 * @since   4.0.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract REST API Settings Model class.
 *
 * @package WooCommerce\RestApi\Models
 */
abstract class WC_REST_Settings_Model {

	/**
	 * Settings group ID.
	 *
	 * @var string
	 */
	protected $group_id;

	/**
	 * Settings group title.
	 *
	 * @var string
	 */
	protected $group_title;

	/**
	 * Settings group description.
	 *
	 * @var string
	 */
	protected $group_description;

	/**
	 * Settings group order.
	 *
	 * @var int
	 */
	protected $group_order = 999;

	/**
	 * Constructor.
	 *
	 * @param string $group_id          Settings group ID.
	 * @param string $group_title       Settings group title.
	 * @param string $group_description Settings group description.
	 * @param int    $group_order       Settings group display order.
	 */
	public function __construct( $group_id, $group_title = '', $group_description = '', $group_order = 999 ) {
		$this->group_id          = $group_id;
		$this->group_title       = $group_title;
		$this->group_description = $group_description;
		$this->group_order       = $group_order;
	}

	/**
	 * Get the settings definitions for this group.
	 * Each child class must define its own settings.
	 *
	 * @return array Array of setting definitions.
	 */
	abstract public function get_settings_definitions();

	/**
	 * Get settings from a WooCommerce settings class by group ID.
	 * This method extracts settings that belong to a specific group from the settings array.
	 *
	 * @param string $settings_class_name The name of the settings class (e.g., 'WC_Settings_General').
	 * @param string $group_id The group ID to filter by (e.g., 'pricing_options').
	 * @return array Array of setting definitions for the specified group.
	 */
	protected function get_settings_by_group_id( $settings_class_name, $group_id ) {
		if ( ! class_exists( $settings_class_name ) ) {
			return array();
		}

		$settings_instance = new $settings_class_name();
		$all_settings = $settings_instance->get_settings_for_section( '' );
		
		$group_settings = array();
		$in_group = false;

		foreach ( $all_settings as $setting ) {
			// Check if this is the start of our target group
			if ( isset( $setting['type'] ) && 'title' === $setting['type'] && isset( $setting['id'] ) && $setting['id'] === $group_id ) {
				$in_group = true;
				continue;
			}

			// Check if this is the end of our target group
			if ( $in_group && isset( $setting['type'] ) && 'sectionend' === $setting['type'] && isset( $setting['id'] ) && $setting['id'] === $group_id ) {
				break;
			}

			// If we're in the group and this is a setting (not a title/sectionend), add it
			if ( $in_group && isset( $setting['id'] ) && ! in_array( $setting['type'] ?? '', array( 'title', 'sectionend' ), true ) ) {
				$group_settings[] = $setting;
			}
		}

		return $group_settings;
	}

	/**
	 * Get settings data formatted for REST API.
	 *
	 * @return array Formatted settings data.
	 */
	public function get_settings_data() {
		$settings_definitions = $this->get_settings_definitions();
		$fields               = array();

		foreach ( $settings_definitions as $setting ) {
			$field = $this->transform_setting_to_field( $setting );
			if ( $field ) {
				$fields[] = $field;
			}
		}

		// Sort fields by order.
		usort( $fields, array( $this, 'sort_fields_by_order' ) );

		return array(
			'id'          => $this->group_id,
			'title'       => $this->group_title,
			'description' => $this->group_description,
			'order'       => $this->group_order,
			'fields'      => $fields,
		);
	}

	/**
	 * Transform a setting definition into REST API field format.
	 *
	 * @param array $setting Setting definition array.
	 * @return array|null Transformed field or null if should be skipped.
	 */
	protected function transform_setting_to_field( $setting ) {
		$setting_id   = $setting['id'] ?? '';
		$setting_type = $setting['type'] ?? 'text';

		// Skip certain settings that shouldn't be exposed via REST API.
		$skip_settings = $this->get_skipped_settings();
		if ( in_array( $setting_id, $skip_settings, true ) ) {
			return null;
		}

		$field = array(
			'id'    => $setting_id,
			'label' => $setting['title'] ?? $setting_id,
			'type'  => $this->normalize_field_type( $setting_type ),
			'value' => $this->get_setting_value( $setting_id, $setting['default'] ?? '' ),
			'order' => $this->get_field_order( $setting ),
		);

		// Add tip if available.
		if ( ! empty( $setting['desc'] ) && ! empty( $setting['desc_tip'] ) ) {
			$field['tip'] = $setting['desc'];
		}

		// Add options for select fields.
		if ( isset( $setting['options'] ) && is_array( $setting['options'] ) ) {
			$field['options'] = $setting['options'];
		} else {
			// Generate options for special field types that don't have them in the setting definition.
			$field['options'] = $this->get_field_options( $setting_type, $setting_id );
		}

		return $field;
	}

	/**
	 * Get settings that should be skipped in REST API.
	 * Override in child classes to specify which settings to skip.
	 *
	 * @return array Array of setting IDs to skip.
	 */
	protected function get_skipped_settings() {
		return array();
	}

	/**
	 * Get options for specific field types.
	 * Override in child classes to provide custom options.
	 *
	 * @param string $field_type Field type.
	 * @param string $field_id   Field ID.
	 * @return array Field options.
	 */
	protected function get_field_options( $field_type, $field_id ) {
		return array();
	}

	/**
	 * Normalize field types to REST API field types.
	 * Override in child classes to customize type mapping.
	 *
	 * @param string $field_type Field type.
	 * @return string Normalized field type.
	 */
	protected function normalize_field_type( $field_type ) {
		$type_map = array(
			'single_select_country'  => 'select',
			'multi_select_countries' => 'multiselect',
		);

		return $type_map[ $field_type ] ?? $field_type;
	}

	/**
	 * Get the display order for a settings field.
	 *
	 * @param array $setting Setting definition array.
	 * @return int Display order.
	 */
	protected function get_field_order( $setting ) {
		if ( isset( $setting['order'] ) && is_numeric( $setting['order'] ) ) {
			return (int) $setting['order'];
		}

		return 999;
	}

	/**
	 * Sort fields by order for usort callback.
	 *
	 * @param array $a First field.
	 * @param array $b Second field.
	 * @return int Comparison result.
	 */
	protected function sort_fields_by_order( $a, $b ) {
		$order_a = $a['order'] ?? 999;
		$order_b = $b['order'] ?? 999;

		return $order_a <=> $order_b;
	}

	/**
	 * Get setting value.
	 * Override in child classes to customize how values are retrieved.
	 *
	 * @param string $setting_id Setting ID.
	 * @param mixed  $default    Default value.
	 * @return mixed Setting value.
	 */
	protected function get_setting_value( $setting_id, $default = '' ) {
		if ( function_exists( 'get_option' ) ) {
			// @phpcs:ignore WordPress.WP.AlternativeFunctions
			return get_option( $setting_id, $default );
		}
		return $default;
	}

	/**
	 * Set setting value.
	 * Override in child classes to customize how values are stored.
	 *
	 * @param string $setting_id Setting ID.
	 * @param mixed  $value      Setting value.
	 * @return bool True if updated, false otherwise.
	 */
	protected function set_setting_value( $setting_id, $value ) {
		if ( function_exists( 'update_option' ) ) {
			// @phpcs:ignore WordPress.WP.AlternativeFunctions
			return update_option( $setting_id, $value );
		}
		return false;
	}

	/**
	 * Update multiple settings in this group.
	 *
	 * @param array $settings Array of setting_id => value pairs.
	 * @return array Array of successfully updated setting IDs.
	 */
	public function update_settings( $settings ) {
		$updated_settings = array();
		$settings_definitions = $this->get_settings_definitions();
		$settings_by_id   = array_column( $settings_definitions, null, 'id' );
		$valid_setting_ids = array_keys( $settings_by_id );

		foreach ( $settings as $setting_id => $setting_value ) {
			// Security check: only allow updating valid settings for this group.
			if ( ! in_array( $setting_id, $valid_setting_ids, true ) ) {
				continue;
			}

			// Update the setting.
			$update_result = $this->set_setting_value( $setting_id, $setting_value );
			if ( $update_result ) {
				$updated_settings[] = $setting_id;
			}
		}

		return $updated_settings;
	}

	/**
	 * Get REST API arguments for update operations.
	 *
	 * @return array REST API arguments.
	 */
	public function get_update_args() {
		$args = array();

		// Get valid setting IDs and their types for this group.
		$settings = $this->get_settings_definitions();

		foreach ( $settings as $setting ) {
			if ( isset( $setting['id'] ) ) {
				$setting_id   = $setting['id'];
				$setting_type = $setting['type'] ?? 'text';

				$args[ $setting_id ] = array(
					'description' => $setting['title'] ?? $setting_id,
					'type'        => $this->map_wc_type_to_rest_type( $setting_type ),
					'required'    => false,
				);

				// Add validation for specific setting types.
				if ( 'number' === $setting_type ) {
					$args[ $setting_id ]['minimum'] = 0;
				}
			}
		}

		return $args;
	}

	/**
	 * Map field types to REST API types.
	 *
	 * @param string $field_type Field type.
	 * @return string REST API type.
	 */
	protected function map_wc_type_to_rest_type( $field_type ) {
		switch ( $field_type ) {
			case 'number':
				return 'number';
			case 'checkbox':
				return 'boolean';
			case 'multiselect':
			case 'multi_select_countries':
				return 'array';
			default:
				return 'string';
		}
	}

	/**
	 * Get the schema for this settings group.
	 *
	 * @return array JSON Schema.
	 */
	public function get_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->group_id . '_settings_group',
			'type'       => 'object',
			'properties' => array(
				'id'          => array(
					'description' => __( 'Unique identifier for the settings group.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'title'       => array(
					'description' => __( 'Settings group title.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'description' => array(
					'description' => __( 'Settings group description.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
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
		);

		return $schema;
	}

	/**
	 * Get the schema for individual setting fields.
	 *
	 * @return array Field schema.
	 */
	protected function get_field_schema() {
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
				'value'   => array(
					'description' => __( 'Setting field value.', 'woocommerce' ),
					'type'        => array( 'string', 'number', 'array', 'boolean' ),
					'context'     => array( 'view', 'edit' ),
				),
				'options' => array(
					'description' => __( 'Available options for select/multiselect fields.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
				),
				'tip'     => array(
					'description' => __( 'Help text for the setting field.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'order'   => array(
					'description' => __( 'Display order for the field.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);
	}

	/**
	 * Get the group ID.
	 *
	 * @return string Group ID.
	 */
	public function get_group_id() {
		return $this->group_id;
	}

	/**
	 * Get the group title.
	 *
	 * @return string Group title.
	 */
	public function get_group_title() {
		return $this->group_title;
	}

	/**
	 * Get the group description.
	 *
	 * @return string Group description.
	 */
	public function get_group_description() {
		return $this->group_description;
	}

	/**
	 * Get the group order.
	 *
	 * @return int Group order.
	 */
	public function get_group_order() {
		return $this->group_order;
	}
}