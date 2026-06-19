<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings\PaymentsProviders\WooPayments;

use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\WooPayments\WooPaymentsOverviewService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAccountService;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use WC_Unit_Test_Case;

/**
 * Tests for the WooPaymentsOverviewService class.
 */
class WooPaymentsOverviewServiceTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WooPaymentsOverviewService
	 */
	private WooPaymentsOverviewService $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = new WooPaymentsOverviewService();
		$this->sut->init( $this->create_account_service() );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		delete_option( 'wcpay_account_data' );
		delete_option( 'woocommerce_woocommerce_payments_settings' );
		delete_option( 'woocommerce_dismissed_todo_tasks' );
		delete_option( 'woocommerce_deleted_todo_tasks' );
		delete_option( 'woocommerce_remind_me_later_todo_tasks' );
		delete_option( 'wcpay_connection_success_modal_dismissed' );
		delete_option( 'wcpay_onboarding_test_mode' );
		delete_option( 'wcpay_dispute_status_counts_cache' );
		delete_option( 'wcpay_test_dispute_status_counts_cache' );
		delete_option( '_wcpay_feature_dispute_readiness_overview' );

		parent::tearDown();
	}

	/**
	 * @testdox Should return a safe Overview projection from the preserved account snapshot.
	 */
	public function test_get_overview_returns_safe_projection(): void {
		$this->cache_account_data(
			array(
				'account_id'           => 'acct_native_test',
				'test_publishable_key' => 'pk_test_secret',
				'live_publishable_key' => 'pk_live_secret',
				'details_submitted'    => true,
				'payments_enabled'     => true,
				'deposits_enabled'     => true,
				'is_live'              => false,
				'is_test_drive'        => true,
				'account_details'      => array(
					'account_status' => array(
						'text'             => 'Restricted soon',
						'background_color' => 'yellow',
					),
					'payout_status'  => array(
						'text'             => 'Payouts enabled',
						'background_color' => 'green',
					),
					'banner'         => array(
						'text'             => 'Action required',
						'background_color' => 'yellow',
					),
				),
				'fees'                 => array(
					'card'           => array(
						'base'     => array(
							'percentage_rate' => 2.9,
							'fixed_rate'      => 0.3,
						),
						'discount' => array(
							array(
								'currency'        => 'usd',
								'percentage_rate' => 1.1,
								'fixed_rate'      => 0.15,
							),
						),
					),
					'link'           => array(
						'discount' => array(),
					),
					'invalid_method' => array(
						'discount' => array(
							array(
								'percentage_rate' => 99.9,
							),
						),
					),
				),
				'capital'              => array(
					'has_active_loan' => true,
				),
				'requirements'         => array(
					'currently_due'    => array( 'representative.verification.document' ),
					'current_deadline' => 1781740800,
					'errors'           => array(
						array(
							'code'   => 'verification_document_missing_front',
							'reason' => 'Document front is missing.',
						),
					),
				),
				'account_link'         => 'https://connect.stripe.com/setup/s/acct_native_test',
			)
		);
		update_option( 'wcpay_onboarding_test_mode', 'yes' );
		update_option( 'woocommerce_dismissed_todo_tasks', array( 'old-task' ) );
		update_option( 'woocommerce_deleted_todo_tasks', array( 'deleted-task' ) );
		update_option( 'woocommerce_remind_me_later_todo_tasks', array( 'later-task' => 1781740800000 ) );
		update_option( 'wcpay_connection_success_modal_dismissed', 'yes' );
		update_option( '_wcpay_feature_dispute_readiness_overview', 'yes' );
		update_option(
			'woocommerce_woocommerce_payments_settings',
			array(
				'upe_enabled_payment_method_ids' => array( 'card', 'link', 'invalid_method' ),
			)
		);
		update_option(
			'wcpay_test_dispute_status_counts_cache',
			array(
				'data'    => array(
					'needs_response'         => 2,
					'warning_needs_response' => 1,
					'under_review'           => 4,
				),
				'fetched' => time(),
				'errored' => false,
			)
		);

		$overview = $this->sut->get_overview();

		$this->assertSame( 'acct_native_test', $overview['account']['id'] );
		$this->assertSame( 'test', $overview['account']['mode'] );
		$this->assertTrue( $overview['account']['connected'] );
		$this->assertTrue( $overview['account']['working'] );
		$this->assertTrue( $overview['account']['can_process_payments'] );
		$this->assertTrue( $overview['account']['details_submitted'] );
		$this->assertTrue( $overview['account']['test_mode'] );
		$this->assertTrue( $overview['account']['test_mode_onboarding'] );
		$this->assertTrue( $overview['account']['test_drive'] );
		$this->assertFalse( $overview['account']['sandbox'] );
		$this->assertFalse( $overview['account']['live'] );
		$this->assertSame( 'restricted_soon', $overview['account_status']['status'] );
		$this->assertSame( 1781740800, $overview['account_status']['current_deadline'] );
		$this->assertFalse( $overview['account_status']['past_due'] );
		$this->assertSame( 'https://connect.stripe.com/setup/s/acct_native_test', $overview['account_status']['account_link'] );
		$this->assertSame( 'verification_document_missing_front', $overview['account_status']['requirements']['errors'][0]['code'] );
		$this->assertTrue( $overview['account_status']['details_submitted'] );
		$this->assertTrue( $overview['account_status']['payments_enabled'] );
		$this->assertTrue( $overview['account_status']['deposits_enabled'] );
		$this->assertTrue( $overview['show_update_details_task'] );
		$this->assertSame( array( 'old-task' ), $overview['overview_tasks_visibility']['dismissed_todo_tasks'] );
		$this->assertSame( array( 'deleted-task' ), $overview['overview_tasks_visibility']['deleted_todo_tasks'] );
		$this->assertSame( array( 'later-task' => 1781740800000 ), $overview['overview_tasks_visibility']['remind_me_later_todo_tasks'] );
		$this->assertTrue( $overview['is_connection_success_modal_dismissed'] );
		$this->assertSame( 3, $overview['disputes_awaiting_response_count'] );
		$this->assertSame( 'Restricted soon', $overview['account_details']['account_status']['text'] );
		$this->assertSame( 'Payouts enabled', $overview['account_details']['payout_status']['text'] );
		$this->assertSame( 'Action required', $overview['account_details']['banner']['text'] );
		$this->assertSame(
			array(
				array(
					'payment_method' => 'card',
					'fee'            => array(
						'base'     => array(
							'percentage_rate' => 2.9,
							'fixed_rate'      => 0.3,
						),
						'discount' => array(
							array(
								'currency'        => 'usd',
								'percentage_rate' => 1.1,
								'fixed_rate'      => 0.15,
							),
						),
					),
				),
			),
			$overview['account_fees']
		);
		$this->assertTrue( $overview['feature_flags']['dispute_readiness_overview'] );
		$this->assertTrue( $overview['account_loans']['has_active_loan'] );
		$this->assertSame( '', $overview['wpcom_reconnect_url'] );
		$this->assertStringContainsString( 'path=/woopayments/overview', $overview['urls']['overview_page'] );
		$this->assertStringContainsString( 'path=/woopayments/onboarding', $overview['urls']['setup'] );
		$this->assertStringContainsString( 'path=/woopayments/settings', $overview['urls']['settings'] );
		$this->assertStringContainsString( 'path=/woopayments/onboarding', $overview['urls']['onboarding'] );
		$this->assertArrayNotHasKey( 'test_publishable_key', $overview['account'] );
		$this->assertArrayNotHasKey( 'live_publishable_key', $overview['account'] );
		$this->assertArrayNotHasKey( 'test_publishable_key', $overview['account_status'] );
		$this->assertArrayNotHasKey( 'live_publishable_key', $overview['account_status'] );
	}

	/**
	 * @testdox Should return a fail-closed Overview projection when no account is connected.
	 */
	public function test_get_overview_returns_fail_closed_projection_without_account(): void {
		$this->cache_account_data( array() );

		$overview = $this->sut->get_overview();

		$this->assertSame( '', $overview['account']['id'] );
		$this->assertFalse( $overview['account']['connected'] );
		$this->assertFalse( $overview['account']['working'] );
		$this->assertFalse( $overview['account']['can_process_payments'] );
		$this->assertFalse( $overview['account']['details_submitted'] );
		$this->assertSame( 'not_connected', $overview['account_status']['status'] );
		$this->assertNull( $overview['account_status']['current_deadline'] );
		$this->assertFalse( $overview['account_status']['past_due'] );
		$this->assertSame( '', $overview['account_status']['account_link'] );
		$this->assertSame( array(), $overview['account_status']['requirements']['errors'] );
		$this->assertFalse( $overview['show_update_details_task'] );
		$this->assertNull( $overview['disputes_awaiting_response_count'] );
		$this->assertNull( $overview['account_details'] );
		$this->assertSame( array(), $overview['account_fees'] );
		$this->assertSame( array( 'dispute_readiness_overview' => true ), $overview['feature_flags'] );
		$this->assertSame( array( 'has_active_loan' => false ), $overview['account_loans'] );
		$this->assertSame( '', $overview['wpcom_reconnect_url'] );
	}

	/**
	 * @testdox Should expose unfinished setup as restricted account status with the finish-setup task.
	 */
	public function test_get_overview_marks_unfinished_setup_as_restricted_with_finish_setup_task(): void {
		$this->cache_account_data(
			array(
				'account_id'        => 'acct_incomplete',
				'details_submitted' => false,
				'payments_enabled'  => false,
				'is_live'           => true,
			)
		);

		$overview = $this->sut->get_overview();

		$this->assertSame( 'restricted', $overview['account_status']['status'] );
		$this->assertFalse( $overview['account_status']['details_submitted'] );
		$this->assertFalse( $overview['account']['details_submitted'] );
		$this->assertTrue( $overview['show_update_details_task'] );
	}

	/**
	 * @testdox Should map cached account data to reference-compatible account statuses.
	 *
	 * @dataProvider account_status_data
	 *
	 * @param array  $account_data    Cached account data overrides.
	 * @param string $expected_status Expected account status.
	 */
	public function test_get_overview_maps_cached_account_statuses( array $account_data, string $expected_status ): void {
		$this->cache_account_data(
			array_merge(
				array(
					'account_id'        => 'acct_status_test',
					'details_submitted' => true,
					'payments_enabled'  => true,
					'is_live'           => true,
				),
				$account_data
			)
		);

		$overview = $this->sut->get_overview();

		$this->assertSame( $expected_status, $overview['account_status']['status'] );
	}

	/**
	 * Data provider for cached account status mapping.
	 *
	 * @return array<string,array{array<string,mixed>,string}>
	 */
	public function account_status_data(): array {
		return array(
			'explicit status wins'                 => array(
				array(
					'status'       => 'under_review',
					'requirements' => array(
						'disabled_reason' => 'requirements.fields_needed',
					),
				),
				'under_review',
			),
			'pending verification disabled reason' => array(
				array(
					'requirements' => array(
						'disabled_reason' => 'requirements.pending_verification',
					),
				),
				'pending_verification',
			),
			'fields needed disabled reason'        => array(
				array(
					'requirements' => array(
						'disabled_reason' => 'requirements.fields_needed',
					),
				),
				'restricted_partially',
			),
			'rejected disabled reason'             => array(
				array(
					'requirements' => array(
						'disabled_reason' => 'rejected.fraud',
					),
				),
				'rejected.fraud',
			),
			'other disabled reason'                => array(
				array(
					'requirements' => array(
						'disabled_reason' => 'listed',
					),
				),
				'restricted',
			),
			'past due requirements'                => array(
				array(
					'requirements' => array(
						'past_due' => array( 'business_profile.url' ),
					),
				),
				'restricted',
			),
			'currently due with deadline'          => array(
				array(
					'requirements' => array(
						'currently_due'    => array( 'representative.verification.document' ),
						'current_deadline' => 1781740800,
					),
				),
				'restricted_soon',
			),
			'eventually due without deadline'      => array(
				array(
					'requirements' => array(
						'eventually_due' => array( 'company.tax_id' ),
					),
				),
				'enabled',
			),
			'complete connected account'           => array(
				array(),
				'complete',
			),
		);
	}

	/**
	 * Cache account data in the preserved WooPayments account cache wrapper.
	 *
	 * @param array<string,mixed> $account_data Account data.
	 */
	private function cache_account_data( array $account_data ): void {
		update_option(
			'wcpay_account_data',
			array(
				'data'               => $account_data,
				'fetched'            => time(),
				'errored'            => false,
				'consecutive_errors' => 0,
			)
		);
	}

	/**
	 * Create the native account service dependency.
	 *
	 * @return WooPaymentsAccountService
	 */
	private function create_account_service(): WooPaymentsAccountService {
		$account_service = new WooPaymentsAccountService();
		$account_service->init( wc_get_container()->get( LegacyProxy::class ) );

		return $account_service;
	}
}
