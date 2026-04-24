<?php
/**
 * ModernSettingsSchema tests.
 *
 * @package WooCommerce\Tests\Internal\Admin\Settings
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings;

use Automattic\WooCommerce\Internal\Admin\Settings\ModernSettingsSchema;
use WC_Unit_Test_Case;

/**
 * Tests for ModernSettingsSchema.
 */
class ModernSettingsSchemaTest extends WC_Unit_Test_Case {

	/**
	 * It groups fields that appear before the first title marker.
	 */
	public function test_from_legacy_settings_creates_default_group_for_fields_before_title(): void {
		update_option( 'woocommerce_test_text', 'saved value' );

		$schema = ModernSettingsSchema::from_legacy_settings(
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
		$this->assertSame( 'woocommerce_test_text', $schema['groups']['default']['fields'][0]['id'] );
		$this->assertSame( 'saved value', $schema['groups']['default']['fields'][0]['value'] );
	}

	/**
	 * It keeps component metadata with the field schema.
	 */
	public function test_from_legacy_settings_preserves_component_metadata(): void {
		$schema = ModernSettingsSchema::from_legacy_settings(
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
					'id'        => 'woocommerce_test_component',
					'type'      => 'multiselect',
					'title'     => 'Component field',
					'component' => 'test/component',
					'custom_attributes' => array(
						'min'  => 1,
						'step' => 1,
					),
					'options'   => array(
						'a' => 'Option A',
					),
				),
			)
		);

		$field = $schema['groups']['group']['fields'][0];

		$this->assertSame( 'array', $field['type'] );
		$this->assertSame( 'test/component', $field['component'] );
		$this->assertSame( array( 'min' => 1, 'step' => 1 ), $field['customAttributes'] );
		$this->assertSame( array( array( 'label' => 'Option A', 'value' => 'a' ) ), $field['options'] );
	}

	/**
	 * It preserves sanitized group description markup and header actions.
	 */
	public function test_from_legacy_settings_preserves_group_description_and_actions(): void {
		$schema = ModernSettingsSchema::from_legacy_settings(
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
	 * It supports compound fields that contain multiple persisted child settings.
	 */
	public function test_from_legacy_settings_preserves_compound_child_fields(): void {
		update_option( 'woocommerce_gateway_enabled', 'yes' );
		update_option( 'woocommerce_gateway_locations', array( 'cart', 'checkout' ) );

		$schema = ModernSettingsSchema::from_legacy_settings(
			'test',
			'advanced',
			'Test settings',
			array(
				array(
					'id'     => 'gateway_panel',
					'type'   => 'compound',
					'title'  => 'Gateway panel',
					'fields' => array(
						array(
							'id'    => 'woocommerce_gateway_enabled',
							'type'  => 'checkbox',
							'desc'  => 'Enable gateway',
							'title' => 'Enabled',
						),
						array(
							'id'      => 'woocommerce_gateway_locations',
							'type'    => 'multiselect',
							'title'   => 'Locations',
							'options' => array(
								'cart'     => 'Cart',
								'checkout' => 'Checkout',
							),
						),
					),
				),
			)
		);

		$field = $schema['groups']['default']['fields'][0];

		$this->assertSame( 'compound', $field['type'] );
		$this->assertSame( array( 'adapter' => 'none' ), $field['save'] );
		$this->assertCount( 2, $field['fields'] );
		$this->assertSame( true, $field['fields'][0]['value'] );
		$this->assertSame( array( 'cart', 'checkout' ), $field['fields'][1]['value'] );
		$this->assertSame(
			array(
				'adapter' => 'form_post',
				'name'    => 'woocommerce_gateway_locations',
			),
			$field['fields'][1]['save']
		);
	}

	/**
	 * It uses checkbox descriptions as labels and desc_tip as help text.
	 */
	public function test_from_legacy_settings_uses_checkbox_desc_as_label(): void {
		$schema = ModernSettingsSchema::from_legacy_settings(
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
	 * It does not render boolean desc_tip values as help text.
	 */
	public function test_from_legacy_settings_ignores_boolean_desc_tip(): void {
		$schema = ModernSettingsSchema::from_legacy_settings(
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
	 * It uses legacy field names for form POST save schema.
	 */
	public function test_from_legacy_settings_uses_field_name_for_form_post_save_schema(): void {
		$schema = ModernSettingsSchema::from_legacy_settings(
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
	 * It marks info fields as non-saving when their text is rendered as description.
	 */
	public function test_from_legacy_settings_marks_info_fields_as_non_saving(): void {
		$schema = ModernSettingsSchema::from_legacy_settings(
			'test',
			'',
			'Test settings',
			array(
				array(
					'id'   => 'woocommerce_test_info',
					'type' => 'info',
					'text' => 'Read-only information.',
				),
			)
		);

		$field = $schema['groups']['default']['fields'][0];

		$this->assertSame( 'Read-only information.', $field['description'] );
		$this->assertSame( array( 'adapter' => 'none' ), $field['save'] );
	}
}
