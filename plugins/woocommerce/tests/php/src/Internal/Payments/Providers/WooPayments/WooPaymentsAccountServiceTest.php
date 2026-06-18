<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiException;
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
		set_current_screen( 'front' );
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
					'is_live'              => true,
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
	 * @testdox Should expose the account default currency from the preserved account cache.
	 */
	public function test_exposes_account_default_currency_from_account_cache(): void {
		update_option(
			'wcpay_account_data',
			array(
				'data' => array(
					'account_id'       => 'acct_123',
					'is_live'          => true,
					'store_currencies' => array(
						'default'   => 'eur',
						'supported' => array( 'eur' ),
					),
				),
			)
		);

		$sut = $this->create_service();

		$this->assertSame( 'eur', $sut->get_account_default_currency() );
	}

	/**
	 * @testdox Should fall back to USD when the preserved account cache does not include a default currency.
	 */
	public function test_account_default_currency_falls_back_to_usd_when_account_cache_omits_currency(): void {
		update_option(
			'wcpay_account_data',
			array(
				'data' => array(
					'account_id' => 'acct_123',
					'is_live'    => true,
				),
			)
		);

		$sut = $this->create_service();

		$this->assertSame( 'usd', $sut->get_account_default_currency() );
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
						'is_live'              => true,
						'payments_enabled'     => false,
						'details_submitted'    => true,
					),
				),
				array(
					'data' => array(
						'account_id'           => 'acct_123',
						'live_publishable_key' => 'pk_live_123',
						'is_live'              => true,
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
	 * @testdox Should expose live, test-drive, and sandbox account state from the preserved account cache.
	 */
	public function test_exposes_account_type_state_from_account_cache(): void {
		update_option( 'wcpay_onboarding_test_mode', 'yes' );
		update_option(
			'wcpay_account_data',
			array(
				'data' => array(
					'account_id'        => 'acct_123',
					'is_live'           => false,
					'is_test_drive'     => true,
					'payments_enabled'  => true,
					'details_submitted' => true,
				),
			)
		);

		$sut = $this->create_service();

		$this->assertTrue( $sut->has_account() );
		$this->assertTrue( $sut->has_test_account() );
		$this->assertFalse( $sut->has_sandbox_account() );
		$this->assertFalse( $sut->has_live_account() );
		$this->assertTrue( $sut->has_working_account() );

		update_option(
			'wcpay_account_data',
			array(
				'data' => array(
					'account_id'        => 'acct_123',
					'is_live'           => false,
					'is_test_drive'     => false,
					'payments_enabled'  => true,
					'details_submitted' => true,
				),
			)
		);
		$sut = $this->create_service();

		$this->assertTrue( $sut->has_sandbox_account() );
		$this->assertFalse( $sut->has_test_account() );
		$this->assertFalse( $sut->has_live_account() );

		update_option(
			'wcpay_account_data',
			array(
				'data' => array(
					'account_id'    => 'acct_123',
					'is_live'       => true,
					'is_test_drive' => false,
				),
			)
		);
		$sut = $this->create_service();

		$this->assertTrue( $sut->has_live_account() );
		$this->assertFalse( $sut->has_test_account() );
		$this->assertFalse( $sut->has_sandbox_account() );
	}

	/**
	 * @testdox Should clear the preserved account cache so the next account read can refresh from the provider.
	 */
	public function test_clear_cache_deletes_preserved_account_cache(): void {
		update_option(
			'wcpay_account_data',
			array(
				'data' => array(
					'account_id' => 'acct_123',
				),
			)
		);

		$sut = $this->create_service();
		$sut->clear_cache();

		$this->assertFalse( get_option( 'wcpay_account_data' ) );
		$this->assertSame( '', $sut->get_account_id() );
	}

	/**
	 * @testdox Should immediately overwrite the preserved account cache with a connected-no-account payload.
	 */
	public function test_overwrite_cache_with_no_account_updates_preserved_account_cache(): void {
		update_option(
			'wcpay_account_data',
			array(
				'data' => array(
					'account_id'        => 'acct_123',
					'is_live'           => true,
					'payments_enabled'  => true,
					'details_submitted' => true,
				),
			)
		);

		$sut = $this->create_service();
		$sut->overwrite_cache_with_no_account();

		$cached = get_option( 'wcpay_account_data' );

		$this->assertIsArray( $cached );
		$this->assertSame( array(), $cached['data'] );
		$this->assertSame( '', $sut->get_account_id() );
		$this->assertFalse( $sut->can_process_payments() );
	}

	/**
	 * @testdox Should refresh account data from the native API client and persist the full account payload.
	 */
	public function test_refresh_account_data_fetches_and_caches_full_account_payload(): void {
		update_option( 'woocommerce_store_id', 'store_123' );
		update_option( 'wcpay_onboarding_test_mode', 'yes' );
		update_option(
			'wcpay_account_data',
			array(
				'data' => array(
					'account_id' => 'acct_stale',
				),
			)
		);

		$api_client = new class() extends WooPaymentsApiClient {
			/**
			 * Store ID passed to the account request.
			 *
			 * @var string
			 */
			public string $woocommerce_store_id = '';

			/**
			 * Tell whether the fake client is available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return true;
			}

			/**
			 * Return a full fake account payload.
			 *
			 * @param string $woocommerce_store_id WooCommerce store ID.
			 * @return array<string,mixed>
			 */
			public function get_account( string $woocommerce_store_id = '' ): array {
				$this->woocommerce_store_id = $woocommerce_store_id;

				return array(
					'account_id'                => 'acct_native_123',
					'email'                     => 'merchant@example.test',
					'communications_email'      => 'support@example.test',
					'test_publishable_key'      => 'pk_test_native',
					'live_publishable_key'      => 'pk_live_native',
					'is_live'                   => false,
					'is_test_drive'             => true,
					'payments_enabled'          => true,
					'details_submitted'         => true,
					'business_profile'          => array(
						'name' => 'Native Merchant',
					),
					'fraud_mitigation_settings' => array(
						'card_testing_protection' => array(
							'enabled' => true,
						),
					),
					'pre_check_save_my_info'    => true,
					'account_details'           => array(
						'business_type' => 'individual',
					),
				);
			}
		};
		$sut        = $this->create_service_with_api_client( $api_client );
		$refreshed  = array();
		$hook       = static function ( $account ) use ( &$refreshed ): void {
			$refreshed = $account;
		};

		add_action( 'woocommerce_payments_account_refreshed', $hook );

		try {
			$result = $sut->refresh_account_data();
		} finally {
			remove_action( 'woocommerce_payments_account_refreshed', $hook );
		}

		$cached = get_option( 'wcpay_account_data' );

		$this->assertSame( 'acct_native_123', $result['account_id'] );
		$this->assertSame( 'store_123', $api_client->woocommerce_store_id );
		$this->assertIsArray( $cached );
		$this->assertSame( $result, $cached['data'] );
		$this->assertSame( $result, $refreshed );
		$this->assertArrayHasKey( 'fraud_mitigation_settings', $cached['data'] );
		$this->assertArrayHasKey( 'account_details', $cached['data'] );
		$this->assertSame( 'acct_native_123', $sut->get_account_id() );
	}

	/**
	 * @testdox Should preserve stale account data and mark the cache errored when a forced refresh fails.
	 */
	public function test_refresh_account_data_preserves_stale_account_data_on_transport_error(): void {
		$stale_account = $this->get_valid_live_account_payload(
			array(
				'account_id' => 'acct_stale',
			)
		);
		update_option(
			'wcpay_account_data',
			array(
				'data'               => $stale_account,
				'fetched'            => time() - DAY_IN_SECONDS,
				'errored'            => true,
				'consecutive_errors' => 1,
			)
		);

		$api_client = new class() extends WooPaymentsApiClient {
			/**
			 * Number of account fetch attempts.
			 *
			 * @var int
			 */
			public int $calls = 0;

			/**
			 * Tell whether the fake client is available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return true;
			}

			/**
			 * Throw a transient account-fetch failure.
			 *
			 * @param string $woocommerce_store_id WooCommerce store ID.
			 * @throws WooPaymentsApiException Always throws a transient account-fetch failure.
			 */
			public function get_account( string $woocommerce_store_id = '' ): array {
				++$this->calls;
				throw new WooPaymentsApiException( 'Temporary failure.', 'wcpay_temporary_failure', 500 );
			}
		};
		$sut        = $this->create_service_with_api_client( $api_client );
		$refreshed  = array();
		$hook       = static function ( $account ) use ( &$refreshed ): void {
			$refreshed = $account;
		};

		add_action( 'woocommerce_payments_account_refreshed', $hook );

		try {
			$result = $sut->refresh_account_data();
		} finally {
			remove_action( 'woocommerce_payments_account_refreshed', $hook );
		}

		$cached = get_option( 'wcpay_account_data' );

		$this->assertSame( $stale_account, $result );
		$this->assertSame( 1, $api_client->calls );
		$this->assertSame( array(), $refreshed );
		$this->assertIsArray( $cached );
		$this->assertSame( $stale_account, $cached['data'] );
		$this->assertTrue( $cached['errored'] );
		$this->assertSame( 2, $cached['consecutive_errors'] );
	}

	/**
	 * @testdox Should use the non-admin account cache for twenty-four hours before refreshing.
	 */
	public function test_get_cached_account_data_uses_frontend_ttl_before_refreshing(): void {
		$stale_account = $this->get_valid_live_account_payload(
			array(
				'account_id' => 'acct_stale',
			)
		);
		$fresh_account = $this->get_valid_live_account_payload(
			array(
				'account_id' => 'acct_fresh',
			)
		);
		$api_client    = $this->create_counting_account_api_client( $fresh_account );
		$sut           = $this->create_service_with_api_client( $api_client );

		update_option(
			'wcpay_account_data',
			array(
				'data'               => $stale_account,
				'fetched'            => time() - ( 23 * HOUR_IN_SECONDS ),
				'errored'            => false,
				'consecutive_errors' => 0,
			)
		);

		$this->assertSame( $stale_account, $sut->get_cached_account_data() );
		$this->assertSame( 0, $api_client->calls );

		update_option(
			'wcpay_account_data',
			array(
				'data'               => $stale_account,
				'fetched'            => time() - ( 25 * HOUR_IN_SECONDS ),
				'errored'            => false,
				'consecutive_errors' => 0,
			)
		);
		$sut = $this->create_service_with_api_client( $api_client );

		$this->assertSame( $fresh_account, $sut->get_cached_account_data() );
		$this->assertSame( 1, $api_client->calls );
	}

	/**
	 * @testdox Should use shorter admin TTLs and progressive backoff for errored account caches.
	 */
	public function test_get_cached_account_data_uses_admin_ttl_and_error_backoff(): void {
		set_current_screen( 'dashboard' );

		$stale_account = $this->get_valid_live_account_payload(
			array(
				'account_id' => 'acct_stale',
			)
		);
		$fresh_account = $this->get_valid_live_account_payload(
			array(
				'account_id' => 'acct_fresh',
			)
		);
		$api_client    = $this->create_counting_account_api_client( $fresh_account );
		$sut           = $this->create_service_with_api_client( $api_client );

		update_option(
			'wcpay_account_data',
			array(
				'data'               => $stale_account,
				'fetched'            => time() - ( 4 * MINUTE_IN_SECONDS ),
				'errored'            => true,
				'consecutive_errors' => 2,
			)
		);

		$this->assertSame( $stale_account, $sut->get_cached_account_data() );
		$this->assertSame( 0, $api_client->calls );

		update_option(
			'wcpay_account_data',
			array(
				'data'               => $stale_account,
				'fetched'            => time() - ( 6 * MINUTE_IN_SECONDS ),
				'errored'            => true,
				'consecutive_errors' => 2,
			)
		);
		$sut = $this->create_service_with_api_client( $api_client );

		$this->assertSame( $fresh_account, $sut->get_cached_account_data() );
		$this->assertSame( 1, $api_client->calls );
	}

	/**
	 * @testdox Should not refresh expired account data while Action Scheduler jobs are running.
	 */
	public function test_get_cached_account_data_does_not_refresh_during_action_scheduler_jobs(): void {
		$stale_account = $this->get_valid_live_account_payload(
			array(
				'account_id' => 'acct_stale',
			)
		);
		$fresh_account = $this->get_valid_live_account_payload(
			array(
				'account_id' => 'acct_fresh',
			)
		);
		$api_client    = $this->create_counting_account_api_client( $fresh_account );
		$sut           = $this->create_service_with_api_client( $api_client );

		update_option(
			'wcpay_account_data',
			array(
				'data'               => $stale_account,
				'fetched'            => time() - ( 25 * HOUR_IN_SECONDS ),
				'errored'            => false,
				'consecutive_errors' => 0,
			)
		);

		$sut->register();

		/**
		 * Fires before Action Scheduler executes an action.
		 *
		 * @since 11.0.0
		 */
		do_action( 'action_scheduler_before_execute' );

		try {
			$this->assertSame( $stale_account, $sut->get_cached_account_data() );
		} finally {
			remove_action( 'action_scheduler_before_execute', array( $sut, 'disable_refresh' ) );
		}

		$this->assertSame( 0, $api_client->calls );
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
					'is_live'              => true,
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
					'is_live'              => true,
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

	/**
	 * Create the service under test with a fake native API client.
	 *
	 * @param WooPaymentsApiClient $api_client Fake API client.
	 * @return WooPaymentsAccountService
	 */
	private function create_service_with_api_client( WooPaymentsApiClient $api_client ): WooPaymentsAccountService {
		$sut = new class( $api_client ) extends WooPaymentsAccountService {
			/**
			 * Fake native API client.
			 *
			 * @var WooPaymentsApiClient
			 */
			private WooPaymentsApiClient $api_client;

			/**
			 * Constructor.
			 *
			 * @param WooPaymentsApiClient $api_client Fake API client.
			 */
			public function __construct( WooPaymentsApiClient $api_client ) {
				$this->api_client = $api_client;
			}

			/**
			 * Get the fake native API client.
			 *
			 * @return WooPaymentsApiClient|null
			 */
			protected function get_api_client(): ?WooPaymentsApiClient {
				return $this->api_client;
			}
		};
		$sut->init( new LegacyProxy() );

		return $sut;
	}

	/**
	 * Create a fake account API client that counts account fetches.
	 *
	 * @param array<string,mixed> $account_data Account payload.
	 * @return WooPaymentsApiClient
	 */
	private function create_counting_account_api_client( array $account_data ): WooPaymentsApiClient {
		return new class( $account_data ) extends WooPaymentsApiClient {
			/**
			 * Account payload.
			 *
			 * @var array<string,mixed>
			 */
			private array $account_data;

			/**
			 * Number of account fetches.
			 *
			 * @var int
			 */
			public int $calls = 0;

			/**
			 * Constructor.
			 *
			 * @param array<string,mixed> $account_data Account payload.
			 */
			public function __construct( array $account_data ) {
				$this->account_data = $account_data;
			}

			/**
			 * Tell whether the fake client is available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return true;
			}

			/**
			 * Return the fake account payload.
			 *
			 * @param string $woocommerce_store_id WooCommerce store ID.
			 * @return array<string,mixed>
			 */
			public function get_account( string $woocommerce_store_id = '' ): array {
				++$this->calls;

				return $this->account_data;
			}
		};
	}

	/**
	 * Get a valid live account payload for cache tests.
	 *
	 * @param array<string,mixed> $overrides Account overrides.
	 * @return array<string,mixed>
	 */
	private function get_valid_live_account_payload( array $overrides = array() ): array {
		return array_merge(
			array(
				'account_id'           => 'acct_123',
				'live_publishable_key' => 'pk_live_123',
				'is_live'              => true,
				'payments_enabled'     => true,
				'details_submitted'    => true,
			),
			$overrides
		);
	}
}
