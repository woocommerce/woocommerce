<?php
/**
 * @package WooCommerce\Tests\PaymentGateways
 */

/**
 * Class WC_Payment_Gateways_Test.
 */
class WC_Payment_Gateways_Test extends WC_Unit_Test_Case {

	/**
	 * @var WC_Payment_Gateways The system under test.
	 */
	private $sut;

	/**
	 * Setup, enable payment gateways Cash on delivery and direct bank deposit.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->reset_legacy_proxy_mocks();
		$container = wc_get_container();
		$container->reset_all_resolved();
		$this->sut = new WC_Payment_Gateways();
		$this->sut->init();
	}

	/**
	 * Test that enabling a gateway sends an email to the site admin and logs the event.
	 */
	public function test_wc_payment_gateway_enabled_notification() {
		// Create a fake logger to capture log entries.
		// phpcs:disable Squiz.Commenting
		$fake_logger = new class() {
			public $infos = array();

			public function info( $message, $data = array() ) {
				$this->infos[] = array(
					'message' => $message,
					'data'    => $data,
				);
			}
		};
		// phpcs:enable Squiz.Commenting
		$this->register_legacy_proxy_function_mocks(
			array(
				'wc_get_logger' => function() use ( $fake_logger ) {
					return $fake_logger;
				},
			)
		);

		// Register a watcher for wp_mail to capture email details.
		$email_details = array();
		$watcher       = function( $args ) use ( &$email_details ) {
			$email_details = $args;
		};
		add_filter( 'wp_mail', $watcher );

		// Enable each gateway and check that the email and log entry are created.
		foreach ( $this->sut->payment_gateways() as $gateway ) {
			// Disable the gateway and save the settings.
			$gateway->settings['enabled'] = 'no';
			update_option( $gateway->get_option_key(), $gateway->settings );

			// Enable the gateway and save its settings; this should send the email and add a log entry.
			$gateway->settings['enabled'] = 'yes';
			update_option( $gateway->get_option_key(), $gateway->settings );

			// Check that the log entry was created.
			$this->assertEquals( 'Payment gateway enabled: "' . $gateway->get_title() . '"', end( $fake_logger->infos )['message'] );

			// Check that the email was sent correctly.
			$this->assertStringContainsString( '@', $email_details['to'][0] );
			$this->assertEquals( get_option( 'admin_email' ), $email_details['to'][0] );
			$this->assertEquals( '[Test Blog] Payment gateway "' . $gateway->get_title() . '" enabled', $email_details['subject'] );
			$this->assertStringContainsString( 'The payment gateway "' . $gateway->get_title() . '" was just enabled on this site', $email_details['message'] );
			$this->assertStringContainsString( 'If you did not enable this payment gateway, please log in to your site and consider disabling it here:', $email_details['message'] );
			$this->assertStringContainsString( '/wp-admin/admin.php?page=wc-settings&tab=checkout&section=' . $gateway->id, $email_details['message'] );

			// Reset the email details.
			$email_details = array();
		}
		remove_filter( 'wp_mail', $watcher );
	}
}
