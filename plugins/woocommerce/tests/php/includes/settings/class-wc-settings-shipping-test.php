<?php
/**
 * Class WC_Settings_Shipping_Test file.
 *
 * @package WooCommerce\Tests\Settings
 */

use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\FunctionsMockerHack;
use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\StaticMockerHack;

require_once __DIR__ . '/class-wc-settings-unit-test-case.php';

/**
 * Unit tests for the WC_Settings_Shipping class.
 */
class WC_Settings_Shipping_Test extends WC_Settings_Unit_Test_Case {

	/**
	 * @testDox 'get_sections' returns a fixed set of sections, plus one section for each shipping method having settings.
	 */
	public function test_get_sections_returns_fixed_sections_and_one_section_per_shipping_method_with_settings() {
		$methods = array(
			//phpcs:disable Squiz.Commenting
			new class() {
				public $id;
				public $method_title;

				public function has_settings() {
					return false;
				}

				public function __construct() {
					$this->id = 'method_with_no_settings';
				}
			},
			new class() {
				public $id;
				public $method_title;

				public function has_settings() {
					return true;
				}

				public function __construct() {
					$this->id = 'method_1_id';
					$this->method_title = null;
				}
			},
			new class() {
				public $id;
				public $method_title;

				public function has_settings() {
					return true;
				}

				public function __construct() {
					$this->id = 'method_2_id';
					$this->method_title = 'method_2_title';
				}
			},
			//phpcs:enable
		);

		$sut = $this->getMockBuilder( WC_Settings_Shipping::class )
					->setMethods( array( 'get_shipping_methods', 'wc_is_installing' ) )
					->getMock();

		$sut->method( 'get_shipping_methods' )->willReturn( $methods );
		$sut->method( 'wc_is_installing' )->willReturn( false );

		$sections = $sut->get_sections();

		$expected = array(
			''            => 'Shipping zones',
			'options'     => 'Shipping options',
			'classes'     => 'Shipping classes',
			'method_1_id' => 'Method_1_id',
			'method_2_id' => 'method_2_title',
		);
		$this->assertEquals( $expected, $sections );
	}

	/**
	 * @testDox 'get_settings' returns proper default settings, also 'options' is an alias for the default section.
	 *
	 * @testWith [""]
	 *           ["options"]
	 *
	 * @param string $section_name The name of the section to get the settings for.
	 */
	public function test_get_default_and_options_settings_returns_all_settings( $section_name ) {
		$sut = new WC_Settings_Shipping();

		$settings               = $sut->get_settings_for_section( $section_name );
		$settings_ids_and_types = $this->get_ids_and_types( $settings );

		$expected = array(
			'shipping_options'                           => array( 'title', 'sectionend' ),
			'woocommerce_enable_shipping_calc'           => 'checkbox',
			'woocommerce_shipping_cost_requires_address' => 'checkbox',
			'woocommerce_ship_to_destination'            => 'radio',
			'woocommerce_shipping_debug_mode'            => 'checkbox',
		);

		$this->assertEquals( $expected, $settings_ids_and_types );
	}

	/**
	 * @testDox 'output' for a predefined section invokes the appropriate internal method.
	 *
	 * @testWith ["", "output_zones_screen"]
	 *           ["classes", "output_shipping_class_screen"]
	 *
	 * @param string $section_name Current section name.
	 * @param string $expected_method_invoked Name of the internal method expected to be invoked.
	 */
	public function test_output_for_predefined_section( $section_name, $expected_method_invoked ) {
		global $current_section;
		$current_section = $section_name;

		$method_invoked = null;

		$sut = $this->getMockBuilder( WC_Settings_Shipping::class )
					->setMethods( array( 'get_shipping_methods', 'output_zones_screen', 'output_fields', 'output_shipping_class_screen' ) )
					->getMock();

		$sut->method( 'get_shipping_methods' )->willReturn( array() );
		$sut->method( 'output_zones_screen' )->will(
			$this->returnCallback(
				function() use ( &$method_invoked ) {
					$method_invoked = 'output_zones_screen';
				}
			)
		);
		$sut->method( 'output_shipping_class_screen' )->will(
			$this->returnCallback(
				function() use ( &$method_invoked ) {
					$method_invoked = 'output_shipping_class_screen';
				}
			)
		);

		$sut->output();

		$this->assertEquals( $expected_method_invoked, $method_invoked );
	}

	/**
	 * @testDox 'output' invokes 'admin_options' of the method if section is the id of a shipping method with settings.
	 */
	public function test_output_for_shipping_method_id_with_settings() {
		global $current_section;
		$current_section = 'method_id';

		//phpcs:disable Squiz.Commenting
		$method =
			new class() {
				public $id;
				public $method_title;
				public $admin_options_invoked;

				public function has_settings() {
					return true;
				}

				public function admin_options() {
					$this->admin_options_invoked = true;
				}

				public function __construct() {
					$this->id                    = 'method_id';
					$this->admin_options_invoked = false;
				}
			};
		//phpcs:enable

		$sut = $this->getMockBuilder( WC_Settings_Shipping::class )
					->setMethods( array( 'get_shipping_methods' ) )
					->getMock();

		$sut->method( 'get_shipping_methods' )->willReturn( array( $method ) );

		$sut->output();

		$this->assertTrue( $method->admin_options_invoked );
	}

	/**
	 * @testDox 'output' fallbacks to default behavior when section is for a shipping method without settings, or not a known shipping method.
	 *
	 * @testWith ["method_name"]
	 *           ["foobar"]
	 *
	 * @param string $section_name Current section name.
	 */
	public function test_output_for_shipping_method_without_settings_or_unknown_section( $section_name ) {
		global $current_section;
		$current_section = $section_name;

		$output_fields_in_admin_settings_invoked = false;

		StaticMockerHack::add_method_mocks(
			array(
				'WC_Admin_Settings' => array(
					'output_fields' => function( $settings ) use ( &$output_fields_in_admin_settings_invoked ) {
						$output_fields_in_admin_settings_invoked = true;
					},
				),
			)
		);

		//phpcs:disable Squiz.Commenting
		$method =
			new class() {
				public $id;
				public $method_title;
				public $admin_options_invoked;

				public function has_settings() {
					return false;
				}

				public function admin_options() {
					$this->admin_options_invoked = true;
				}

				public function __construct() {
					$this->id                    = 'method_id';
					$this->admin_options_invoked = false;
				}
			};
		//phpcs:enable

		$sut = $this->getMockBuilder( WC_Settings_Shipping::class )
					->setMethods( array( 'get_shipping_methods' ) )
					->getMock();

		$sut->method( 'get_shipping_methods' )->willReturn( array( $method ) );

		$sut->output();

		$this->assertFalse( $method->admin_options_invoked );
		$this->assertTrue( $output_fields_in_admin_settings_invoked );
	}

	/**
	 * @testDox 'save' triggers the appropriate action, and also 'save_settings_for_current_section' if needed, when the current section is a predefined one.
	 *
	 * @testWith ["", false, false]
	 *           ["options", true, true]
	 *           ["classes", false, true]
	 *
	 * @param string $section_name Current section name.
	 * @param bool   $expect_save_settings_for_current_section_invoked Is 'save_settings_for_current_section' expected to be invoked?.
	 * @param bool   $expect_update_options_action_invoked Is the 'woocommerce_update_options_' action expected to be triggered?.
	 */
	public function test_save_predefined_section( $section_name, $expect_save_settings_for_current_section_invoked, $expect_update_options_action_invoked ) {
		global $current_section;
		$current_section = $section_name;

		$save_settings_for_current_section_invoked = false;

		$sut = $this->getMockBuilder( WC_Settings_Shipping::class )
					->setMethods( array( 'get_shipping_methods', 'save_settings_for_current_section' ) )
					->getMock();

		$sut->method( 'get_shipping_methods' )->willReturn( array() );
		$sut->method( 'save_settings_for_current_section' )->will(
			$this->returnCallback(
				function() use ( &$save_settings_for_current_section_invoked ) {
					$save_settings_for_current_section_invoked = true;
				}
			)
		);

		$sut->save();

		$this->assertEquals( $expect_save_settings_for_current_section_invoked, $save_settings_for_current_section_invoked );
		$this->assertEquals( $expect_update_options_action_invoked ? 1 : 0, did_action( 'woocommerce_update_options_shipping_' . $section_name ) );
	}

	/**
	 * @testDox 'save' triggers 'woocommerce_update_options_shipping_{method_id}' when the current section is the id of a shipping method with settings.
	 */
	public function test_save_for_shipping_method_triggers_appropriate_action() {
		global $current_section;
		$current_section = 'method_id';

		//phpcs:disable Squiz.Commenting
		$method =
			new class() {
				public $id;
				public $method_title;
				public $admin_options_invoked;

				public function has_settings() {
					return true;
				}

				public function __construct() {
					$this->id = 'method_id';
				}
			};
		//phpcs:enable

		$sut = $this->getMockBuilder( WC_Settings_Shipping::class )
					->setMethods( array( 'get_shipping_methods' ) )
					->getMock();

		$sut->method( 'get_shipping_methods' )->willReturn( array( $method ) );

		$sut->save();

		$this->assertEquals( 1, did_action( 'woocommerce_update_options_shipping_method_id' ) );
	}
}
