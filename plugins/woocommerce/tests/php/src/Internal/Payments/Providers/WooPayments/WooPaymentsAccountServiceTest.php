<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAccountService;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use WC_Unit_Test_Case;

/**
 * Tests for the WooPaymentsAccountService class.
 */
class WooPaymentsAccountServiceTest extends WC_Unit_Test_Case {

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		delete_option( 'wcpay_account_data' );
		delete_option( 'woocommerce_woocommerce_payments_settings' );
		delete_option( 'wcpay_onboarding_test_mode' );
		remove_all_filters( 'wcpay_dev_mode' );
		remove_all_filters( 'wcpay_test_mode' );
		remove_all_filters( 'wcpay_test_mode_onboarding' );
		parent::tearDown();
	}

	/**
	 * @testdox Should expose account ID, mode-specific publishable key, and readiness from the preserved account cache.
	 */
	public function test_exposes_account_keys_and_readiness_from_account_cache(): void {
		update_option(
			'wcpay_account_data',
			array(
				'data' => array(
					'account_id'           => 'acct_123',
					'test_publishable_key' => 'pk_test_123',
					'live_publishable_key' => 'pk_live_123',
					'payments_enabled'     => true,
					'details_submitted'    => true,
				),
			)
		);
		update_option( 'woocommerce_woocommerce_payments_settings', array( 'test_mode' => 'no' ) );

		$sut = $this->create_service();

		$this->assertSame( 'acct_123', $sut->get_account_id() );
		$this->assertSame( 'pk_live_123', $sut->get_publishable_key() );
		$this->assertSame( 'live', $sut->get_mode() );
		$this->assertTrue( $sut->can_process_payments() );

		update_option( 'woocommerce_woocommerce_payments_settings', array( 'test_mode' => 'yes' ) );

		$this->assertSame( 'pk_test_123', $sut->get_publishable_key() );
		$this->assertSame( 'test', $sut->get_mode() );
		$this->assertTrue( $sut->is_test_mode_enabled() );
	}

	/**
	 * @testdox Should fail closed for invalid or incomplete account cache payloads.
	 */
	public function test_fails_closed_for_invalid_or_incomplete_account_cache_payloads(): void {
		foreach (
			array(
				false,
				'invalid',
				array(),
				array( 'data' => 'invalid' ),
				array( 'data' => array() ),
			) as $payload
		) {
			update_option( 'wcpay_account_data', $payload );

			$sut = $this->create_service();

			$this->assertSame( '', $sut->get_account_id() );
			$this->assertSame( '', $sut->get_publishable_key() );
			$this->assertFalse( $sut->can_process_payments() );
		}
	}

	/**
	 * @testdox Should expose cached account IDs and keys while failing readiness for disabled or incomplete accounts.
	 */
	public function test_exposes_cache_values_but_fails_readiness_for_disabled_or_incomplete_accounts(): void {
		foreach (
			array(
				array(
					'data' => array(
						'account_id'           => 'acct_123',
						'live_publishable_key' => 'pk_live_123',
						'payments_enabled'     => false,
						'details_submitted'    => true,
					),
				),
				array(
					'data' => array(
						'account_id'           => 'acct_123',
						'live_publishable_key' => 'pk_live_123',
						'payments_enabled'     => true,
						'details_submitted'    => false,
					),
				),
			) as $payload
		) {
			update_option( 'wcpay_account_data', $payload );

			$sut = $this->create_service();

			$this->assertSame( 'acct_123', $sut->get_account_id() );
			$this->assertSame( 'pk_live_123', $sut->get_publishable_key() );
			$this->assertFalse( $sut->can_process_payments() );
		}
	}

	/**
	 * @testdox Should let onboarding test mode and the legacy test-mode filter override persisted gateway settings.
	 */
	public function test_mode_follows_onboarding_option_and_test_mode_filter(): void {
		update_option(
			'wcpay_account_data',
			array(
				'data' => array(
					'account_id'           => 'acct_123',
					'test_publishable_key' => 'pk_test_123',
					'live_publishable_key' => 'pk_live_123',
					'payments_enabled'     => true,
					'details_submitted'    => true,
				),
			)
		);
		update_option( 'woocommerce_woocommerce_payments_settings', array( 'test_mode' => 'no' ) );
		update_option( 'wcpay_onboarding_test_mode', 'yes' );

		$sut = $this->create_service();

		$this->assertTrue( $sut->is_test_mode_enabled() );
		$this->assertSame( 'test', $sut->get_mode() );
		$this->assertSame( 'pk_test_123', $sut->get_publishable_key() );

		update_option( 'wcpay_onboarding_test_mode', 'no' );
		add_filter( 'wcpay_test_mode', '__return_true' );

		$this->assertTrue( $sut->is_test_mode_enabled() );
		$this->assertSame( 'test', $sut->get_mode() );
	}

	/**
	 * @testdox Should force test mode when WooPayments onboarding or dev-mode filters are enabled.
	 */
	public function test_mode_follows_onboarding_and_dev_mode_filters(): void {
		update_option(
			'wcpay_account_data',
			array(
				'data' => array(
					'account_id'           => 'acct_123',
					'test_publishable_key' => 'pk_test_123',
					'live_publishable_key' => 'pk_live_123',
					'payments_enabled'     => true,
					'details_submitted'    => true,
				),
			)
		);
		update_option( 'woocommerce_woocommerce_payments_settings', array( 'test_mode' => 'no' ) );
		add_filter( 'wcpay_test_mode_onboarding', '__return_true' );

		$sut = $this->create_service();

		$this->assertTrue( $sut->is_test_mode_enabled() );
		$this->assertSame( 'test', $sut->get_mode() );
		$this->assertSame( 'pk_test_123', $sut->get_publishable_key() );

		remove_all_filters( 'wcpay_test_mode_onboarding' );
		add_filter( 'wcpay_dev_mode', '__return_true' );

		$this->assertTrue( $sut->is_test_mode_enabled() );
		$this->assertSame( 'test', $sut->get_mode() );
		$this->assertSame( 'pk_test_123', $sut->get_publishable_key() );
	}

	/**
	 * Create the service under test.
	 *
	 * @return WooPaymentsAccountService
	 */
	private function create_service(): WooPaymentsAccountService {
		$sut = new WooPaymentsAccountService();
		$sut->init( new LegacyProxy() );

		return $sut;
	}
}
