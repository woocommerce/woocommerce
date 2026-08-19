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
	 * @testdox It preserves sanitized group description markup and omits header actions.
	 */
	public function test_from_legacy_settings_preserves_group_description_without_actions(): void {
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
		$this->assertArrayNotHasKey( 'actions', $group );
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
		SettingsUISchema::assert_valid_schema( $schema );
	}

	/**
	 * @testdox It builds options for legacy page selectors that do not declare options.
	 */
	public function test_from_legacy_settings_builds_page_options(): void {
		$page_id = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'Checkout',
			)
		);

		$schema = SettingsUISchema::from_legacy_settings(
			'acme',
			'',
			'Acme',
			array(
				array(
					'id'    => 'acme_page',
					'label' => 'Acme page',
					'type'  => 'single_select_page',
					'value' => (string) $page_id,
				),
			)
		);

		$field   = $schema['groups']['default']['fields'][0];
		$options = array_column( $field['options'], 'label', 'value' );

		$this->assertSame( 'select', $field['type'], 'The legacy page selector should use the canonical select type.' );
		$this->assertSame( (string) $page_id, $field['value'], 'The selected page ID should stay unchanged.' );
		$this->assertSame( 'Checkout', $options[ (string) $page_id ], 'The created page should be available as an option.' );
		SettingsUISchema::assert_valid_schema( $schema );
	}

	/**
	 * @testdox It builds country and state options for legacy country selectors that do not declare options.
	 */
	public function test_from_legacy_settings_builds_country_and_state_options(): void {
		$schema = SettingsUISchema::from_legacy_settings(
			'acme',
			'',
			'Acme',
			array(
				array(
					'id'    => 'acme_country',
					'label' => 'Acme country',
					'type'  => 'single_select_country',
					'value' => 'US:CA',
				),
			)
		);

		$field         = $schema['groups']['default']['fields'][0];
		$options       = array_column( $field['options'], 'label', 'value' );
		$country_label = WC()->countries->get_countries()['US'];
		$state_label   = WC()->countries->get_states( 'US' )['CA'];

		$this->assertSame( 'select', $field['type'], 'The legacy country selector should use the canonical select type.' );
		$this->assertSame( 'US:CA', $field['value'], 'The selected country and state value should stay unchanged.' );
		$this->assertSame( $country_label . ' — ' . $state_label, $options['US:CA'], 'The state option should include its country label.' );
		SettingsUISchema::assert_valid_schema( $schema );
	}

	/**
	 * @testdox It builds options for legacy country multiselects that do not declare options.
	 */
	public function test_from_legacy_settings_builds_country_multiselect_options(): void {
		$schema = SettingsUISchema::from_legacy_settings(
			'acme',
			'',
			'Acme',
			array(
				array(
					'id'    => 'acme_countries',
					'label' => 'Acme countries',
					'type'  => 'multi_select_countries',
					'value' => array( 'US', 'MA' ),
				),
			)
		);

		$field   = $schema['groups']['default']['fields'][0];
		$options = array_column( $field['options'], 'label', 'value' );

		$this->assertSame( 'array', $field['type'], 'The legacy country multiselect should use the canonical array type.' );
		$this->assertSame( array( 'US', 'MA' ), $field['value'], 'The selected country values should stay unchanged.' );
		$this->assertSame( WC()->countries->get_countries()['US'], $options['US'], 'The United States should be available as an option.' );
		$this->assertSame( WC()->countries->get_countries()['MA'], $options['MA'], 'Morocco should be available as an option.' );
		SettingsUISchema::assert_valid_schema( $schema );
	}

	/**
	 * @testdox It sorts declared legacy country multiselect options by label.
	 */
	public function test_from_legacy_settings_sorts_declared_country_multiselect_options(): void {
		$schema = SettingsUISchema::from_legacy_settings(
			'acme',
			'',
			'Acme',
			array(
				array(
					'id'      => 'acme_countries',
					'label'   => 'Acme countries',
					'type'    => 'multi_select_countries',
					'options' => array(
						'US' => 'Zulu country',
						'MA' => 'Alpha country',
					),
				),
			)
		);

		$field = $schema['groups']['default']['fields'][0];

		$this->assertSame(
			array(
				array(
					'label' => 'Alpha country',
					'value' => 'MA',
				),
				array(
					'label' => 'Zulu country',
					'value' => 'US',
				),
			),
			$field['options'],
			'The canonical options should match the label order used by the classic renderer.'
		);
		SettingsUISchema::assert_valid_schema( $schema );
	}

	/**
	 * @testdox It leaves generated country options empty when the countries controller is unavailable.
	 */
	public function test_from_legacy_settings_handles_an_unavailable_countries_controller(): void {
		$woocommerce            = WC();
		$original_countries     = $woocommerce->countries;
		$woocommerce->countries = null;

		try {
			$schema = SettingsUISchema::from_legacy_settings(
				'acme',
				'',
				'Acme',
				array(
					array(
						'id'   => 'acme_country',
						'type' => 'single_select_country',
					),
					array(
						'id'   => 'acme_countries',
						'type' => 'multi_select_countries',
					),
				)
			);
		} finally {
			$woocommerce->countries = $original_countries;
		}

		$fields = $schema['groups']['default']['fields'];
		$this->assertArrayNotHasKey( 'options', $fields[0], 'The country selector should not fail when the countries controller is unavailable.' );
		$this->assertArrayNotHasKey( 'options', $fields[1], 'The country multiselect should not fail when the countries controller is unavailable.' );
		SettingsUISchema::assert_valid_schema( $schema );
	}

	/**
	 * @testdox It accepts an ordinary legacy multiselect with no options.
	 */
	public function test_from_legacy_settings_accepts_multiselect_without_options(): void {
		$schema = SettingsUISchema::from_legacy_settings(
			'acme',
			'',
			'Acme',
			array(
				array(
					'id'    => 'acme_choices',
					'label' => 'Acme choices',
					'type'  => 'multiselect',
					'value' => array(),
				),
			)
		);

		SettingsUISchema::assert_valid_schema( $schema );

		$field = $schema['groups']['default']['fields'][0];
		$this->assertSame( 'array', $field['type'], 'The legacy multiselect should use the canonical array type.' );
		$this->assertArrayNotHasKey( 'options', $field, 'An empty legacy option map should remain an empty choice set.' );
	}

	/**
	 * @testdox It rejects duplicate legacy group ids before either group can be overwritten.
	 *
	 * @dataProvider duplicate_legacy_group_ids
	 *
	 * @param string $group_id Duplicate group id.
	 */
	public function test_from_legacy_settings_rejects_duplicate_group_ids( string $group_id ): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( sprintf( 'Group id "%s" is duplicated.', $group_id ) );

		SettingsUISchema::from_legacy_settings(
			'acme',
			'',
			'Acme',
			array(
				array(
					'id'    => $group_id,
					'type'  => 'title',
					'title' => 'First',
				),
				array(
					'id'    => 'acme_enabled',
					'type'  => 'checkbox',
					'title' => 'Enabled',
				),
				array( 'type' => 'sectionend' ),
				array(
					'id'    => $group_id,
					'type'  => 'title',
					'title' => 'Second',
				),
				array(
					'id'    => 'acme_label',
					'type'  => 'text',
					'title' => 'Label',
				),
			)
		);
	}

	/**
	 * Duplicate legacy group id fixtures.
	 *
	 * @return array<string, array{string}>
	 */
	public static function duplicate_legacy_group_ids(): array {
		return array(
			'normal id'      => array( 'main' ),
			'zero-string id' => array( '0' ),
		);
	}

	/**
	 * @testdox It gives separate generated groups to separate runs of loose fields.
	 */
	public function test_from_legacy_settings_preserves_separate_runs_of_loose_fields(): void {
		$schema = SettingsUISchema::from_legacy_settings(
			'acme',
			'',
			'Acme',
			array(
				array(
					'id'    => 'acme_before',
					'type'  => 'text',
					'title' => 'Before',
				),
				array(
					'id'    => 'main',
					'type'  => 'title',
					'title' => 'Main',
				),
				array(
					'id'    => 'acme_main',
					'type'  => 'text',
					'title' => 'Main field',
				),
				array( 'type' => 'sectionend' ),
				array(
					'id'    => 'acme_after',
					'type'  => 'text',
					'title' => 'After',
				),
			)
		);

		$this->assertSame( array( 'default', 'main', 'default_1' ), array_keys( $schema['groups'] ) );
		$this->assertSame( 'acme_before', $schema['groups']['default']['fields'][0]['id'] );
		$this->assertSame( 'acme_main', $schema['groups']['main']['fields'][0]['id'] );
		$this->assertSame( 'acme_after', $schema['groups']['default_1']['fields'][0]['id'] );
	}

	/**
	 * @testdox It reserves explicit group ids when it generates a group for loose fields.
	 */
	public function test_from_legacy_settings_avoids_explicit_group_id_when_generating_loose_group(): void {
		$schema = SettingsUISchema::from_legacy_settings(
			'acme',
			'',
			'Acme',
			array(
				array(
					'id'    => 'acme_loose',
					'type'  => 'text',
					'title' => 'Loose field',
				),
				array(
					'id'    => 'default',
					'type'  => 'title',
					'title' => 'Declared default',
				),
				array(
					'id'    => 'acme_declared',
					'type'  => 'text',
					'title' => 'Declared field',
				),
			)
		);

		$this->assertSame( array( 'default_1', 'default' ), array_keys( $schema['groups'] ) );
		$this->assertSame( 'acme_loose', $schema['groups']['default_1']['fields'][0]['id'] );
		$this->assertSame( 'acme_declared', $schema['groups']['default']['fields'][0]['id'] );
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
	 * @testdox It accepts Settings UI transport values without interpreting the field type.
	 *
	 * @dataProvider settings_ui_values
	 *
	 * @param string $type Field type.
	 * @param mixed  $value Field value.
	 */
	public function test_assert_valid_schema_accepts_settings_ui_values_without_interpreting_field_type( string $type, $value ): void {
		$field = array(
			'id'    => 'acme_custom_field',
			'label' => 'Acme custom field',
			'type'  => $type,
			'value' => $value,
			'save'  => array( 'adapter' => 'form_post' ),
		);

		SettingsUISchema::assert_valid_schema( $this->get_native_schema_with_field( $field ) );
		$this->addToAssertionCount( 1 );
	}

	/**
	 * Settings UI transport value fixtures.
	 *
	 * @return array<string, array{string, mixed}>
	 */
	public static function settings_ui_values(): array {
		return array(
			'number string'         => array( 'number', '02' ),
			'datetime-local string' => array( 'datetime-local', '2026-08-03T12:30' ),
			'extension string'      => array( 'acme/custom', 'Acme' ),
			'extension integer'     => array( 'acme/custom', 10 ),
			'extension float'       => array( 'acme/custom', 10.5 ),
			'extension boolean'     => array( 'acme/custom', true ),
			'extension string list' => array( 'acme/custom', array( 'one', 'two' ) ),
			'extension null'        => array( 'acme/custom', null ),
		);
	}

	/**
	 * @testdox It accepts choice fields with missing or empty option lists.
	 */
	public function test_assert_valid_schema_accepts_choice_fields_without_options(): void {
		$fields = array(
			array(
				'id'    => 'acme_select_without_options',
				'label' => 'Select without options',
				'type'  => 'select',
				'value' => '',
				'save'  => array( 'adapter' => 'form_post' ),
			),
			array(
				'id'      => 'acme_array_with_empty_options',
				'label'   => 'Array with empty options',
				'type'    => 'array',
				'value'   => array(),
				'options' => array(),
				'save'    => array( 'adapter' => 'form_post' ),
			),
		);

		SettingsUISchema::assert_valid_schema( $this->get_native_schema_with_fields( $fields ) );
		$this->addToAssertionCount( 1 );
	}

	/**
	 * @testdox It accepts scalar custom attributes without interpreting renderer semantics.
	 */
	public function test_assert_valid_schema_accepts_scalar_custom_attributes_without_interpreting_renderer_semantics(): void {
		$field = array(
			'id'               => 'acme_custom_field',
			'label'            => 'Acme custom field',
			'type'             => 'acme/custom',
			'value'            => '',
			'customAttributes' => array(
				'min'          => 'extension-defined',
				'max'          => 10,
				'step'         => 'any',
				'data-enabled' => true,
			),
			'save'             => array( 'adapter' => 'form_post' ),
		);

		SettingsUISchema::assert_valid_schema( $this->get_native_schema_with_field( $field ) );
		$this->addToAssertionCount( 1 );
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

		$empty_type                                        = $valid;
		$empty_type['groups']['main']['fields'][0]['type'] = '';

		$duplicate_id                               = $valid;
		$duplicate_id['groups']['main']['fields'][] = $duplicate_id['groups']['main']['fields'][0];
		$group_field_collision                      = $valid;
		$group_field_collision['groups']['main']['fields'][0]['id'] = 'main';
		$empty_schema_id                                        = $valid;
		$empty_schema_id['id']                                  = '';
		$malformed_group                                        = $valid;
		$malformed_group['groups']['main']['fields']            = 'invalid';
		$invalid_options                                        = $valid;
		$invalid_options['groups']['main']['fields'][0]['type'] = 'select';
		$invalid_options['groups']['main']['fields'][0]['options'] = array( 'one' => 'One' );
		$null_options                                        = $valid;
		$null_options['groups']['main']['fields'][0]['type'] = 'select';
		$null_options['groups']['main']['fields'][0]['options'] = null;
		$invalid_option                                        = $valid;
		$invalid_option['groups']['main']['fields'][0]['type'] = 'select';
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
		$invalid_field_value = $valid;
		$invalid_field_value['groups']['main']['fields'][0]['value'] = array( 'tier' => 1 );
		$invalid_custom_attributes                                   = $valid;
		$invalid_custom_attributes['groups']['main']['fields'][0]['customAttributes'] = 'invalid';
		$invalid_custom_attribute_value = $valid;
		$invalid_custom_attribute_value['groups']['main']['fields'][0]['customAttributes'] = array( 'data-values' => array() );
		$invalid_custom_attribute_float = $valid;
		$invalid_custom_attribute_float['groups']['main']['fields'][0]['customAttributes'] = array( 'data-value' => INF );
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
				'intent' => array( 'invalid' ),
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
			'empty field type'            => array( $empty_type, 'Field "acme_field" type must be a non-empty string.' ),
			'duplicate field id'          => array( $duplicate_id, 'Field id "acme_field" is duplicated.' ),
			'group and field collision'   => array( $group_field_collision, 'Field id "main" collides with a group id.' ),
			'empty schema id'             => array( $empty_schema_id, 'Schema id must be a non-empty string.' ),
			'malformed group fields'      => array( $malformed_group, 'Group "main" fields must be a list.' ),
			'non-list choice options'     => array( $invalid_options, 'Field "acme_field" options must be a list.' ),
			'null choice options'         => array( $null_options, 'Field "acme_field" options must be a list.' ),
			'non-string option value'     => array( $invalid_option, 'Field "acme_field" option 0 value must be a string.' ),
			'empty component name'        => array( $invalid_component, 'Field "acme_field" component must be a non-empty string.' ),
			'unsupported field save'      => array( $invalid_field_save, 'Field "acme_field" save adapter must be "form_post" or "none".' ),
			'missing visibility control'  => array( $invalid_visibility, 'Field "acme_field" visibility controller "missing" does not reference a field.' ),
			'invalid field value'         => array( $invalid_field_value, 'Field "acme_field" value is not a valid Settings UI value.' ),
			'invalid custom attributes'   => array( $invalid_custom_attributes, 'Field "acme_field" customAttributes must be a map.' ),
			'invalid custom value'        => array( $invalid_custom_attribute_value, 'Field "acme_field" custom attribute "data-values" has an invalid value.' ),
			'non-finite custom value'     => array( $invalid_custom_attribute_float, 'Field "acme_field" custom attribute "data-value" has an invalid value.' ),
			'saving info field'           => array( $invalid_info, 'Field "acme_field" of type "info" must use the "none" save adapter.' ),
			'malformed shell navigation'  => array( $invalid_shell, 'Shell navigation item 0 href must be a string.' ),
			'malformed breadcrumb'        => array( $invalid_breadcrumb, 'Shell breadcrumb 0 label must be a string.' ),
			'invalid badge intent'        => array( $invalid_badge, 'Shell badge 0 intent must be a string.' ),
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
					'intent' => 'extension-defined-intent',
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
