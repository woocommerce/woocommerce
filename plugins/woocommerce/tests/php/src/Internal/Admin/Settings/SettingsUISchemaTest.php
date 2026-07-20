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
				'adapter'      => 'form_post',
				'name'         => 'woocommerce_test[nested]',
				'initialValue' => '',
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
	 * @testdox It emits canonical numeric values, integer types, and numeric validation.
	 */
	public function test_from_legacy_settings_normalizes_numeric_fields(): void {
		$schema = SettingsUISchema::from_legacy_settings(
			'test',
			'',
			'Test settings',
			array(
				array(
					'id'                => 'woocommerce_test_integer',
					'type'              => 'number',
					'title'             => 'Integer field',
					'value'             => '02',
					'custom_attributes' => array(
						'max'  => '10',
						'step' => '1',
					),
				),
				array(
					'id'                => 'woocommerce_test_decimal',
					'type'              => 'number',
					'title'             => 'Decimal field',
					'value'             => '2.5',
					'custom_attributes' => array(
						'min'  => '0.5',
						'max'  => 5,
						'step' => '0.5',
					),
				),
				array(
					'id'                => 'woocommerce_test_fractional_base',
					'type'              => 'number',
					'title'             => 'Fractional base field',
					'value'             => '1.5',
					'custom_attributes' => array( 'step' => '1' ),
				),
			)
		);

		$integer         = $schema['groups']['default']['fields'][0];
		$decimal         = $schema['groups']['default']['fields'][1];
		$fractional_base = $schema['groups']['default']['fields'][2];

		$this->assertSame( 'integer', $integer['type'] );
		$this->assertSame( 2, $integer['value'] );
		$this->assertSame( array( 'max' => 10 ), $integer['validation'] );
		$this->assertSame( '02', $integer['save']['initialValue'] );
		$this->assertSame( 'number', $decimal['type'] );
		$this->assertSame( 2.5, $decimal['value'] );
		$this->assertSame(
			array(
				'min' => 0.5,
				'max' => 5,
			),
			$decimal['validation']
		);
		$this->assertSame( '2.5', $decimal['save']['initialValue'] );
		$this->assertSame( 'number', $fractional_base['type'] );
		$this->assertSame( 1.5, $fractional_base['value'] );
	}

	/**
	 * @testdox It represents empty numeric settings as null.
	 */
	public function test_from_legacy_settings_normalizes_empty_numeric_values(): void {
		$field = $this->transform_field(
			array(
				'id'    => 'woocommerce_test_number',
				'type'  => 'number',
				'value' => '',
			)
		);

		$this->assertNull( $field['value'] );
		$this->assertSame( '', $field['save']['initialValue'] );
	}

	/**
	 * @testdox It rejects invalid numeric settings.
	 */
	public function test_from_legacy_settings_rejects_invalid_numeric_values(): void {
		$this->expectException( \UnexpectedValueException::class );
		$this->expectExceptionMessage( 'woocommerce_test_number' );

		$this->transform_field(
			array(
				'id'    => 'woocommerce_test_number',
				'type'  => 'number',
				'value' => 'not-a-number',
			)
		);
	}

	/**
	 * @testdox It accepts positive and negative JavaScript safe integer boundaries from legacy settings.
	 */
	public function test_from_legacy_settings_accepts_safe_integer_boundaries(): void {
		$schema = SettingsUISchema::from_legacy_settings(
			'test',
			'',
			'Test settings',
			array(
				array(
					'id'                => 'maximum_safe_integer',
					'type'              => 'number',
					'title'             => 'Maximum safe integer',
					'value'             => '9007199254740991',
					'custom_attributes' => array(
						'min'  => '-9007199254740991',
						'max'  => '9007199254740991',
						'step' => 1,
					),
				),
				array(
					'id'                => 'minimum_safe_integer',
					'type'              => 'number',
					'title'             => 'Minimum safe integer',
					'value'             => '-9007199254740991',
					'custom_attributes' => array( 'step' => 1 ),
				),
			)
		);

		$fields = $schema['groups']['default']['fields'];
		$this->assertSame( 9007199254740991, $fields[0]['value'] );
		$this->assertSame(
			array(
				'min' => -9007199254740991,
				'max' => 9007199254740991,
			),
			$fields[0]['validation']
		);
		$this->assertSame( -9007199254740991, $fields[1]['value'] );
	}

	/**
	 * @testdox It rejects legacy integer strings outside JavaScript's safe range before rounding.
	 * @dataProvider unsafe_integer_string_provider
	 *
	 * @param string $value Unsafe integer string.
	 */
	public function test_from_legacy_settings_rejects_unsafe_integer_strings( string $value ): void {
		$this->expectException( \UnexpectedValueException::class );

		$this->transform_field(
			array(
				'id'                => 'woocommerce_test_integer',
				'type'              => 'number',
				'value'             => $value,
				'custom_attributes' => array( 'step' => 1 ),
			)
		);
	}

	/**
	 * Provide unsafe integer strings.
	 *
	 * @return array<string, array{string}>
	 */
	public function unsafe_integer_string_provider(): array {
		return array(
			'positive value' => array( '9007199254740993' ),
			'negative value' => array( '-9007199254740993' ),
		);
	}

	/**
	 * @testdox It rejects legacy validation bounds outside JavaScript's safe integer range before rounding.
	 * @dataProvider unsafe_legacy_validation_bound_provider
	 *
	 * @param string $rule Validation rule.
	 * @param string $value Unsafe validation bound.
	 */
	public function test_from_legacy_settings_rejects_unsafe_validation_bounds( string $rule, string $value ): void {
		$this->expectException( \UnexpectedValueException::class );

		$this->transform_field(
			array(
				'id'                => 'woocommerce_test_number',
				'type'              => 'number',
				'value'             => '1',
				'custom_attributes' => array( $rule => $value ),
			)
		);
	}

	/**
	 * Provide unsafe legacy numeric validation bounds.
	 *
	 * @return array<string, array{string, string}>
	 */
	public function unsafe_legacy_validation_bound_provider(): array {
		return array(
			'positive maximum' => array( 'max', '9007199254740993' ),
			'negative minimum' => array( 'min', '-9007199254740993' ),
		);
	}

	/**
	 * @testdox It converts local datetimes to ISO while retaining the original form value.
	 */
	public function test_from_legacy_settings_normalizes_local_datetime_values(): void {
		$original_timezone = get_option( 'timezone_string' );
		update_option( 'timezone_string', 'America/New_York' );

		try {
			$field = $this->transform_field(
				array(
					'id'    => 'woocommerce_test_datetime',
					'type'  => 'datetime-local',
					'value' => '2026-07-17T13:30:45',
				)
			);
		} finally {
			update_option( 'timezone_string', $original_timezone );
		}

		$this->assertSame( '2026-07-17T17:30:45+00:00', $field['value'] );
		$this->assertSame( '2026-07-17T13:30:45', $field['save']['initialValue'] );
	}

	/**
	 * @testdox It keeps local datetimes inside a DST gap as the shifted instant.
	 */
	public function test_from_legacy_settings_accepts_dst_gap_datetime_values(): void {
		$original_timezone = get_option( 'timezone_string' );
		update_option( 'timezone_string', 'America/New_York' );

		try {
			$field = $this->transform_field(
				array(
					'id'    => 'woocommerce_test_datetime',
					'type'  => 'datetime-local',
					'value' => '2026-03-08T02:30',
				)
			);
		} finally {
			update_option( 'timezone_string', $original_timezone );
		}

		// 02:30 does not exist on this date in New York; PHP parses it as 03:30 EDT.
		$this->assertSame( '2026-03-08T07:30:00+00:00', $field['value'] );
		$this->assertSame( '2026-03-08T02:30', $field['save']['initialValue'] );
	}

	/**
	 * @testdox It rejects invalid local datetime settings.
	 */
	public function test_from_legacy_settings_rejects_invalid_datetime_values(): void {
		$this->expectException( \UnexpectedValueException::class );
		$this->expectExceptionMessage( 'woocommerce_test_datetime' );

		$this->transform_field(
			array(
				'id'    => 'woocommerce_test_datetime',
				'type'  => 'datetime-local',
				'value' => 'not-a-date',
			)
		);
	}

	/**
	 * @testdox It normalizes array members and their original form values to strings.
	 */
	public function test_from_legacy_settings_normalizes_array_values(): void {
		$field = $this->transform_field(
			array(
				'id'    => 'woocommerce_test_array',
				'type'  => 'multiselect',
				'value' => array( 'one', 2 ),
			)
		);

		$this->assertSame( array( 'one', '2' ), $field['value'] );
		$this->assertSame( array( 'one', '2' ), $field['save']['initialValue'] );
	}

	/**
	 * @testdox It preserves already canonical field values during legacy compatibility normalization.
	 */
	public function test_canonicalize_legacy_values_preserves_canonical_values(): void {
		$schema = $this->get_schema(
			array(
				array(
					'id'    => 'enabled',
					'label' => 'Enabled',
					'type'  => 'checkbox',
					'value' => true,
				),
				array(
					'id'    => 'choices',
					'label' => 'Choices',
					'type'  => 'array',
					'value' => array( 'one', 'two' ),
				),
				array(
					'id'    => 'count',
					'label' => 'Count',
					'type'  => 'integer',
					'value' => 2,
				),
				array(
					'id'    => 'amount',
					'label' => 'Amount',
					'type'  => 'number',
					'value' => 2.5,
				),
				array(
					'id'    => 'starts_at',
					'label' => 'Starts at',
					'type'  => 'datetime-local',
					'value' => '2026-07-17T17:30:45+00:00',
				),
			)
		);

		$this->assertSame( $schema, SettingsUISchema::canonicalize_legacy_values( $schema ) );
	}

	/**
	 * @testdox It canonicalizes explicitly supported legacy field values with one developer notice per schema.
	 */
	public function test_canonicalize_legacy_values_converts_supported_values_with_one_notice(): void {
		$this->setExpectedIncorrectUsage( SettingsUISchema::class . '::canonicalize_legacy_values' );

		$notices  = array();
		$listener = static function ( $function_name, $message ) use ( &$notices ): void {
			if ( SettingsUISchema::class . '::canonicalize_legacy_values' === $function_name ) {
				$notices[] = $message;
			}
		};
		add_action( 'doing_it_wrong_run', $listener, 10, 2 );

		$original_timezone = get_option( 'timezone_string' );
		update_option( 'timezone_string', 'America/New_York' );

		try {
			$schema = SettingsUISchema::canonicalize_legacy_values(
				$this->get_schema(
					array(
						array(
							'id'    => 'yes',
							'label' => 'Yes',
							'type'  => 'checkbox',
							'value' => 'yes',
						),
						array(
							'id'    => 'no',
							'label' => 'No',
							'type'  => 'checkbox',
							'value' => 'no',
						),
						array(
							'id'    => 'true',
							'label' => 'True',
							'type'  => 'checkbox',
							'value' => 'true',
						),
						array(
							'id'    => 'false',
							'label' => 'False',
							'type'  => 'checkbox',
							'value' => 'false',
						),
						array(
							'id'    => 'one_string',
							'label' => 'One string',
							'type'  => 'checkbox',
							'value' => '1',
						),
						array(
							'id'    => 'zero_string',
							'label' => 'Zero string',
							'type'  => 'checkbox',
							'value' => '0',
						),
						array(
							'id'    => 'one_integer',
							'label' => 'One integer',
							'type'  => 'checkbox',
							'value' => 1,
						),
						array(
							'id'    => 'zero_integer',
							'label' => 'Zero integer',
							'type'  => 'checkbox',
							'value' => 0,
						),
						array(
							'id'    => 'empty',
							'label' => 'Empty',
							'type'  => 'checkbox',
							'value' => '',
						),
						array(
							'id'    => 'choices',
							'label' => 'Choices',
							'type'  => 'array',
							'value' => array( 'one', 2, true, 2.5 ),
						),
						array(
							'id'    => 'count',
							'label' => 'Count',
							'type'  => 'integer',
							'value' => '2',
						),
						array(
							'id'    => 'amount',
							'label' => 'Amount',
							'type'  => 'number',
							'value' => '2.5',
						),
						array(
							'id'    => 'starts_at',
							'label' => 'Starts at',
							'type'  => 'datetime-local',
							'value' => '2026-07-17T13:30:45',
						),
					)
				)
			);
		} finally {
			update_option( 'timezone_string', $original_timezone );
			remove_action( 'doing_it_wrong_run', $listener, 10 );
		}

		$fields = array_column( $schema['groups']['general']['fields'], null, 'id' );
		$this->assertTrue( $fields['yes']['value'] );
		$this->assertFalse( $fields['no']['value'] );
		$this->assertTrue( $fields['true']['value'] );
		$this->assertFalse( $fields['false']['value'] );
		$this->assertTrue( $fields['one_string']['value'] );
		$this->assertFalse( $fields['zero_string']['value'] );
		$this->assertTrue( $fields['one_integer']['value'] );
		$this->assertFalse( $fields['zero_integer']['value'] );
		$this->assertFalse( $fields['empty']['value'] );
		$this->assertSame( array( 'one', '2', '1', '2.5' ), $fields['choices']['value'] );
		$this->assertSame( 2, $fields['count']['value'] );
		$this->assertSame( 2.5, $fields['amount']['value'] );
		$this->assertSame( '2026-07-17T17:30:45+00:00', $fields['starts_at']['value'] );
		$this->assertCount( 1, $notices );
		$this->assertStringContainsString( 'count (integer)', $notices[0] );
		$this->assertStringContainsString( 'starts_at (datetime-local)', $notices[0] );
	}

	/**
	 * @testdox It leaves malformed and ambiguous legacy values for strict schema validation to reject.
	 */
	public function test_canonicalize_legacy_values_preserves_invalid_values(): void {
		$schema = $this->get_schema(
			array(
				array(
					'id'    => 'enabled',
					'label' => 'Enabled',
					'type'  => 'checkbox',
					'value' => 'maybe',
				),
				array(
					'id'    => 'scalar_choices',
					'label' => 'Scalar choices',
					'type'  => 'array',
					'value' => 'one',
				),
				array(
					'id'    => 'invalid_choices',
					'label' => 'Invalid choices',
					'type'  => 'array',
					'value' => array( 'one', new \stdClass() ),
				),
				array(
					'id'    => 'count',
					'label' => 'Count',
					'type'  => 'integer',
					'value' => '2.5',
				),
				array(
					'id'    => 'amount',
					'label' => 'Amount',
					'type'  => 'number',
					'value' => 'many',
				),
				array(
					'id'    => 'unsafe_count',
					'label' => 'Unsafe count',
					'type'  => 'integer',
					'value' => '9007199254740993',
				),
				array(
					'id'    => 'starts_at',
					'label' => 'Starts at',
					'type'  => 'datetime-local',
					'value' => 'tomorrow',
				),
			)
		);

		$canonicalized = SettingsUISchema::canonicalize_legacy_values( $schema );
		$this->assertSame( $schema, $canonicalized );

		$this->expectException( \UnexpectedValueException::class );
		SettingsUISchema::assert_valid( $canonicalized );
	}

	/**
	 * @testdox It accepts canonical numeric values and bounds at JavaScript's safe integer boundaries.
	 */
	public function test_assert_valid_accepts_safe_integer_boundaries(): void {
		$schema = $this->get_schema(
			array(
				array(
					'id'         => 'safe_range',
					'label'      => 'Safe range',
					'type'       => 'integer',
					'value'      => 9007199254740991,
					'validation' => array(
						'min' => -9007199254740991,
						'max' => 9007199254740991,
					),
				),
			)
		);

		SettingsUISchema::assert_valid( $schema );
		$this->assertSame( 9007199254740991, $schema['groups']['general']['fields'][0]['value'] );
	}

	/**
	 * @testdox It rejects provider numeric values outside JavaScript's safe integer range.
	 * @dataProvider unsafe_canonical_number_provider
	 *
	 * @param int|float $value Unsafe canonical number.
	 */
	public function test_assert_valid_rejects_unsafe_canonical_numbers( $value ): void {
		$this->expectException( \UnexpectedValueException::class );

		SettingsUISchema::assert_valid(
			$this->get_schema(
				array(
					array(
						'id'    => 'unsafe_count',
						'label' => 'Unsafe count',
						'type'  => 'number',
						'value' => $value,
					),
				)
			)
		);
	}

	/**
	 * Provide unsafe canonical numbers.
	 *
	 * @return array<string, array{int|float}>
	 */
	public function unsafe_canonical_number_provider(): array {
		return array(
			'positive integer' => array( 9007199254740992 ),
			'negative integer' => array( -9007199254740992 ),
			'positive float'   => array( 9007199254740992.0 ),
			'negative float'   => array( -9007199254740992.0 ),
		);
	}

	/**
	 * @testdox It rejects validation bounds outside JavaScript's safe integer range.
	 * @dataProvider unsafe_validation_bound_provider
	 *
	 * @param string    $rule Validation rule.
	 * @param int|float $value Unsafe validation bound.
	 */
	public function test_assert_valid_rejects_unsafe_validation_bounds( string $rule, $value ): void {
		$this->expectException( \UnexpectedValueException::class );

		SettingsUISchema::assert_valid(
			$this->get_schema(
				array(
					array(
						'id'         => 'unsafe_bound',
						'label'      => 'Unsafe bound',
						'type'       => 'number',
						'value'      => 0,
						'validation' => array( $rule => $value ),
					),
				)
			)
		);
	}

	/**
	 * Provide unsafe numeric validation bounds.
	 *
	 * @return array<string, array{string, int|float}>
	 */
	public function unsafe_validation_bound_provider(): array {
		return array(
			'positive maximum' => array( 'max', 9007199254740992 ),
			'negative minimum' => array( 'min', -9007199254740992 ),
		);
	}

	/**
	 * @testdox It rejects duplicate field IDs in canonical schemas.
	 */
	public function test_assert_valid_rejects_duplicate_field_ids(): void {
		$this->expectException( \UnexpectedValueException::class );
		$this->expectExceptionMessage( 'duplicate' );

		SettingsUISchema::assert_valid(
			$this->get_schema(
				array(
					array(
						'id'    => 'duplicate',
						'label' => 'First',
						'type'  => 'text',
						'value' => 'one',
					),
					array(
						'id'    => 'duplicate',
						'label' => 'Second',
						'type'  => 'text',
						'value' => 'two',
					),
				)
			)
		);
	}

	/**
	 * @testdox It rejects noncanonical built-in field values.
	 */
	public function test_assert_valid_rejects_noncanonical_builtin_values(): void {
		$this->expectException( \UnexpectedValueException::class );
		$this->expectExceptionMessage( 'count' );

		SettingsUISchema::assert_valid(
			$this->get_schema(
				array(
					array(
						'id'    => 'count',
						'label' => 'Count',
						'type'  => 'integer',
						'value' => '2',
					),
				)
			)
		);
	}

	/**
	 * Transform one legacy field.
	 *
	 * @param array $setting Legacy field definition.
	 * @return array
	 */
	private function transform_field( array $setting ): array {
		$setting += array( 'title' => 'Test field' );
		$schema   = SettingsUISchema::from_legacy_settings( 'test', '', 'Test settings', array( $setting ) );
		return $schema['groups']['default']['fields'][0];
	}

	/**
	 * Build a minimal canonical schema.
	 *
	 * @param array $fields Field definitions.
	 * @return array
	 */
	private function get_schema( array $fields ): array {
		return array(
			'id'     => 'test',
			'groups' => array(
				'general' => array(
					'id'     => 'general',
					'fields' => $fields,
				),
			),
		);
	}
}
