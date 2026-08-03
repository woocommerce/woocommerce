<?php
/**
 * SettingsUISchema tests.
 *
 * @package WooCommerce\Tests\Internal\Admin\Settings
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings;

use Automattic\WooCommerce\Internal\Admin\Settings\SettingsUISchema;
use WC_Unit_Test_Case;

/**
 * Tests for SettingsUISchema.
 */
class SettingsUISchemaTest extends WC_Unit_Test_Case {

	/**
	 * @testdox It includes page-level save and shell metadata.
	 */
	public function test_from_legacy_settings_includes_page_save_and_shell_metadata(): void {
		$schema = SettingsUISchema::from_legacy_settings(
			'test',
			'',
			'Test &amp; settings',
			array(),
			'none'
		);

		$this->assertSame(
			array( 'adapter' => 'none' ),
			$schema['save'],
			'The page-level save strategy should use the provided default adapter.'
		);
		$this->assertSame(
			array( 'title' => 'Test & settings' ),
			$schema['shell'],
			'The shell title should use the decoded page title.'
		);
		$this->assertSame( 'default', $schema['section'], 'The default section should remain the stable schema value.' );
	}

	/**
	 * @testdox It skips malformed settings entries.
	 */
	public function test_from_legacy_settings_skips_malformed_settings_entries(): void {
		$schema = SettingsUISchema::from_legacy_settings(
			'test',
			'',
			'Test settings',
			array(
				'not a setting',
				null,
				array(
					'id'    => 'woocommerce_test_text',
					'type'  => 'text',
					'title' => 'Test text',
				),
			)
		);

		$this->assertCount( 1, $schema['groups']['default']['fields'] );
		$this->assertSame( 'woocommerce_test_text', $schema['groups']['default']['fields'][0]['id'] );
	}

	/**
	 * @testdox It groups fields that appear before the first title marker.
	 */
	public function test_from_legacy_settings_creates_default_group_for_fields_before_title(): void {
		update_option( 'woocommerce_test_text', 'saved value' );

		$schema = SettingsUISchema::from_legacy_settings(
			'test',
			'',
			'Test settings',
			array(
				array(
					'id'    => 'woocommerce_test_text',
					'type'  => 'text',
					'title' => 'Test text',
				),
			)
		);

		$this->assertArrayHasKey( 'default', $schema['groups'] );
		$this->assertSame( 'default', array_key_first( $schema['groups'] ) );
		$this->assertArrayNotHasKey( 'order', $schema['groups']['default'], 'Internal group ordering should not leak into the schema.' );
		$this->assertSame( 'woocommerce_test_text', $schema['groups']['default']['fields'][0]['id'] );
		$this->assertSame( 'saved value', $schema['groups']['default']['fields'][0]['value'] );
	}

	/**
	 * @testdox It keeps component metadata with the field schema.
	 */
	public function test_from_legacy_settings_preserves_component_metadata(): void {
		$schema = SettingsUISchema::from_legacy_settings(
			'test',
			'advanced',
			'Test settings',
			array(
				array(
					'id'    => 'group',
					'type'  => 'title',
					'title' => 'Group',
				),
				array(
					'id'                => 'woocommerce_test_component',
					'type'              => 'multiselect',
					'title'             => 'Component field',
					'component'         => 'test/component',
					'custom_attributes' => array(
						'min'  => 1,
						'step' => 1,
					),
					'options'           => array(
						'a' => 'Option A',
					),
				),
			)
		);

		$field = $schema['groups']['group']['fields'][0];

		$this->assertSame( 'array', $field['type'] );
		$this->assertSame( 'test/component', $field['component'] );
		$this->assertSame(
			array(
				'min'  => 1,
				'step' => 1,
			),
			$field['customAttributes']
		);
		$this->assertSame(
			array(
				array(
					'label' => 'Option A',
					'value' => 'a',
				),
			),
			$field['options']
		);
	}

	/**
	 * @testdox It preserves sanitized group description markup and header actions.
	 */
	public function test_from_legacy_settings_preserves_group_description_and_actions(): void {
		$schema = SettingsUISchema::from_legacy_settings(
			'test',
			'advanced',
			'Test settings',
			array(
				array(
					'id'      => 'group',
					'type'    => 'title',
					'title'   => 'Group',
					'desc'    => 'Read the <a href="https://woocommerce.com">documentation</a><script>alert("x")</script>.',
					'actions' => array(
						array(
							'id'      => 'learn-more',
							'label'   => 'Learn more',
							'href'    => 'https://woocommerce.com/documentation',
							'variant' => 'secondary',
							'target'  => '_blank',
							'rel'     => 'noopener noreferrer',
						),
					),
				),
			)
		);

		$group = $schema['groups']['group'];

		$this->assertSame( 'Read the <a href="https://woocommerce.com">documentation</a>alert("x").', $group['description'] );
		$this->assertSame(
			array(
				array(
					'id'      => 'learn-more',
					'label'   => 'Learn more',
					'href'    => 'https://woocommerce.com/documentation',
					'variant' => 'secondary',
					'target'  => '_blank',
					'rel'     => 'noopener noreferrer',
				),
			),
			$group['actions']
		);
	}

	/**
	 * @testdox It uses checkbox descriptions as labels and desc_tip as help text.
	 */
	public function test_from_legacy_settings_uses_checkbox_desc_as_label(): void {
		$schema = SettingsUISchema::from_legacy_settings(
			'test',
			'',
			'Test settings',
			array(
				array(
					'id'       => 'woocommerce_test_checkbox',
					'type'     => 'checkbox',
					'title'    => 'Checkbox row',
					'desc'     => 'Enable the test option',
					'desc_tip' => 'This is help text.',
				),
			)
		);

		$field = $schema['groups']['default']['fields'][0];

		$this->assertSame( 'Enable the test option', $field['label'] );
		$this->assertSame( 'This is help text.', $field['description'] );
	}

	/**
	 * @testdox It does not render boolean desc_tip values as help text.
	 */
	public function test_from_legacy_settings_ignores_boolean_desc_tip(): void {
		$schema = SettingsUISchema::from_legacy_settings(
			'test',
			'',
			'Test settings',
			array(
				array(
					'id'       => 'woocommerce_test_select',
					'type'     => 'select',
					'title'    => 'Select field',
					'desc'     => 'Select help text.',
					'desc_tip' => true,
					'options'  => array(
						'a' => 'Option A',
					),
				),
			)
		);

		$this->assertSame( 'Select help text.', $schema['groups']['default']['fields'][0]['description'] );
	}

	/**
	 * @testdox It uses legacy field names for form POST save schema.
	 */
	public function test_from_legacy_settings_uses_field_name_for_form_post_save_schema(): void {
		$schema = SettingsUISchema::from_legacy_settings(
			'test',
			'',
			'Test settings',
			array(
				array(
					'id'         => 'woocommerce_test_nested',
					'type'       => 'text',
					'title'      => 'Nested field',
					'field_name' => 'woocommerce_test[nested]',
				),
			)
		);

		$this->assertSame(
			array(
				'adapter' => 'form_post',
				'name'    => 'woocommerce_test[nested]',
			),
			$schema['groups']['default']['fields'][0]['save']
		);
	}

	/**
	 * @testdox It sanitizes info field text and marks info fields as non-saving.
	 */
	public function test_from_legacy_settings_sanitizes_info_field_text_and_marks_info_fields_as_non_saving(): void {
		$schema = SettingsUISchema::from_legacy_settings(
			'test',
			'',
			'Test settings',
			array(
				array(
					'id'   => 'woocommerce_test_info',
					'type' => 'info',
					'text' => 'Read-only <strong>information</strong><script>alert("x")</script>.',
				),
			)
		);

		$field = $schema['groups']['default']['fields'][0];

		$this->assertSame( 'Read-only <strong>information</strong>alert("x").', $field['description'] );
		$this->assertSame( array( 'adapter' => 'none' ), $field['save'] );
	}

	/**
	 * @testdox It keeps info fields non-saving when their description comes from desc.
	 */
	public function test_from_legacy_settings_marks_info_fields_with_descriptions_as_non_saving(): void {
		$schema = SettingsUISchema::from_legacy_settings(
			'test',
			'',
			'Test settings',
			array(
				array(
					'id'   => 'woocommerce_test_info',
					'type' => 'info',
					'desc' => 'Read-only information.',
				),
			)
		);

		$field = $schema['groups']['default']['fields'][0];

		$this->assertSame( 'Read-only information.', $field['description'] );
		$this->assertSame( array( 'adapter' => 'none' ), $field['save'] );
		SettingsUISchema::assert_valid_schema( $schema );
	}

	/**
	 * @testdox It preserves both legacy descriptions and string desc_tip values.
	 */
	public function test_from_legacy_settings_preserves_desc_and_string_desc_tip(): void {
		$schema = SettingsUISchema::from_legacy_settings(
			'test',
			'',
			'Test settings',
			array(
				array(
					'id'       => 'woocommerce_test_text',
					'type'     => 'text',
					'title'    => 'Text field',
					'desc'     => 'Visible help text.',
					'desc_tip' => 'Tooltip help text.',
				),
			)
		);

		$this->assertSame( 'Visible help text.<br />Tooltip help text.', $schema['groups']['default']['fields'][0]['description'] );
	}

	/**
	 * @testdox It adds visibility metadata for legacy checkbox groups and stock fields.
	 */
	public function test_from_legacy_settings_adds_visibility_metadata_for_legacy_conditional_fields(): void {
		$schema = SettingsUISchema::from_legacy_settings(
			'test',
			'',
			'Test settings',
			array(
				array(
					'id'              => 'woocommerce_enable_reviews',
					'type'            => 'checkbox',
					'desc'            => 'Enable product reviews',
					'checkboxgroup'   => 'start',
					'show_if_checked' => 'option',
				),
				array(
					'id'              => 'woocommerce_review_rating_required',
					'type'            => 'checkbox',
					'desc'            => 'Star ratings should be required',
					'checkboxgroup'   => 'end',
					'show_if_checked' => 'yes',
				),
				array(
					'id'    => 'woocommerce_hold_stock_minutes',
					'type'  => 'number',
					'title' => 'Hold stock',
					'class' => 'manage_stock_field',
				),
			)
		);

		$fields = $schema['groups']['default']['fields'];

		$this->assertSame(
			array(
				'controller' => 'woocommerce_enable_reviews',
				'value'      => true,
			),
			$fields[1]['visibility']
		);
		$this->assertSame(
			array(
				'controller' => 'woocommerce_manage_stock',
				'value'      => true,
			),
			$fields[2]['visibility']
		);
	}

	/**
	 * @testdox It sanitizes custom attribute keys and option labels.
	 */
	public function test_from_legacy_settings_sanitizes_custom_attribute_keys_and_option_labels(): void {
		$schema = SettingsUISchema::from_legacy_settings(
			'test',
			'',
			'Test settings',
			array(
				array(
					'id'                => 'woocommerce_test_select',
					'type'              => 'select',
					'title'             => 'Select field',
					'custom_attributes' => array(
						'onChange' => 'alert(1)',
						'min'      => 1,
					),
					'options'           => array(
						'a' => '<strong>Option A</strong>',
					),
				),
			)
		);

		$field = $schema['groups']['default']['fields'][0];

		$this->assertSame(
			array(
				'onchange' => 'alert(1)',
				'min'      => 1,
			),
			$field['customAttributes']
		);
		$this->assertSame( 'Option A', $field['options'][0]['label'] );
	}

	/**
	 * @testdox It canonicalizes scalar option values and the selected value to strings.
	 */
	public function test_canonicalize_option_values_stringifies_scalar_option_values(): void {
		$this->setExpectedIncorrectUsage( SettingsUISchema::class . '::canonicalize_option_values' );

		$schema = SettingsUISchema::canonicalize_option_values(
			$this->get_native_schema_with_field(
				array(
					'id'      => 'acme_tier',
					'type'    => 'select',
					'value'   => 1,
					'options' => array(
						array(
							'label' => 'One',
							'value' => 1,
						),
						array(
							'label' => 'Enabled',
							'value' => true,
						),
					),
				)
			)
		);

		$field = $schema['groups']['main']['fields'][0];

		$this->assertSame( '1', $field['value'] );
		$this->assertSame( array( '1', 'true' ), array_column( $field['options'], 'value' ), 'Boolean option values should convert like the client String() coercion, not the PHP string cast.' );
	}

	/**
	 * @testdox It canonicalizes boolean values to the strings the client String() coercion produced before conversion.
	 */
	public function test_canonicalize_option_values_matches_client_coercion_for_booleans(): void {
		$this->setExpectedIncorrectUsage( SettingsUISchema::class . '::canonicalize_option_values' );

		$string_options = array(
			array(
				'label' => 'On',
				'value' => 'true',
			),
			array(
				'label' => 'Off',
				'value' => 'false',
			),
		);

		$schema = SettingsUISchema::canonicalize_option_values(
			$this->get_native_schema_with_fields(
				array(
					array(
						'id'      => 'acme_enabled',
						'type'    => 'select',
						'value'   => true,
						'options' => $string_options,
					),
					array(
						'id'      => 'acme_disabled',
						'type'    => 'select',
						'value'   => false,
						'options' => $string_options,
					),
				)
			)
		);

		$fields = $schema['groups']['main']['fields'];

		$this->assertSame( 'true', $fields[0]['value'], 'A true value must keep matching the string option the client matched before canonicalization.' );
		$this->assertSame( 'false', $fields[1]['value'], 'A false value must not collapse to the empty no-selection string.' );
		$this->assertSame( array( 'true', 'false' ), array_column( $fields[0]['options'], 'value' ), 'String option values should pass through unchanged.' );
	}

	/**
	 * @testdox It canonicalizes float option values locale-independently.
	 */
	public function test_canonicalize_option_values_stringifies_float_values(): void {
		$this->setExpectedIncorrectUsage( SettingsUISchema::class . '::canonicalize_option_values' );

		$schema = SettingsUISchema::canonicalize_option_values(
			$this->get_native_schema_with_field(
				array(
					'id'      => 'acme_rate',
					'type'    => 'select',
					'value'   => 1.5,
					'options' => array(
						array(
							'label' => 'Half',
							'value' => 0.5,
						),
						array(
							'label' => 'One and a half',
							'value' => 1.5,
						),
					),
				)
			)
		);

		$field = $schema['groups']['main']['fields'][0];

		$this->assertSame( '1.5', $field['value'] );
		$this->assertSame( array( '0.5', '1.5' ), array_column( $field['options'], 'value' ), 'Float option values should convert with a dot decimal separator in any locale, matching the client String() coercion.' );
	}

	/**
	 * @testdox It canonicalizes scalar members of a multiselect value list.
	 */
	public function test_canonicalize_option_values_stringifies_value_lists(): void {
		$this->setExpectedIncorrectUsage( SettingsUISchema::class . '::canonicalize_option_values' );

		$schema = SettingsUISchema::canonicalize_option_values(
			$this->get_native_schema_with_field(
				array(
					'id'      => 'acme_tiers',
					'type'    => 'array',
					'value'   => array( 1, '2' ),
					'options' => array(
						array(
							'label' => 'One',
							'value' => 1,
						),
						array(
							'label' => 'Two',
							'value' => 2,
						),
					),
				)
			)
		);

		$field = $schema['groups']['main']['fields'][0];

		$this->assertSame( array( '1', '2' ), $field['value'] );
		$this->assertSame( array( '1', '2' ), array_column( $field['options'], 'value' ) );
	}

	/**
	 * @testdox It leaves schemas with string option values untouched.
	 */
	public function test_canonicalize_option_values_leaves_canonical_schemas_untouched(): void {
		$schema = $this->get_native_schema_with_field(
			array(
				'id'      => 'acme_tier',
				'type'    => 'select',
				'value'   => '1',
				'options' => array(
					array(
						'label' => 'One',
						'value' => '1',
					),
				),
			)
		);

		$this->assertSame( $schema, SettingsUISchema::canonicalize_option_values( $schema ), 'Canonical schemas should pass through unchanged without a doing-it-wrong notice.' );
	}

	/**
	 * @testdox It leaves malformed option entries and values unchanged.
	 */
	public function test_canonicalize_option_values_leaves_malformed_entries_unchanged(): void {
		$schema = $this->get_native_schema_with_field(
			array(
				'id'      => 'acme_tier',
				'type'    => 'select',
				'value'   => new \stdClass(),
				'options' => array(
					array(
						'label' => 'Nested',
						'value' => array( 'not-scalar' ),
					),
					'not-an-array',
				),
			)
		);

		$this->assertEquals( $schema, SettingsUISchema::canonicalize_option_values( $schema ), 'Malformed entries should pass through for the provider to fix.' );
	}

	/**
	 * @testdox It canonicalizes visibility values compared against an options field.
	 */
	public function test_canonicalize_option_values_stringifies_visibility_values_for_option_controllers(): void {
		$this->setExpectedIncorrectUsage( SettingsUISchema::class . '::canonicalize_option_values' );

		$schema = SettingsUISchema::canonicalize_option_values(
			$this->get_native_schema_with_fields(
				array(
					array(
						'id'      => 'acme_tier',
						'type'    => 'select',
						'value'   => 1,
						'options' => array(
							array(
								'label' => 'One',
								'value' => 1,
							),
							array(
								'label' => 'Two',
								'value' => 2,
							),
						),
					),
					array(
						'id'         => 'acme_tier_notes',
						'type'       => 'text',
						'value'      => '',
						'visibility' => array(
							'controller' => 'acme_tier',
							'value'      => 1,
						),
					),
					array(
						'id'         => 'acme_tier_badge',
						'type'       => 'text',
						'value'      => '',
						'visibility' => array(
							'controller' => 'acme_tier',
							'value'      => array( 1, true ),
						),
					),
				)
			)
		);

		$fields = $schema['groups']['main']['fields'];

		$this->assertSame( '1', $fields[1]['visibility']['value'], 'Scalar visibility values must convert with the controller value they are compared against.' );
		$this->assertSame( array( '1', 'true' ), $fields[2]['visibility']['value'], 'Visibility value lists must convert with the controller value they are compared against.' );
	}

	/**
	 * @testdox It leaves visibility value lists containing a non-scalar member unchanged.
	 */
	public function test_canonicalize_option_values_leaves_visibility_value_lists_with_non_scalar_members_unchanged(): void {
		$schema = $this->get_native_schema_with_fields(
			array(
				array(
					'id'      => 'acme_tier',
					'type'    => 'select',
					'value'   => '1',
					'options' => array(
						array(
							'label' => 'One',
							'value' => '1',
						),
					),
				),
				array(
					'id'         => 'acme_tier_notes',
					'type'       => 'text',
					'value'      => '',
					'visibility' => array(
						'controller' => 'acme_tier',
						'value'      => array( 1, array( 'not-scalar' ) ),
					),
				),
			)
		);

		$this->assertSame( $schema, SettingsUISchema::canonicalize_option_values( $schema ), 'Visibility value lists with a non-scalar member should pass through whole, scalar members included, for the provider to fix.' );
	}

	/**
	 * @testdox It leaves visibility values unchanged when the controller has no options.
	 */
	public function test_canonicalize_option_values_leaves_visibility_values_for_non_option_controllers(): void {
		$schema = $this->get_native_schema_with_fields(
			array(
				array(
					'id'    => 'acme_enabled',
					'type'  => 'checkbox',
					'value' => true,
				),
				array(
					'id'         => 'acme_enabled_notes',
					'type'       => 'text',
					'value'      => '',
					'visibility' => array(
						'controller' => 'acme_enabled',
						'value'      => true,
					),
				),
			)
		);

		$this->assertSame( $schema, SettingsUISchema::canonicalize_option_values( $schema ), 'Checkbox controllers compare boolean values, so their visibility rules should pass through unchanged.' );
	}

	/**
	 * @testdox It leaves associative value arrays unchanged.
	 */
	public function test_canonicalize_option_values_leaves_associative_values_unchanged(): void {
		$schema = $this->get_native_schema_with_field(
			array(
				'id'      => 'acme_tiers',
				'type'    => 'array',
				'value'   => array( 'tier' => 1 ),
				'options' => array(
					array(
						'label' => 'One',
						'value' => '1',
					),
				),
			)
		);

		$this->assertSame( $schema, SettingsUISchema::canonicalize_option_values( $schema ), 'Associative value arrays should pass through unreindexed for the provider to fix.' );
	}

	/**
	 * @testdox It accepts every field type supported by the current renderer.
	 *
	 * @dataProvider supported_field_types
	 *
	 * @param string $type Field type.
	 * @param mixed  $value Field value.
	 */
	public function test_assert_valid_schema_accepts_supported_field_types( string $type, $value ): void {
		$field = array(
			'id'    => 'acme_' . str_replace( '-', '_', $type ),
			'label' => 'Acme field',
			'type'  => $type,
			'value' => $value,
			'save'  => array( 'adapter' => 'form_post' ),
		);

		if ( in_array( $type, array( 'array', 'radio', 'select' ), true ) ) {
			$field['options'] = array(
				array(
					'label' => 'Option A',
					'value' => 'a',
				),
			);
		}

		if ( 'info' === $type ) {
			unset( $field['value'] );
			$field['save'] = array( 'adapter' => 'none' );
		}

		SettingsUISchema::assert_valid_schema( $this->get_native_schema_with_field( $field ) );
		$this->addToAssertionCount( 1 );
	}

	/**
	 * Supported field type fixtures.
	 *
	 * @return array<string, array{string, mixed}>
	 */
	public static function supported_field_types(): array {
		return array(
			'array'          => array( 'array', array( 'a' ) ),
			'checkbox'       => array( 'checkbox', true ),
			'date'           => array( 'date', '2026-08-03' ),
			'datetime-local' => array( 'datetime-local', '2026-08-03T12:30' ),
			'email'          => array( 'email', 'merchant@example.com' ),
			'info'           => array( 'info', null ),
			'number'         => array( 'number', '02' ),
			'password'       => array( 'password', 'secret' ),
			'radio'          => array( 'radio', 'a' ),
			'select'         => array( 'select', 'a' ),
			'tel'            => array( 'tel', '+1 555 555 5555' ),
			'text'           => array( 'text', 'Acme' ),
			'textarea'       => array( 'textarea', 'Acme description' ),
			'time'           => array( 'time', '12:30' ),
			'url'            => array( 'url', 'https://example.com' ),
		);
	}

	/**
	 * @testdox It normalizes every legacy field type alias before validation.
	 *
	 * @dataProvider legacy_field_type_aliases
	 *
	 * @param string $legacy_type Legacy field type.
	 * @param string $canonical_type Canonical field type.
	 */
	public function test_from_legacy_settings_normalizes_aliases_before_validation( string $legacy_type, string $canonical_type ): void {
		$schema = SettingsUISchema::from_legacy_settings(
			'acme',
			'',
			'Acme',
			array(
				array(
					'id'      => 'acme_field',
					'label'   => 'Acme field',
					'type'    => $legacy_type,
					'value'   => 'a',
					'options' => array( 'a' => 'Option A' ),
				),
			)
		);

		$this->assertSame( $canonical_type, $schema['groups']['default']['fields'][0]['type'] );
		SettingsUISchema::assert_valid_schema( $schema );
	}

	/**
	 * Legacy field type aliases.
	 *
	 * @return array<string, array{string, string}>
	 */
	public static function legacy_field_type_aliases(): array {
		return array(
			'multiselect'            => array( 'multiselect', 'array' ),
			'multi_select_countries' => array( 'multi_select_countries', 'array' ),
			'single_select_country'  => array( 'single_select_country', 'select' ),
			'single_select_page'     => array( 'single_select_page', 'select' ),
		);
	}

	/**
	 * @testdox It rejects malformed schemas with a precise boundary reason.
	 *
	 * @dataProvider invalid_schemas
	 *
	 * @param array  $schema Invalid schema.
	 * @param string $reason Expected exception message.
	 */
	public function test_assert_valid_schema_rejects_malformed_schemas( array $schema, string $reason ): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( $reason );

		SettingsUISchema::assert_valid_schema( $schema );
	}

	/**
	 * Invalid schema fixtures.
	 *
	 * @return array<string, array{array, string}>
	 */
	public static function invalid_schemas(): array {
		$valid = self::get_valid_schema_for_validation();

		$unknown_type                                        = $valid;
		$unknown_type['groups']['main']['fields'][0]['type'] = 'custom';
		$duplicate_id                                        = $valid;
		$duplicate_id['groups']['main']['fields'][]          = $duplicate_id['groups']['main']['fields'][0];
		$group_field_collision                               = $valid;
		$group_field_collision['groups']['main']['fields'][0]['id'] = 'main';
		$empty_schema_id                                        = $valid;
		$empty_schema_id['id']                                  = '';
		$malformed_group                                        = $valid;
		$malformed_group['groups']['main']['fields']            = 'invalid';
		$missing_options                                        = $valid;
		$missing_options['groups']['main']['fields'][0]['type'] = 'select';
		$invalid_option = $missing_options;
		$invalid_option['groups']['main']['fields'][0]['options'] = array(
			array(
				'label' => 'One',
				'value' => 1,
			),
		);
		$invalid_component                                        = $valid;
		$invalid_component['groups']['main']['fields'][0]['component'] = '';
		$invalid_field_save                                        = $valid;
		$invalid_field_save['groups']['main']['fields'][0]['save'] = array( 'adapter' => 'custom' );
		$invalid_visibility                                        = $valid;
		$invalid_visibility['groups']['main']['fields'][0]['visibility'] = array( 'controller' => 'missing' );
		$invalid_bound = $valid;
		$invalid_bound['groups']['main']['fields'][0]['customAttributes'] = array( 'min' => 1 );
		$invalid_info                                        = $valid;
		$invalid_info['groups']['main']['fields'][0]['type'] = 'info';
		$invalid_shell                                       = $valid;
		$invalid_shell['shell']['navigation']                = array(
			array(
				'id'    => 'general',
				'label' => 'General',
			),
		);
		$invalid_breadcrumb                                  = $valid;
		$invalid_breadcrumb['shell']['breadcrumbs']          = array( array( 'label' => 1 ) );
		$invalid_badge                                       = $valid;
		$invalid_badge['shell']['badges']                    = array(
			array(
				'label'  => 'Beta',
				'intent' => 'purple',
			),
		);
		$invalid_action                                      = $valid;
		$invalid_action['groups']['main']['actions']         = array(
			array(
				'id'    => '',
				'label' => 'Docs',
				'href'  => 'https://example.com',
			),
		);
		$invalid_page_save                                   = $valid;
		$invalid_page_save['save']                           = array( 'adapter' => 'custom' );
		$invalid_navigation_component                        = $valid;
		$invalid_navigation_component['shell']['navigationComponent'] = '';
		$invalid_group_map                    = $valid;
		$invalid_group_map['groups']['other'] = $invalid_group_map['groups']['main'];
		unset( $invalid_group_map['groups']['main'] );

		return array(
			'unknown field type'          => array( $unknown_type, 'Field "acme_field" has unsupported type "custom".' ),
			'duplicate field id'          => array( $duplicate_id, 'Field id "acme_field" is duplicated.' ),
			'group and field collision'   => array( $group_field_collision, 'Field id "main" collides with a group id.' ),
			'empty schema id'             => array( $empty_schema_id, 'Schema id must be a non-empty string.' ),
			'malformed group fields'      => array( $malformed_group, 'Group "main" fields must be a list.' ),
			'missing choice options'      => array( $missing_options, 'Field "acme_field" of type "select" must define a non-empty options list.' ),
			'non-string option value'     => array( $invalid_option, 'Field "acme_field" option 0 value must be a string.' ),
			'empty component name'        => array( $invalid_component, 'Field "acme_field" component must be a non-empty string.' ),
			'unsupported field save'      => array( $invalid_field_save, 'Field "acme_field" save adapter must be "form_post" or "none".' ),
			'missing visibility control'  => array( $invalid_visibility, 'Field "acme_field" visibility controller "missing" does not reference a field.' ),
			'bound on text field'         => array( $invalid_bound, 'Field "acme_field" may define "min" only when its type is "number".' ),
			'saving info field'           => array( $invalid_info, 'Field "acme_field" of type "info" must use the "none" save adapter.' ),
			'malformed shell navigation'  => array( $invalid_shell, 'Shell navigation item 0 href must be a string.' ),
			'malformed breadcrumb'        => array( $invalid_breadcrumb, 'Shell breadcrumb 0 label must be a string.' ),
			'invalid badge intent'        => array( $invalid_badge, 'Shell badge 0 intent "purple" is not supported.' ),
			'empty group action id'       => array( $invalid_action, 'Group "main" action 0 id must be a non-empty string.' ),
			'custom save without handler' => array( $invalid_page_save, 'Schema custom save strategy must define a non-empty handler.' ),
			'empty navigation component'  => array( $invalid_navigation_component, 'Shell navigationComponent must be a non-empty string.' ),
			'group map id mismatch'       => array( $invalid_group_map, 'Group map key "other" must match group id "main".' ),
		);
	}

	/**
	 * @testdox It accepts valid optional shell, field, visibility, action, and save metadata.
	 */
	public function test_assert_valid_schema_accepts_optional_metadata(): void {
		$schema                              = self::get_valid_schema_for_validation();
		$schema['save']                      = array(
			'adapter' => 'custom',
			'handler' => 'acme/save',
		);
		$schema['shell']                     = array(
			'header'              => 'visible',
			'title'               => 'Acme settings',
			'subtitle'            => 'Configure Acme.',
			'breadcrumbs'         => array(
				array(
					'label' => 'Settings',
					'href'  => 'https://example.com/settings',
				),
			),
			'badges'              => array(
				array(
					'label'  => 'Beta',
					'intent' => 'info',
				),
			),
			'navigation'          => array(
				array(
					'id'     => 'general',
					'label'  => 'General',
					'href'   => 'https://example.com/general',
					'active' => true,
				),
			),
			'sectionNavigation'   => array(),
			'navigationComponent' => 'acme/navigation',
		);
		$schema['groups']['main']['actions'] = array(
			array(
				'id'      => 'docs',
				'label'   => 'Documentation',
				'href'    => 'https://example.com/docs',
				'variant' => 'link',
				'target'  => '_blank',
				'rel'     => 'noopener',
			),
		);
		$schema['groups']['main']['fields'][0]['component']  = 'acme/text';
		$schema['groups']['main']['fields'][0]['visibility'] = array(
			'controller' => 'acme_enabled',
			'value'      => array( true, false ),
		);
		$schema['groups']['main']['fields'][]                = array(
			'id'    => 'acme_enabled',
			'label' => 'Enabled',
			'type'  => 'checkbox',
			'value' => true,
			'save'  => array(
				'adapter' => 'form_post',
				'name'    => 'acme_enabled',
			),
		);

		SettingsUISchema::assert_valid_schema( $schema );
		$this->addToAssertionCount( 1 );
	}

	/**
	 * Build a valid schema for validation tests.
	 *
	 * @return array
	 */
	private static function get_valid_schema_for_validation(): array {
		return array(
			'id'      => 'acme',
			'title'   => 'Acme',
			'section' => 'general',
			'save'    => array( 'adapter' => 'form_post' ),
			'shell'   => array(
				'header' => 'hidden',
				'title'  => 'Acme',
			),
			'groups'  => array(
				'main' => array(
					'id'          => 'main',
					'title'       => 'Main',
					'description' => 'Main settings.',
					'actions'     => array(),
					'fields'      => array(
						array(
							'id'          => 'acme_field',
							'label'       => 'Acme field',
							'type'        => 'text',
							'description' => 'A text field.',
							'value'       => 'Acme',
							'save'        => array(
								'adapter' => 'form_post',
								'name'    => 'acme_field',
							),
						),
					),
				),
			),
		);
	}

	/**
	 * Build a minimal native schema with one field.
	 *
	 * @param array $field Field definition.
	 * @return array
	 */
	private function get_native_schema_with_field( array $field ): array {
		return $this->get_native_schema_with_fields( array( $field ) );
	}

	/**
	 * Build a minimal native schema with the given fields.
	 *
	 * @param array $fields Field definitions.
	 * @return array
	 */
	private function get_native_schema_with_fields( array $fields ): array {
		return array(
			'id'     => 'acme',
			'title'  => 'Acme',
			'groups' => array(
				'main' => array(
					'id'     => 'main',
					'fields' => $fields,
				),
			),
		);
	}
}
