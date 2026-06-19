<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAccountService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsPmPromotionsService;
use WC_Unit_Test_Case;

/**
 * Tests for the WooPaymentsPmPromotionsService class.
 */
class WooPaymentsPmPromotionsServiceTest extends WC_Unit_Test_Case {

	/**
	 * System under test.
	 *
	 * @var WooPaymentsPmPromotionsService
	 */
	private WooPaymentsPmPromotionsService $sut;

	/**
	 * Recording native API client.
	 *
	 * @var RecordingPmPromotionsApiClient
	 */
	private RecordingPmPromotionsApiClient $api_client;

	/**
	 * Recording native account service.
	 *
	 * @var RecordingPmPromotionsAccountService
	 */
	private RecordingPmPromotionsAccountService $account_service;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->api_client      = new RecordingPmPromotionsApiClient();
		$this->account_service = new RecordingPmPromotionsAccountService();
		$this->sut             = new WooPaymentsPmPromotionsService();
		$this->sut->init( $this->api_client, $this->account_service );

		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		wp_set_current_user( 0 );
		delete_option( 'woocommerce_woocommerce_payments_settings' );
		delete_option( '_wcpay_pm_promotion_dismissals' );
		delete_transient( WooPaymentsPmPromotionsService::PROMOTIONS_CACHE_KEY );

		parent::tearDown();
	}

	/**
	 * @testdox Should filter invalid, enabled, dismissed, discounted, and duplicate promotion groups.
	 */
	public function test_get_visible_promotions_filters_invalid_enabled_dismissed_discounted_and_duplicate_promo_ids(): void {
			update_option(
				'woocommerce_woocommerce_payments_settings',
				array(
					'upe_available_payment_methods'  => array( 'card', 'affirm', 'klarna', 'afterpay_clearpay' ),
					'upe_enabled_payment_method_ids' => array( 'card' ),
				)
			);
		update_option(
			'_wcpay_pm_promotion_dismissals',
			array(
				'klarna-dismissed__spotlight' => time() - HOUR_IN_SECONDS,
			)
		);
		$this->account_service->cached_account_data = array(
			'fees' => array(
				'affirm' => array(
					'discount' => array(
						array( 'discount' => array( 'percentage' => 50 ) ),
					),
				),
			),
		);
		$this->api_client->promotions_response       = array(
			$this->promotion_fixture( 'card-promo__spotlight', 'card-promo', 'card', 'spotlight' ),
			$this->promotion_fixture( 'unknown-promo__spotlight', 'unknown-promo', 'unknown_method', 'spotlight' ),
			$this->promotion_fixture( 'klarna-dismissed__spotlight', 'klarna-dismissed', 'klarna', 'spotlight' ),
			$this->promotion_fixture( 'affirm-discount__spotlight', 'affirm-discount', 'affirm', 'spotlight' ),
			$this->promotion_fixture( 'afterpay-first__spotlight', 'afterpay-first', 'afterpay_clearpay', 'spotlight' ),
			$this->promotion_fixture( 'afterpay-second__badge', 'afterpay-second', 'afterpay_clearpay', 'badge' ),
			$this->promotion_fixture( 'afterpay-first__badge', 'afterpay-first', 'afterpay_clearpay', 'badge' ),
		);

		$promotions = $this->sut->get_visible_promotions();

		$this->assertIsArray( $promotions );
		$this->assertSame(
			array( 'afterpay-first__spotlight', 'afterpay-first__badge' ),
			array_column( $promotions, 'id' )
		);
	}

	/**
	 * @testdox Should normalize titles, CTA labels, terms labels, badge type, URLs, and light HTML.
	 */
	public function test_get_visible_promotions_normalizes_titles_cta_terms_badge_type_and_html(): void {
		update_option(
			'woocommerce_woocommerce_payments_settings',
			array(
				'upe_available_payment_methods'  => array( 'card', 'klarna' ),
				'upe_enabled_payment_method_ids' => array( 'card' ),
			)
		);
		$this->api_client->promotions_response = array(
			array(
				'id'             => 'klarna-promo__spotlight',
				'promo_id'       => 'klarna-promo',
				'payment_method' => 'klarna',
				'type'           => 'spotlight',
				'title'          => 'Activate <Klarna>',
				'description'    => '<strong>Flexible payments</strong><script>bad()</script>',
				'tc_url'         => 'https://example.com/terms',
				'image'          => 'https://example.com/image.png',
				'footnote'       => '<em>Limited time</em><script>bad()</script>',
				'badge_type'     => 'not-real',
			),
		);

		$promotions = $this->sut->get_visible_promotions();

		$this->assertIsArray( $promotions );
		$this->assertSame( 'Klarna', $promotions[0]['payment_method_title'] );
		$this->assertSame( 'Enable Klarna', $promotions[0]['cta_label'] );
		$this->assertSame( 'See terms', $promotions[0]['tc_label'] );
		$this->assertSame( 'success', $promotions[0]['badge_type'] );
		$this->assertSame( 'https://example.com/terms', $promotions[0]['tc_url'] );
		$this->assertSame( 'https://example.com/image.png', $promotions[0]['image'] );
		$this->assertStringContainsString( '<strong>Flexible payments</strong>', $promotions[0]['description'] );
		$this->assertStringNotContainsString( '<script>', $promotions[0]['description'] );
		$this->assertStringContainsString( '<em>Limited time</em>', $promotions[0]['footnote'] );
		$this->assertStringNotContainsString( '<script>', $promotions[0]['footnote'] );
	}

	/**
	 * @testdox Should store dismissals and hide dismissed promotions.
	 */
	public function test_dismiss_promotion_stores_timestamp_and_hides_promotion(): void {
		update_option(
			'woocommerce_woocommerce_payments_settings',
			array(
				'upe_available_payment_methods'  => array( 'card', 'klarna' ),
				'upe_enabled_payment_method_ids' => array( 'card' ),
			)
		);
		$this->api_client->promotions_response = array(
			$this->promotion_fixture( 'klarna-promo__spotlight', 'klarna-promo', 'klarna', 'spotlight' ),
		);
		$before                           = time();

		$result     = $this->sut->dismiss_promotion( 'klarna-promo__spotlight' );
		$dismissals = get_option( '_wcpay_pm_promotion_dismissals' );

		$this->assertTrue( $result );
		$this->assertIsArray( $dismissals );
		$this->assertGreaterThanOrEqual( $before, $dismissals['klarna-promo__spotlight'] );
		$this->assertLessThanOrEqual( time(), $dismissals['klarna-promo__spotlight'] );
		$this->assertNull( $this->sut->get_visible_promotions() );
	}

	/**
	 * @testdox Should activate through the platform, dismiss, enable the method, and clear caches.
	 */
	public function test_activate_promotion_calls_platform_marks_dismissed_enables_method_and_clears_cache(): void {
			update_option(
				'woocommerce_woocommerce_payments_settings',
				array(
					'upe_available_payment_methods'  => array( 'card', 'klarna' ),
					'upe_enabled_payment_method_ids' => array( 'card' ),
				)
			);
		set_transient( WooPaymentsPmPromotionsService::PROMOTIONS_CACHE_KEY, array( 'stale' => true ), DAY_IN_SECONDS );
		$this->api_client->promotions_response = array(
			$this->promotion_fixture( 'klarna-promo__spotlight', 'klarna-promo', 'klarna', 'spotlight' ),
		);

		$result     = $this->sut->activate_promotion( 'klarna-promo__spotlight' );
		$settings   = get_option( 'woocommerce_woocommerce_payments_settings' );
		$dismissals = get_option( '_wcpay_pm_promotion_dismissals' );

		$this->assertTrue( $result );
		$this->assertSame( array( 'klarna-promo__spotlight' ), $this->api_client->activated_promotion_ids );
		$this->assertIsArray( $dismissals );
		$this->assertArrayHasKey( 'klarna-promo__spotlight', $dismissals );
		$this->assertIsArray( $settings );
		$this->assertSame( array( 'card', 'klarna' ), $settings['upe_enabled_payment_method_ids'] );
		$this->assertFalse( get_transient( WooPaymentsPmPromotionsService::PROMOTIONS_CACHE_KEY ) );
		$this->assertSame( 1, $this->account_service->clear_cache_calls );
	}

	/**
	 * @testdox Should activate settings-save promotions before the method is enabled.
	 */
	public function test_maybe_activate_promotion_for_payment_method_runs_before_settings_enable(): void {
			update_option(
				'woocommerce_woocommerce_payments_settings',
				array(
					'upe_available_payment_methods'  => array( 'card', 'klarna' ),
					'upe_enabled_payment_method_ids' => array( 'card' ),
				)
			);
		$this->api_client->promotions_response = array(
			$this->promotion_fixture( 'klarna-promo__spotlight', 'klarna-promo', 'klarna', 'spotlight' ),
		);

		$result     = $this->sut->maybe_activate_promotion_for_payment_method( 'klarna' );
		$settings   = get_option( 'woocommerce_woocommerce_payments_settings' );
		$dismissals = get_option( '_wcpay_pm_promotion_dismissals', array() );

		$this->assertTrue( $result );
		$this->assertSame( array( 'klarna-promo__spotlight' ), $this->api_client->activated_promotion_ids );
		$this->assertIsArray( $settings );
		$this->assertSame( array( 'card' ), $settings['upe_enabled_payment_method_ids'] );
		$this->assertSame( array(), $dismissals );
		$this->assertSame( 1, $this->account_service->clear_cache_calls );
	}

	/**
	 * @testdox Should hide and refuse activation for promotions unavailable to the account.
	 */
	public function test_promotions_for_unavailable_payment_methods_are_hidden_and_not_activated(): void {
		update_option(
			'woocommerce_woocommerce_payments_settings',
			array(
				'upe_available_payment_methods'  => array( 'card' ),
				'upe_enabled_payment_method_ids' => array( 'card' ),
			)
		);
		$this->api_client->promotions_response = array(
			$this->promotion_fixture( 'klarna-promo__spotlight', 'klarna-promo', 'klarna', 'spotlight' ),
		);

		$this->assertNull( $this->sut->get_visible_promotions() );
		$this->assertFalse( $this->sut->activate_promotion( 'klarna-promo__spotlight' ) );
		$this->assertSame( array(), $this->api_client->activated_promotion_ids );
	}

	/**
	 * @testdox Should not expose promotions to users without manage_woocommerce.
	 */
	public function test_get_visible_promotions_returns_null_without_manage_woocommerce(): void {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'subscriber' ) ) );
		$this->api_client->promotions_response = array(
			$this->promotion_fixture( 'klarna-promo__spotlight', 'klarna-promo', 'klarna', 'spotlight' ),
		);

		$this->assertNull( $this->sut->get_visible_promotions() );
		$this->assertSame( 0, $this->api_client->get_pm_promotions_calls );
	}

	/**
	 * Build a minimal valid promotion fixture.
	 *
	 * @param string $id                Promotion ID.
	 * @param string $promo_id          Promotion group ID.
	 * @param string $payment_method_id Payment method ID.
	 * @param string $type              Promotion type.
	 * @return array<string,mixed>
	 */
	private function promotion_fixture( string $id, string $promo_id, string $payment_method_id, string $type ): array {
		return array(
			'id'             => $id,
			'promo_id'       => $promo_id,
			'payment_method' => $payment_method_id,
			'type'           => $type,
			'title'          => 'Activate ' . $payment_method_id,
			'description'    => 'Offer flexible payments.',
			'tc_url'         => 'https://example.com/terms',
		);
	}
}

/**
 * Recording API client for promotion service tests.
 */
class RecordingPmPromotionsApiClient extends WooPaymentsApiClient {

	/**
	 * Promotions response.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public array $promotions_response = array();

	/**
	 * Activated promotion IDs.
	 *
	 * @var string[]
	 */
	public array $activated_promotion_ids = array();

	/**
	 * Number of promotion fetches.
	 *
	 * @var int
	 */
	public int $get_pm_promotions_calls = 0;

	/**
	 * Retrieve PM promotions.
	 *
	 * @param array<string,mixed> $store_context Store context.
	 * @return array<int,array<string,mixed>>
	 */
	public function get_pm_promotions( array $store_context ): array {
		++$this->get_pm_promotions_calls;

		return $this->promotions_response;
	}

	/**
	 * Activate a PM promotion.
	 *
	 * @param string $promotion_id Promotion ID.
	 * @return array<string,mixed>
	 */
	public function activate_pm_promotion( string $promotion_id ): array {
		$this->activated_promotion_ids[] = $promotion_id;

		return array( 'success' => true );
	}
}

/**
 * Recording account service for promotion service tests.
 */
class RecordingPmPromotionsAccountService extends WooPaymentsAccountService {

	/**
	 * Cached account data.
	 *
	 * @var array<string,mixed>
	 */
	public array $cached_account_data = array();

	/**
	 * Clear cache calls.
	 *
	 * @var int
	 */
	public int $clear_cache_calls = 0;

	/**
	 * Get cached account data.
	 *
	 * @param bool $force_refresh Whether to force refresh.
	 * @return array<string,mixed>
	 */
	public function get_cached_account_data( bool $force_refresh = false ): array {
		return $this->cached_account_data;
	}

	/**
	 * Clear account cache.
	 *
	 * @return void
	 */
	public function clear_cache(): void {
		++$this->clear_cache_calls;
	}
}
