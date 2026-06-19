<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAccountService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsDisputeReadinessService;
use WC_Unit_Test_Case;

/**
 * Tests for the native WooPayments dispute readiness service.
 */
class WooPaymentsDisputeReadinessServiceTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WooPaymentsDisputeReadinessService
	 */
	private $sut;

	/**
	 * Mock account service.
	 *
	 * @var WooPaymentsAccountService
	 */
	private $account_service;

	/**
	 * Mutable preserved account data fixture.
	 *
	 * @var array<string,mixed>
	 */
	private array $account_data = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( 'blogname', 'Native Store' );
		delete_option( 'wcpay_dispute_readiness_card_dismissed' );
		delete_option( 'wcpay_dispute_readiness_statement_descriptor_confirmed' );
		delete_option( 'woocommerce_refund_returns_page_id' );
		delete_option( 'woocommerce_terms_page_id' );

		$this->account_service = $this->create_account_service(
			array(
				'statement_descriptor' => 'NATIVE STORE',
				'business_profile'     => array(
					'support_email' => 'support@example.test',
				),
			)
		);
		$this->sut             = new WooPaymentsDisputeReadinessService();
		$this->sut->init( $this->account_service );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		delete_option( 'wcpay_dispute_readiness_card_dismissed' );
		delete_option( 'wcpay_dispute_readiness_statement_descriptor_confirmed' );
		delete_option( 'woocommerce_refund_returns_page_id' );
		delete_option( 'woocommerce_terms_page_id' );

		parent::tearDown();
	}

	/**
	 * @testdox Should return the reference disabled overview payload.
	 */
	public function test_get_disabled_overview_payload_returns_reference_shape(): void {
		$payload = $this->sut->get_disabled_overview_payload();

		$this->assertSame(
			array(
				'overview' => array(
					'enabled'             => false,
					'hidden'              => true,
					'score'               => 0,
					'total'               => 0,
					'state'               => 'incomplete',
					'isDismissed'         => true,
					'completeSignalIds'   => array(),
					'incompleteSignalIds' => array(),
					'signals'             => array(),
					'dismissal'           => array(
						'isDismissed'       => true,
						'isStoredDismissal' => false,
						'reappearReason'    => 'feature_disabled',
					),
				),
			),
			$payload,
			'Disabled dispute readiness should preserve the legacy payload shape.'
		);
	}

	/**
	 * @testdox Should build complete enabled overview signals from Core pages and preserved account data.
	 */
	public function test_get_overview_payload_marks_all_signals_complete(): void {
		update_option( 'woocommerce_refund_returns_page_id', $this->create_published_page( 'Refunds are available within 30 days.' ) );
		update_option( 'woocommerce_terms_page_id', $this->create_published_page( 'Terms and conditions for checkout.' ) );

		$payload  = $this->sut->get_overview_payload();
		$overview = $payload['overview'];

		$this->assertTrue( $overview['enabled'] );
		$this->assertFalse( $overview['hidden'] );
		$this->assertSame( 4, $overview['score'] );
		$this->assertSame( 4, $overview['total'] );
		$this->assertSame( 'complete', $overview['state'] );
		$this->assertFalse( $overview['isDismissed'] );
		$this->assertSame(
			array( 'statement_descriptor', 'refund_policy', 'support_contact', 'terms_and_conditions' ),
			$overview['completeSignalIds']
		);
		$this->assertSame( array(), $overview['incompleteSignalIds'] );
		$this->assertSame( 'Recognizable statement descriptor', $overview['signals'][0]['label'] );
		$this->assertStringContainsString( 'page=wc-settings&tab=checkout&path=/woopayments/settings', rawurldecode( $overview['signals'][0]['actionUrl'] ) );
		$this->assertStringContainsString( 'post.php?post=', $overview['signals'][1]['actionUrl'] );
		$this->assertStringContainsString( 'page=wc-settings&tab=checkout&path=/woopayments/settings', rawurldecode( $overview['signals'][2]['actionUrl'] ) );
		$this->assertStringContainsString( 'page=wc-settings&tab=advanced', $overview['signals'][3]['actionUrl'] );
	}

	/**
	 * @testdox Should report incomplete signals for missing pages and generic account data.
	 */
	public function test_get_overview_payload_reports_incomplete_signals(): void {
		$this->set_account_data(
			array(
				'statement_descriptor' => 'WooPayments',
				'business_profile'     => array(),
			)
		);

		$payload  = $this->sut->get_overview_payload();
		$overview = $payload['overview'];

		$this->assertSame( 0, $overview['score'] );
		$this->assertSame( 'incomplete', $overview['state'] );
		$this->assertSame(
			array( 'statement_descriptor', 'refund_policy', 'support_contact', 'terms_and_conditions' ),
			$overview['incompleteSignalIds']
		);
		$this->assertSame( 'needs_review', $overview['signals'][0]['reason'] );
		$this->assertSame( 'WooPayments', $overview['signals'][0]['reviewPrompt']['currentDescriptor'] );
	}

	/**
	 * @testdox Should persist dismissal metadata without deleting merchant data.
	 */
	public function test_dismiss_overview_card_persists_metadata(): void {
		update_option( 'woocommerce_woocommerce_payments_settings', array( 'enabled' => 'yes' ) );
		$this->set_account_data(
			array(
				'statement_descriptor' => 'WooPayments',
				'business_profile'     => array(),
			)
		);

		$payload = $this->sut->dismiss_overview_card();
		$stored  = get_option( 'wcpay_dispute_readiness_card_dismissed' );

		$this->assertTrue( $payload['overview']['isDismissed'] );
		$this->assertIsArray( $stored );
		$this->assertTrue( $stored['dismissed'] );
		$this->assertSame( 0, $stored['score_at_dismissal'] );
		$this->assertSame( 4, $stored['total_at_dismissal'] );
		$this->assertSame( $payload['overview']['incompleteSignalIds'], $stored['incomplete_signal_ids'] );
		$this->assertSame(
			array( 'enabled' => 'yes' ),
			get_option( 'woocommerce_woocommerce_payments_settings' ),
			'Dismissing the card should not delete merchant gateway data.'
		);
	}

	/**
	 * @testdox Should make a stored dismissal reappear when the incomplete signal set changes.
	 */
	public function test_dismissal_reappears_when_incomplete_signal_set_changes(): void {
		update_option( 'woocommerce_refund_returns_page_id', $this->create_published_page( 'Refunds are available within 30 days.' ) );
		update_option( 'woocommerce_terms_page_id', $this->create_published_page( 'Terms and conditions for checkout.' ) );
		$this->set_account_data(
			array(
				'statement_descriptor' => 'WooPayments',
				'business_profile'     => array(),
			)
		);
		$this->sut->dismiss_overview_card();

		delete_option( 'woocommerce_terms_page_id' );
		$this->set_account_data(
			array(
				'statement_descriptor' => 'Native Store',
				'business_profile'     => array(),
			)
		);

		$payload = $this->sut->get_overview_payload();

		$this->assertFalse( $payload['overview']['isDismissed'] );
		$this->assertTrue( $payload['overview']['dismissal']['isStoredDismissal'] );
		$this->assertSame( 'incomplete_signals_changed', $payload['overview']['dismissal']['reappearReason'] );
		$this->assertSame( array( 'support_contact', 'terms_and_conditions' ), $payload['overview']['incompleteSignalIds'] );
	}

	/**
	 * @testdox Should confirm only the current normalized statement descriptor.
	 */
	public function test_confirm_statement_descriptor_is_tied_to_current_normalized_descriptor(): void {
		$this->set_account_data(
			array(
				'statement_descriptor' => 'WooPayments',
				'business_profile'     => array(),
			)
		);

		$payload = $this->sut->confirm_statement_descriptor();
		$stored  = get_option( 'wcpay_dispute_readiness_statement_descriptor_confirmed' );

		$this->assertIsArray( $stored );
		$this->assertTrue( $stored['confirmed'] );
		$this->assertSame( 'WooPayments', $stored['descriptor'] );
		$this->assertSame( 'woopayments', $stored['normalized_descriptor'] );
		$this->assertContains( 'statement_descriptor', $payload['overview']['completeSignalIds'] );

		$this->set_account_data(
			array(
				'statement_descriptor' => 'WooPayments Store',
				'business_profile'     => array(),
			)
		);

		$payload = $this->sut->get_overview_payload();

		$this->assertContains( 'statement_descriptor', $payload['overview']['incompleteSignalIds'] );
		$this->assertSame( 'needs_review', $payload['overview']['signals'][0]['reason'] );
	}

	/**
	 * Create a mock account service.
	 *
	 * @param array<string,mixed> $account_data Preserved account data.
	 * @return WooPaymentsAccountService
	 */
	private function create_account_service( array $account_data ): WooPaymentsAccountService {
		$account_service = $this->getMockBuilder( WooPaymentsAccountService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_preserved_account_data_snapshot', 'get_cached_account_data', 'refresh_account_data' ) )
			->getMock();
		$account_service->method( 'get_preserved_account_data_snapshot' )->willReturnCallback(
			function () use ( &$account_data ): array {
				return $account_data;
			}
		);
		$account_service->expects( $this->never() )->method( 'get_cached_account_data' );
		$account_service->expects( $this->never() )->method( 'refresh_account_data' );

		$this->account_data = &$account_data;

		return $account_service;
	}

	/**
	 * Replace preserved account data returned by the mock account service.
	 *
	 * @param array<string,mixed> $account_data Preserved account data.
	 */
	private function set_account_data( array $account_data ): void {
		$this->account_data = $account_data;
	}

	/**
	 * Create a published page with content.
	 *
	 * @param string $content Page content.
	 * @return int
	 */
	private function create_published_page( string $content ): int {
		return self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => $content,
			)
		);
	}
}
