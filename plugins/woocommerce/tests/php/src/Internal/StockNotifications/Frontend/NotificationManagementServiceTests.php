<?php

declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Frontend;

use Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailManager;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Factory;
use Automattic\WooCommerce\Internal\StockNotifications\Frontend\NotificationManagementService;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use WC_Helper_Product;

/**
 * Tests for NotificationManagementService resend handler.
 */
class NotificationManagementServiceTests extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var NotificationManagementService
	 */
	private $sut;

	/**
	 * Mock email manager.
	 *
	 * @var EmailManager&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $email_manager;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		// Intercept redirects so headers aren't emitted, and throw so the trailing `exit;`
		// in production code never runs during the test.
		add_filter( 'wp_redirect', array( $this, 'intercept_redirect' ) );

		// Clear any notices left by earlier tests in the suite so the silent-drop
		// assertion in test_resend_request_rejects_invalid_nonce is meaningful.
		wc_clear_notices();

		$this->email_manager = $this->createMock( EmailManager::class );
		$this->sut           = new NotificationManagementService();
		$this->sut->init( $this->email_manager );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_filter( 'wp_redirect', array( $this, 'intercept_redirect' ) );

		unset( $_GET['_wpnonce'], $_GET[ NotificationManagementService::RESEND_QUERY_ARG ] );

		// DELETE rather than TRUNCATE so the outer WP_UnitTestCase transaction can still roll back.
		// TRUNCATE is DDL and implicitly commits the surrounding transaction.
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_stock_notificationmeta" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_stock_notifications" );

		parent::tearDown();
	}

	/**
	 * `wp_redirect` filter callback that throws so the SUT's trailing `exit;`
	 * never executes and the test can still assert state after the method.
	 *
	 * @param string $location Redirect target.
	 * @return never
	 * @throws \RuntimeException Always.
	 */
	public function intercept_redirect( string $location ): void {
		throw new \RuntimeException( 'wp_redirect intercepted: ' . esc_url_raw( $location ) );
	}

	/**
	 * @testdox Should generate a nonce-protected resend URL including the notification id.
	 */
	public function test_get_resend_verification_email_url_contains_notification_id_and_nonce() {
		$notification = $this->build_pending_notification();

		$url = $this->sut->get_resend_verification_email_url( $notification );

		$this->assertStringContainsString( NotificationManagementService::RESEND_QUERY_ARG . '=' . $notification->get_id(), $url );
		$this->assertStringContainsString( '_wpnonce=', $url );
	}

	/**
	 * @testdox Should send the verify email and persist last-sent timestamp on a valid resend request.
	 */
	public function test_resend_request_sends_verify_email_and_persists_timestamp() {
		$notification = $this->build_pending_notification();

		$this->seed_resend_request( $notification->get_id() );

		$this->email_manager
			->expects( $this->once() )
			->method( 'send_verify_email' )
			->with(
				$this->callback(
					static function ( $arg ) use ( $notification ) {
						return $arg instanceof Notification && $arg->get_id() === $notification->get_id();
					}
				)
			);

		// The SUT redirects (and would `exit`) at the end of the happy path; the redirect filter
		// throws so we can assert side effects instead of actually halting.
		try {
			$this->sut->maybe_process_resend_request();
			$this->fail( 'Expected redirect to be intercepted via exception.' );
		} catch ( \RuntimeException $e ) {
			$this->assertStringContainsString( 'wp_redirect intercepted', $e->getMessage() );
		}

		$reloaded = Factory::get_notification( $notification->get_id() );
		$this->assertNotEmpty( $reloaded->get_meta( NotificationManagementService::LAST_VERIFY_EMAIL_SENT_META ) );
	}

	/**
	 * @testdox Should persist the rate-limit timestamp before dispatching the email (TOCTOU guard).
	 */
	public function test_resend_request_writes_rate_limit_timestamp_before_sending_email() {
		$notification    = $this->build_pending_notification();
		$notification_id = $notification->get_id();

		$this->seed_resend_request( $notification_id );

		$this->email_manager
			->expects( $this->once() )
			->method( 'send_verify_email' )
			->willReturnCallback(
				function () use ( $notification_id ) {
					// At the moment the email would be dispatched, the rate-limit timestamp must
					// already be persisted so a concurrent request can't pass the rate-limit check.
					$reloaded = Factory::get_notification( $notification_id );
					$this->assertNotEmpty( $reloaded->get_meta( NotificationManagementService::LAST_VERIFY_EMAIL_SENT_META ) );
				}
			);

		try {
			$this->sut->maybe_process_resend_request();
			$this->fail( 'Expected redirect to be intercepted via exception.' );
		} catch ( \RuntimeException $e ) {
			$this->assertStringContainsString( 'wp_redirect intercepted', $e->getMessage() );
		}
	}

	/**
	 * @testdox Should not send a verify email when the most recent send is within the rate-limit window.
	 */
	public function test_resend_request_rate_limited() {
		$notification = $this->build_pending_notification();
		$notification->update_meta_data( NotificationManagementService::LAST_VERIFY_EMAIL_SENT_META, time() );
		$notification->save();

		$this->seed_resend_request( $notification->get_id() );

		$this->email_manager
			->expects( $this->never() )
			->method( 'send_verify_email' );

		$this->expectException( \RuntimeException::class );
		$this->sut->maybe_process_resend_request();
	}

	/**
	 * @testdox Should not send a verify email when the notification is already verified.
	 */
	public function test_resend_request_rejected_if_already_verified() {
		$product      = WC_Helper_Product::create_simple_product();
		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->set_user_email( 'customer@example.com' );
		$notification->save();

		$this->seed_resend_request( $notification->get_id() );

		$this->email_manager
			->expects( $this->never() )
			->method( 'send_verify_email' );

		$this->expectException( \RuntimeException::class );
		$this->sut->maybe_process_resend_request();
	}

	/**
	 * @testdox Should silently drop the request when the nonce is invalid — no email, no redirect, no meta write, no notice.
	 */
	public function test_resend_request_rejects_invalid_nonce() {
		$notification = $this->build_pending_notification();

		$_GET[ NotificationManagementService::RESEND_QUERY_ARG ] = (string) $notification->get_id();
		$_GET['_wpnonce']                                        = 'not-a-real-nonce';

		$this->email_manager
			->expects( $this->never() )
			->method( 'send_verify_email' );

		// Invalid nonce must return before reaching the redirect path.
		$this->sut->maybe_process_resend_request();

		// Silent drop: no rate-limit meta written, no notice queued, no session cookie primed.
		$reloaded = Factory::get_notification( $notification->get_id() );
		$this->assertSame( '', (string) $reloaded->get_meta( NotificationManagementService::LAST_VERIFY_EMAIL_SENT_META ) );
		$this->assertEmpty( wc_get_notices() );
	}

	/**
	 * @testdox A nonce minted for notification A must not validate for notification B.
	 */
	public function test_resend_request_rejects_cross_notification_nonce_replay() {
		$victim   = $this->build_pending_notification();
		$attacker = $this->build_pending_notification();

		// Attacker mints a valid nonce for their own notification and replays it on the victim's id.
		$_GET[ NotificationManagementService::RESEND_QUERY_ARG ] = (string) $victim->get_id();
		$_GET['_wpnonce']                                        = wp_create_nonce(
			NotificationManagementService::RESEND_NONCE_ACTION . '_' . $attacker->get_id()
		);

		$this->email_manager
			->expects( $this->never() )
			->method( 'send_verify_email' );

		$this->sut->maybe_process_resend_request();
	}

	/**
	 * @testdox Should be a no-op when the request does not carry the resend query arg.
	 */
	public function test_resend_request_noop_without_query_arg() {
		$this->email_manager
			->expects( $this->never() )
			->method( 'send_verify_email' );

		$this->sut->maybe_process_resend_request();
	}

	/**
	 * Build a pending notification for a fresh simple product.
	 *
	 * @return Notification
	 */
	private function build_pending_notification(): Notification {
		$product = WC_Helper_Product::create_simple_product();

		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_status( NotificationStatus::PENDING );
		$notification->set_user_email( 'customer@example.com' );
		$notification->save();

		return $notification;
	}

	/**
	 * Populate superglobals as if a valid resend request reached the site.
	 *
	 * @param int $notification_id Notification id.
	 */
	private function seed_resend_request( int $notification_id ): void {
		$_GET[ NotificationManagementService::RESEND_QUERY_ARG ] = (string) $notification_id;
		$_GET['_wpnonce']                                        = wp_create_nonce( NotificationManagementService::RESEND_NONCE_ACTION . '_' . $notification_id );
	}
}
