<?php

declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Emails;

use Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailActionController;
use Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailManager;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationCancellationSource;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\Factory;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use WC_Helper_Product;

/**
 * EmailActionControllerTests tests.
 */
class EmailActionControllerTests extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var EmailActionController
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

		$this->email_manager = $this->createMock( EmailManager::class );
		$this->sut           = new EmailActionController();
		$this->sut->init( $this->email_manager );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_filter( 'wp_redirect', array( $this, 'intercept_redirect' ) );
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
	 * Persist a notification with a single action-key meta entry.
	 *
	 * @param string $status     Initial NotificationStatus value to set on the notification.
	 * @param string $meta_key   Meta key to store the action key under (e.g. 'verification_action_key').
	 * @param string $stored_key Already-formatted key value (caller hashes/timestamps as needed).
	 * @return int Saved notification id.
	 */
	private function arrange_notification( string $status, string $meta_key, string $stored_key ): int {
		$product      = WC_Helper_Product::create_simple_product();
		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_status( $status );
		$notification->set_user_email( 'test@example.com' );
		$notification->update_meta_data( $meta_key, $stored_key );
		return $notification->save();
	}

	/**
	 * @testdox Should set notification status to active when verification key matches.
	 */
	public function test_process_verification_action_sets_status_active() {
		$id = $this->arrange_notification(
			NotificationStatus::PENDING,
			'verification_action_key',
			time() . ':' . wp_fast_hash( 'test' )
		);

		try {
			$this->sut->validate_and_maybe_process_request( $id, 'test', 'verify' );
			$this->fail( 'Expected redirect to be intercepted via exception.' );
		} catch ( \RuntimeException $e ) {
			$this->assertStringContainsString( 'wp_redirect intercepted', $e->getMessage() );
		}

		$this->assertEquals( NotificationStatus::ACTIVE, Factory::get_notification( $id )->get_status() );
	}

	/**
	 * @testdox Should send the verified email after a successful verification.
	 */
	public function test_verified_email_sent_after_successful_verification() {
		$id = $this->arrange_notification(
			NotificationStatus::PENDING,
			'verification_action_key',
			time() . ':' . wp_fast_hash( 'test' )
		);

		$this->email_manager
			->expects( $this->once() )
			->method( 'send_verified_email' )
			->with(
				$this->callback(
					static function ( $arg ) use ( $id ) {
						return $arg instanceof Notification && $arg->get_id() === $id;
					}
				)
			);

		try {
			$this->sut->validate_and_maybe_process_request( $id, 'test', 'verify' );
			$this->fail( 'Expected redirect to be intercepted via exception.' );
		} catch ( \RuntimeException $e ) {
			$this->assertStringContainsString( 'wp_redirect intercepted', $e->getMessage() );
		}
	}

	/**
	 * @testdox Should not send the verified email when the verification key is invalid.
	 */
	public function test_verified_email_not_sent_when_verification_key_invalid() {
		$id = $this->arrange_notification(
			NotificationStatus::PENDING,
			'verification_action_key',
			time() . ':' . wp_fast_hash( 'correct' )
		);

		$this->email_manager
			->expects( $this->never() )
			->method( 'send_verified_email' );

		$this->sut->validate_and_maybe_process_request( $id, 'wrong-key', 'verify' );
	}

	/**
	 * @testdox Should only dispatch the verified email once when the same verification URL is hit repeatedly.
	 */
	public function test_verified_email_sent_only_once_on_repeated_verification_hits() {
		$product      = WC_Helper_Product::create_simple_product();
		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_status( NotificationStatus::PENDING );
		$notification->set_user_email( 'test@example.com' );
		$key = time() . ':' . wp_fast_hash( 'test' );
		$notification->update_meta_data( 'verification_action_key', $key );
		$id = $notification->save();

		$this->email_manager
			->expects( $this->once() )
			->method( 'send_verified_email' );

		// First hit transitions PENDING -> ACTIVE and dispatches the verified email. The
		// trailing redirect throws via the intercept filter so the SUT's `exit;` is skipped.
		try {
			$this->sut->validate_and_maybe_process_request( $id, 'test', 'verify' );
			$this->fail( 'Expected redirect to be intercepted via exception.' );
		} catch ( \RuntimeException $e ) {
			$this->assertStringContainsString( 'wp_redirect intercepted', $e->getMessage() );
		}

		// Second hit (double-click, email prefetch, bot) must short-circuit without re-dispatch.
		$this->sut->validate_and_maybe_process_request( $id, 'test', 'verify' );
	}

	/**
	 * @testdox Should set notification status to cancelled and cancellation source to user on unsubscribe.
	 */
	public function test_process_unsubscribe_action_sets_status_cancelled() {
		$id = $this->arrange_notification(
			NotificationStatus::ACTIVE,
			'unsubscribe_action_key',
			wp_fast_hash( 'test' )
		);

		try {
			$this->sut->validate_and_maybe_process_request( $id, 'test', 'unsubscribe' );
			$this->fail( 'Expected redirect to be intercepted via exception.' );
		} catch ( \RuntimeException $e ) {
			$this->assertStringContainsString( 'wp_redirect intercepted', $e->getMessage() );
		}

		$updated = Factory::get_notification( $id );
		$this->assertEquals( NotificationStatus::CANCELLED, $updated->get_status() );
		$this->assertEquals( NotificationCancellationSource::USER, $updated->get_cancellation_source() );
	}

	/**
	 * A verification request with a key that doesn't match the stored one must
	 * leave the notification untouched.
	 */
	public function test_process_verification_action_with_invalid_key_leaves_status_pending() {
		$id = $this->arrange_notification(
			NotificationStatus::PENDING,
			'verification_action_key',
			time() . ':' . wp_fast_hash( 'real-key' )
		);

		$this->sut->validate_and_maybe_process_request( $id, 'wrong-key', 'verify' );

		$this->assertEquals( NotificationStatus::PENDING, Factory::get_notification( $id )->get_status() );
	}

	/**
	 * An `unsubscribe` action routed against a notification that only has a
	 * verification key must not cancel the notification.
	 */
	public function test_process_unsubscribe_action_with_only_verification_key_does_not_cancel() {
		// Only a verification key is stored — the unsubscribe_action_key meta
		// is deliberately empty to simulate a mis-routed link.
		$id = $this->arrange_notification(
			NotificationStatus::ACTIVE,
			'verification_action_key',
			time() . ':' . wp_fast_hash( 'test' )
		);

		$this->sut->validate_and_maybe_process_request( $id, 'test', 'unsubscribe' );

		$this->assertEquals( NotificationStatus::ACTIVE, Factory::get_notification( $id )->get_status() );
	}

	/**
	 * Calling with a zero/missing notification id must early-return without
	 * error.
	 */
	public function test_process_action_with_missing_notification_id_handles_gracefully() {
		// The guard in validate_and_maybe_process_request short-circuits when
		// the id is 0; no side-effect to assert, so suppress PHPUnit's risky
		// warning without a no-op assertion.
		$this->expectNotToPerformAssertions();

		$this->sut->validate_and_maybe_process_request( 0, 'any-key', 'verify' );
	}

	/**
	 * Unknown action tokens must no-op rather than running either the verify
	 * or unsubscribe code paths.
	 */
	public function test_process_action_with_unknown_token_does_not_mutate_notification() {
		$id = $this->arrange_notification(
			NotificationStatus::PENDING,
			'verification_action_key',
			time() . ':' . wp_fast_hash( 'test' )
		);

		$this->sut->validate_and_maybe_process_request( $id, 'test', 'bogus-action' );

		$this->assertEquals( NotificationStatus::PENDING, Factory::get_notification( $id )->get_status() );
	}

	/**
	 * An empty \$action is a caller-side bug (missing argument) that must
	 * short-circuit before the switch; asserted separately from the
	 * unknown-token branch, which takes the `default:` debug-log path.
	 */
	public function test_process_action_with_empty_action_early_returns() {
		$id = $this->arrange_notification(
			NotificationStatus::PENDING,
			'verification_action_key',
			time() . ':' . wp_fast_hash( 'test' )
		);

		$this->sut->validate_and_maybe_process_request( $id, 'test', '' );

		$this->assertEquals( NotificationStatus::PENDING, Factory::get_notification( $id )->get_status() );
	}
}
