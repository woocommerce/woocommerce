<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsLegacyRuntime;
use WC_Unit_Test_Case;

/**
 * Tests for the WooPaymentsLegacyRuntime class.
 */
class WooPaymentsLegacyRuntimeTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should fail closed when the WooPayments runtime is absent.
	 */
	public function test_fails_closed_when_woopayments_runtime_is_absent(): void {
		$sut = new WooPaymentsLegacyRuntime();
		$sut->init( new LegacyRuntimeProxy( false ) );

		$this->assertFalse( $sut->is_loaded() );
		$this->assertNull( $sut->get_gateway() );
		$this->assertNull( $sut->get_account_service() );
		$this->assertNull( $sut->get_payments_api_client() );
	}

	/**
	 * @testdox Should return WooPayments runtime services when available.
	 */
	public function test_returns_woopayments_runtime_services_when_available(): void {
		$gateway    = (object) array( 'id' => 'gateway' );
		$account    = (object) array( 'id' => 'account' );
		$api_client = (object) array( 'id' => 'api_client' );
		$logger     = (object) array( 'id' => 'logger' );
		$sut        = new WooPaymentsLegacyRuntime();
		$sut->init( new LegacyRuntimeProxy( true, $gateway, $account, $api_client, $logger ) );

		$this->assertTrue( $sut->is_loaded() );
		$this->assertSame( $gateway, $sut->get_gateway() );
		$this->assertSame( $account, $sut->get_account_service() );
		$this->assertSame( $api_client, $sut->get_payments_api_client() );
		$this->assertSame( $logger, $sut->get_logger() );
	}

	/**
	 * @testdox Should return WooPayments account URLs when the account helpers are callable.
	 */
	public function test_returns_woopayments_account_urls_when_helpers_are_callable(): void {
		$sut = new WooPaymentsLegacyRuntime();
		$sut->init(
			new LegacyRuntimeProxy(
				true,
				null,
				null,
				null,
				null,
				'https://example.com/connect',
				'https://example.com/overview'
			)
		);

		$this->assertSame( 'https://example.com/connect', $sut->get_account_connect_url( 'native-payments' ) );
		$this->assertSame( 'https://example.com/overview', $sut->get_account_overview_page_url() );
	}

	/**
	 * @testdox Should return WooPayments mode flags when available.
	 */
	public function test_returns_woopayments_mode_flags_when_available(): void {
		$mode = new class() {
			/**
			 * Tell whether WooPayments is in test mode.
			 *
			 * @return bool
			 */
			public function is_test(): bool {
				return true;
			}

			/**
			 * Tell whether WooPayments is in dev mode.
			 *
			 * @return bool
			 */
			public function is_dev(): bool {
				return false;
			}

			/**
			 * Tell whether WooPayments is in test-mode onboarding.
			 *
			 * @return bool
			 */
			public function is_test_mode_onboarding(): bool {
				return true;
			}
		};
		$sut  = new WooPaymentsLegacyRuntime();
		$sut->init( new LegacyRuntimeProxy( true, null, null, null, null, null, null, $mode ) );

		$this->assertTrue( $sut->is_test_mode() );
		$this->assertFalse( $sut->is_dev_mode() );
		$this->assertTrue( $sut->is_test_mode_onboarding() );
	}

	/**
	 * @testdox Should return WooPayments gateway state flags when available.
	 */
	public function test_returns_gateway_state_helpers_when_available(): void {
		$gateway = new class() {
			/**
			 * Tell whether the gateway is connected.
			 *
			 * @return bool
			 */
			public function is_connected(): bool {
				return true;
			}

			/**
			 * Tell whether the account is partially onboarded.
			 *
			 * @return bool
			 */
			public function is_account_partially_onboarded(): bool {
				return false;
			}
		};
		$sut     = new WooPaymentsLegacyRuntime();
		$sut->init( new LegacyRuntimeProxy( true, $gateway ) );

		$this->assertTrue( $sut->is_gateway_connected() );
		$this->assertFalse( $sut->is_gateway_partially_onboarded() );
	}

	/**
	 * @testdox Should fail closed when WooPayments gateway state helpers are unavailable.
	 */
	public function test_gateway_state_helpers_fail_closed_when_unavailable(): void {
		$gateway_without_methods = (object) array( 'id' => 'woocommerce_payments' );
		$absent_runtime          = new WooPaymentsLegacyRuntime();
		$missing_methods_runtime = new WooPaymentsLegacyRuntime();

		$absent_runtime->init( new LegacyRuntimeProxy( false ) );
		$missing_methods_runtime->init( new LegacyRuntimeProxy( true, $gateway_without_methods ) );

		$this->assertNull( $absent_runtime->is_gateway_connected() );
		$this->assertNull( $absent_runtime->is_gateway_partially_onboarded() );
		$this->assertNull( $missing_methods_runtime->is_gateway_connected() );
		$this->assertNull( $missing_methods_runtime->is_gateway_partially_onboarded() );
	}

	/**
	 * @testdox Should hide WooPayments gateways on the settings page through the legacy runtime.
	 */
	public function test_hides_gateways_on_settings_page_through_runtime(): void {
		$proxy = new LegacyRuntimeProxy( true );
		$sut   = new WooPaymentsLegacyRuntime();
		$sut->init( $proxy );

		$this->assertTrue( $sut->hide_gateways_on_settings_page() );
		$this->assertSame( 1, $proxy->get_hide_gateways_on_settings_page_calls() );
	}

	/**
	 * @testdox Should not hide WooPayments gateways when the legacy runtime is absent.
	 */
	public function test_does_not_hide_gateways_on_settings_page_when_runtime_is_absent(): void {
		$proxy = new LegacyRuntimeProxy( false );
		$sut   = new WooPaymentsLegacyRuntime();
		$sut->init( $proxy );

		$this->assertFalse( $sut->hide_gateways_on_settings_page() );
		$this->assertSame( 0, $proxy->get_hide_gateways_on_settings_page_calls() );
	}

	/**
	 * @testdox Should return account status data and supported countries when available.
	 */
	public function test_returns_account_status_data_and_supported_countries_when_available(): void {
		$account = new class() {
			/**
			 * Get account status data.
			 *
			 * @return array<string,mixed>
			 */
			public function get_account_status_data(): array {
				return array(
					'testDrive' => true,
					'isLive'    => false,
				);
			}
		};
		$sut     = new WooPaymentsLegacyRuntime();
		$sut->init(
			new LegacyRuntimeProxy(
				true,
				null,
				$account,
				null,
				null,
				null,
				null,
				null,
				array(
					'US' => 'United States',
					'CA' => 'Canada',
				)
			)
		);

		$this->assertSame(
			array(
				'testDrive' => true,
				'isLive'    => false,
			),
			$sut->get_account_status_data()
		);
		$this->assertSame(
			array(
				'US' => 'United States',
				'CA' => 'Canada',
			),
			$sut->get_supported_countries()
		);
	}

	/**
	 * @testdox Should report cached WooPayments account data when an account ID exists.
	 */
	public function test_reports_cached_account_data_when_account_id_exists(): void {
		$sut = $this->create_runtime_with_account_data(
			array(
				'data' => array(
					'account_id' => 'acct_123',
				),
			)
		);

		$this->assertTrue( $sut->has_cached_account_data() );
		$this->assertFalse( $sut->is_account_onboarded_from_cache() );
	}

	/**
	 * @testdox Should return admin onboarding context values from WooPayments constants when available.
	 */
	public function test_returns_admin_onboarding_context_values_from_woopayments_constants(): void {
		Constants::set_constant( 'WC_Payments_Onboarding_Service::FROM_WCADMIN_PAYMENTS_SETTINGS', 'RUNTIME_FROM' );
		Constants::set_constant( 'WC_Payments_Onboarding_Service::SOURCE_WCADMIN_SETTINGS_PAGE', 'runtime-source' );

		try {
			$sut = new WooPaymentsLegacyRuntime();
			$sut->init( new LegacyRuntimeProxy( true ) );

			$this->assertSame(
				array(
					'from'   => 'RUNTIME_FROM',
					'source' => 'runtime-source',
				),
				$sut->get_admin_onboarding_context()
			);
		} finally {
			Constants::clear_constants();
		}
	}

	/**
	 * @testdox Should return Core admin onboarding context fallbacks when WooPayments constants are unavailable.
	 */
	public function test_returns_admin_onboarding_context_fallback_values(): void {
		Constants::clear_constants();

		$sut = new WooPaymentsLegacyRuntime();
		$sut->init( new LegacyRuntimeProxy( true ) );

		$this->assertSame(
			array(
				'from'   => 'WCADMIN_PAYMENT_SETTINGS',
				'source' => 'wcadmin-settings-page',
			),
			$sut->get_admin_onboarding_context()
		);
	}

	/**
	 * @testdox Should compare WooPayments extension version when the version constant is available.
	 */
	public function test_compares_extension_version_when_constant_is_available(): void {
		Constants::set_constant( 'WCPAY_VERSION_NUMBER', '10.0.0' );

		try {
			$sut = new WooPaymentsLegacyRuntime();
			$sut->init( new LegacyRuntimeProxy( true ) );

			$this->assertTrue( $sut->is_extension_version_less_than( '10.1.0' ) );
			$this->assertFalse( $sut->is_extension_version_less_than( '10.0.0' ) );
			$this->assertFalse( $sut->is_extension_version_less_than( '9.9.0' ) );
		} finally {
			Constants::clear_constants();
		}
	}

	/**
	 * @testdox Should return null when the WooPayments extension version is unavailable.
	 */
	public function test_returns_null_when_extension_version_is_unavailable(): void {
		Constants::clear_constants();

		$sut = new WooPaymentsLegacyRuntime();
		$sut->init( new LegacyRuntimeProxy( true ) );

		$this->assertNull( $sut->is_extension_version_less_than( '10.0.0' ) );
	}

	/**
	 * @testdox Should return null when the WooPayments extension version is not scalar.
	 */
	public function test_returns_null_when_extension_version_is_not_scalar(): void {
		Constants::set_constant( 'WCPAY_VERSION_NUMBER', array( 'version' => '10.0.0' ) );

		try {
			$sut = new WooPaymentsLegacyRuntime();
			$sut->init( new LegacyRuntimeProxy( true ) );

			$this->assertNull( $sut->is_extension_version_less_than( '10.0.0' ) );
		} finally {
			Constants::clear_constants();
		}
	}

	/**
	 * @testdox Should report onboarded WooPayments account data when details are submitted.
	 */
	public function test_reports_onboarded_account_data_when_details_are_submitted(): void {
		foreach ( array( true, 'true', 'yes', '1', 1 ) as $details_submitted ) {
			$sut = $this->create_runtime_with_account_data(
				array(
					'data' => array(
						'account_id'        => 'acct_123',
						'details_submitted' => $details_submitted,
					),
				)
			);

			$this->assertTrue( $sut->has_cached_account_data() );
			$this->assertTrue( $sut->is_account_onboarded_from_cache() );
		}
	}

	/**
	 * @testdox Should ignore invalid WooPayments account cache payloads.
	 */
	public function test_ignores_invalid_account_cache_payloads(): void {
		foreach ( array( false, 'invalid', array(), array( 'data' => 'invalid' ), array( 'data' => array() ) ) as $account_data ) {
			$sut = $this->create_runtime_with_account_data( $account_data );

			$this->assertFalse( $sut->has_cached_account_data() );
			$this->assertFalse( $sut->is_account_onboarded_from_cache() );
		}
	}

	/**
	 * @testdox Should reset WooPayments onboarding test mode option when available.
	 */
	public function test_resets_onboarding_test_mode_option_when_available(): void {
		Constants::set_constant( 'WC_Payments_Onboarding_Service::TEST_MODE_OPTION', 'wcpay_test_mode' );
		$proxy = new LegacyRuntimeProxy( true );
		$sut   = new WooPaymentsLegacyRuntime();
		$sut->init( $proxy );

		try {
			$sut->reset_onboarding_test_mode_option();

			$this->assertSame(
				array(
					'wcpay_test_mode' => 'no',
				),
				$proxy->get_updated_options()
			);
		} finally {
			Constants::clear_constants();
		}
	}

	/**
	 * @testdox Should fail closed for account URLs when account helpers are unavailable.
	 */
	public function test_fails_closed_for_account_urls_when_helpers_are_unavailable(): void {
		$sut = new WooPaymentsLegacyRuntime();
		$sut->init( new LegacyRuntimeProxy( true ) );

		$this->assertNull( $sut->get_account_connect_url( 'native-payments' ) );
		$this->assertNull( $sut->get_account_overview_page_url() );
	}

	/**
	 * @testdox Should swallow legacy runtime lookup exceptions.
	 */
	public function test_swallows_runtime_lookup_exceptions(): void {
		$sut = new WooPaymentsLegacyRuntime();
		$sut->init( new ThrowingLegacyRuntimeProxy() );

		$this->assertFalse( $sut->is_loaded() );
		$this->assertNull( $sut->get_gateway() );
		$this->assertNull( $sut->get_account_service() );
		$this->assertNull( $sut->get_payments_api_client() );
		$this->assertNull( $sut->get_logger() );
		$this->assertNull( $sut->get_account_connect_url( 'native-payments' ) );
		$this->assertNull( $sut->get_account_overview_page_url() );
		$this->assertNull( $sut->is_test_mode() );
		$this->assertNull( $sut->is_dev_mode() );
		$this->assertNull( $sut->is_test_mode_onboarding() );
		$this->assertNull( $sut->get_account_status_data() );
		$this->assertNull( $sut->get_supported_countries() );
	}

	/**
	 * Create a runtime with injected WooPayments account data.
	 *
	 * @param mixed $account_data Account data payload.
	 * @return WooPaymentsLegacyRuntime
	 */
	private function create_runtime_with_account_data( $account_data ): WooPaymentsLegacyRuntime {
		$proxy = new LegacyRuntimeProxy( true );
		$proxy->set_account_data( $account_data );

		$sut = new WooPaymentsLegacyRuntime();
		$sut->init( $proxy );

		return $sut;
	}
}
