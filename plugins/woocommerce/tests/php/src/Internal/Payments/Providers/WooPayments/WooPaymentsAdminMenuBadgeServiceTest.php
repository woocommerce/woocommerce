<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAccountService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAdminMenuBadgeService;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use WC_Unit_Test_Case;

/**
 * Tests for the WooPaymentsAdminMenuBadgeService class.
 */
class WooPaymentsAdminMenuBadgeServiceTest extends WC_Unit_Test_Case {

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		delete_option( 'wcpay_dispute_status_counts_cache' );
		delete_option( 'wcpay_test_dispute_status_counts_cache' );
		delete_option( 'wcpay_authorization_summary_cache' );
		delete_option( 'wcpay_test_authorization_summary_cache' );
		remove_all_filters( 'wcpay_database_cache_ttl' );

		parent::tearDown();
	}

	/**
	 * @testdox Should sum only actionable dispute statuses for the menu badge.
	 */
	public function test_get_disputes_awaiting_response_count_sums_only_actionable_statuses(): void {
		$api_client = $this->create_api_client(
			array(
				'needs_response'         => 2,
				'warning_needs_response' => 1,
				'under_review'           => 7,
			)
		);
		$sut        = $this->create_service( $api_client );

		$this->assertSame( 3, $sut->get_disputes_awaiting_response_count() );
		$this->assertSame( 1, $api_client->dispute_status_count_calls );
		$this->assertSame(
			array(
				'data'    => array(
					'needs_response'         => 2,
					'warning_needs_response' => 1,
					'under_review'           => 7,
				),
				'fetched' => get_option( 'wcpay_dispute_status_counts_cache' )['fetched'],
				'errored' => false,
			),
			get_option( 'wcpay_dispute_status_counts_cache' )
		);
	}

	/**
	 * @testdox Should use stale valid dispute cache when refreshing fails.
	 */
	public function test_get_disputes_awaiting_response_count_uses_stale_valid_cache_when_fetch_fails(): void {
		update_option(
			'wcpay_dispute_status_counts_cache',
			array(
				'data'    => array( 'needs_response' => 4 ),
				'fetched' => time() - DAY_IN_SECONDS - 5,
				'errored' => false,
			),
			false
		);

		$api_client                    = $this->create_api_client();
		$api_client->throw_on_disputes = true;
		$sut                           = $this->create_service( $api_client );

		$this->assertSame( 4, $sut->get_disputes_awaiting_response_count() );
		$this->assertSame( 1, $api_client->dispute_status_count_calls );
		$this->assertSame( array( 'needs_response' => 4 ), get_option( 'wcpay_dispute_status_counts_cache' )['data'] );
		$this->assertTrue( get_option( 'wcpay_dispute_status_counts_cache' )['errored'] );
	}

	/**
	 * @testdox Should return zero when dispute count refresh fails without valid stale data.
	 */
	public function test_get_disputes_awaiting_response_count_returns_zero_without_valid_cache_on_fetch_failure(): void {
		$api_client                    = $this->create_api_client();
		$api_client->throw_on_disputes = true;
		$sut                           = $this->create_service( $api_client );

		$this->assertSame( 0, $sut->get_disputes_awaiting_response_count() );
		$this->assertSame( 1, $api_client->dispute_status_count_calls );
		$this->assertTrue( get_option( 'wcpay_dispute_status_counts_cache' )['errored'] );

		$this->assertSame( 0, $sut->get_disputes_awaiting_response_count() );
		$this->assertSame( 1, $api_client->dispute_status_count_calls, 'Errored cache wrappers should suppress repeated admin-load refreshes until TTL expiry.' );
	}

	/**
	 * @testdox Should retry errored cold dispute cache after the short failure TTL expires.
	 */
	public function test_get_disputes_awaiting_response_count_retries_cold_failure_after_short_ttl(): void {
		update_option(
			'wcpay_dispute_status_counts_cache',
			array(
				'data'    => null,
				'fetched' => time() - MINUTE_IN_SECONDS - 1,
				'errored' => true,
			),
			false
		);

		$api_client = $this->create_api_client( array( 'needs_response' => 2 ) );
		$sut        = $this->create_service( $api_client );

		$this->assertSame( 2, $sut->get_disputes_awaiting_response_count() );
		$this->assertSame( 1, $api_client->dispute_status_count_calls );
		$this->assertFalse( get_option( 'wcpay_dispute_status_counts_cache' )['errored'] );
	}

	/**
	 * @testdox Should not fetch authorization summary when manual capture is disabled.
	 */
	public function test_get_uncaptured_transactions_count_returns_zero_when_manual_capture_is_disabled(): void {
		$api_client = $this->create_api_client( array(), array( 'count' => 5 ) );
		$sut        = $this->create_service( $api_client, false, 'no' );

		$this->assertSame( 0, $sut->get_uncaptured_transactions_count() );
		$this->assertSame( 0, $api_client->authorization_summary_calls );
	}

	/**
	 * @testdox Should read authorization summary when manual capture is enabled.
	 */
	public function test_get_uncaptured_transactions_count_reads_authorization_summary_when_manual_capture_is_enabled(): void {
		$api_client = $this->create_api_client( array(), array( 'count' => 6 ) );
		$sut        = $this->create_service( $api_client, false, 'yes' );

		$this->assertSame( 6, $sut->get_uncaptured_transactions_count() );
		$this->assertSame( 1, $api_client->authorization_summary_calls );
	}

	/**
	 * @testdox Should use test-mode cache key for authorization summary.
	 */
	public function test_get_uncaptured_transactions_count_uses_test_mode_cache_key(): void {
		$api_client = $this->create_api_client( array(), array( 'count' => 2 ) );
		$sut        = $this->create_service( $api_client, true, 'yes' );

		$this->assertSame( 2, $sut->get_uncaptured_transactions_count() );
		$this->assertFalse( get_option( 'wcpay_authorization_summary_cache', false ) );
		$this->assertSame( array( 'count' => 2 ), get_option( 'wcpay_test_authorization_summary_cache' )['data'] );
	}

	/**
	 * Create the service under test.
	 *
	 * @param WooPaymentsApiClient $api_client       API client fake.
	 * @param bool                 $test_mode        Whether test mode is enabled.
	 * @param string               $manual_capture   Manual capture setting.
	 * @return WooPaymentsAdminMenuBadgeService
	 */
	private function create_service( WooPaymentsApiClient $api_client, bool $test_mode = false, string $manual_capture = 'no' ): WooPaymentsAdminMenuBadgeService {
		$account_service = $this->create_account_service( $test_mode, $manual_capture );
		$sut             = new WooPaymentsAdminMenuBadgeService();
		$sut->init( $account_service, $api_client );

		return $sut;
	}

	/**
	 * Create an account service mock.
	 *
	 * @param bool   $test_mode      Whether test mode is enabled.
	 * @param string $manual_capture Manual capture setting.
	 * @return WooPaymentsAccountService&MockObject
	 */
	private function create_account_service( bool $test_mode, string $manual_capture ): WooPaymentsAccountService {
		$account_service = $this->getMockBuilder( WooPaymentsAccountService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'is_test_mode_enabled', 'get_gateway_setting' ) )
			->getMock();

		$account_service->method( 'is_test_mode_enabled' )->willReturn( $test_mode );
		$account_service->method( 'get_gateway_setting' )->willReturnMap(
			array(
				array( 'manual_capture', 'no', $manual_capture ),
			)
		);

		return $account_service;
	}

	/**
	 * Create an API client fake.
	 *
	 * @param array<string,mixed> $dispute_status_counts Dispute status-count payload.
	 * @param array<string,mixed> $authorization_summary Authorization summary payload.
	 * @return WooPaymentsApiClient
	 */
	private function create_api_client( array $dispute_status_counts = array(), array $authorization_summary = array() ): WooPaymentsApiClient {
		return new class( $dispute_status_counts, $authorization_summary ) extends WooPaymentsApiClient {
			/**
			 * Dispute status-count payload.
			 *
			 * @var array<string,mixed>
			 */
			private array $dispute_status_counts;

			/**
			 * Authorization summary payload.
			 *
			 * @var array<string,mixed>
			 */
			private array $authorization_summary;

			/**
			 * Dispute status-count call count.
			 *
			 * @var int
			 */
			public int $dispute_status_count_calls = 0;

			/**
			 * Authorization summary call count.
			 *
			 * @var int
			 */
			public int $authorization_summary_calls = 0;

			/**
			 * Whether dispute calls should fail.
			 *
			 * @var bool
			 */
			public bool $throw_on_disputes = false;

			/**
			 * Constructor.
			 *
			 * @param array<string,mixed> $dispute_status_counts Dispute status-count payload.
			 * @param array<string,mixed> $authorization_summary Authorization summary payload.
			 */
			public function __construct( array $dispute_status_counts, array $authorization_summary ) {
				$this->dispute_status_counts = $dispute_status_counts;
				$this->authorization_summary = $authorization_summary;
			}

			/**
			 * Return fake dispute status-count data.
			 *
			 * @return array<string,mixed>
			 */
			public function get_dispute_status_counts(): array {
				++$this->dispute_status_count_calls;

				if ( $this->throw_on_disputes ) {
					throw new RuntimeException( 'Failed dispute count fetch.' );
				}

				return $this->dispute_status_counts;
			}

			/**
			 * Return fake authorization summary data.
			 *
			 * @return array<string,mixed>
			 */
			public function get_authorizations_summary(): array {
				++$this->authorization_summary_calls;

				return $this->authorization_summary;
			}
		};
	}
}
