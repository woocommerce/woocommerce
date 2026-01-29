<?php
/**
 * @package WooCommerce\Tests\PaymentGateways
 */

use Automattic\WooCommerce\Internal\FraudProtection\SessionClearanceManager;

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

		// Set jetpack_activation_source option to prevent "Cannot use bool as array" error
		// in Jetpack Connection Manager's apply_activation_source_to_args method.
		update_option( 'jetpack_activation_source', array( '', '' ) );

		$container = wc_get_container();
		$container->reset_all_resolved();
		$this->sut = new WC_Payment_Gateways();
		$this->sut->init();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		parent::tearDown();
		delete_option( 'woocommerce_feature_fraud_protection_enabled' );
		delete_option( 'jetpack_activation_source' );
		wc_get_container()->get( SessionClearanceManager::class )->reset_session();
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
			$gateway->settings['title']   = null;
			update_option( $gateway->get_option_key(), $gateway->settings );

			// Enable the gateway and save its settings; this should send the email and add a log entry.
			$gateway->settings['enabled'] = 'yes';
			update_option( $gateway->get_option_key(), $gateway->settings );

			// Check that the log entry was created.
			$this->assertEquals( 'Payment gateway enabled: "' . $gateway->get_method_title() . '"', end( $fake_logger->infos )['message'] );

			// Check that the email was sent correctly.
			$this->assertStringContainsString( '@', $email_details['to'][0] );
			$this->assertEquals( get_option( 'admin_email' ), $email_details['to'][0] );
			$this->assertEquals( '[Test Blog] Payment gateway "' . $gateway->get_method_title() . '" enabled', $email_details['subject'] );
			$this->assertStringContainsString( 'The payment gateway "' . $gateway->get_method_title() . '" was just enabled on this site', $email_details['message'] );
			$this->assertStringContainsString( 'If you did not enable this payment gateway, please log in to your site and consider disabling it here:', $email_details['message'] );
			$this->assertStringContainsString( '/wp-admin/admin.php?page=wc-settings&tab=checkout&section=' . $gateway->id, $email_details['message'] );

			// Reset the email details.
			$email_details = array();
		}
		remove_filter( 'wp_mail', $watcher );
	}

	/**
	 * Test that payment gateways are hidden when fraud protection blocks the session.
	 */
	public function test_get_available_payment_gateways_returns_empty_when_session_blocked() {
		// Enable fraud protection and block the session.
		update_option( 'woocommerce_feature_fraud_protection_enabled', 'yes' );
		wc_get_container()->get( SessionClearanceManager::class )->block_session();

		$this->enable_all_gateways();

		$gateways = $this->sut->get_available_payment_gateways();

		$this->assertEmpty( $gateways, 'Should return empty array when session is blocked' );
	}

	/**
	 * Test that payment gateways are returned when fraud protection is disabled, even if session is blocked.
	 */
	public function test_get_available_payment_gateways_returns_gateways_when_feature_disabled() {
		// Disable fraud protection but block the session.
		update_option( 'woocommerce_feature_fraud_protection_enabled', 'no' );
		wc_get_container()->get( SessionClearanceManager::class )->block_session();

		$this->enable_all_gateways();

		$gateways = $this->sut->get_available_payment_gateways();

		$this->assertNotEmpty( $gateways, 'Should return gateways when feature is disabled' );
	}

	/**
	 * Test get_payment_gateway_name_by_id returns gateway title for known gateway.
	 *
	 * @return void
	 */
	public function test_get_payment_gateway_name_by_id_returns_gateway_title_for_known_gateway(): void {
		// Test with a known gateway (bacs is available by default in WooCommerce).
		$result = $this->sut->get_payment_gateway_name_by_id( 'bacs' );

		// Should return a readable name, not just the ID.
		$this->assertNotEmpty( $result );
		$this->assertEquals( 'Direct bank transfer', $result );
	}

	/**
	 * Test get_payment_gateway_name_by_id returns ID when gateway not found.
	 *
	 * @return void
	 */
	public function test_get_payment_gateway_name_by_id_returns_id_when_gateway_not_found(): void {
		// Test that get_payment_gateway_name_by_id returns the ID as fallback.
		$result = $this->sut->get_payment_gateway_name_by_id( 'nonexistent_gateway' );
		$this->assertEquals( 'nonexistent_gateway', $result );
	}

	/**
	 * Enable all payment gateways.
	 */
	private function enable_all_gateways() {
		foreach ( $this->sut->payment_gateways() as $gateway ) {
			$gateway->enabled = 'yes';
		}
	}
}
