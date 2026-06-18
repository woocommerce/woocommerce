<?php
/**
 * Class WC_Settings_Payment_Gateways_Test file.
 *
 * @package WooCommerce\Tests\Settings
 */

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
	 * @testdox Should render WooPayments settings as a React section by default.
	 */
	public function test_woopayments_section_is_reactified_by_default() {
		$sut = new WC_Settings_Payment_Gateways();

		$this->assertTrue( $sut->should_render_react_section( 'woocommerce_payments' ) );
	}

	/**
	 * @testdox Should render the WooPayments React root for the WooPayments settings section.
	 */
	public function test_woopayments_section_outputs_react_root() {
		global $current_section;
		$current_section = 'woocommerce_payments';
		$sut             = new WC_Settings_Payment_Gateways();

		ob_start();
		$sut->output();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'id="experimental_wc_settings_payments_woocommerce_payments"', $output );
	}

	/**
	 * @testdox Should preserve classic WooPayments settings field extensions.
	 */
	public function test_woopayments_section_preserves_classic_settings_field_extensions() {
		$filter_callback = static function ( $fields ) {
			$fields['custom_extension_field'] = array(
				'title' => 'Custom extension field',
				'type'  => 'text',
			);

			return $fields;
		};
		add_filter( 'woocommerce_settings_api_form_fields_woocommerce_payments', $filter_callback );

		try {
			$sut = new WC_Settings_Payment_Gateways();

			$this->assertFalse( $sut->should_render_react_section( 'woocommerce_payments' ) );
		} finally {
			remove_filter( 'woocommerce_settings_api_form_fields_woocommerce_payments', $filter_callback );
		}
	}

	/**
	 * @testdox Should allow the WooPayments React section to be removed through the optional sections filter.
	 */
	public function test_woopayments_section_can_be_removed_from_optional_reactified_sections() {
		$filter_callback = static function () {
			return array(
				WC_Settings_Payment_Gateways::COD_SECTION_NAME,
				WC_Settings_Payment_Gateways::BACS_SECTION_NAME,
				WC_Settings_Payment_Gateways::CHEQUE_SECTION_NAME,
			);
		};
		add_filter( 'experimental_woocommerce_admin_payment_reactify_render_sections', $filter_callback );

		$sut = new WC_Settings_Payment_Gateways();

		$this->assertFalse( $sut->should_render_react_section( 'woocommerce_payments' ) );

		remove_filter( 'experimental_woocommerce_admin_payment_reactify_render_sections', $filter_callback );
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
}
