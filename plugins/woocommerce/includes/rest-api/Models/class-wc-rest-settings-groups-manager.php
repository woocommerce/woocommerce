<?php
/**
 * REST API Settings Groups Manager
 *
 * Manages multiple settings groups and provides methods to work with them collectively.
 *
 * @package WooCommerce\RestApi\Models
 * @since   4.0.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Settings Groups Manager class.
 *
 * @package WooCommerce\RestApi\Models
 */
class WC_REST_Settings_Groups_Manager {

	/**
	 * Settings groups.
	 *
	 * @var WC_REST_Settings_Model[]
	 */
	protected $groups = array();

	/**
	 * Add a settings group to the manager.
	 *
	 * @param WC_REST_Settings_Model $group Settings group model.
	 * @return void
	 */
	public function add_group( WC_REST_Settings_Model $group ) {
		$this->groups[ $group->get_group_id() ] = $group;
	}

	/**
	 * Remove a settings group from the manager.
	 *
	 * @param string $group_id Group ID to remove.
	 * @return void
	 */
	public function remove_group( $group_id ) {
		unset( $this->groups[ $group_id ] );
	}

	/**
	 * Check if a group exists.
	 *
	 * @param string $group_id Group ID to check.
	 * @return bool True if group exists, false otherwise.
	 */
	public function has_group( $group_id ) {
		return isset( $this->groups[ $group_id ] );
	}

	/**
	 * Replace an existing group with a new one.
	 *
	 * @param WC_REST_Settings_Model $group New group to replace with.
	 * @return void
	 */
	public function replace_group( WC_REST_Settings_Model $group ) {
		$this->groups[ $group->get_group_id() ] = $group;
	}

	/**
	 * Get a specific settings group.
	 *
	 * @param string $group_id Group ID.
	 * @return WC_REST_Settings_Model|null Settings group or null if not found.
	 */
	public function get_group( $group_id ) {
		return $this->groups[ $group_id ] ?? null;
	}

	/**
	 * Get all settings groups.
	 *
	 * @return WC_REST_Settings_Model[] Array of settings groups.
	 */
	public function get_groups() {
		return $this->groups;
	}

	/**
	 * Get all settings groups data formatted for REST API.
	 *
	 * @return array Formatted settings groups data.
	 */
	public function get_all_groups_data() {
		$groups_data = array();

		foreach ( $this->groups as $group ) {
			$groups_data[ $group->get_group_id() ] = $group->get_settings_data();
		}

		// Sort groups by order.
		uasort( $groups_data, array( $this, 'sort_groups_by_order' ) );

		return $groups_data;
	}

	/**
	 * Update settings across multiple groups.
	 * All settings are validated first, and only if all are valid do they get saved.
	 *
	 * @param array $settings Array of setting_id => value pairs.
	 * @return array|WP_Error Array of successfully updated setting IDs grouped by group, or WP_Error if validation fails.
	 */
	public function update_settings( $settings ) {
		$updated_settings = array();
		$group_settings   = array();

		// First pass: validate all settings and group them by their respective groups.
		foreach ( $settings as $setting_id => $setting_value ) {
			$group = $this->get_group_for_setting( $setting_id );
			if ( $group ) {
				$group_settings[ $group->get_group_id() ][ $setting_id ] = $setting_value;
			}
		}

		// Second pass: validate all groups first (without saving).
		foreach ( $group_settings as $group_id => $group_setting_values ) {
			$group = $this->groups[ $group_id ];

			// Use the group's validation method to check all settings for this group.
			foreach ( $group_setting_values as $setting_id => $setting_value ) {
				$validation_result = $group->validate_setting_value( $setting_id, $setting_value );
				if ( is_wp_error( $validation_result ) ) {
					return $validation_result;
				}
			}
		}

		// Third pass: if all validations passed, update all groups.
		foreach ( $group_settings as $group_id => $group_setting_values ) {
			$group         = $this->groups[ $group_id ];
			$group_updated = $group->update_settings( $group_setting_values );

			// Check if the group update returned an error (shouldn't happen after validation, but just in case).
			if ( is_wp_error( $group_updated ) ) {
				return $group_updated;
			}

			if ( ! empty( $group_updated ) ) {
				$updated_settings[ $group_id ] = $group_updated;
			}
		}

		return $updated_settings;
	}

	/**
	 * Get REST API arguments for update operations across all groups.
	 *
	 * @return array REST API arguments.
	 */
	public function get_update_args() {
		$args = array();

		foreach ( $this->groups as $group ) {
			$group_args = $group->get_update_args();
			$args       = array_merge( $args, $group_args );
		}

		return $args;
	}

	/**
	 * Get the combined schema for all settings groups.
	 *
	 * @return array JSON Schema.
	 */
	public function get_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'settings_groups',
			'type'       => 'object',
			'properties' => array(),
		);

		foreach ( $this->groups as $group ) {
			$group_schema                                   = $group->get_schema();
			$schema['properties'][ $group->get_group_id() ] = $group_schema;
		}

		return $schema;
	}

	/**
	 * Sort groups by order for usort callback.
	 *
	 * @param array $a First group.
	 * @param array $b Second group.
	 * @return int Comparison result.
	 */
	protected function sort_groups_by_order( $a, $b ) {
		$order_a = $a['order'] ?? 999;
		$order_b = $b['order'] ?? 999;

		return $order_a <=> $order_b;
	}

	/**
	 * Check if a setting ID belongs to any of the managed groups.
	 *
	 * @param string $setting_id Setting ID to check.
	 * @return bool True if the setting belongs to one of the groups.
	 */
	public function is_setting_managed( $setting_id ) {
		foreach ( $this->groups as $group ) {
			$settings_definitions = $group->get_settings_definitions();
			$setting_ids          = array_column( $settings_definitions, 'id' );

			if ( in_array( $setting_id, $setting_ids, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the group that manages a specific setting.
	 *
	 * @param string $setting_id Setting ID.
	 * @return WC_REST_Settings_Model|null The group that manages the setting, or null if not found.
	 */
	public function get_group_for_setting( $setting_id ) {
		foreach ( $this->groups as $group ) {
			$settings_definitions = $group->get_settings_definitions();
			$setting_ids          = array_column( $settings_definitions, 'id' );

			if ( in_array( $setting_id, $setting_ids, true ) ) {
				return $group;
			}
		}

		return null;
	}
}
