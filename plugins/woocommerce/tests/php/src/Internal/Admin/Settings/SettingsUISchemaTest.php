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
		$this->assertArrayHasKey( 'adapter', $field['save'] );
		$this->assertSame( 'none', $field['save']['adapter'] );
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
	 * @testdox It canonicalizes visibility values with their typed controllers.
	 */
	public function test_canonicalize_schema_values_matches_typed_visibility_values(): void {
		$this->setExpectedIncorrectUsage( SettingsUISchema::class . '::canonicalize_schema_values' );

		$schema = SettingsUISchema::canonicalize_schema_values(
			$this->get_native_schema_with_fields(
				array(
					array(
						'id'    => 'acme_enabled',
						'type'  => 'checkbox',
						'value' => 'yes',
						'save'  => array( 'adapter' => 'none' ),
					),
					array(
						'id'         => 'acme_enabled_note',
						'type'       => 'text',
						'value'      => '',
						'visibility' => array(
							'controller' => 'acme_enabled',
							'value'      => array( 'no', 'yes', 'maybe' ),
						),
						'save'       => array( 'adapter' => 'none' ),
					),
					array(
						'id'    => 'acme_ratio',
						'type'  => 'number',
						'value' => '2.5',
						'save'  => array( 'adapter' => 'none' ),
					),
					array(
						'id'    => 'acme_count',
						'type'  => 'integer',
						'value' => '2',
						'save'  => array( 'adapter' => 'none' ),
					),
					array(
						'id'         => 'acme_count_note',
						'type'       => 'text',
						'value'      => '',
						'visibility' => array(
							'controller' => 'acme_count',
							'value'      => array( '2', '2.5' ),
						),
						'save'       => array( 'adapter' => 'none' ),
					),
					array(
						'id'    => 'acme_optional_ratio',
						'type'  => 'number',
						'value' => '',
						'save'  => array( 'adapter' => 'none' ),
					),
					array(
						'id'         => 'acme_optional_ratio_note',
						'type'       => 'text',
						'value'      => '',
						'visibility' => array(
							'controller' => 'acme_optional_ratio',
							'value'      => '',
						),
						'save'       => array( 'adapter' => 'none' ),
					),
					array(
						'id'         => 'acme_ratio_note',
						'type'       => 'text',
						'value'      => '',
						'visibility' => array(
							'controller' => 'acme_ratio',
							'value'      => array( '1.5', '2.5' ),
						),
						'save'       => array( 'adapter' => 'none' ),
					),
					array(
						'id'    => 'acme_start',
						'type'  => 'datetime-local',
						'value' => '2026-08-03T12:30Z',
						'save'  => array( 'adapter' => 'none' ),
					),
					array(
						'id'         => 'acme_start_note',
						'type'       => 'text',
						'value'      => '',
						'visibility' => array(
							'controller' => 'acme_start',
							'value'      => '2026-08-03T12:30Z',
						),
						'save'       => array( 'adapter' => 'none' ),
					),
					array(
						'id'    => 'acme_methods',
						'type'  => 'array',
						'value' => array( 1, 2 ),
						'save'  => array( 'adapter' => 'none' ),
					),
					array(
						'id'         => 'acme_methods_note',
						'type'       => 'text',
						'value'      => '',
						'visibility' => array(
							'controller' => 'acme_methods',
							'value'      => array( array( 1, 2 ) ),
						),
						'save'       => array( 'adapter' => 'none' ),
					),
				)
			)
		);

		$fields = array_column( $schema['groups']['main']['fields'], null, 'id' );

		$this->assertSame( true, $fields['acme_enabled']['value'] );
		$this->assertSame( array( false, true, 'maybe' ), $fields['acme_enabled_note']['visibility']['value'] );
		$this->assertSame( 2.5, $fields['acme_ratio']['value'] );
		$this->assertSame( array( 1.5, 2.5 ), $fields['acme_ratio_note']['visibility']['value'] );
		$this->assertSame( 2, $fields['acme_count']['value'] );
		$this->assertSame( array( 2, '2.5' ), $fields['acme_count_note']['visibility']['value'] );
		$this->assertNull( $fields['acme_optional_ratio']['value'] );
		$this->assertNull( $fields['acme_optional_ratio_note']['visibility']['value'] );
		$this->assertSame( '2026-08-03T12:30:00+00:00', $fields['acme_start']['value'] );
		$this->assertSame( '2026-08-03T12:30:00+00:00', $fields['acme_start_note']['visibility']['value'] );
		$this->assertSame( array( '1', '2' ), $fields['acme_methods']['value'] );
		$this->assertSame( array( array( '1', '2' ) ), $fields['acme_methods_note']['visibility']['value'] );
	}

	/**
	 * @testdox It canonicalizes legacy values, numeric bounds, and original form representations atomically.
	 */
	public function test_from_legacy_settings_canonicalizes_typed_values_and_preserves_form_values(): void {
		$schema = SettingsUISchema::from_legacy_settings(
			'acme',
			'',
			'Acme',
			array(
				array(
					'id'                => 'acme_quantity',
					'label'             => 'Quantity',
					'type'              => 'number',
					'value'             => '02',
					'custom_attributes' => array(
						'min'  => '0',
						'max'  => '10',
						'step' => '1',
					),
				),
			)
		);

		$field = $schema['groups']['default']['fields'][0];

		$this->assertSame( 'integer', $field['type'] );
		$this->assertSame( 2, $field['value'] );
		$this->assertSame(
			array(
				'min' => 0,
				'max' => 10,
			),
			$field['validation']
		);
		$this->assertSame( '02', $field['save']['initialValue'] );
		SettingsUISchema::assert_valid_schema( $schema );
	}

	/**
	 * @testdox It canonicalizes native numeric values without rounding unsafe integers first.
	 *
	 * @dataProvider canonical_numeric_values
	 *
	 * @param mixed          $raw Raw schema value.
	 * @param string         $type Field type.
	 * @param int|float|null $expected Expected canonical value.
	 */
	public function test_canonicalize_schema_values_handles_numeric_boundaries( $raw, string $type, $expected ): void {
		$this->setExpectedIncorrectUsage( SettingsUISchema::class . '::canonicalize_schema_values' );

		$schema = $this->get_native_schema_with_field(
			array(
				'id'    => 'acme_number',
				'label' => 'Number',
				'type'  => $type,
				'value' => $raw,
				'save'  => array( 'adapter' => 'custom' ),
			)
		);

		$canonical = SettingsUISchema::canonicalize_schema_values( $schema );

		$this->assertSame( $expected, $canonical['groups']['main']['fields'][0]['value'] );
	}

	/**
	 * Canonical numeric value fixtures.
	 *
	 * @return array<string, array{mixed, string, int|float|null}>
	 */
	public static function canonical_numeric_values(): array {
		return array(
			'empty number'         => array( '', 'number', null ),
			'whitespace number'    => array( '  ', 'number', null ),
			'zero number'          => array( '0', 'number', 0 ),
			'decimal number'       => array( '1.25', 'number', 1.25 ),
			'equivalent decimal'   => array( '01.2500e0', 'number', 1.25 ),
			'exponent number'      => array( '1e3', 'number', 1000 ),
			'padded exponent'      => array( '1e+0000007', 'integer', 10000000 ),
			'negative zero exp'    => array( '1e-0000000', 'integer', 1 ),
			'safe integer maximum' => array( '9007199254740991', 'integer', 9007199254740991 ),
			'safe integer minimum' => array( '-9007199254740991', 'integer', -9007199254740991 ),
		);
	}

	/**
	 * @testdox It converts supported non-typed scalar and null values to strings.
	 *
	 * @dataProvider supported_scalar_values
	 *
	 * @param string $type Field type.
	 * @param mixed  $value Raw field value.
	 * @param string $expected Expected canonical value.
	 */
	public function test_canonicalize_schema_values_converts_supported_scalar_values( string $type, $value, string $expected ): void {
		$this->setExpectedIncorrectUsage( SettingsUISchema::class . '::canonicalize_schema_values' );

		$schema = SettingsUISchema::canonicalize_schema_values(
			$this->get_native_schema_with_field(
				array(
					'id'    => 'acme_value',
					'label' => 'Value',
					'type'  => $type,
					'value' => $value,
					'save'  => array( 'adapter' => 'none' ),
				)
			)
		);

		$this->assertSame( $expected, $schema['groups']['main']['fields'][0]['value'] );
		SettingsUISchema::assert_valid_schema( $schema );
	}

	/**
	 * @testdox It does not read a missing field type while it canonicalizes option values.
	 */
	public function test_canonicalize_schema_values_handles_missing_field_type(): void {
		$this->setExpectedIncorrectUsage( SettingsUISchema::class . '::canonicalize_schema_values' );

		$schema = SettingsUISchema::canonicalize_schema_values(
			$this->get_native_schema_with_field(
				array(
					'id'      => 'acme_value',
					'label'   => 'Value',
					'value'   => 1,
					'options' => array(
						array(
							'label' => 'One',
							'value' => 1,
						),
					),
				)
			)
		);

		$field = $schema['groups']['main']['fields'][0];
		$this->assertArrayNotHasKey( 'type', $field );
		$this->assertSame( '1', $field['value'] );
		$this->assertSame( '1', $field['options'][0]['value'] );
	}

	/**
	 * Supported scalar value fixtures.
	 *
	 * @return array<string, array{string, mixed, string}>
	 */
	public static function supported_scalar_values(): array {
		return array(
			'integer text'     => array( 'text', 12, '12' ),
			'float textarea'   => array( 'textarea', 1.25, '1.25' ),
			'boolean password' => array( 'password', false, 'false' ),
			'null URL'         => array( 'url', null, '' ),
		);
	}

	/**
	 * @testdox It rejects decimal values that change during numeric canonicalization.
	 *
	 * @dataProvider lossy_decimal_values
	 *
	 * @param string $value Lossy decimal value.
	 */
	public function test_canonicalize_schema_values_rejects_lossy_decimal_values( string $value ): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'cannot be represented as a finite number without loss' );

		SettingsUISchema::canonicalize_schema_values(
			$this->get_native_schema_with_field(
				array(
					'id'    => 'acme_number',
					'label' => 'Number',
					'type'  => 'number',
					'value' => $value,
					'save'  => array( 'adapter' => 'custom' ),
				)
			)
		);
	}

	/**
	 * Lossy decimal value fixtures.
	 *
	 * @return array<string, array{string}>
	 */
	public static function lossy_decimal_values(): array {
		return array(
			'rounded fraction' => array( '0.10000000000000001' ),
			'rounded integer'  => array( '1.0000000000000000001' ),
			'underflow'        => array( '1e-324' ),
		);
	}

	/**
	 * @testdox It rejects integral numeric values outside JavaScript's safe range before conversion.
	 *
	 * @dataProvider unsafe_integral_values
	 *
	 * @param string $value Unsafe integral value.
	 */
	public function test_canonicalize_schema_values_rejects_unsafe_integral_values( string $value ): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'outside the JavaScript safe integer range' );

		SettingsUISchema::canonicalize_schema_values(
			$this->get_native_schema_with_field(
				array(
					'id'    => 'acme_integer',
					'label' => 'Integer',
					'type'  => 'integer',
					'value' => $value,
					'save'  => array( 'adapter' => 'custom' ),
				)
			)
		);
	}

	/**
	 * Unsafe integral value fixtures.
	 *
	 * @return array<string, array{string}>
	 */
	public static function unsafe_integral_values(): array {
		return array(
			'above maximum' => array( '9007199254740992' ),
			'below minimum' => array( '-9007199254740992' ),
			'exponent'      => array( '9.007199254740992e15' ),
		);
	}

	/**
	 * @testdox It rejects unsafe integral bounds before converting them to floats.
	 *
	 * @dataProvider unsafe_integral_bounds
	 *
	 * @param string $bound Bound name.
	 * @param string $value Unsafe bound value.
	 */
	public function test_canonicalize_schema_values_rejects_unsafe_integral_bounds( string $bound, string $value ): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( $bound . ' is outside the JavaScript safe integer range' );

		SettingsUISchema::canonicalize_schema_values(
			$this->get_native_schema_with_field(
				array(
					'id'               => 'acme_number',
					'label'            => 'Number',
					'type'             => 'number',
					'value'            => 2,
					'customAttributes' => array( $bound => $value ),
					'save'             => array( 'adapter' => 'custom' ),
				)
			)
		);
	}

	/**
	 * Unsafe integral bound fixtures.
	 *
	 * @return array<string, array{string, string}>
	 */
	public static function unsafe_integral_bounds(): array {
		return array(
			'above maximum' => array( 'max', '9007199254740992' ),
			'below minimum' => array( 'min', '-9007199254740992' ),
		);
	}

	/**
	 * @testdox It promotes step-one numbers only when the selected step base is integral.
	 *
	 * @dataProvider integer_inference_values
	 *
	 * @param array  $field Field definition.
	 * @param string $expected_type Expected canonical type.
	 */
	public function test_canonicalize_schema_values_infers_integer_from_step_base( array $field, string $expected_type ): void {
		$field += array(
			'id'    => 'acme_number',
			'title' => 'Number',
			'type'  => 'number',
		);

		$schema = SettingsUISchema::from_legacy_settings( 'acme', '', 'Acme', array( $field ), 'custom' );

		$this->assertSame( $expected_type, $schema['groups']['default']['fields'][0]['type'] );
	}

	/**
	 * Integer inference fixtures.
	 *
	 * @return array<string, array{array, string}>
	 */
	public static function integer_inference_values(): array {
		return array(
			'min takes precedence'       => array(
				array(
					'value'             => '2',
					'custom_attributes' => array(
						'step' => '1',
						'min'  => '0.5',
					),
				),
				'number',
			),
			'integral current value'     => array(
				array(
					'value'             => '2',
					'custom_attributes' => array( 'step' => 1 ),
				),
				'integer',
			),
			'empty uses zero step base'  => array(
				array(
					'value'             => '',
					'custom_attributes' => array( 'step' => 1 ),
				),
				'integer',
			),
			'non-unit step stays number' => array(
				array(
					'value'             => '2',
					'custom_attributes' => array( 'step' => '0.5' ),
				),
				'number',
			),
			'near-one step stays number' => array(
				array(
					'value'             => '2',
					'custom_attributes' => array( 'step' => '1.0000000000000000001' ),
				),
				'number',
			),
		);
	}

	/**
	 * @testdox It does not require form representation metadata for a page-level custom save strategy.
	 */
	public function test_canonicalize_schema_values_skips_initial_value_for_custom_page_save(): void {
		$this->setExpectedIncorrectUsage( SettingsUISchema::class . '::canonicalize_schema_values' );

		$schema         = $this->get_native_schema_with_field(
			array(
				'id'      => 'acme_choices',
				'label'   => 'Choices',
				'type'    => 'array',
				'value'   => array( 1 ),
				'options' => array(
					array(
						'label' => 'One',
						'value' => '1',
					),
				),
			)
		);
		$schema['save'] = array(
			'adapter' => 'custom',
			'handler' => 'acme/save',
		);

		$canonical = SettingsUISchema::canonicalize_schema_values( $schema );
		$field     = $canonical['groups']['main']['fields'][0];

		$this->assertSame( array( '1' ), $field['value'] );
		$this->assertArrayNotHasKey( 'save', $field );
		SettingsUISchema::assert_valid_schema( $canonical );
	}

	/**
	 * @testdox It converts store-local datetimes to timezone-qualified ISO values while preserving form precision.
	 */
	public function test_from_legacy_settings_canonicalizes_local_datetime(): void {
		$original_timezone = get_option( 'timezone_string' );
		update_option( 'timezone_string', 'America/New_York' );

		try {
			$schema = SettingsUISchema::from_legacy_settings(
				'acme',
				'',
				'Acme',
				array(
					array(
						'id'    => 'acme_start',
						'label' => 'Starts',
						'type'  => 'datetime-local',
						'value' => '2026-11-01T01:30',
					),
				)
			);
		} finally {
			update_option( 'timezone_string', $original_timezone );
		}

		$field = $schema['groups']['default']['fields'][0];
		$this->assertSame( '2026-11-01T01:30:00-04:00', $field['value'], 'PHP deterministically chooses the first occurrence of New York\'s repeated hour.' );
		$this->assertSame( '2026-11-01T01:30', $field['save']['initialValue'] );
	}

	/**
	 * @testdox It reads one-level legacy form names and captures the exact nested member.
	 */
	public function test_from_legacy_settings_reads_nested_form_option_once(): void {
		update_option(
			'acme_settings',
			array(
				'quantity' => '02',
				'other'    => 'keep',
			)
		);

		try {
			$schema = SettingsUISchema::from_legacy_settings(
				'acme',
				'',
				'Acme',
				array(
					array(
						'id'                => 'acme_quantity',
						'field_name'        => 'acme_settings[quantity]',
						'label'             => 'Quantity',
						'type'              => 'number',
						'custom_attributes' => array( 'step' => 1 ),
					),
				)
			);
		} finally {
			delete_option( 'acme_settings' );
		}

		$field = $schema['groups']['default']['fields'][0];
		$this->assertSame( 2, $field['value'] );
		$this->assertSame( '02', $field['save']['initialValue'] );
	}

	/**
	 * @testdox It falls back to the field default when a nested option parent is not an array.
	 */
	public function test_from_legacy_settings_falls_back_for_non_array_nested_option_parent(): void {
		update_option( 'acme_settings', 'not-an-array' );

		try {
			$schema = SettingsUISchema::from_legacy_settings(
				'acme',
				'',
				'Acme',
				array(
					array(
						'id'         => 'acme_quantity',
						'field_name' => 'acme_settings[quantity]',
						'label'      => 'Quantity',
						'type'       => 'text',
						'default'    => 'fallback',
					),
				)
			);
		} finally {
			delete_option( 'acme_settings' );
		}

		$field = $schema['groups']['default']['fields'][0];
		$this->assertSame( 'fallback', $field['value'] );
		$this->assertSame( 'fallback', $field['save']['initialValue'] );
	}

	/**
	 * @testdox It uses the classic settings reader's unslash behavior.
	 */
	public function test_from_legacy_settings_unslashes_nested_option_values(): void {
		update_option( 'acme_settings', array( 'copy' => "It\\'s ready" ) );

		try {
			$schema = SettingsUISchema::from_legacy_settings(
				'acme',
				'',
				'Acme',
				array(
					array(
						'id'         => 'acme_copy',
						'field_name' => 'acme_settings[copy]',
						'label'      => 'Copy',
						'type'       => 'text',
					),
				)
			);
		} finally {
			delete_option( 'acme_settings' );
		}

		$field = $schema['groups']['default']['fields'][0];
		$this->assertSame( 'It\'s ready', $field['value'] );
		$this->assertSame( 'It\'s ready', $field['save']['initialValue'] );
	}

	/**
	 * @testdox It falls back to the field ID when field_name is an empty scalar.
	 */
	public function test_from_legacy_settings_uses_id_for_empty_field_name(): void {
		update_option( 'acme_quantity', 'from-id' );

		try {
			$schema = SettingsUISchema::from_legacy_settings(
				'acme',
				'',
				'Acme',
				array(
					array(
						'id'         => 'acme_quantity',
						'field_name' => '',
						'label'      => 'Quantity',
						'type'       => 'text',
					),
				)
			);
		} finally {
			delete_option( 'acme_quantity' );
		}

		$field = $schema['groups']['default']['fields'][0];
		$this->assertSame( 'acme_quantity', $field['save']['name'] );
		$this->assertSame( 'from-id', $field['value'] );
		$this->assertSame( 'from-id', $field['save']['initialValue'] );
	}

	/**
	 * @testdox It reads a legacy array option whose configured form name includes the list suffix.
	 */
	public function test_from_legacy_settings_reads_array_option_with_list_suffix(): void {
		update_option(
			'acme_settings',
			array(
				'methods' => array( 'card', 'link' ),
			)
		);

		try {
			$schema = SettingsUISchema::from_legacy_settings(
				'acme',
				'',
				'Acme',
				array(
					array(
						'id'         => 'acme_methods',
						'field_name' => 'acme_settings[methods][]',
						'label'      => 'Methods',
						'type'       => 'multiselect',
					),
				)
			);
		} finally {
			delete_option( 'acme_settings' );
		}

		$field = $schema['groups']['default']['fields'][0];
		$this->assertSame( array( 'card', 'link' ), $field['value'] );
		$this->assertSame( 'acme_settings[methods][]', $field['save']['name'] );
		$this->assertSame( array( 'card', 'link' ), $field['save']['initialValue'] );
	}

	/**
	 * @testdox It rejects deep legacy form names before the Settings UI mounts.
	 */
	public function test_from_legacy_settings_rejects_deep_form_option_names(): void {
		update_option( 'acme_quantity', '02' );
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'has an unsupported name' );

		try {
			SettingsUISchema::from_legacy_settings(
				'acme',
				'',
				'Acme',
				array(
					array(
						'id'         => 'acme_quantity',
						'field_name' => 'acme_settings[group][quantity]',
						'label'      => 'Quantity',
						'type'       => 'number',
					),
				)
			);
		} finally {
			delete_option( 'acme_quantity' );
		}
	}

	/**
	 * @testdox It rejects deep legacy array names before the Settings UI mounts.
	 */
	public function test_from_legacy_settings_rejects_deep_array_form_names(): void {
		update_option( 'acme_methods', array( 'card', 'link' ) );
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'has an unsupported name' );

		try {
			SettingsUISchema::from_legacy_settings(
				'acme',
				'',
				'Acme',
				array(
					array(
						'id'         => 'acme_methods',
						'field_name' => 'acme_settings[group][methods][]',
						'label'      => 'Methods',
						'type'       => 'multiselect',
					),
				)
			);
		} finally {
			delete_option( 'acme_methods' );
		}
	}

	/**
	 * @testdox It requires an explicit original form value when native compatibility conversion cannot preserve one.
	 */
	public function test_canonicalize_schema_values_rejects_ambiguous_native_form_value(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'must define save.initialValue' );

		SettingsUISchema::canonicalize_schema_values(
			$this->get_native_schema_with_field(
				array(
					'id'      => 'acme_choices',
					'label'   => 'Choices',
					'type'    => 'array',
					'value'   => array( 1 ),
					'options' => array(
						array(
							'label' => 'One',
							'value' => '1',
						),
					),
					'save'    => array( 'adapter' => 'form_post' ),
				)
			)
		);
	}

	/**
	 * @testdox It rejects an original form value that does not match the canonical current value.
	 */
	public function test_assert_valid_schema_rejects_mismatched_initial_form_value(): void {
		$schema = SettingsUISchema::canonicalize_schema_values(
			$this->get_native_schema_with_field(
				array(
					'id'    => 'acme_quantity',
					'label' => 'Quantity',
					'type'  => 'number',
					'value' => 2,
					'save'  => array(
						'adapter'      => 'form_post',
						'name'         => 'acme_quantity',
						'initialValue' => '03',
					),
				)
			)
		);

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'save.initialValue cannot be replayed safely' );
		SettingsUISchema::assert_valid_schema( $schema );
	}

	/**
	 * @testdox It rejects an initial form value when a typed field has no current value.
	 */
	public function test_assert_valid_schema_rejects_initial_form_value_without_current_value(): void {
		$schema = $this->get_native_schema_with_field(
			array(
				'id'    => 'acme_quantity',
				'label' => 'Quantity',
				'type'  => 'number',
				'save'  => array(
					'adapter'      => 'form_post',
					'name'         => 'acme_quantity',
					'initialValue' => '',
				),
			)
		);

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'save.initialValue cannot be replayed safely' );
		SettingsUISchema::assert_valid_schema( $schema );
	}

	/**
	 * @testdox It rejects an initial form value that disagrees with an untyped string field.
	 */
	public function test_assert_valid_schema_rejects_mismatched_initial_form_value_for_string_field(): void {
		$schema = $this->get_native_schema_with_field(
			array(
				'id'    => 'acme_label',
				'label' => 'Label',
				'type'  => 'text',
				'value' => 'shown',
				'save'  => array(
					'adapter'      => 'form_post',
					'name'         => 'acme_label',
					'initialValue' => 'stored',
				),
			)
		);

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'save.initialValue cannot be replayed safely' );
		SettingsUISchema::assert_valid_schema( $schema );
	}

	/**
	 * @testdox It keeps the canonical string transport for existing option-provider conversion.
	 *
	 * @dataProvider compatible_option_provider_values
	 *
	 * @param string         $type Field type.
	 * @param bool|int|float $value Provider value.
	 * @param string         $expected Canonical string value.
	 */
	public function test_canonicalize_schema_values_keeps_option_provider_string_transport( string $type, $value, string $expected ): void {
		$this->setExpectedIncorrectUsage( SettingsUISchema::class . '::canonicalize_schema_values' );

		$schema = SettingsUISchema::canonicalize_schema_values(
			$this->get_native_schema_with_field(
				array(
					'id'      => 'acme_option',
					'label'   => 'Option',
					'type'    => $type,
					'value'   => $value,
					'options' => array(
						array(
							'label' => 'Current',
							'value' => $value,
						),
					),
					'save'    => array( 'adapter' => 'form_post' ),
				)
			)
		);

		$field = $schema['groups']['main']['fields'][0];
		$this->assertSame( $expected, $field['value'] );
		$this->assertSame( $expected, $field['options'][0]['value'] );
		$this->assertArrayNotHasKey( 'initialValue', $field['save'] );
		SettingsUISchema::assert_valid_schema( $schema );
	}

	/**
	 * Existing option-provider conversion fixtures.
	 *
	 * @return array<string, array{string, bool|int|float, string}>
	 */
	public static function compatible_option_provider_values(): array {
		return array(
			'select integer'    => array( 'select', 1, '1' ),
			'radio boolean'     => array( 'radio', true, 'true' ),
			'extension boolean' => array( 'acme/custom', false, 'false' ),
		);
	}

	/**
	 * @testdox It rejects conflicting legacy and canonical numeric bounds.
	 */
	public function test_canonicalize_schema_values_rejects_conflicting_bounds(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'min disagrees between customAttributes and validation' );

		SettingsUISchema::canonicalize_schema_values(
			$this->get_native_schema_with_field(
				array(
					'id'               => 'acme_number',
					'label'            => 'Number',
					'type'             => 'number',
					'value'            => 2,
					'customAttributes' => array( 'min' => '1' ),
					'validation'       => array( 'min' => 2 ),
					'save'             => array( 'adapter' => 'custom' ),
				)
			)
		);
	}

	/**
	 * @testdox It uses PHP's warning-free shifted instant for a store-local DST gap.
	 *
	 * @dataProvider dst_gap_local_datetime_values
	 *
	 * @param string $value Store-local datetime in New York's spring-forward gap.
	 */
	public function test_canonicalize_schema_values_accepts_dst_gap_local_datetime( string $value ): void {
		$this->setExpectedIncorrectUsage( SettingsUISchema::class . '::canonicalize_schema_values' );

		$original_timezone = get_option( 'timezone_string' );
		update_option( 'timezone_string', 'America/New_York' );

		try {
			$schema = SettingsUISchema::canonicalize_schema_values(
				$this->get_native_schema_with_field(
					array(
						'id'    => 'acme_start',
						'label' => 'Starts',
						'type'  => 'datetime-local',
						'value' => $value,
						'save'  => array(
							'adapter' => 'form_post',
							'name'    => 'acme_start',
						),
					)
				)
			);
		} finally {
			update_option( 'timezone_string', $original_timezone );
		}

		$field = $schema['groups']['main']['fields'][0];
		$this->assertSame( '2026-03-08T03:30:00-04:00', $field['value'] );
	}

	/**
	 * Store-local datetimes that fall in America/New_York's 2026 spring-forward gap.
	 *
	 * @return array<string, array{string}>
	 */
	public static function dst_gap_local_datetime_values(): array {
		return array(
			'without seconds' => array( '2026-03-08T02:30' ),
			'with seconds'    => array( '2026-03-08T02:30:00' ),
		);
	}

	/**
	 * @testdox It accepts local, UTC, and signed-offset datetime grammar with optional seconds.
	 *
	 * @dataProvider valid_datetime_grammar_values
	 *
	 * @param string $value Candidate datetime value.
	 * @param string $expected Expected canonical datetime.
	 * @param bool   $expects_notice Whether compatibility conversion is expected.
	 */
	public function test_canonicalize_schema_values_accepts_datetime_grammar_boundaries( string $value, string $expected, bool $expects_notice ): void {
		if ( $expects_notice ) {
			$this->setExpectedIncorrectUsage( SettingsUISchema::class . '::canonicalize_schema_values' );
		}

		$original_timezone = get_option( 'timezone_string' );
		update_option( 'timezone_string', 'UTC' );

		try {
			$schema = SettingsUISchema::canonicalize_schema_values(
				$this->get_native_schema_with_field(
					array(
						'id'    => 'acme_start',
						'label' => 'Starts',
						'type'  => 'datetime-local',
						'value' => $value,
						'save'  => array( 'adapter' => 'custom' ),
					)
				)
			);

			$this->assertSame( $expected, $schema['groups']['main']['fields'][0]['value'] );
		} finally {
			update_option( 'timezone_string', $original_timezone );
		}
	}

	/**
	 * Valid datetime grammar fixtures.
	 *
	 * @return array<string, array{string, string, bool}>
	 */
	public static function valid_datetime_grammar_values(): array {
		return array(
			'local without seconds'           => array( '2026-08-03T12:30', '2026-08-03T12:30:00+00:00', true ),
			'local with seconds'              => array( '2026-08-03T12:30:45', '2026-08-03T12:30:45+00:00', true ),
			'UTC Z without seconds'           => array( '2026-08-03T12:30Z', '2026-08-03T12:30:00+00:00', true ),
			'UTC Z with seconds'              => array( '2026-08-03T12:30:45Z', '2026-08-03T12:30:45+00:00', true ),
			'positive offset without seconds' => array( '2026-08-03T12:30+02:30', '2026-08-03T12:30:00+02:30', true ),
			'negative offset with seconds'    => array( '2026-08-03T12:30:45-04:00', '2026-08-03T12:30:45-04:00', false ),
		);
	}

	/**
	 * @testdox It rejects invalid dates and malformed datetime offsets.
	 *
	 * @dataProvider invalid_datetime_grammar_values
	 *
	 * @param string $value Candidate datetime value.
	 */
	public function test_canonicalize_schema_values_rejects_invalid_datetime_grammar( string $value ): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'datetime value is malformed' );

		SettingsUISchema::canonicalize_schema_values(
			$this->get_native_schema_with_field(
				array(
					'id'    => 'acme_start',
					'label' => 'Starts',
					'type'  => 'datetime-local',
					'value' => $value,
					'save'  => array( 'adapter' => 'custom' ),
				)
			)
		);
	}

	/**
	 * Invalid datetime grammar fixtures.
	 *
	 * @return array<string, array{string}>
	 */
	public static function invalid_datetime_grammar_values(): array {
		return array(
			'invalid local date'     => array( '2026-02-30T12:00' ),
			'invalid qualified date' => array( '2026-02-30T12:00Z' ),
			'short offset hour'      => array( '2026-08-03T12:30+2:00' ),
			'short offset minute'    => array( '2026-08-03T12:30+02:0' ),
			'compact offset'         => array( '2026-08-03T12:30+0200' ),
			'offset without minutes' => array( '2026-08-03T12:30+02' ),
			'offset +24 hours'       => array( '2026-08-03T12:30+24:00' ),
			'offset -24 hours'       => array( '2026-08-03T12:30-24:00' ),
			'offset +60 minutes'     => array( '2026-08-03T12:30+02:60' ),
			'offset -99 minutes'     => array( '2026-08-03T12:30-02:99' ),
		);
	}

	/**
	 * @testdox It leaves fully canonical native typed values unchanged without a compatibility notice.
	 */
	public function test_canonicalize_schema_values_leaves_canonical_native_values_unchanged(): void {
		$schema = $this->get_native_schema_with_fields(
			array(
				array(
					'id'               => 'acme_number',
					'label'            => 'Number',
					'type'             => 'number',
					'value'            => 2,
					'customAttributes' => array( 'step' => 1 ),
					'save'             => array( 'adapter' => 'custom' ),
				),
				array(
					'id'    => 'acme_integer',
					'label' => 'Integer',
					'type'  => 'integer',
					'value' => 2,
					'save'  => array( 'adapter' => 'custom' ),
				),
				array(
					'id'    => 'acme_start',
					'label' => 'Starts',
					'type'  => 'datetime-local',
					'value' => '2026-08-03T12:30:00+00:00',
					'save'  => array( 'adapter' => 'custom' ),
				),
			)
		);

		$this->assertSame( $schema, SettingsUISchema::canonicalize_schema_values( $schema ) );
	}

	/**
	 * @testdox It requires checkbox form representations to match the classic sanitizer meaning.
	 */
	public function test_canonicalize_schema_values_rejects_incompatible_checkbox_form_value(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'must define save.initialValue' );

		SettingsUISchema::canonicalize_schema_values(
			$this->get_native_schema_with_field(
				array(
					'id'    => 'acme_enabled',
					'label' => 'Enabled',
					'type'  => 'checkbox',
					'value' => 'true',
					'save'  => array(
						'adapter' => 'form_post',
						'name'    => 'acme_enabled',
					),
				)
			)
		);
	}

	/**
	 * @testdox It mirrors canonical native validation without reporting a compatibility conversion.
	 */
	public function test_canonicalize_schema_values_silently_mirrors_canonical_validation(): void {
		$schema = $this->get_native_schema_with_field(
			array(
				'id'         => 'acme_number',
				'label'      => 'Number',
				'type'       => 'number',
				'value'      => 1.5,
				'validation' => array(
					'min' => 0.5,
					'max' => 2.5,
				),
				'save'       => array( 'adapter' => 'custom' ),
			)
		);

		$canonical = SettingsUISchema::canonicalize_schema_values( $schema );
		$field     = $canonical['groups']['main']['fields'][0];

		$this->assertSame( $field['validation'], $field['customAttributes'] );
	}

	/**
	 * @testdox It reports and mirrors native legacy numeric custom attributes into validation metadata.
	 */
	public function test_canonicalize_schema_values_reports_and_mirrors_legacy_numeric_attributes(): void {
		$this->setExpectedIncorrectUsage( SettingsUISchema::class . '::canonicalize_schema_values' );

		$schema = $this->get_native_schema_with_field(
			array(
				'id'               => 'acme_number',
				'label'            => 'Number',
				'type'             => 'number',
				'value'            => 1.5,
				'customAttributes' => array(
					'min' => '0.5',
					'max' => '2.5',
				),
				'save'             => array( 'adapter' => 'none' ),
			)
		);

		$canonical = SettingsUISchema::canonicalize_schema_values( $schema );
		$field     = $canonical['groups']['main']['fields'][0];
		$bounds    = array(
			'min' => 0.5,
			'max' => 2.5,
		);

		$this->assertSame( $bounds, $field['customAttributes'] );
		$this->assertSame( $bounds, $field['validation'] );
		SettingsUISchema::assert_valid_schema( $canonical );
	}

	/**
	 * @testdox It treats empty numeric min and max attributes as absent.
	 */
	public function test_canonicalize_schema_values_omits_empty_numeric_bounds(): void {
		$schema = $this->get_native_schema_with_field(
			array(
				'id'               => 'acme_number',
				'label'            => 'Number',
				'type'             => 'number',
				'value'            => 1.5,
				'customAttributes' => array(
					'min'  => '',
					'max'  => '   ',
					'step' => 'any',
				),
				'save'             => array( 'adapter' => 'none' ),
			)
		);

		$canonical = SettingsUISchema::canonicalize_schema_values( $schema );
		$field     = $canonical['groups']['main']['fields'][0];

		$this->assertSame( array( 'step' => 'any' ), $field['customAttributes'] );
		$this->assertArrayNotHasKey( 'validation', $field );
		SettingsUISchema::assert_valid_schema( $canonical );
	}

	/**
	 * @testdox It does not read option-backed values for non-saving legacy fields.
	 */
	public function test_from_legacy_settings_does_not_read_options_for_non_saving_fields(): void {
		$option_reads = 0;
		$listener     = static function ( $fallback_value ) use ( &$option_reads ) {
			++$option_reads;
			return $fallback_value;
		};
		add_filter( 'default_option_acme_external', $listener );

		try {
			SettingsUISchema::from_legacy_settings(
				'acme',
				'',
				'Acme',
				array(
					array(
						'id'        => 'acme_external',
						'label'     => 'External',
						'type'      => 'text',
						'is_option' => false,
					),
				)
			);
			SettingsUISchema::from_legacy_settings(
				'acme',
				'',
				'Acme',
				array(
					array(
						'id'    => 'acme_external',
						'label' => 'External',
						'type'  => 'text',
					),
				),
				'custom'
			);
		} finally {
			remove_filter( 'default_option_acme_external', $listener );
		}

		$this->assertSame( 0, $option_reads );
	}

	/**
	 * @testdox It preserves an unchanged numeric form value through the classic save pipeline.
	 */
	public function test_schema_post_preserves_unchanged_numeric_form_value(): void {
		$settings = $this->get_numeric_save_settings();
		update_option( 'acme_quantity', '02' );

		try {
			$schema   = SettingsUISchema::from_legacy_settings( 'acme', '', 'Acme', $settings );
			$post     = $this->get_schema_post_data( $schema );
			$captured = $this->save_fields_and_capture( $settings, $post, 'acme_quantity' );

			$this->assertSame( array( 'acme_quantity' => '02' ), $post );
			$this->assertSame( '02', $captured['global']['raw'] );
			$this->assertSame( '02', $captured['global']['sanitized'] );
			$this->assertSame( '02', $captured['specific']['raw'] );
			$this->assertSame( '02', $captured['specific']['sanitized'] );
			$this->assertSame( '02', get_option( 'acme_quantity' ) );
			$this->assertSame( '02', $this->get_raw_option_value( 'acme_quantity' ) );
		} finally {
			delete_option( 'acme_quantity' );
		}
	}

	/**
	 * @testdox It sends edited numeric state through the existing classic sanitizer.
	 */
	public function test_schema_post_saves_edited_numeric_value_canonically(): void {
		$settings = $this->get_numeric_save_settings();
		update_option( 'acme_quantity', '01' );

		try {
			$schema   = SettingsUISchema::from_legacy_settings( 'acme', '', 'Acme', $settings );
			$post     = $this->get_schema_post_data( $schema, array( 'acme_quantity' => '2' ) );
			$captured = $this->save_fields_and_capture( $settings, $post, 'acme_quantity' );

			$this->assertSame( array( 'acme_quantity' => '2' ), $post );
			$this->assertSame( '2', $captured['global']['raw'] );
			$this->assertSame( '2', $captured['global']['sanitized'] );
			$this->assertSame( '2', $captured['specific']['raw'] );
			$this->assertSame( '2', $captured['specific']['sanitized'] );
			$this->assertSame( '2', get_option( 'acme_quantity' ) );
			$this->assertSame( '2', $this->get_raw_option_value( 'acme_quantity' ) );
		} finally {
			delete_option( 'acme_quantity' );
		}
	}

	/**
	 * @testdox It omits an unchanged empty legacy multiselect like the classic form.
	 */
	public function test_schema_post_omits_unchanged_empty_legacy_multiselect(): void {
		$settings = array(
			array(
				'id'      => 'acme_methods',
				'title'   => 'Methods',
				'type'    => 'multiselect',
				'options' => array( 'card' => 'Card' ),
			),
		);
		update_option( 'acme_methods', '' );

		try {
			$schema       = SettingsUISchema::from_legacy_settings( 'acme', '', 'Acme', $settings );
			$field        = $schema['groups']['default']['fields'][0];
			$post         = $this->get_schema_post_data( $schema );
			$post['save'] = 'Save changes';

			$this->assertSame( array(), $field['save']['initialValue'] );
			$this->assertArrayNotHasKey( 'acme_methods', $post );

			$captured = $this->save_fields_and_capture( $settings, $post, 'acme_methods' );
			$this->assertNull( $captured['global']['raw'] );
			$this->assertSame( array(), $captured['global']['sanitized'] );
			$this->assertNull( $captured['specific']['raw'] );
			$this->assertSame( array(), $captured['specific']['sanitized'] );
		} finally {
			delete_option( 'acme_methods' );
		}
	}

	/**
	 * @testdox It preserves a native checkbox through the classic save pipeline with a compatible original value.
	 */
	public function test_schema_post_preserves_native_checkbox_form_value(): void {
		$this->setExpectedIncorrectUsage( SettingsUISchema::class . '::canonicalize_schema_values' );

		$settings = array(
			array(
				'id'    => 'acme_enabled',
				'title' => 'Enabled',
				'type'  => 'checkbox',
			),
		);
		update_option( 'acme_enabled', 'yes' );

		try {
			$schema = SettingsUISchema::canonicalize_schema_values(
				$this->get_native_schema_with_field(
					array(
						'id'    => 'acme_enabled',
						'label' => 'Enabled',
						'type'  => 'checkbox',
						'value' => 'true',
						'save'  => array(
							'adapter'      => 'form_post',
							'name'         => 'acme_enabled',
							'initialValue' => 'yes',
						),
					)
				)
			);
			SettingsUISchema::assert_valid_schema( $schema );
			$post     = $this->get_schema_post_data( $schema );
			$captured = $this->save_fields_and_capture( $settings, $post, 'acme_enabled' );

			$this->assertSame( array( 'acme_enabled' => 'yes' ), $post );
			$this->assertSame( 'yes', $captured['global']['raw'] );
			$this->assertSame( 'yes', $captured['specific']['sanitized'] );
			$this->assertSame( 'yes', get_option( 'acme_enabled' ) );
			$this->assertSame( 'yes', $this->get_raw_option_value( 'acme_enabled' ) );
		} finally {
			delete_option( 'acme_enabled' );
		}
	}

	/**
	 * @testdox It rejects form-post names that the hidden-input serializer cannot represent.
	 */
	public function test_assert_valid_schema_rejects_unsupported_form_post_names(): void {
		$schema = self::get_valid_schema_for_validation();
		$schema['groups']['main']['fields'][0]['save']['name'] = 'settings[group][quantity';

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'is not a supported form-post field name' );
		SettingsUISchema::assert_valid_schema( $schema );
	}

	/**
	 * @testdox It rejects list initial values for scalar fields before form serialization.
	 */
	public function test_assert_valid_schema_rejects_list_initial_value_for_scalar_field(): void {
		$schema                        = self::get_valid_schema_for_validation();
		$field                         = &$schema['groups']['main']['fields'][0];
		$field['type']                 = 'number';
		$field['value']                = 2;
		$field['save']['initialValue'] = array( '01', '02' );

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'save.initialValue cannot be replayed safely through classic form-post semantics' );
		SettingsUISchema::assert_valid_schema( $schema );
	}

	/**
	 * @testdox It accepts the case-insensitive any keyword for number steps.
	 */
	public function test_assert_valid_schema_accepts_case_insensitive_any_number_step(): void {
		$schema                    = self::get_valid_schema_for_validation();
		$field                     = &$schema['groups']['main']['fields'][0];
		$field['type']             = 'number';
		$field['value']            = 2;
		$field['customAttributes'] = array( 'step' => 'AnY' );

		SettingsUISchema::assert_valid_schema( $schema );
		$this->addToAssertionCount( 1 );
	}

	/**
	 * @testdox It rejects non-positive number steps.
	 *
	 * @dataProvider invalid_number_steps
	 *
	 * @param int|float|string $step Invalid number step.
	 */
	public function test_assert_valid_schema_rejects_invalid_number_steps( $step ): void {
		$schema                    = self::get_valid_schema_for_validation();
		$field                     = &$schema['groups']['main']['fields'][0];
		$field['type']             = 'number';
		$field['value']            = 2;
		$field['customAttributes'] = array( 'step' => $step );

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'custom attribute "step" must be a positive finite number or "any"' );
		SettingsUISchema::assert_valid_schema( $schema );
	}

	/**
	 * Invalid number step fixtures.
	 *
	 * @return array<string, array{int|float|string}>
	 */
	public static function invalid_number_steps(): array {
		return array(
			'integer zero'    => array( 0 ),
			'decimal zero'    => array( 0.0 ),
			'negative number' => array( -0.5 ),
			'zero string'     => array( '0' ),
		);
	}

	/**
	 * @testdox It rejects integer steps that cannot preserve integer values.
	 *
	 * @dataProvider invalid_integer_steps
	 *
	 * @param int|float|string $step Invalid integer step.
	 */
	public function test_assert_valid_schema_rejects_invalid_integer_steps( $step ): void {
		$schema                    = self::get_valid_schema_for_validation();
		$field                     = &$schema['groups']['main']['fields'][0];
		$field['type']             = 'integer';
		$field['value']            = 2;
		$field['customAttributes'] = array( 'step' => $step );

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'custom attribute "step" must be a positive integer' );
		SettingsUISchema::assert_valid_schema( $schema );
	}

	/**
	 * Invalid integer step fixtures.
	 *
	 * @return array<string, array{int|float|string}>
	 */
	public static function invalid_integer_steps(): array {
		return array(
			'fractional' => array( 0.5 ),
			'zero'       => array( 0 ),
			'negative'   => array( -1 ),
			'any'        => array( 'any' ),
		);
	}

	/**
	 * @testdox It preserves classic POST shapes for nested, checkbox, array, and datetime fields.
	 */
	public function test_schema_post_matches_classic_shapes_for_typed_fields(): void {
		$original_timezone = get_option( 'timezone_string' );
		$settings          = array(
			array(
				'id'                => 'acme_quantity',
				'field_name'        => 'acme_settings[quantity]',
				'title'             => 'Quantity',
				'type'              => 'number',
				'custom_attributes' => array( 'step' => 1 ),
			),
			array(
				'id'    => 'acme_enabled',
				'title' => 'Enabled',
				'type'  => 'checkbox',
			),
			array(
				'id'         => 'acme_methods',
				'field_name' => 'acme_settings[methods][]',
				'title'      => 'Methods',
				'type'       => 'multiselect',
				'options'    => array(
					'card' => 'Card',
					'link' => 'Link',
				),
			),
			array(
				'id'    => 'acme_start',
				'title' => 'Starts',
				'type'  => 'datetime-local',
			),
		);
		$option_names      = array( 'acme_settings', 'acme_enabled', 'acme_start' );

		update_option(
			'acme_settings',
			array(
				'quantity' => '02',
				'methods'  => array( 'card' ),
				'other'    => 'keep',
			)
		);
		update_option( 'acme_enabled', 'yes' );
		update_option( 'acme_start', '2026-08-03T12:30' );
		update_option( 'timezone_string', 'America/New_York' );

		$captured = array();
		$listener = static function ( $value, $option, $raw_value ) use ( &$captured ) {
			$captured[ $option['id'] ] = $raw_value;
			return $value;
		};
		add_filter( 'woocommerce_admin_settings_sanitize_option', $listener, 10, 3 );

		try {
			include_once WC_ABSPATH . 'includes/admin/class-wc-admin-settings.php';
			$schema = SettingsUISchema::from_legacy_settings( 'acme', '', 'Acme', $settings );
			$post   = $this->get_schema_post_data(
				$schema,
				array(
					'acme_quantity' => '3',
					'acme_enabled'  => 'no',
					'acme_methods'  => array( 'card', 'link' ),
					'acme_start'    => '2026-08-03T13:45:00',
				)
			);

			$this->assertTrue( \WC_Admin_Settings::save_fields( $settings, $post ) );
			$this->clear_option_caches( $option_names );

			$this->assertSame(
				array(
					'acme_settings' => array(
						'quantity' => '3',
						'methods'  => array( 'card', 'link' ),
					),
					'acme_enabled'  => 'no',
					'acme_start'    => '2026-08-03T13:45:00',
				),
				$post
			);
			$this->assertSame( '3', $captured['acme_quantity'] );
			$this->assertSame( 'no', $captured['acme_enabled'] );
			$this->assertSame( array( 'card', 'link' ), $captured['acme_methods'] );
			$this->assertSame( '2026-08-03T13:45:00', $captured['acme_start'] );
			$this->assertSame(
				array(
					'quantity' => '3',
					'methods'  => array( 'card', 'link' ),
					'other'    => 'keep',
				),
				get_option( 'acme_settings' )
			);
			$this->assertSame( 'no', get_option( 'acme_enabled' ) );
			$this->assertSame( '2026-08-03T13:45:00', get_option( 'acme_start' ) );
			$this->assertSame( maybe_serialize( get_option( 'acme_settings' ) ), $this->get_raw_option_value( 'acme_settings' ) );
		} finally {
			remove_filter( 'woocommerce_admin_settings_sanitize_option', $listener, 10 );
			update_option( 'timezone_string', $original_timezone );
			foreach ( $option_names as $option_name ) {
				delete_option( $option_name );
			}
		}
	}

	/**
	 * @testdox It preserves valid native form values and accepts explicit original representations.
	 */
	public function test_canonicalize_schema_values_preserves_native_form_representations(): void {
		$this->setExpectedIncorrectUsage( SettingsUISchema::class . '::canonicalize_schema_values' );

		$schema = SettingsUISchema::canonicalize_schema_values(
			$this->get_native_schema_with_fields(
				array(
					array(
						'id'    => 'acme_quantity',
						'label' => 'Quantity',
						'type'  => 'number',
						'value' => '02',
						'save'  => array(
							'adapter' => 'form_post',
							'name'    => 'acme_quantity',
						),
					),
					array(
						'id'      => 'acme_methods',
						'label'   => 'Methods',
						'type'    => 'array',
						'value'   => array( 1 ),
						'options' => array(
							array(
								'label' => 'One',
								'value' => '1',
							),
						),
						'save'    => array(
							'adapter'      => 'form_post',
							'name'         => 'acme_methods',
							'initialValue' => array( 'legacy-one' ),
						),
					),
				)
			)
		);

		$fields = $schema['groups']['main']['fields'];
		$this->assertSame( 2, $fields[0]['value'] );
		$this->assertSame( '02', $fields[0]['save']['initialValue'] );
		$this->assertSame( array( '1' ), $fields[1]['value'] );
		$this->assertSame( array( 'legacy-one' ), $fields[1]['save']['initialValue'] );
	}

	/**
	 * @testdox It accepts canonical native values and extension transport values.
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
		if ( 'info' === $type ) {
			unset( $field['value'] );
			$field['save'] = array( 'adapter' => 'none' );
		}

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
			'array'                 => array( 'array', array( 'a' ) ),
			'checkbox'              => array( 'checkbox', true ),
			'date'                  => array( 'date', '2026-08-03' ),
			'datetime-local'        => array( 'datetime-local', '2026-08-03T12:30:00+00:00' ),
			'email'                 => array( 'email', 'merchant@example.com' ),
			'info'                  => array( 'info', null ),
			'integer'               => array( 'integer', 2 ),
			'number'                => array( 'number', 2 ),
			'password'              => array( 'password', 'secret' ),
			'radio'                 => array( 'radio', 'a' ),
			'select'                => array( 'select', 'a' ),
			'tel'                   => array( 'tel', '+1 555 555 5555' ),
			'text'                  => array( 'text', 'Acme' ),
			'textarea'              => array( 'textarea', 'Acme description' ),
			'time'                  => array( 'time', '12:30' ),
			'url'                   => array( 'url', 'https://example.com' ),
			'extension string'      => array( 'acme/custom', 'Acme' ),
			'extension integer'     => array( 'acme/custom', 10 ),
			'extension float'       => array( 'acme/custom', 10.5 ),
			'extension boolean'     => array( 'acme/custom', true ),
			'extension string list' => array( 'acme/custom', array( 'one', 'two' ) ),
			'extension null'        => array( 'acme/custom', null ),
		);
	}

	/**
	 * @testdox It accepts HTML range attributes for native temporal fields.
	 *
	 * @dataProvider native_temporal_fields_with_range_attributes
	 *
	 * @param string $type Field type.
	 * @param string $value Field value.
	 * @param array  $custom_attributes HTML range attributes.
	 */
	public function test_assert_valid_schema_accepts_range_attributes_for_native_temporal_fields( string $type, string $value, array $custom_attributes ): void {
		$field = array(
			'id'               => 'acme_' . $type,
			'label'            => 'Acme ' . $type,
			'type'             => $type,
			'value'            => $value,
			'customAttributes' => $custom_attributes,
			'save'             => array( 'adapter' => 'form_post' ),
		);

		SettingsUISchema::assert_valid_schema( $this->get_native_schema_with_field( $field ) );
		$this->addToAssertionCount( 1 );
	}

	/**
	 * Native temporal field fixtures with valid HTML range attributes.
	 *
	 * @return array<string, array{string, string, array<string, int|string>}>
	 */
	public static function native_temporal_fields_with_range_attributes(): array {
		return array(
			'date'           => array(
				'date',
				'2026-08-03',
				array(
					'min'  => '2026-01-01',
					'max'  => '2026-12-31',
					'step' => 1,
				),
			),
			'time'           => array(
				'time',
				'12:30',
				array(
					'min'  => '09:00',
					'max'  => '17:00',
					'step' => 900,
				),
			),
			'datetime-local' => array(
				'datetime-local',
				'2026-08-03T12:30:00+00:00',
				array(
					'min'  => '2026-08-03T09:00',
					'max'  => '2026-08-03T17:00',
					'step' => 'any',
				),
			),
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
		$invalid_bound = $valid;
		$invalid_bound['groups']['main']['fields'][0]['customAttributes'] = array( 'min' => 1 );
		$bad_validation = $valid;
		$bad_validation['groups']['main']['fields'][0]['validation'] = 'invalid';
		$text_validation = $valid;
		$text_validation['groups']['main']['fields'][0]['validation'] = array( 'min' => 1 );
		$bad_rule                                        = $valid;
		$bad_rule['groups']['main']['fields'][0]['type'] = 'number';
		$bad_rule['groups']['main']['fields'][0]['value']      = 1;
		$bad_rule['groups']['main']['fields'][0]['validation'] = array( 'step' => 1 );
		$null_bound                                        = $valid;
		$null_bound['groups']['main']['fields'][0]['type'] = 'number';
		$null_bound['groups']['main']['fields'][0]['value']      = 1;
		$null_bound['groups']['main']['fields'][0]['validation'] = array( 'min' => null );
		$infinite_bound                                        = $valid;
		$infinite_bound['groups']['main']['fields'][0]['type'] = 'number';
		$infinite_bound['groups']['main']['fields'][0]['value']      = 1;
		$infinite_bound['groups']['main']['fields'][0]['validation'] = array( 'max' => INF );
		$fractional_bound                                        = $valid;
		$fractional_bound['groups']['main']['fields'][0]['type'] = 'integer';
		$fractional_bound['groups']['main']['fields'][0]['value']      = 1;
		$fractional_bound['groups']['main']['fields'][0]['validation'] = array( 'min' => 0.5 );
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
			'invalid field value'         => array( $invalid_field_value, 'Field "acme_field" value is invalid for type "text".' ),
			'invalid custom attributes'   => array( $invalid_custom_attributes, 'Field "acme_field" customAttributes must be a map.' ),
			'invalid custom value'        => array( $invalid_custom_attribute_value, 'Field "acme_field" custom attribute "data-values" has an invalid value.' ),
			'non-finite custom value'     => array( $invalid_custom_attribute_float, 'Field "acme_field" custom attribute "data-value" has an invalid value.' ),
			'bound on text field'         => array( $invalid_bound, 'Field "acme_field" may define "min" only when its type supports range attributes.' ),
			'invalid validation metadata' => array( $bad_validation, 'Field "acme_field" validation is supported only for numeric fields.' ),
			'validation on text field'    => array( $text_validation, 'Field "acme_field" validation is supported only for numeric fields.' ),
			'unsupported validation rule' => array( $bad_rule, 'Field "acme_field" validation rule "step" must be a finite numeric bound.' ),
			'null validation bound'       => array( $null_bound, 'Field "acme_field" validation rule "min" must be a finite numeric bound.' ),
			'non-finite validation bound' => array( $infinite_bound, 'Field "acme_field" validation rule "max" must be a finite numeric bound.' ),
			'fractional integer bound'    => array( $fractional_bound, 'Field "acme_field" validation rule "min" must be an integer.' ),
			'saving info field'           => array( $invalid_info, 'Field "acme_field" of type "info" must use the "none" save adapter.' ),
			'malformed shell navigation'  => array( $invalid_shell, 'Shell navigation item 0 href must be a string.' ),
			'malformed breadcrumb'        => array( $invalid_breadcrumb, 'Shell breadcrumb 0 label must be a string.' ),
			'invalid badge intent'        => array( $invalid_badge, 'Shell badge 0 intent must be a string.' ),
			'custom save without handler' => array( $invalid_page_save, 'Schema custom save strategy must define a non-empty handler.' ),
			'empty navigation component'  => array( $invalid_navigation_component, 'Shell navigationComponent must be a non-empty string.' ),
			'group map id mismatch'       => array( $invalid_group_map, 'Group map key "other" must match group id "main".' ),
		);
	}

	/**
	 * @testdox It accepts valid optional shell, field, visibility, and save metadata.
	 */
	public function test_assert_valid_schema_accepts_optional_metadata(): void {
		$schema          = self::get_valid_schema_for_validation();
		$schema['save']  = array(
			'adapter' => 'custom',
			'handler' => 'acme/save',
		);
		$schema['shell'] = array(
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

	/**
	 * Get a legacy numeric field used by save-pipeline tests.
	 *
	 * @return array
	 */
	private function get_numeric_save_settings(): array {
		return array(
			array(
				'id'                => 'acme_quantity',
				'title'             => 'Quantity',
				'type'              => 'number',
				'custom_attributes' => array( 'step' => 1 ),
			),
		);
	}

	/**
	 * Build classic POST data from schema fields and optional edited values.
	 *
	 * @param array $schema Settings UI schema.
	 * @param array $edited_form_values Serialized values changed by the client, keyed by field id.
	 * @return array
	 */
	private function get_schema_post_data( array $schema, array $edited_form_values = array() ): array {
		$post = array();

		foreach ( $schema['groups'] as $group ) {
			foreach ( $group['fields'] as $field ) {
				if ( 'form_post' !== ( $field['save']['adapter'] ?? null ) ) {
					continue;
				}

				if ( array_key_exists( $field['id'], $edited_form_values ) ) {
					$form_value = $edited_form_values[ $field['id'] ];
				} elseif ( array_key_exists( 'initialValue', $field['save'] ) ) {
					$form_value = $field['save']['initialValue'];
				} else {
					continue;
				}
				if ( array() === $form_value ) {
					continue;
				}

				$name      = $field['save']['name'] ?? $field['id'];
				$base_name = '[]' === substr( $name, -2 ) ? substr( $name, 0, -2 ) : $name;
				$open      = strpos( $base_name, '[' );
				if ( false === $open ) {
					$post[ $base_name ] = $form_value;
					continue;
				}

				$parent                     = substr( $base_name, 0, $open );
				$member                     = substr( $base_name, $open + 1, -1 );
				$post[ $parent ][ $member ] = $form_value;
			}
		}

		return $post;
	}

	/**
	 * Save fields while capturing global and option-specific sanitizer inputs.
	 *
	 * @param array  $settings Legacy settings definitions.
	 * @param array  $post Schema-derived POST data.
	 * @param string $option_name Option name.
	 * @return array
	 */
	private function save_fields_and_capture( array $settings, array $post, string $option_name ): array {
		include_once WC_ABSPATH . 'includes/admin/class-wc-admin-settings.php';

		$captured = array();
		$global   = static function ( $value, $option, $raw_value ) use ( &$captured ) {
			unset( $option );
			$captured['global'] = array(
				'raw'       => $raw_value,
				'sanitized' => $value,
			);
			return $value;
		};
		$specific = static function ( $value, $option, $raw_value ) use ( &$captured ) {
			unset( $option );
			$captured['specific'] = array(
				'raw'       => $raw_value,
				'sanitized' => $value,
			);
			return $value;
		};

		add_filter( 'woocommerce_admin_settings_sanitize_option', $global, 10, 3 );
		add_filter( 'woocommerce_admin_settings_sanitize_option_' . $option_name, $specific, 10, 3 );

		try {
			$this->assertTrue( \WC_Admin_Settings::save_fields( $settings, $post ) );
		} finally {
			remove_filter( 'woocommerce_admin_settings_sanitize_option', $global, 10 );
			remove_filter( 'woocommerce_admin_settings_sanitize_option_' . $option_name, $specific, 10 );
		}

		$this->clear_option_caches( array( $option_name ) );

		return $captured;
	}

	/**
	 * Clear option caches before persistence assertions.
	 *
	 * @param string[] $option_names Option names.
	 */
	private function clear_option_caches( array $option_names ): void {
		foreach ( $option_names as $option_name ) {
			wp_cache_delete( $option_name, 'options' );
		}

		wp_cache_delete( 'alloptions', 'options' );
		wp_cache_delete( 'notoptions', 'options' );
	}

	/**
	 * Read an option's raw database representation.
	 *
	 * @param string $option_name Option name.
	 * @return string|null
	 */
	private function get_raw_option_value( string $option_name ): ?string {
		global $wpdb;

		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
				$option_name
			)
		);
	}
}
