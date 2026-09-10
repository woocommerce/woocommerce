<?php
/**
 * Settings UI View Config tests.
 *
 * @package WooCommerce\Tests\Internal\Admin\Settings
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings;

use Automattic\WooCommerce\Internal\Admin\Settings\SettingsUISchema;
use Automattic\WooCommerce\Internal\Admin\Settings\SettingsUIViewConfig;
use WC_Unit_Test_Case;

/**
 * Tests for the Settings UI View Config integration.
 */
class SettingsUIViewConfigTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should expose no View Config identity when WordPress lacks the public API.
	 */
	public function test_metadata_matches_wordpress_support(): void {
		$metadata = SettingsUIViewConfig::get_metadata( 'products', 'default' );
		$this->assertSame( array( 'supported' => false ), SettingsUIViewConfig::get_metadata( 'general', 'default' ) );

		if ( SettingsUIViewConfig::is_supported() ) {
			$this->assertTrue( $metadata['supported'] );
			$this->assertSame( 'woocommerce-settings', $metadata['kind'] );
			$this->assertSame( 'products:default', $metadata['name'] );
			$this->assertSame( 1, $metadata['version'] );
		} else {
			$this->assertSame( array( 'supported' => false ), $metadata, 'Unsupported sites must not receive a request identity.' );
		}
	}

	/**
	 * @testdox Should convert the Woo schema to a layout with existing field ids only.
	 */
	public function test_builds_layout_only_form_from_schema(): void {
		$schema = array(
			'id'      => 'products',
			'title'   => 'Products',
			'section' => 'default',
			'save'    => array( 'adapter' => 'form_post' ),
			'shell'   => array( 'title' => 'Products' ),
			'groups'  => array(
				'product_options' => array(
					'id'          => 'product_options',
					'title'       => 'Product options',
					'description' => '<p>Choose product units.</p>',
					'fields'      => array(
						array(
							'id'        => 'woocommerce_weight_unit',
							'type'      => 'select',
							'label'     => 'Weight unit',
							'value'     => 'kg',
							'component' => 'unsafe-component',
						),
					),
				),
			),
		);

		$form = SettingsUISchema::get_dataform_layout( $schema );

		$this->assertSame(
			array(
				'fields' => array(
					array(
						'id'          => 'product_options',
						'layout'      => array(
							'type'          => 'card',
							'isCollapsible' => false,
						),
						'children'    => array( 'woocommerce_weight_unit' ),
						'label'       => 'Product options',
						'description' => 'Choose product units.',
					),
				),
			),
			$form,
			'The View Config form should contain only layout and existing identities.'
		);
		$this->assertStringNotContainsString( 'unsafe-component', wp_json_encode( $form ) );
		$this->assertStringNotContainsString( 'value', wp_json_encode( $form ) );
		$this->assertStringNotContainsString( 'nonce', wp_json_encode( $form ) );
	}
}
