<?php
/**
 * Class WC_Settings_Page_Test file.
 *
 * @package WooCommerce\Tests\Settings
 */

use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\StaticMockerHack;

require_once __DIR__ . '/class-wc-settings-example.php';
require_once __DIR__ . '/class-wc-legacy-settings-example.php';

/**
 * Unit tests for the base functionality of WC_Settings_Page.
 */
class WC_Settings_Page_Test extends WC_Unit_Test_Case {

	/**
	 * Test for constructor.
	 */
	public function test_constructor() {
		remove_all_filters( 'woocommerce_settings_tabs_array' );
		remove_all_filters( 'woocommerce_sections_example' );
		remove_all_filters( 'woocommerce_settings_example' );
		remove_all_filters( 'woocommerce_settings_save_example' );

		$sut = new WC_Settings_Example();

		$this->assertTrue( has_filter( 'woocommerce_settings_tabs_array' ) );
		$this->assertTrue( has_filter( 'woocommerce_sections_example' ) );
		$this->assertTrue( has_filter( 'woocommerce_settings_example' ) );
		$this->assertTrue( has_filter( 'woocommerce_settings_save_example' ) );
	}

	/**
	 * Test for add_settings_page.
	 */
	public function test_add_settings_page() {
		$pages = array( 'foo' => 'bar' );

		$sut    = new WC_Settings_Example();
		$actual = $sut->add_settings_page( $pages );

		$expected = array(
			'foo'     => 'bar',
			'example' => 'Example',
		);
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test for get_settings (default section).
	 */
	public function test_get_settings__default_section() {
		$sut = new WC_Settings_Example();

		$actual = $sut->get_settings_for_section( '' );

		$expected = array( 'key' => 'value' );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test for get_settings_for_section (default section).
	 */
	public function test_get_settings_for_section__default_section() {
		$sut = new WC_Settings_Example();

		$actual = $sut->get_settings_for_section( '' );

		$expected = array( 'key' => 'value' );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test for get_settings (named section with its own get_settings_for_X_section method).
	 */
	public function test_get_settings__named_section_with_own_method() {
		$sut = new WC_Settings_Example();

		$actual = $sut->get_settings( 'foobar' );

		$expected = array( 'foo' => 'bar' );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test for get_settings_for_section (named section with its own get_settings_for_X_section method).
	 */
	public function test_get_settings_for_section__named_section_with_own_method() {
		$sut = new WC_Settings_Example();

		$actual = $sut->get_settings_for_section( 'foobar' );

		$expected = array( 'foo' => 'bar' );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test for get_settings (named section without get_settings_for_X_section method).
	 */
	public function test_get_settings__named_section_without_own_method() {
		$sut = new WC_Settings_Example();

		$actual = $sut->get_settings_for_section( 'fizzbuzz' );

		$expected = array( 'fizzbuzz_key' => 'fizzbuzz_value' );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test for get_settings (triggers woocommerce_get_settings_X filter).
	 */
	public function test_get_settings__get_settings_filter() {
		$actual_settings = null;
		$actual_section  = null;

		add_filter(
			'woocommerce_get_settings_example',
			function( $settings, $section ) use ( &$actual_settings, &$actual_section ) {
				$actual_settings = $settings;
				$actual_section  = $section;
			},
			10,
			2
		);

		$sut = new WC_Settings_Example();
		$sut->get_settings_for_section( 'foobar' );
		remove_all_filters( 'woocommerce_get_settings_example' );

		$expected_section  = 'foobar';
		$expected_settings = array( 'foo' => 'bar' );
		$this->assertEquals( $expected_section, $actual_section );
		$this->assertEquals( $expected_settings, $actual_settings );
	}

	/**
	 * Test for get_section (returned value).
	 */
	public function test_get_sections__result() {
		$sut    = new WC_Settings_Example();
		$actual = $sut->get_sections();

		$expected = array(
			''            => 'General',
			'new_section' => 'New Section',
		);

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test for get_section (triggers the woocommerce_get_sections_X filter).
	 */
	public function test_get_sections__get_sections_filter() {
		$actual_sections = null;

		add_filter(
			'woocommerce_get_sections_example',
			function( $sections ) use ( &$actual_sections ) {
				$actual_sections = $sections;
			},
			10,
			1
		);

		$sut = new WC_Settings_Example();
		$sut->get_sections();
		remove_all_filters( 'woocommerce_get_sections_example' );

		$expected_sections = array(
			''            => 'General',
			'new_section' => 'New Section',
		);
		$this->assertEquals( $expected_sections, $actual_sections );
	}

	/**
	 * Test for output_sections.
	 */
	public function test_output_sections() {
		$sut = new WC_Settings_Example();

		$expected = <<<'HTML'
			<ul class="subsubsub">
				<li>
					<a href="http://example.org/wp-admin/admin.php?page=wc-settings&tab=example&section=" class="">General</a> | </li>
				<li>
					<a href="http://example.org/wp-admin/admin.php?page=wc-settings&tab=example&section=new_section" class="">New Section</a></li>
			</ul>
			<br class="clear" />
HTML;

		$this->assertOutputsHTML( $expected, array( $sut, 'output_sections' ) );
	}

	/**
	 * Test for output.
	 */
	public function test_output() {
		global $current_section;

		$actual = null;

		StaticMockerHack::add_method_mocks(
			array(
				'WC_Admin_Settings' => array(
					'output_fields' => function( $settings ) use ( &$actual ) {
						$actual = $settings;
					},
				),
			)
		);

		$sut = new WC_Settings_Example();

		$current_section = 'foobar';
		$sut->output();

		$expected = array( 'foo' => 'bar' );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test for output on a legacy settings class.
	 */
	public function test_output_on_legacy_class() {
		global $current_section;

		$actual = null;

		StaticMockerHack::add_method_mocks(
			array(
				'WC_Admin_Settings' => array(
					'output_fields' => function( $settings ) use ( &$actual ) {
						$actual = $settings;
					},
				),
			)
		);

		$sut = new WC_Legacy_Settings_Example();

		$current_section = 'foobar';
		$sut->output();

		$expected = array( 'foo' => 'bar' );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test for save (invokes the save_fields method).
	 */
	public function test_save__saves_fields() {
		global $current_section;

		$actual = null;

		StaticMockerHack::add_method_mocks(
			array(
				'WC_Admin_Settings' => array(
					'save_fields' => function( $settings ) use ( &$actual ) {
						$actual = $settings;
					},
				),
			)
		);

		$sut = new WC_Settings_Example();

		$current_section = 'foobar';
		$sut->save();

		$expected = array( 'foo' => 'bar' );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test for save (invokes the save_fields method) on a legacy settings class.
	 */
	public function test_save_on_legacy_class__saves_fields() {
		global $current_section;

		$actual = null;

		StaticMockerHack::add_method_mocks(
			array(
				'WC_Admin_Settings' => array(
					'save_fields' => function( $settings ) use ( &$actual ) {
						$actual = $settings;
					},
				),
			)
		);

		$sut = new WC_Legacy_Settings_Example();

		$current_section = 'foobar';
		$sut->save();

		$expected = array( 'foo' => 'bar' );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test for save (named section, triggers the woocommerce_update_options_example_foobar action).
	 */
	public function test_save__does_update_options_action__named_section() {
		global $current_section;

		$current_section = 'foobar';
		remove_all_filters( 'woocommerce_update_options_example_foobar' );

		$sut = new WC_Settings_Example();
		$sut->save();

		$this->assertEquals( 1, did_action( 'woocommerce_update_options_example_foobar' ) );
	}

	/**
	 * Test for save (default section, doesn't trigger any woocommerce_update_options_ action).
	 */
	public function test_save__does_update_options_action__default_section() {
		global $current_section;

		$current_section = '';
		remove_all_filters( 'woocommerce_update_options_example_' );

		$sut = new WC_Settings_Example();
		$sut->save();

		$this->assertEquals( 0, did_action( 'woocommerce_update_options_example_' ) );
	}
}
