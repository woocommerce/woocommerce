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
		$this->assertSame( array( '1', '1' ), array_column( $field['options'], 'value' ), 'Boolean option values should use the PHP string cast, matching stored values.' );
	}

	/**
	 * @testdox It canonicalizes float option values with the PHP string cast.
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
		$this->assertSame( array( '0.5', '1.5' ), array_column( $field['options'], 'value' ), 'Float option values should use the PHP string cast, matching stored values.' );
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
	 * Build a minimal native schema with one field.
	 *
	 * @param array $field Field definition.
	 * @return array
	 */
	private function get_native_schema_with_field( array $field ): array {
		return array(
			'id'     => 'acme',
			'title'  => 'Acme',
			'groups' => array(
				'main' => array(
					'id'     => 'main',
					'fields' => array( $field ),
				),
			),
		);
	}
}
