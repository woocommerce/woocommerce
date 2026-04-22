<?php
/**
 * ReactSettingsPageInterface contract tests.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings;

use Automattic\WooCommerce\Internal\Admin\Settings\ReactSettingsPageInterface;
use Automattic\WooCommerce\Internal\Admin\Settings\ReactSettingsSchema;
use WC_Settings_Page;
use WC_Unit_Test_Case;

/**
 * Covers the contract between ReactSettingsSchema and the per-page
 * ReactSettingsPageInterface — specifically the three pathways that
 * replaced the woocommerce_react_settings_{supported_types,type_map,field_options}
 * filters in 10.8.0, plus the render-gate rule 2 (modern rendering requires a
 * non-null interface).
 *
 * @covers \Automattic\WooCommerce\Internal\Admin\Settings\ReactSettingsSchema
 */
class ReactSettingsPageInterfaceTest extends WC_Unit_Test_Case {

	/**
	 * @testdox get_extra_type_map entries extend the default type map.
	 */
	public function test_extra_type_map_is_merged(): void {
		$interface = $this->make_interface( array( 'custom_type' => 'text' ) );
		$page      = $this->make_page_with_interface( 'tab_x', $interface );

		$map = ReactSettingsSchema::get_type_map( 'tab_x', '', array(), $page );

		$this->assertArrayHasKey( 'custom_type', $map );
		$this->assertSame( 'text', $map['custom_type'] );
		$this->assertArrayHasKey( 'single_select_country', $map );
		$this->assertArrayHasKey( 'multi_select_countries', $map );
	}

	/**
	 * @testdox get_extra_type_map entries can override default type map entries.
	 */
	public function test_extra_type_map_can_override_defaults(): void {
		$interface = $this->make_interface( array( 'single_select_country' => 'text' ) );
		$page      = $this->make_page_with_interface( 'tab_x', $interface );

		$map = ReactSettingsSchema::get_type_map( 'tab_x', '', array(), $page );

		$this->assertSame( 'text', $map['single_select_country'] );
	}

	/**
	 * @testdox get_type_map returns only the defaults when no interface is present.
	 */
	public function test_type_map_without_interface_returns_defaults_only(): void {
		$page = $this->make_page_without_interface( 'tab_x' );

		$map = ReactSettingsSchema::get_type_map( 'tab_x', '', array(), $page );

		$this->assertArrayHasKey( 'single_select_country', $map );
		$this->assertArrayNotHasKey( 'custom_type', $map );
	}

	/**
	 * @testdox get_extra_supported_types entries are merged into the default supported types list.
	 */
	public function test_extra_supported_types_is_merged(): void {
		$interface = $this->make_interface( array(), array( 'custom_type' ) );
		$page      = $this->make_page_with_interface( 'tab_x', $interface );

		$types = ReactSettingsSchema::get_supported_types( 'tab_x', '', array(), $page );

		$this->assertContains( 'custom_type', $types );
		$this->assertContains( 'text', $types );
		$this->assertContains( 'select', $types );
	}

	/**
	 * @testdox get_supported_types returns only the defaults when no interface is present.
	 */
	public function test_supported_types_without_interface_returns_defaults_only(): void {
		$page = $this->make_page_without_interface( 'tab_x' );

		$types = ReactSettingsSchema::get_supported_types( 'tab_x', '', array(), $page );

		$this->assertContains( 'text', $types );
		$this->assertNotContains( 'custom_type', $types );
	}

	/**
	 * @testdox get_field_options overrides inline options when returning a non-null array.
	 */
	public function test_field_options_override_inline_options(): void {
		$interface = $this->make_interface(
			array(),
			array(),
			array(
				array(
					'label' => 'Alpha',
					'value' => 'alpha',
				),
				array(
					'label' => 'Beta',
					'value' => 'beta',
				),
			)
		);
		$page      = $this->make_page_with_interface( 'tab_x', $interface );

		$settings = array(
			array(
				'type'  => 'title',
				'id'    => 'group_one',
				'title' => 'Group one',
			),
			array(
				'id'      => 'tab_specific_field',
				'type'    => 'select',
				'options' => array( 'existing' => 'Existing' ),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'group_one',
			),
		);

		$response = ReactSettingsSchema::build_response( 'tab_x', '', $settings, $page );

		$fields = $response['groups']['group_one']['fields'];
		$this->assertNotEmpty( $fields );
		$this->assertArrayHasKey( 'options', $fields[0] );

		$options = $fields[0]['options'];
		$this->assertIsArray( $options );
		$this->assertCount( 2, $options );

		// Normalised list-of-arrays shape: each entry has label + value keys.
		$this->assertSame( 'Alpha', $options[0]['label'] );
		$this->assertSame( 'alpha', $options[0]['value'] );
		$this->assertSame( 'Beta', $options[1]['label'] );
		$this->assertSame( 'beta', $options[1]['value'] );
	}

	/**
	 * @testdox get_field_options returning null preserves inline options.
	 */
	public function test_field_options_null_preserves_inline(): void {
		$interface = $this->make_interface( array(), array(), null );
		$page      = $this->make_page_with_interface( 'tab_x', $interface );

		$settings = array(
			array(
				'type' => 'title',
				'id'   => 'group_one',
			),
			array(
				'id'      => 'some_field',
				'type'    => 'select',
				'options' => array(
					'red'  => 'Red',
					'blue' => 'Blue',
				),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'group_one',
			),
		);

		$response = ReactSettingsSchema::build_response( 'tab_x', '', $settings, $page );

		$fields = $response['groups']['group_one']['fields'];
		$this->assertSame( 'Red', $fields[0]['options']['red'] );
		$this->assertSame( 'Blue', $fields[0]['options']['blue'] );
	}

	/**
	 * @testdox get_field_options returning an empty array counts as an override (not a fall-through).
	 */
	public function test_field_options_empty_array_is_override(): void {
		$interface = $this->make_interface( array(), array(), array() );
		$page      = $this->make_page_with_interface( 'tab_x', $interface );

		$settings = array(
			array(
				'type' => 'title',
				'id'   => 'group_one',
			),
			array(
				'id'      => 'some_field',
				'type'    => 'select',
				'options' => array(
					'red'  => 'Red',
					'blue' => 'Blue',
				),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'group_one',
			),
		);

		$response = ReactSettingsSchema::build_response( 'tab_x', '', $settings, $page );

		$fields = $response['groups']['group_one']['fields'];
		$this->assertArrayNotHasKey( 'options', $fields[0], 'Empty-array override should suppress options entirely.' );
	}

	/**
	 * @testdox Render-gate rule 2: a page returning null from get_react_settings_page() does not render modern.
	 */
	public function test_render_gate_rule_2_blocks_page_without_interface(): void {
		$page = $this->make_page_without_interface( 'tab_x' );

		$settings = array(
			array(
				'type' => 'title',
				'id'   => 'group_one',
			),
			array(
				'id'   => 'setting_one',
				'type' => 'text',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'group_one',
			),
		);

		$plan = ReactSettingsSchema::get_screen_render_context( 'tab_x', '', $settings, $page );

		$this->assertFalse( $plan['should_render'], 'Rule 2: page without interface must not render modern.' );
		$this->assertFalse( $plan['is_opted_out'], 'Rule 2 is distinct from the opt-out filter.' );
		$this->assertSame( array(), $plan['unsupported_fields'], 'Rule 2 failing should not advertise unsupported-field noise.' );
		$this->assertNull( $plan['response'] );
	}

	/**
	 * @testdox Render-gate rule 2 positive: a page returning a valid interface can render modern.
	 */
	public function test_render_gate_rule_2_allows_page_with_interface(): void {
		$interface = $this->make_interface();
		$page      = $this->make_page_with_interface( 'tab_x', $interface );

		$settings = array(
			array(
				'type' => 'title',
				'id'   => 'group_one',
			),
			array(
				'id'   => 'setting_one',
				'type' => 'text',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'group_one',
			),
		);

		$plan = ReactSettingsSchema::get_screen_render_context( 'tab_x', '', $settings, $page );

		$this->assertTrue( $plan['should_render'] );
		$this->assertFalse( $plan['is_opted_out'] );
		$this->assertIsArray( $plan['response'] );
	}

	/**
	 * @testdox A raw null $settings_page fails rule 2 and does not render modern.
	 */
	public function test_render_gate_rule_2_blocks_null_settings_page(): void {
		$settings = array(
			array(
				'type' => 'title',
				'id'   => 'group_one',
			),
			array(
				'id'   => 'setting_one',
				'type' => 'text',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'group_one',
			),
		);

		$plan = ReactSettingsSchema::get_screen_render_context( 'tab_x', '', $settings, null );

		$this->assertFalse( $plan['should_render'] );
		$this->assertNull( $plan['response'] );
	}

	/**
	 * Build a scripted ReactSettingsPageInterface instance for a test.
	 *
	 * @param array<string, string>                                          $type_map      Extra type-map entries to return.
	 * @param array<int, string>                                             $supported     Extra supported types to return.
	 * @param array<int, array{label: string, value: string}>|null           $field_options Field options to return (or null to fall through).
	 */
	private function make_interface( array $type_map = array(), array $supported = array(), ?array $field_options = null ): ReactSettingsPageInterface {
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
	 * Build an anonymous WC_Settings_Page subclass whose get_react_settings_page()
	 * returns the supplied scripted interface.
	 *
	 * @param string                      $tab_id    Tab id to expose via `$this->id`.
	 * @param ReactSettingsPageInterface  $interface Scripted interface implementation.
	 */
	private function make_page_with_interface( string $tab_id, ReactSettingsPageInterface $interface ): WC_Settings_Page {
		return new class( $tab_id, $interface ) extends WC_Settings_Page {
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
			 * @param ReactSettingsPageInterface $interface Scripted interface.
			 */
			public function __construct( string $tab_id, ReactSettingsPageInterface $interface ) {
				$this->id    = $tab_id;
				$this->label = $tab_id;
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

	/**
	 * Build a bare WC_Settings_Page subclass with no interface override.
	 *
	 * @param string $tab_id Tab id to expose via `$this->id`.
	 */
	private function make_page_without_interface( string $tab_id ): WC_Settings_Page {
		return new class( $tab_id ) extends WC_Settings_Page {
			/**
			 * Constructor.
			 *
			 * @param string $tab_id Tab id.
			 */
			public function __construct( string $tab_id ) {
				$this->id    = $tab_id;
				$this->label = $tab_id;
				// Intentionally skip parent::__construct().
			}
		};
	}
}
