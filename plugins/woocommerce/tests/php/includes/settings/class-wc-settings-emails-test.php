<?php
/**
 * Class WC_Settings_Emails_Test file.
 *
 * @package WooCommerce\Tests\Settings
 */

use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\StaticMockerHack;

require_once __DIR__ . '/class-wc-settings-unit-test-case.php';

/**
 * Unit tests for the WC_Settings_Email class.
 */
class WC_Settings_Emails_Test extends WC_Settings_Unit_Test_Case {

	/**
	 * @testdox get_sections should get all the existing sections.
	 */
	public function test_get_sections() {
		$sut = new WC_Settings_Emails();

		$section_names = array_keys( $sut->get_sections() );

		$expected = array(
			'',
		);

		$this->assertEquals( $expected, $section_names );
	}

	/**
	 * get_settings should trigger the appropriate filter depending on the requested section name.
	 *
	 * @testWith ["", "woocommerce_email_settings"]
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

		$sut = new WC_Settings_Emails();

		$actual_settings_returned = $sut->get_settings_for_section( $section_name );
		remove_all_filters( $filter_name );

		$this->assertSame( $actual_settings_returned, $actual_settings_via_filter );
	}

	/**
	 * @testdox get_settings('') should return all the settings for the default section.
	 */
	public function test_get_default_settings_returns_all_settings() {
		$sut = new WC_Settings_Emails();

		$settings               = $sut->get_settings_for_section( '' );
		$settings_ids_and_types = $this->get_ids_and_types( $settings );

		$expected = array(
			'email_notification_settings'              => array( 'title', 'sectionend' ),
			''                                         => 'email_notification',
			'email_recipient_options'                  => 'sectionend',
			'email_options'                            => array( 'title', 'sectionend' ),
			'woocommerce_email_from_name'              => 'text',
			'woocommerce_email_from_address'           => 'email',
			'email_template_options'                   => array( 'title', 'sectionend' ),
			'woocommerce_email_header_image'           => 'text',
			'woocommerce_email_footer_text'            => 'textarea',
			'woocommerce_email_base_color'             => 'color',
			'woocommerce_email_background_color'       => 'color',
			'woocommerce_email_body_background_color'  => 'color',
			'woocommerce_email_text_color'             => 'color',
			'woocommerce_email_footer_text_color'      => 'color',
			'email_merchant_notes'                     => array( 'title', 'sectionend' ),
			'woocommerce_merchant_email_notifications' => 'checkbox',
		);

		$this->assertEquals( $expected, $settings_ids_and_types );
	}

	/**
	 * @testDox When the current section is the name of an existing email, 'output' invokes that email's 'admin_options' method.
	 */
	public function test_output_is_done_via_admin_options_method_of_email_specified_as_settings_section() {
		global $current_section;
		$current_section = 'wc_email_new_order';

		$admin_options_invoked = false;
		$actual_email          = null;

		$sut = $this->getMockBuilder( WC_Settings_Emails::class )
					->setMethods( array( 'run_email_admin_options' ) )
					->getMock();

		$sut->method( 'run_email_admin_options' )
			->will(
				$this->returnCallback(
					function( $email ) use ( &$admin_options_invoked, &$actual_email ) {
						$admin_options_invoked = true;
						$actual_email          = $email;
					}
				)
			);

		$sut->output();

		$this->assertTrue( $admin_options_invoked );
		$this->assertInstanceOf( WC_Email_New_Order::class, $actual_email );
	}

	/**
	 * @testDox 'save' will trigger 'save_settings_for_current_section_invoked', and the appropriate actions.
	 *
	 * @testWith ["wc_email_new_order", false]
	 *           ["", true]
	 *
	 * @param string $section_name The current section name.
	 * @param bool   $expect_save_settings_for_current_section Whether 'save_settings_for_current_section' is expected to be invoked or not.
	 */
	public function test_save_triggers_appropriate_methods_and_actions( $section_name, $expect_save_settings_for_current_section ) {
		global $current_section;
		$current_section = $section_name;

		$save_settings_for_current_section_invoked = false;

		$email = WC_Emails::instance()->get_emails()[ WC_Email_New_Order::class ];

		$emails = $this->getMockBuilder( WC_Emails::class )
								 ->setMethods( array( 'get_emails' ) )
								 ->getMock();

		$emails->method( 'get_emails' )
						 ->willReturn( array( WC_Email_New_Order::class => $email ) );

		StaticMockerHack::add_method_mocks(
			array(
				'WC_Emails' => array(
					'instance' => function() use ( $emails ) {
						return $emails;
					},
				),
			)
		);

		$sut = $this->getMockBuilder( WC_Settings_Emails::class )
					   ->setMethods( array( 'save_settings_for_current_section' ) )
					   ->getMock();

		$sut->method( 'save_settings_for_current_section' )
						->will(
							$this->returnCallback(
								function() use ( &$save_settings_for_current_section_invoked ) {
									$save_settings_for_current_section_invoked = true;
								}
							)
						);

		$sut->save();

		$this->assertEquals( $expect_save_settings_for_current_section, $save_settings_for_current_section_invoked );
		$this->assertEquals( '' === $section_name ? 0 : 1, did_action( 'woocommerce_update_options_email_new_order' ) );
	}
}
