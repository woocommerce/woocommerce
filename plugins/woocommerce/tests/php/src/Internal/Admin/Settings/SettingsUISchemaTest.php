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
