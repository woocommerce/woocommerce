<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings;

use Automattic\WooCommerce\Internal\Admin\Settings;
use Automattic\WooCommerce\Internal\Admin\Settings\ReactSettingsSchema;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\General\Schema\GeneralSettingsSchema;
use WC_Unit_Test_Case;
use WP_REST_Request;

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
			/**
			 * @return string
			 */
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

	/**
	 * @testdox Injects field options via the tab-specific filter hook.
	 */
	public function test_build_response_applies_field_options_filter() {
		$filter = static function ( $options, $field_id ) {
			if ( 'tab_specific_field' === $field_id ) {
				return array(
					'alpha' => 'Alpha',
					'beta'  => 'Beta',
				);
			}
			return $options;
		};

		add_filter( 'woocommerce_react_settings_field_options', $filter, 10, 2 );

		$settings = array(
			array(
				'type'  => 'title',
				'id'    => 'group_one',
				'title' => 'Group one',
			),
			array(
				'id'   => 'tab_specific_field',
				'type' => 'select',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'group_one',
			),
		);

		$response = ReactSettingsSchema::build_response( 'custom_tab', '', $settings, null );

		remove_filter( 'woocommerce_react_settings_field_options', $filter, 10 );

		$fields = $response['groups']['group_one']['fields'];
		$this->assertNotEmpty( $fields );
		$this->assertArrayHasKey( 'options', $fields[0] );
		$this->assertSame( 'Alpha', $fields[0]['options']['alpha'] );
		$this->assertSame( 'Beta', $fields[0]['options']['beta'] );
	}

	/**
	 * @testdox Does not generate country options without a registered field options filter.
	 *
	 * Regression guard: prior to this change, `ReactSettingsSchema::get_field_options()`
	 * had a hardcoded dispatch for `single_select_country` / `multi_select_countries`
	 * that duplicated the logic now owned by `GeneralSettingsSchema::inject_field_options()`.
	 * The base class should trust the filter to fill `$options` for types that need
	 * external data, producing an empty options array when no callback is registered.
	 */
	public function test_build_response_does_not_generate_country_options_without_filter() {
		// Ensure no general-tab callback is registered for this test (it might have been
		// registered by a previous test that instantiated GeneralSettingsSchema).
		remove_filter(
			'woocommerce_react_settings_field_options',
			array( GeneralSettingsSchema::class, 'inject_field_options' ),
			10
		);

		$settings = array(
			array(
				'type'  => 'title',
				'id'    => 'group_one',
				'title' => 'Group one',
			),
			array(
				'id'   => 'some_country_field',
				'type' => 'single_select_country',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'group_one',
			),
		);

		$response = ReactSettingsSchema::build_response( 'custom_tab', '', $settings, null );

		$fields = $response['groups']['group_one']['fields'];
		$this->assertNotEmpty( $fields );
		$this->assertArrayNotHasKey( 'options', $fields[0] );
	}

	/**
	 * @testdox Resolves settings pages registered via WC_Admin_Settings without relying on the legacy hardcoded map.
	 *
	 * Regression guard: prior to 10.8.0 `get_settings_page_instance()` fell back
	 * to a hardcoded `general`/`products` class map. The shipping tab was never
	 * in that map, so resolving it proves we're now going through the generic
	 * `WC_Admin_Settings::get_settings_pages()` iteration.
	 */
	public function test_settings_page_instance_resolves_non_hardcoded_page() {
		// Ensure admin-side settings classes are loaded so WC_Admin_Settings::get_settings_pages() is populated.
		if ( ! class_exists( 'WC_Admin_Settings', false ) ) {
			include_once WC_ABSPATH . 'includes/admin/class-wc-admin-settings.php';
		}

		$sut        = Settings::get_instance();
		$reflection = new \ReflectionClass( $sut );
		$method     = $reflection->getMethod( 'get_settings_page_instance' );
		$method->setAccessible( true );

		$instance = $method->invoke( $sut, 'shipping' );

		$this->assertInstanceOf( \WC_Settings_Shipping::class, $instance );
		$this->assertSame( 'shipping', $instance->get_id() );
	}

	/**
	 * @testdox Returns null for unknown settings page ids.
	 */
	public function test_settings_page_instance_returns_null_for_unknown_page() {
		if ( ! class_exists( 'WC_Admin_Settings', false ) ) {
			include_once WC_ABSPATH . 'includes/admin/class-wc-admin-settings.php';
		}

		$sut        = Settings::get_instance();
		$reflection = new \ReflectionClass( $sut );
		$method     = $reflection->getMethod( 'get_settings_page_instance' );
		$method->setAccessible( true );

		$instance = $method->invoke( $sut, 'this_tab_does_not_exist' );

		$this->assertNull( $instance );
	}

	/**
	 * @testdox GeneralSettingsSchema::get_item_response returns the same core shape as ReactSettingsSchema::build_response.
	 *
	 * This guards the "one canonical transformer" contract introduced in 10.8.0
	 * — the v4 REST response must share groups/values/field shape with the
	 * admin preloader payload.
	 */
	public function test_general_settings_schema_shares_shape_with_build_response() {
		update_option( 'setting_one', 'saved' );

		$raw_settings = array(
			array(
				'type'  => 'title',
				'id'    => 'group_one',
				'title' => 'Group one',
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
		);

		$sut     = new GeneralSettingsSchema();
		$request = new WP_REST_Request( 'GET', '/wc/v4/settings/general' );

		$schema_response = $sut->get_item_response( $raw_settings, $request );
		$canonical       = ReactSettingsSchema::build_response( 'general', '', $raw_settings, null );

		delete_option( 'setting_one' );

		// Structural parity — the transform contract.
		$this->assertSame( $canonical['values'], $schema_response['values'] );
		$this->assertSame( array_keys( $canonical['groups'] ), array_keys( $schema_response['groups'] ) );
		$this->assertSame(
			$canonical['groups']['group_one']['fields'],
			$schema_response['groups']['group_one']['fields']
		);

		// Tab-specific copy is preserved on the REST response.
		$this->assertSame( 'general', $schema_response['id'] );
		$this->assertSame( 'General', $schema_response['title'] );
		$this->assertNotEmpty( $schema_response['description'] );
	}
}
