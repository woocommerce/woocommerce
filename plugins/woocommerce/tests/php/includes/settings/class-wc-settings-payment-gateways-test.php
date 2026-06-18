<?php
/**
 * Class WC_Settings_Payment_Gateways_Test file.
 *
 * @package WooCommerce\Tests\Settings
 */

use Automattic\WooCommerce\Admin\Settings\SettingsSection;
use Automattic\WooCommerce\Admin\Settings\SettingsSectionInterface;
use Automattic\WooCommerce\Admin\Settings\SettingsSectionRegistry;
use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\FunctionsMockerHack;
use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\StaticMockerHack;

require_once __DIR__ . '/class-wc-settings-unit-test-case.php';

/**
 * Unit tests for the WC_Settings_Payment_Gateways class.
 */
class WC_Settings_Payment_Gateways_Test extends WC_Settings_Unit_Test_Case {

	/**
	 * Setup test case.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// Make sure the class file is loaded.
		require_once WC_ABSPATH . 'includes/admin/settings/class-wc-settings-payment-gateways.php';
		SettingsSectionRegistry::get_instance()->unregister_all();
	}

	/**
	 * Tear down test case.
	 */
	public function tearDown(): void {
		SettingsSectionRegistry::get_instance()->unregister_all();

		parent::tearDown();
	}

	/**
	 * @testdox get_sections should get all the existing sections.
	 */
	public function test_get_sections() {
		$sut = new WC_Settings_Payment_Gateways();

		$section_names = array_keys( $sut->get_sections() );

		$expected = array(
			'',
		);

		$this->assertEquals( $expected, $section_names );
	}

	/**
	 * get_settings should trigger the appropriate filter depending on the requested section name.
	 *
	 * @testWith ["woocommerce_com", "woocommerce_get_settings_checkout"]
	 *
	 * @param string $section_name The section name to test getting the settings for.
	 * @param string $filter_name The name of the filter that is expected to be triggered.
	 */
	public function test_get_settings_triggers_filter( $section_name, $filter_name ) {
		$actual_settings_via_filter = null;

		add_filter(
			$filter_name,
			function ( $settings ) use ( &$actual_settings_via_filter ) {
				$actual_settings_via_filter = $settings;

				return $settings;
			},
			10,
			1
		);

		$sut = new WC_Settings_Payment_Gateways();

		$actual_settings_returned = $sut->get_settings_for_section( $section_name );
		remove_all_filters( $filter_name );

		$this->assertSame( $actual_settings_returned, $actual_settings_via_filter );
	}

	/**
	 * @testdox get_settings('') should return all the settings for the default section.
	 */
	public function test_get_default_settings_returns_all_settings() {
		$sut = new WC_Settings_Payment_Gateways();

		$settings              = $sut->get_settings_for_section( '' );
		$setting_ids_and_types = $this->get_ids_and_types( $settings );

		$expected = array(
			'payment_gateways_options' => 'sectionend',
			''                         => 'title',
		);

		$this->assertEquals( $expected, $setting_ids_and_types );
	}

	/**
	 * @testdox Output should render registered checkout settings sections.
	 */
	public function test_output_renders_registered_checkout_settings_section(): void {
		global $current_section;
		$current_section = 'acme_payments';

		SettingsSectionRegistry::get_instance()->register( $this->get_registered_payment_section( 'acme_payments' ) );
		$disable_reactified_sections = static function () {
			return array();
		};
		add_filter( 'experimental_woocommerce_admin_payment_reactify_render_sections', $disable_reactified_sections );

		$sut = $this->getMockBuilder( WC_Settings_Payment_Gateways::class )
			->setMethods( array( 'run_gateway_admin_options' ) )
			->getMock();
		$sut->expects( $this->never() )->method( 'run_gateway_admin_options' );

		try {
			ob_start();
			$sut->output();
			$output = ob_get_clean();
		} finally {
			remove_filter( 'experimental_woocommerce_admin_payment_reactify_render_sections', $disable_reactified_sections );
		}

		$this->assertStringContainsString( 'name="registered_acme_payments_setting"', $output );
	}

	/**
	 * @testDox 'save' will trigger 'init' (and 'process_admin_options' if current section is the name of an existing gateway), and the appropriate actions.
	 *
	 * @testWith ["bacs", false]
	 *           ["wc_gateway_bacs", false]
	 *           ["", true]
	 *
	 * @param string $section_name The current section name.
	 * @param bool   $expect_to_run_process_admin_options Whether 'admin_options' is expected to be invoked in WC_Payment_Gateways or not.
	 */
	public function test_save_triggers_appropriate_gateway_methods_and_actions( $section_name, $expect_to_run_process_admin_options ) {
		global $current_section;
		$current_section = $section_name;

		$process_admin_options_invoked = false;
		$init_invoked                  = false;

		$gateway = WC_Payment_Gateways::instance()->payment_gateways()[ WC_Gateway_BACS::ID ];

		$payment_gateways = $this->getMockBuilder( WC_Payment_Gateways::class )
								 ->setMethods( array( 'process_admin_options', 'init', 'payment_gateways' ) )
								 ->getMock();

		$payment_gateways->method( 'process_admin_options' )
						->will(
							$this->returnCallback(
								function() use ( &$process_admin_options_invoked ) {
									$process_admin_options_invoked = true;
								}
							)
						);

		$payment_gateways->method( 'init' )
						->will(
							$this->returnCallback(
								function() use ( &$init_invoked ) {
									$init_invoked = true;
								}
							)
						);

		$payment_gateways->method( 'payment_gateways' )
						 ->willReturn( array( $gateway ) );

		StaticMockerHack::add_method_mocks(
			array(
				'WC_Payment_Gateways' => array(
					'instance' => function() use ( $payment_gateways ) {
						return $payment_gateways;
					},
				),
			)
		);

		$sut = new WC_Settings_Payment_Gateways();
		$sut->save();

		$this->assertTrue( $init_invoked );
		$this->assertEquals( $expect_to_run_process_admin_options, $process_admin_options_invoked );

		$this->assertEquals( '' === $section_name ? 0 : 1, did_action( 'woocommerce_update_options_payment_gateways_bacs' ) );
		$this->assertEquals( '' === $section_name ? 0 : 1, did_action( 'woocommerce_update_options_checkout_' . $section_name ) );
	}

	/**
	 * Build a registered payment settings section.
	 *
	 * @param string $section_id Section id.
	 * @return SettingsSectionInterface
	 */
	private function get_registered_payment_section( string $section_id ): SettingsSectionInterface {
		return new class( $section_id ) extends SettingsSection {
			/**
			 * Section id.
			 *
			 * @var string
			 */
			private string $section_id;

			/**
			 * Constructor.
			 *
			 * @param string $section_id Section id.
			 */
			public function __construct( string $section_id ) {
				$this->section_id = $section_id;
			}

			/**
			 * Get the parent page id.
			 *
			 * @return string
			 */
			public function get_parent_page_id(): string {
				return WC_Settings_Payment_Gateways::TAB_NAME;
			}

			/**
			 * Get the section id.
			 *
			 * @return string
			 */
			public function get_id(): string {
				return $this->section_id;
			}

			/**
			 * Get the section label.
			 *
			 * @return string
			 */
			public function get_label(): string {
				return 'Registered payment section';
			}

			/**
			 * Get legacy settings.
			 *
			 * @param WC_Settings_Page $parent_page Parent settings page.
			 * @return array
			 */
			public function get_settings( WC_Settings_Page $parent_page ): array {
				return array(
					array(
						'id'    => 'registered_' . $this->section_id . '_setting',
						'type'  => 'text',
						'title' => 'Registered payment section setting',
					),
				);
			}

		};
	}
}
