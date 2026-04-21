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
	 * @testdox Includes the 10.8 SDK native types in the default supported types list.
	 *
	 * @dataProvider provider_sdk_10_8_supported_types
	 *
	 * @param string $expected_type Type expected in the defaults.
	 */
	public function test_get_supported_types_includes_sdk_10_8_native_types( string $expected_type ) {
		$types = ReactSettingsSchema::get_supported_types( 'general', '', array(), null );

		$this->assertContains(
			$expected_type,
			$types,
			"Expected '{$expected_type}' to be in the default supported types list."
		);
	}

	/**
	 * Data provider for the 10.8 SDK native type defaults.
	 *
	 * @return array<string, array{0: string}>
	 */
	public function provider_sdk_10_8_supported_types(): array {
		return array(
			'password'                       => array( 'password' ),
			'email'                          => array( 'email' ),
			'url'                            => array( 'url' ),
			'tel'                            => array( 'tel' ),
			'color'                          => array( 'color' ),
			'date'                           => array( 'date' ),
			'datetime'                       => array( 'datetime' ),
			'datetime-local'                 => array( 'datetime-local' ),
			'month'                          => array( 'month' ),
			'week'                           => array( 'week' ),
			'time'                           => array( 'time' ),
			'textarea'                       => array( 'textarea' ),
			'single_select_page_with_search' => array( 'single_select_page_with_search' ),
			'info'                           => array( 'info' ),
		);
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
	 * @testdox Drops textarea and single_select_page_with_search from the default type map so they reach the JS transformer raw.
	 */
	public function test_get_type_map_drops_unmapped_native_types() {
		$type_map = ReactSettingsSchema::get_type_map( 'general', '', array(), null );

		$this->assertArrayNotHasKey(
			'textarea',
			$type_map,
			'textarea must reach the JS transformer untouched so the custom Edit can render.'
		);
		$this->assertArrayNotHasKey(
			'single_select_page_with_search',
			$type_map,
			'single_select_page_with_search must reach the JS transformer untouched so the combobox Edit can render.'
		);
	}

	/**
	 * @testdox Treats every 10.8 SDK native type as renderable.
	 *
	 * @dataProvider provider_sdk_10_8_supported_types
	 *
	 * @param string $type Setting type to check.
	 */
	public function test_has_renderable_fields_returns_true_for_sdk_10_8_native_types( string $type ) {
		$settings = array(
			array(
				'id'   => 'sample_field',
				'type' => $type,
			),
		);

		$this->assertTrue(
			ReactSettingsSchema::has_renderable_fields( 'general', '', $settings, null ),
			"Expected '{$type}' to be treated as a renderable field."
		);
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
	 * @testdox Should pass legacy `info` row `text` content through to the field's `desc` channel.
	 */
	public function test_build_response_falls_back_to_info_text_for_desc() {
		$settings = array(
			array(
				'type'  => 'title',
				'id'    => 'info_group',
				'title' => 'Info group',
			),
			array(
				'id'    => 'info_field',
				'type'  => 'info',
				'title' => 'Heads up',
				'text'  => 'Hello',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'info_group',
			),
		);

		$response = ReactSettingsSchema::build_response( 'general', '', $settings, null );

		$this->assertArrayHasKey( 'info_group', $response['groups'] );
		$fields = $response['groups']['info_group']['fields'];
		$this->assertCount( 1, $fields );
		$this->assertSame( 'info_field', $fields[0]['id'] );
		$this->assertSame( 'Hello', $fields[0]['desc'], "The legacy 'text' channel should be exposed via the field's 'desc'." );
	}

	/**
	 * @testdox Should prefer an explicit `desc` over the legacy `text` channel for `info` rows.
	 */
	public function test_build_response_prefers_desc_over_info_text() {
		$settings = array(
			array(
				'type' => 'title',
				'id'   => 'info_group',
			),
			array(
				'id'    => 'info_field',
				'type'  => 'info',
				'title' => 'Heads up',
				'desc'  => 'Primary',
				'text'  => 'Fallback',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'info_group',
			),
		);

		$response = ReactSettingsSchema::build_response( 'general', '', $settings, null );

		$this->assertSame( 'Primary', $response['groups']['info_group']['fields'][0]['desc'] );
	}

	/**
	 * @testdox Should synthesise a page list for `single_select_page_with_search` when the consumer omits `options`.
	 */
	public function test_build_response_synthesises_pages_for_single_select_page_with_search() {
		$page_one_id = wp_insert_post(
			array(
				'post_title'  => 'Test Page One',
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		);
		$page_two_id = wp_insert_post(
			array(
				'post_title'  => 'Test Page Two',
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		);

		$settings = array(
			array(
				'type' => 'title',
				'id'   => 'page_group',
			),
			array(
				'id'    => 'sample_page_field',
				'type'  => 'single_select_page_with_search',
				'title' => 'Pick a page',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'page_group',
			),
		);

		$response = ReactSettingsSchema::build_response( 'general', '', $settings, null );

		wp_delete_post( $page_one_id, true );
		wp_delete_post( $page_two_id, true );

		$this->assertArrayHasKey( 'page_group', $response['groups'] );
		$fields = $response['groups']['page_group']['fields'];
		$this->assertCount( 1, $fields );
		$this->assertArrayHasKey( 'options', $fields[0], 'Synthesised options should be emitted to React.' );

		$options = $fields[0]['options'];
		$this->assertArrayHasKey( (string) $page_one_id, $options );
		$this->assertArrayHasKey( (string) $page_two_id, $options );
		$this->assertSame( 'Test Page One', $options[ (string) $page_one_id ] );
		$this->assertSame( 'Test Page Two', $options[ (string) $page_two_id ] );
	}

	/**
	 * @testdox Should preserve consumer-provided `options` for `single_select_page_with_search` instead of synthesising them.
	 */
	public function test_build_response_preserves_explicit_options_for_single_select_page_with_search() {
		// This page would be picked up by the synthesis path; if it appears, we know synthesis ran.
		$page_id = wp_insert_post(
			array(
				'post_title'  => 'Should Not Appear',
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		);

		$settings = array(
			array(
				'type' => 'title',
				'id'   => 'page_group',
			),
			array(
				'id'      => 'sample_page_field',
				'type'    => 'single_select_page_with_search',
				'title'   => 'Pick a page',
				'options' => array(
					'42' => 'Custom Option A',
					'99' => 'Custom Option B',
				),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'page_group',
			),
		);

		$response = ReactSettingsSchema::build_response( 'general', '', $settings, null );

		wp_delete_post( $page_id, true );

		$options = $response['groups']['page_group']['fields'][0]['options'];
		$this->assertSame( 'Custom Option A', $options['42'] );
		$this->assertSame( 'Custom Option B', $options['99'] );
		$this->assertArrayNotHasKey( (string) $page_id, $options, 'Explicit options must not be merged with synthesised pages.' );
	}

	/**
	 * @testdox Should honour `args.exclude` when synthesising the page list for `single_select_page_with_search`.
	 */
	public function test_build_response_excludes_pages_for_single_select_page_with_search() {
		$keep_id    = wp_insert_post(
			array(
				'post_title'  => 'Keep Me',
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		);
		$exclude_id = wp_insert_post(
			array(
				'post_title'  => 'Exclude Me',
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		);

		$settings = array(
			array(
				'type' => 'title',
				'id'   => 'page_group',
			),
			array(
				'id'    => 'sample_page_field',
				'type'  => 'single_select_page_with_search',
				'title' => 'Pick a page',
				'args'  => array(
					'exclude' => array( $exclude_id ),
				),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'page_group',
			),
		);

		$response = ReactSettingsSchema::build_response( 'general', '', $settings, null );

		wp_delete_post( $keep_id, true );
		wp_delete_post( $exclude_id, true );

		$options = $response['groups']['page_group']['fields'][0]['options'];
		$this->assertArrayHasKey( (string) $keep_id, $options );
		$this->assertArrayNotHasKey( (string) $exclude_id, $options, 'args.exclude entries must be filtered out of the synthesised page list.' );
	}
}
