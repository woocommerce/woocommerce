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
	 * @testdox Gateway settings option changes use gateway defaults for malformed transition values.
	 * @dataProvider provider_malformed_gateway_settings_transitions
	 *
	 * @param string|null $enabled_default     The gateway's enabled form-field default, or null to remove the field.
	 * @param mixed       $old_value           The previously stored option value, or null for an added option.
	 * @param mixed       $new_value           The option value to add or update.
	 * @param string[]    $warning_sides       The malformed transition sides expected to produce warnings.
	 * @param int         $info_count          The expected number of informational log entries.
	 * @param int         $action_count        The expected number of gateway-enabled actions.
	 * @param int         $enable_tracks_count The expected number of gateway-enable Tracks events.
	 * @param int         $disable_tracks_count The expected number of gateway-disable Tracks events.
	 */
	public function test_gateway_settings_option_changes_handle_non_array_values(
		?string $enabled_default,
		$old_value,
		$new_value,
		array $warning_sides,
		int $info_count,
		int $action_count,
		int $enable_tracks_count,
		int $disable_tracks_count
	): void {
		$option_name     = 'woocommerce_cod_settings';
		$gateway         = $this->sut->payment_gateways()['cod'];
		$tracking_option = get_option( 'woocommerce_allow_tracking', null );
		$current_user_id = get_current_user_id();

		if ( null === $enabled_default ) {
			unset( $gateway->form_fields['enabled'] );
		} else {
			$gateway->form_fields['enabled']['default'] = $enabled_default;
		}

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

			delete_option( $option_name );
			if ( null === $old_value ) {
				$this->clear_tracks_events();
				add_option( $option_name, $new_value );
			} else {
				add_option( $option_name, $old_value );
				$fake_logger->infos    = array();
				$fake_logger->warnings = array();
				$enabled_gateways      = array();
				$this->clear_tracks_events();

				update_option( $option_name, $new_value );
			}

			$this->assertSame( $new_value, get_option( $option_name ), 'The option hook should not rewrite or decode the stored settings value.' );
			$this->assertCount( $info_count, $fake_logger->infos, 'The transition should produce the expected number of informational log entries.' );
			$this->assertCount( $action_count, $enabled_gateways, 'The transition should fire the gateway-enabled action the expected number of times.' );
			if ( 1 === $action_count ) {
				$this->assertSame( $gateway, $enabled_gateways[0], 'The gateway-enabled action should receive the COD gateway instance.' );
			}
			$this->assertCount(
				$enable_tracks_count,
				$this->get_tracks_events( 'wcadmin_settings_payments_provider_enable' ),
				'The transition should record the expected number of gateway-enable events.'
			);
			$this->assertCount(
				$disable_tracks_count,
				$this->get_tracks_events( 'wcadmin_settings_payments_provider_disable' ),
				'The transition should record the expected number of gateway-disable events.'
			);
			if ( 1 === $info_count ) {
				$this->assertSame( 'Payment gateway enabled: "Cash on delivery"', $fake_logger->infos[0]['message'], 'The informational log should identify the enabled gateway.' );
			}

			$this->assertCount( count( $warning_sides ), $fake_logger->warnings, 'Each malformed transition side should produce one warning.' );
			foreach ( $warning_sides as $warning_index => $warning_side ) {
				$expected_warning = 'new' === $warning_side
					? sprintf( 'New payment gateway settings for "%s" were not an array; using gateway defaults for transition handling.', $option_name )
					: sprintf( 'Previous payment gateway settings for "%s" were not an array; using gateway defaults for transition handling.', $option_name );

				$this->assertSame( $expected_warning, $fake_logger->warnings[ $warning_index ]['message'], "The malformed {$warning_side} value warning should describe the fallback behavior." );
				$this->assertSame( array( 'source' => 'payment-gateways' ), $fake_logger->warnings[ $warning_index ]['data'], "The malformed {$warning_side} value warning should use the payment-gateways source." );
			}
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
	 * Provides malformed gateway settings transitions.
	 *
	 * @return array<string, array{0: string|null, 1: mixed, 2: mixed, 3: string[], 4: int, 5: int, 6: int, 7: int}>
	 */
	public function provider_malformed_gateway_settings_transitions(): array {
		$malformed_old = '{"enabled":"yes"}';
		$malformed_new = '{"enabled":"no"}';
		$disabled      = array( 'enabled' => 'no' );
		$enabled       = array( 'enabled' => 'yes' );

		return array(
			'default disabled: malformed to explicit disabled' => array( 'no', $malformed_old, $disabled, array( 'old' ), 0, 0, 0, 0 ),
			'default disabled: malformed to explicit enabled' => array( 'no', $malformed_old, $enabled, array( 'old' ), 1, 1, 1, 0 ),
			'default enabled: malformed to explicit enabled' => array( 'yes', $malformed_old, $enabled, array( 'old' ), 0, 0, 0, 0 ),
			'default enabled: malformed to explicit disabled' => array( 'yes', $malformed_old, $disabled, array( 'old' ), 0, 0, 0, 1 ),
			'default disabled: explicit disabled to malformed' => array( 'no', $disabled, $malformed_new, array( 'new' ), 0, 0, 0, 0 ),
			'default enabled: explicit disabled to malformed' => array( 'yes', $disabled, $malformed_new, array( 'new' ), 1, 1, 1, 0 ),
			'default disabled: explicit enabled to malformed' => array( 'no', $enabled, $malformed_new, array( 'new' ), 0, 0, 0, 1 ),
			'default enabled: explicit enabled to malformed' => array( 'yes', $enabled, $malformed_new, array( 'new' ), 0, 0, 0, 0 ),
			'default enabled: distinct malformed values'  => array( 'yes', $malformed_old, $malformed_new, array( 'new', 'old' ), 0, 0, 0, 0 ),
			'missing enabled field: enabled to malformed' => array( null, $enabled, $malformed_new, array( 'new' ), 0, 0, 0, 1 ),
			'add malformed value with enabled default'    => array( 'yes', null, $malformed_new, array( 'new' ), 0, 0, 0, 0 ),
		);
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
