<?php
/**
 * Class WC_Settings_Integrations_Test file.
 *
 * @package WooCommerce\Tests\Settings
 */

use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\FunctionsMockerHack;
use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\StaticMockerHack;

require_once __DIR__ . '/class-wc-settings-unit-test-case.php';

/**
 * Unit tests for the WC_Settings_Integration class.
 */
class WC_Settings_Integrations_Test extends WC_Settings_Unit_Test_Case {

	/**
	 * @testdox 'get_sections' sets the current section to the id of the first integration if it's not set to anything.
	 */
	public function test_get_sections_sets_current_section_to_first_available_inegration_if_not_set() {
		global $current_section;

		$integrations = array(
			(object) array(
				'id'           => 'int_id',
				'method_title' => 'int_title',
			),
		);

		$sut = $this->getMockBuilder( WC_Settings_Integrations::class )
					->setMethods( array( 'get_integrations', 'wc_is_installing' ) )
					->getMock();

		$sut->method( 'get_integrations' )->willReturn( $integrations );
		$sut->method( 'wc_is_installing' )->willReturn( false );

		$current_section = null;
		$sut->get_sections();

		$this->assertEquals( 'int_id', $current_section );
	}

	/**
	 * @testdox 'get_sections' returns a list of sections made from the ids and titles of the existing integrations.
	 */
	public function test_get_sections_returns_sections_made_from_existing_integrations() {
		$integrations = array(
			(object) array(
				'id'           => 'int_1_id',
				'method_title' => null,
			),
			(object) array(
				'id'           => 'int_2_id',
				'method_title' => 'int_2_title',
			),
		);

		$sut = $this->getMockBuilder( WC_Settings_Integrations::class )
					->setMethods( array( 'get_integrations', 'wc_is_installing' ) )
					->getMock();

		$sut->method( 'get_integrations' )->willReturn( $integrations );
		$sut->method( 'wc_is_installing' )->willReturn( false );

		$sections = $sut->get_sections();

		$expected_sections = array(
			'int_1_id' => 'Int_1_id',
			'int_2_id' => 'int_2_title',
		);
		$this->assertEquals( $expected_sections, $sections );
	}

	/**
	 * @testDox 'output' invokes 'admin_options' in the integration whose id is equal to the current section name.
	 */
	public function test_output_invoked_admin_options_on_integration_pointed_by_current_section() {
		global $current_section;

		$integrations = array(
			'int_id' =>
				new class() {
					//phpcs:disable Squiz.Commenting
					public $id;
					public $admin_options_invoked;

					public function admin_options() {
						$this->admin_options_invoked = true;
					}

					public function __construct() {
						$this->id = 'the_id';
						$this->admin_options_invoked = false;
					}
					//phpcs:enable
				},
		);

		$sut = $this->getMockBuilder( WC_Settings_Integrations::class )
					->setMethods( array( 'get_integrations' ) )
					->getMock();

		$sut->method( 'get_integrations' )->willReturn( $integrations );

		$current_section = 'int_id';
		$sut->output();

		$this->assertTrue( $integrations['int_id']->admin_options_invoked );
	}
}
