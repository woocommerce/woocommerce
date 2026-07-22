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
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		parent::tearDown();
		delete_option( 'jetpack_activation_source' );
	}

	/**
	 * @testdox Gateway settings updates diagnose malformed values and track enabled repairs.
	 */
	public function test_gateway_settings_updates_handle_non_array_values(): void {
		$option_name       = 'woocommerce_cod_settings';
		$disabled_settings = array( 'enabled' => 'no' );
		$enabled_settings  = array( 'enabled' => 'yes' );
		$tracking_option   = get_option( 'woocommerce_allow_tracking', null );
		$current_user_id   = get_current_user_id();

		// phpcs:disable Squiz.Commenting
		$fake_logger = new class() {
			public $infos    = array();
			public $warnings = array();

			public function info( $message, $data = array() ) {
				$this->infos[] = array(
					'message' => $message,
					'data'    => $data,
				);
			}

			public function warning( $message, $data = array() ) {
				$this->warnings[] = array(
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

		$providers_service = $this->createStub( \Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders::class );
		$providers_service->method( 'get_payment_gateway_details' )->willReturn( array() );
		wc_get_container()->replace( \Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders::class, $providers_service );

		$enabled_gateways = array();
		$action_watcher   = function ( $gateway ) use ( &$enabled_gateways ) {
			$enabled_gateways[] = $gateway;
		};
		add_action( 'woocommerce_payment_gateway_enabled', $action_watcher );

		try {
			wp_set_current_user( 0 );
			update_option( 'woocommerce_allow_tracking', 'yes' );
			$this->clear_tracks_events();

			delete_option( $option_name );
			add_option( $option_name, $disabled_settings );

			update_option( $option_name, '{"enabled":"yes"}' );
			$malformed_value        = get_option( $option_name );
			$malformed_info_count   = count( $fake_logger->infos );
			$malformed_action_count = count( $enabled_gateways );
			$malformed_tracks_count = count( $this->get_tracks_events( 'wcadmin_settings_payments_provider_enable' ) );

			update_option( $option_name, $enabled_settings );

			$this->assertSame( '{"enabled":"yes"}', $malformed_value, 'The malformed gateway settings should remain a string.' );
			$this->assertSame( 0, $malformed_info_count, 'A malformed new value should not log an enable transition.' );
			$this->assertSame( 0, $malformed_action_count, 'A malformed new value should not fire the gateway-enabled action.' );
			$this->assertSame( 0, $malformed_tracks_count, 'A malformed new value should not record an enable event.' );

			$this->assertSame( $enabled_settings, get_option( $option_name ), 'Valid gateway settings should be stored after a malformed value.' );
			$this->assertCount( 2, $fake_logger->warnings, 'Both malformed transition values should produce diagnostic warnings.' );
			$this->assertSame(
				'Payment gateway transition handling skipped because the new value for "woocommerce_cod_settings" is not an array.',
				$fake_logger->warnings[0]['message'],
				'The malformed new value warning should identify the affected option.'
			);
			$this->assertSame(
				'Previous payment gateway settings for "woocommerce_cod_settings" were not an array; treating the gateway as disabled.',
				$fake_logger->warnings[1]['message'],
				'The malformed old value warning should describe the repair behavior.'
			);
			$this->assertCount( 1, $fake_logger->infos, 'Repairing to enabled settings should log one enable transition.' );
			$this->assertCount( 1, $enabled_gateways, 'Repairing to enabled settings should fire the gateway-enabled action once.' );
			$this->assertSame( 'cod', $enabled_gateways[0]->id, 'The gateway-enabled action should receive the repaired gateway.' );
			$this->assertCount(
				1,
				$this->get_tracks_events( 'wcadmin_settings_payments_provider_enable' ),
				'Repairing to enabled settings should record one enable event.'
			);
		} finally {
			remove_action( 'woocommerce_payment_gateway_enabled', $action_watcher );
			wc_get_container()->reset_replacement( \Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders::class );
			$this->clear_tracks_events();
			wp_set_current_user( $current_user_id );

			if ( null === $tracking_option ) {
				delete_option( 'woocommerce_allow_tracking' );
			} else {
				update_option( 'woocommerce_allow_tracking', $tracking_option );
			}
		}
	}

	/**
	 * @testdox Enabling a gateway fires the notification action and logs the event.
	 */
	public function test_wc_payment_gateway_enabled_notification(): void {
		$gateways          = $this->sut->payment_gateways();
		$providers_service = $this->createMock( \Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders::class );
		$providers_service->expects( $this->exactly( count( $gateways ) ) )
			->method( 'get_payment_gateway_details' )
			->willReturn( array() );
		wc_get_container()->replace( \Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders::class, $providers_service );

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

		try {
			foreach ( $gateways as $gateway ) {
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

			$this->assertCount( count( $gateways ), $fake_logger->infos, 'Logger should run once per enabled gateway' );
			$this->assertCount( count( $gateways ), $action_fired, 'Action should fire once per enabled gateway' );
		} finally {
			remove_action( 'woocommerce_payment_gateway_enabled', $action_watcher );
			wc_get_container()->reset_replacement( \Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders::class );
		}
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
