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
					'options'   => array(
						'a' => 'Option A',
					),
				),
			)
		);

		$field = $schema['groups']['group']['fields'][0];

		$this->assertSame( 'array', $field['type'] );
		$this->assertSame( 'test/component', $field['component'] );
		$this->assertSame( array( array( 'label' => 'Option A', 'value' => 'a' ) ), $field['options'] );
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
