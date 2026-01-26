<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings;

use Automattic\WooCommerce\Internal\Admin\Settings\ReactSettingsSchema;
use WC_Unit_Test_Case;

/**
 * React settings schema test.
 */
class ReactSettingsSchemaTest extends WC_Unit_Test_Case {
	/**
	 * @testdox Returns payload path with default section.
	 */
	public function test_get_payload_path_uses_default_section() {
		$payload_path = ReactSettingsSchema::get_payload_path( 'general', '' );

		$this->assertSame( array( 'settings', 'general', 'default' ), $payload_path );
	}

	/**
	 * @testdox Returns mount ID with default section.
	 */
	public function test_get_mount_id_uses_default_section() {
		$mount_id = ReactSettingsSchema::get_mount_id( 'products', '' );

		$this->assertSame( 'wc_settings_react_products_default', $mount_id );
	}

	/**
	 * @testdox Returns default supported field types.
	 */
	public function test_get_supported_types_returns_defaults() {
		$types = ReactSettingsSchema::get_supported_types( 'general', '', array(), null );

		$this->assertContains( 'text', $types );
		$this->assertContains( 'select', $types );
		$this->assertContains( 'multiselect', $types );
	}

	/**
	 * @testdox Applies supported types filters.
	 */
	public function test_get_supported_types_applies_filter() {
		$filter = static function ( $types ) {
			$types[] = 'custom_type';
			return $types;
		};

		add_filter( 'woocommerce_react_settings_supported_types', $filter );

		$types = ReactSettingsSchema::get_supported_types( 'general', '', array(), null );

		remove_filter( 'woocommerce_react_settings_supported_types', $filter );

		$this->assertContains( 'custom_type', $types );
	}

	/**
	 * @testdox Returns default type map values.
	 */
	public function test_get_type_map_returns_defaults() {
		$type_map = ReactSettingsSchema::get_type_map( 'general', '', array(), null );

		$this->assertSame( 'select', $type_map['single_select_page'] );
		$this->assertSame( 'multiselect', $type_map['multi_select_countries'] );
	}

	/**
	 * @testdox Applies type map filters.
	 */
	public function test_get_type_map_applies_filter() {
		$filter = static function () {
			return array(
				'custom_wc_type' => 'text',
			);
		};

		add_filter( 'woocommerce_react_settings_type_map', $filter );

		$type_map = ReactSettingsSchema::get_type_map( 'general', '', array(), null );

		remove_filter( 'woocommerce_react_settings_type_map', $filter );

		$this->assertSame( 'text', $type_map['custom_wc_type'] );
	}

	/**
	 * @testdox Returns unsupported fields with normalized types.
	 */
	public function test_get_unsupported_fields_returns_expected_payload() {
		$supported_filter = static function () {
			return array( 'text', 'select' );
		};

		$type_map_filter = static function () {
			return array(
				'custom_wc_type' => 'text',
			);
		};

		add_filter( 'woocommerce_react_settings_supported_types', $supported_filter );
		add_filter( 'woocommerce_react_settings_type_map', $type_map_filter );

		$settings = array(
			array(
				'id'   => 'group_title',
				'type' => 'title',
			),
			array(
				'id'   => 'supported_field',
				'type' => 'text',
			),
			array(
				'id'   => 'mapped_field',
				'type' => 'custom_wc_type',
			),
			array(
				'id'   => 'unsupported_field',
				'type' => 'checkbox',
			),
			array(
				'id'   => 'empty_type_field',
				'type' => '',
			),
			array(
				'id'   => 'group_end',
				'type' => 'sectionend',
			),
		);

		$unsupported = ReactSettingsSchema::get_unsupported_fields( 'general', '', $settings, null );

		remove_filter( 'woocommerce_react_settings_supported_types', $supported_filter );
		remove_filter( 'woocommerce_react_settings_type_map', $type_map_filter );

		$this->assertCount( 2, $unsupported );
		$this->assertSame( 'unsupported_field', $unsupported[0]['id'] );
		$this->assertSame( 'empty_type_field', $unsupported[1]['id'] );
	}

	/**
	 * @testdox Builds grouped response data and values.
	 */
	public function test_build_response_builds_groups_and_values() {
		$settings_page = new class() {
			public function get_label() {
				return 'General';
			}
		};

		update_option( 'setting_one', 'saved_value' );
		update_option( 'setting_two', 'saved_value_two' );

		$settings = array(
			array(
				'type'  => 'title',
				'id'    => 'group_one',
				'title' => 'Group one',
				'desc'  => 'Group description',
				'order' => 2,
			),
			array(
				'id'      => 'setting_one',
				'type'    => 'text',
				'default' => 'default_value',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'group_one',
			),
			array(
				'id'      => 'setting_two',
				'type'    => 'text',
				'default' => 'default_value_two',
			),
		);

		$response = ReactSettingsSchema::build_response( 'general', '', $settings, $settings_page );

		delete_option( 'setting_one' );
		delete_option( 'setting_two' );

		$this->assertSame( 'general', $response['id'] );
		$this->assertSame( 'General', $response['title'] );
		$this->assertArrayHasKey( 'group_one', $response['groups'] );
		$this->assertArrayHasKey( 'default', $response['groups'] );
		$this->assertSame( 'saved_value', $response['values']['setting_one'] );
		$this->assertSame( 'saved_value_two', $response['values']['setting_two'] );
	}
}
