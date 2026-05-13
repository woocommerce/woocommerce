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
		delete_option( 'jetpack_activation_source' );
	}

	/**
	 * @testdox Enabling a gateway fires the notification action and logs the event.
	 */
	public function test_wc_payment_gateway_enabled_notification(): void {
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
				'wc_get_logger' => function () use ( $fake_logger ) {
					return $fake_logger;
				},
			)
		);

		$action_fired   = array();
		$action_watcher = function ( $gateway ) use ( &$action_fired ) {
			$action_fired[] = $gateway;
		};
		add_action( 'woocommerce_payment_gateway_enabled', $action_watcher );

		foreach ( $this->sut->payment_gateways() as $gateway ) {
			$gateway->settings['enabled'] = 'no';
			$gateway->settings['title']   = null;
			update_option( $gateway->get_option_key(), $gateway->settings );

			$gateway->settings['enabled'] = 'yes';
			update_option( $gateway->get_option_key(), $gateway->settings );

			$this->assertEquals(
				'Payment gateway enabled: "' . $gateway->get_method_title() . '"',
				end( $fake_logger->infos )['message'],
				'Logger should record the gateway enable event'
			);

			$last_fired = end( $action_fired );
			$this->assertInstanceOf( WC_Payment_Gateway::class, $last_fired, 'Action should fire with a gateway object' );
			$this->assertEquals( $gateway->id, $last_fired->id, 'Action should fire with the correct gateway' );
		}

		remove_action( 'woocommerce_payment_gateway_enabled', $action_watcher );
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
