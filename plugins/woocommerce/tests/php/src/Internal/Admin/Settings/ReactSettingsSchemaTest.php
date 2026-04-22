<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings;

use Automattic\WooCommerce\Internal\Admin\Settings;
use Automattic\WooCommerce\Internal\Admin\Settings\ReactSettingsPageInterface;
use Automattic\WooCommerce\Internal\Admin\Settings\ReactSettingsSchema;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\General\Schema\GeneralSettingsSchema;
use WC_Settings_Page;
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
	 * @testdox Returns a render plan with payload metadata for a supported settings screen.
	 */
	public function test_get_screen_render_context_returns_payload_for_supported_screen() {
		$settings_page = $this->make_page_with_interface( 'general', 'General', $this->make_noop_interface() );

		update_option( 'setting_one', 'saved_value' );

		$settings = array(
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

		$plan = ReactSettingsSchema::get_screen_render_context( 'general', '', $settings, $settings_page );

		delete_option( 'setting_one' );

		$this->assertFalse( $plan['is_opted_out'] );
		$this->assertTrue( $plan['should_render'] );
		$this->assertSame( 'wc_settings_react_general_default', $plan['mount_id'] );
		$this->assertSame( array( 'settings', 'general', 'default' ), $plan['payload_path'] );
		$this->assertIsArray( $plan['response'] );
		$this->assertSame( 'saved_value', $plan['response']['values']['setting_one'] );
	}

	/**
	 * @testdox Returns unsupported fields in the render plan for a legacy fallback screen.
	 */
	public function test_get_screen_render_context_returns_unsupported_fields_for_legacy_fallback() {
		$settings_page = $this->make_page_with_interface( 'general', 'General', $this->make_noop_interface() );

		$settings = array(
			array(
				'type' => 'title',
				'id'   => 'group_one',
			),
			array(
				'id'   => 'unsupported_field',
				'type' => 'unsupported_field_type',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'group_one',
			),
		);

		$plan = ReactSettingsSchema::get_screen_render_context( 'general', '', $settings, $settings_page );

		$this->assertFalse( $plan['is_opted_out'] );
		$this->assertFalse( $plan['should_render'] );
		$this->assertNull( $plan['response'] );
		$this->assertCount( 1, $plan['unsupported_fields'] );
		$this->assertSame( 'unsupported_field', $plan['unsupported_fields'][0]['id'] );
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
	 * @testdox Returns default type map values.
	 */
	public function test_get_type_map_returns_defaults() {
		$type_map = ReactSettingsSchema::get_type_map( 'general', '', array(), null );

		$this->assertSame( 'select', $type_map['single_select_page'] );
		$this->assertSame( 'multiselect', $type_map['multi_select_countries'] );
	}

	/**
	 * @testdox Returns unsupported fields with normalized types.
	 */
	public function test_get_unsupported_fields_returns_expected_payload() {
		// Drive the type-map extension via a scripted page interface — the
		// legacy `woocommerce_react_settings_type_map` filter was retired in
		// 10.8.0 and coverage of that filter's wiring now lives in the
		// dedicated ReactSettingsPageInterfaceTest.
		$interface     = $this->make_interface_returning(
			array( 'custom_wc_type' => 'text' ),
			array(),
			null
		);
		$settings_page = $this->make_page_with_interface( 'general', 'General', $interface );

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
				'type' => 'completely_unknown_type',
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

		$unsupported = ReactSettingsSchema::get_unsupported_fields( 'general', '', $settings, $settings_page );

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
	 * @testdox Does not generate built-in options for pages without an interface.
	 */
	public function test_build_response_does_not_generate_options_for_page_without_interface() {
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

	/**
	 * Build a no-op ReactSettingsPageInterface implementation.
	 *
	 * Pages using this fake expose no extra types, no extra aliases, and defer
	 * to the transformer's built-in option handling for every field.
	 */
	private function make_noop_interface(): ReactSettingsPageInterface {
		return $this->make_interface_returning( array(), array(), null );
	}

	/**
	 * Build a scripted ReactSettingsPageInterface with the supplied return values.
	 *
	 * @param array<string, string>                                $type_map      Extra type-map entries.
	 * @param array<int, string>                                   $supported     Extra supported types.
	 * @param array<int, array{label: string, value: string}>|null $field_options Field-options return value (null = fall through).
	 */
	private function make_interface_returning( array $type_map, array $supported, ?array $field_options ): ReactSettingsPageInterface {
		return new class( $type_map, $supported, $field_options ) implements ReactSettingsPageInterface {
			/**
			 * Extra type-map entries scripted for the fake page.
			 *
			 * @var array<string, string>
			 */
			private array $type_map;

			/**
			 * Extra supported-type entries scripted for the fake page.
			 *
			 * @var array<int, string>
			 */
			private array $supported;

			/**
			 * Field-options scripted return value. Null means fall through.
			 *
			 * @var array<int, array{label: string, value: string}>|null
			 */
			private ?array $field_options;

			/**
			 * Constructor.
			 *
			 * @param array      $type_map      Type-map entries.
			 * @param array      $supported     Supported-type entries.
			 * @param array|null $field_options Field-options return value.
			 */
			public function __construct( array $type_map, array $supported, ?array $field_options ) {
				$this->type_map      = $type_map;
				$this->supported     = $supported;
				$this->field_options = $field_options;
			}

			/**
			 * {@inheritDoc}
			 */
			public function get_extra_type_map( string $section ): array {
				return $this->type_map;
			}

			/**
			 * {@inheritDoc}
			 */
			public function get_extra_supported_types( string $section ): array {
				return $this->supported;
			}

			/**
			 * {@inheritDoc}
			 */
			public function get_field_options( string $field_id, array $field, string $section ): ?array {
				return $this->field_options;
			}
		};
	}

	/**
	 * Build an anonymous WC_Settings_Page subclass that returns the supplied
	 * interface from get_react_settings_page().
	 *
	 * @param string                     $tab_id    Tab id (exposed via `$this->id`).
	 * @param string                     $label     Tab label (exposed via `$this->label`).
	 * @param ReactSettingsPageInterface $interface Scripted interface.
	 */
	private function make_page_with_interface( string $tab_id, string $label, ReactSettingsPageInterface $interface ): WC_Settings_Page {
		return new class( $tab_id, $label, $interface ) extends WC_Settings_Page {
			/**
			 * Scripted interface instance.
			 *
			 * @var ReactSettingsPageInterface
			 */
			private ReactSettingsPageInterface $iface;

			/**
			 * Constructor.
			 *
			 * @param string                     $tab_id    Tab id.
			 * @param string                     $label     Tab label.
			 * @param ReactSettingsPageInterface $interface Scripted interface.
			 */
			public function __construct( string $tab_id, string $label, ReactSettingsPageInterface $interface ) {
				$this->id    = $tab_id;
				$this->label = $label;
				$this->iface = $interface;
				// Intentionally skip parent::__construct() so this bare subclass
				// does not register hooks against the global settings pipeline.
			}

			/**
			 * Return the scripted interface instance.
			 *
			 * @return ReactSettingsPageInterface|null
			 */
			public function get_react_settings_page(): ?ReactSettingsPageInterface {
				return $this->iface;
			}
		};
	}
}
