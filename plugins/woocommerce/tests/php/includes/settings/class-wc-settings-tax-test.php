<?php
/**
 * Class WC_Settings_Tax_Test file.
 *
 * @package WooCommerce\Tests\Settings
 */

use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\FunctionsMockerHack;
use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\StaticMockerHack;

require_once __DIR__ . '/class-wc-settings-unit-test-case.php';

/**
 * Unit tests for the WC_Settings_Tax class.
 */
class WC_Settings_Tax_Test extends WC_Settings_Unit_Test_Case {

	/**
	 * @testDox 'get_sections' returns the predefined sections as well as one section per existing tax class.
	 */
	public function test_get_sections_returns_predefined_sections_and_one_section_per_tax_class() {
		 $tax_classes = array( 'tax_class_1', 'tax_class_2' );

		StaticMockerHack::add_method_mocks(
			array(
				WC_Tax::class => array(
					'get_tax_classes' => function() use ( $tax_classes ) {
						return $tax_classes;
					},
				),
			)
		);

		$sut      = new WC_Settings_Tax();
		$sections = $sut->get_sections();

		$expected = array(
			''            => 'Tax options',
			'standard'    => 'Standard rates',
			'tax_class_1' => 'tax_class_1 rates',
			'tax_class_2' => 'tax_class_2 rates',
		);

		$this->assertEquals( $expected, $sections );
	}

	/**
	 * @testDox 'get_settings' returns the appropriate settings for the default section.
	 */
	public function test_get_settings_for_default_section() {
		$sut = new WC_Settings_Tax();

		$settings               = $sut->get_settings_for_section( '' );
		$settings_ids_and_types = $this->get_ids_and_types( $settings );

		$expected = array(
			'tax_options'                       => array( 'title', 'sectionend' ),
			'woocommerce_prices_include_tax'    => 'radio',
			'woocommerce_tax_based_on'          => 'select',
			'woocommerce_shipping_tax_class'    => 'select',
			'woocommerce_tax_round_at_subtotal' => 'checkbox',
			'woocommerce_tax_classes'           => 'textarea',
			'woocommerce_tax_display_shop'      => 'select',
			'woocommerce_tax_display_cart'      => 'select',
			'woocommerce_price_display_suffix'  => 'text',
			'woocommerce_tax_total_display'     => 'select',
			''                                  => 'conflict_error',
		);

		$this->assertEquals( $expected, $settings_ids_and_types );
	}

	/**
	 * @testDox 'output' invokes 'output_tax_rates' for the 'standard' section and for sections named as a tax class.
	 *
	 * @testWith ["standard"]
	 *           ["tax_class_slug"]
	 *
	 * @param string $section_name Current section name.
	 */
	public function test_output_for_standard_section_and_known_tax_class( $section_name ) {
		global $current_section;
		$current_section = $section_name;

		$output_tax_rates_invoked = false;

		StaticMockerHack::add_method_mocks(
			array(
				'WC_Tax' => array(
					'get_tax_class_slugs' => function() {
						return array( 'tax_class_slug' );
					},
				),
			)
		);

		$sut = $this->getMockBuilder( WC_Settings_Tax::class )
					->setMethods( array( 'output_tax_rates' ) )
					->getMock();

		$sut->method( 'output_tax_rates' )->will(
			$this->returnCallback(
				function() use ( &$output_tax_rates_invoked ) {
					$output_tax_rates_invoked = true;
				}
			)
		);

		$sut->output();

		$this->assertTrue( $output_tax_rates_invoked );
	}

	/**
	 * @testDox 'output' fallbacks to 'output_fields' in WC_Admin_Settings for an unknown tax class.
	 */
	public function test_output_for_unknown_tax_class() {
		global $current_section;
		$current_section = 'foobar';

		$output_fields_in_admin_settings_invoked = false;

		StaticMockerHack::add_method_mocks(
			array(
				'WC_Admin_Settings' => array(
					'output_fields' => function( $settings ) use ( &$output_fields_in_admin_settings_invoked ) {
						$output_fields_in_admin_settings_invoked = true;
					},
				),
				'WC_Tax'            => array(
					'get_tax_class_slugs' => function() {
						return array( 'tax_class_slug' );
					},
				),
			)
		);

		$sut = new WC_Settings_Tax();

		$sut->output();

		$this->assertTrue( $output_fields_in_admin_settings_invoked );
	}

	/**
	 * @testDox 'save_tax_classes' appropriately creates or deletes the tax classes.
	 */
	public function test_save_tax_classes() {
		$created = array();
		$deleted = array();

		StaticMockerHack::add_method_mocks(
			array(
				'WC_Tax' => array(
					'get_tax_classes'     => function() {
						return array( 'tax_1', 'tax_2', 'tax_3' );
					},
					'delete_tax_class_by' => function( $field, $name ) use ( &$deleted ) {
						$deleted[] = $name;
					},
					'create_tax_class'    => function( $name ) use ( &$created ) {
						$created[] = $name;
					},
				),
			)
		);

		$sut = new WC_Settings_Tax();

		$sut->save_tax_classes( "tax_2\ntax_3\ntax_4" );

		$this->assertEquals( array( 'tax_1' ), $deleted );
		$this->assertEquals( array( 'tax_4' ), $created );
	}
}
