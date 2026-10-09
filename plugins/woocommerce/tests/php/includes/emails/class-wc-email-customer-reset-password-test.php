<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\Internal\Features\FeaturesController;

/**
 * WC_Email_Customer_Reset_Password test.
 *
 * @covers WC_Email_Customer_Reset_Password
 */
class WC_Email_Customer_Reset_Password_Test extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WC_Email_Customer_Reset_Password
	 */
	private $sut;

	/**
	 * Original value of the email_improvements feature flag.
	 *
	 * @var bool
	 */
	private $email_improvements_was_enabled;

	/**
	 * Enable email improvements so Cc/Bcc settings would normally be honoured, plant Cc/Bcc settings, and build the email.
	 */
	public function setUp(): void {
		parent::setUp();

		$features_controller                  = wc_get_container()->get( FeaturesController::class );
		$this->email_improvements_was_enabled = $features_controller->feature_is_enabled( 'email_improvements' );
		$features_controller->change_feature_enable( 'email_improvements', true );

		WC()->mailer();

		update_option(
			'woocommerce_customer_reset_password_settings',
			array(
				'cc'  => 'cc@example.com',
				'bcc' => 'copy@example.com',
			)
		);
		$this->sut = new WC_Email_Customer_Reset_Password();
	}

	/**
	 * Restore the feature flag.
	 */
	public function tearDown(): void {
		wc_get_container()->get( FeaturesController::class )->change_feature_enable( 'email_improvements', $this->email_improvements_was_enabled );

		parent::tearDown();
	}

	/**
	 * @testdox Cc/Bcc are not configurable and stored values never reach the sent email.
	 */
	public function test_stored_cc_bcc_settings_are_ignored(): void {
		$this->assertSame( 'copy@example.com', $this->sut->get_option( 'bcc' ), 'Fixture: stored value must be readable under this option key' );
		$this->assertFalse( $this->sut->supports_cc_bcc() );
		$this->assertArrayNotHasKey( 'cc', $this->sut->get_form_fields() );
		$this->assertArrayNotHasKey( 'bcc', $this->sut->get_form_fields() );

		$user = $this->factory()->user->create_and_get(
			array(
				'user_email' => 'owner@example.com',
				'role'       => 'administrator',
			)
		);

		$mailer = tests_retrieve_phpmailer_instance();
		$before = count( $mailer->mock_sent );

		$this->sut->trigger( $user->user_login, 'reset-key' );

		$this->assertCount( $before + 1, $mailer->mock_sent, 'Exactly one email must be sent' );
		$sent = $mailer->mock_sent[ $before ];
		$this->assertSame( 'owner@example.com', $sent['to'][0][0] );
		$this->assertEmpty( $sent['cc'], 'Stored Cc must be ignored' );
		$this->assertEmpty( $sent['bcc'], 'Stored Bcc must be ignored' );
	}
}
