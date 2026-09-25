<?php

declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Frontend;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Factory;
use Automattic\WooCommerce\Internal\StockNotifications\Frontend\NotificationClaimService;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Tests\Internal\StockNotifications\StockNotificationsFeatureTrait;
use WC_Helper_Product;

/**
 * Tests for NotificationClaimService.
 */
class NotificationClaimServiceTests extends \WC_Unit_Test_Case {

	use StockNotificationsFeatureTrait;

	/**
	 * The System Under Test.
	 *
	 * @var NotificationClaimService
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->enable_stock_notifications_feature();

		$this->sut = new NotificationClaimService();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		// DELETE rather than TRUNCATE so the outer WP_UnitTestCase transaction can still roll back.
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_stock_notificationmeta" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_stock_notifications" );

		$this->restore_stock_notifications_feature_option();
		parent::tearDown();
	}

	/**
	 * @testdox Should link guest sign-ups matching the account email, regardless of its letter case.
	 */
	public function test_claims_matching_guest_signups(): void {
		$customer_id = $this->factory->user->create( array( 'user_email' => 'Shopper@Example.com' ) );

		$mine  = $this->create_notification( 0, 'shopper@example.com' );
		$other = $this->create_notification( 0, 'someone-else@example.com' );

		$claimed = $this->sut->claim_guest_notifications( $customer_id );

		$this->assertSame( 1, $claimed, 'Only the sign-up matching the account email should be claimed' );
		$this->assertSame( $customer_id, $this->reload( $mine )->get_user_id(), 'The matching sign-up should now belong to the account' );
		$this->assertSame( 0, $this->reload( $other )->get_user_id(), "Another customer's sign-up should stay unlinked" );
	}

	/**
	 * @testdox Should leave sign-ups that already belong to an account untouched.
	 */
	public function test_leaves_already_linked_signups_alone(): void {
		$customer_id = $this->factory->user->create( array( 'user_email' => 'shopper@example.com' ) );
		$other_id    = $this->factory->user->create();

		$linked = $this->create_notification( $other_id, 'shopper@example.com' );

		$claimed = $this->sut->claim_guest_notifications( $customer_id );

		$this->assertSame( 0, $claimed, 'A sign-up that already has an owner is not a guest sign-up' );
		$this->assertSame( $other_id, $this->reload( $linked )->get_user_id(), 'The existing owner should be preserved' );
	}

	/**
	 * @testdox Should do nothing when the customer ID does not resolve to a user.
	 */
	public function test_ignores_unknown_customers(): void {
		$notification = $this->create_notification( 0, 'shopper@example.com' );

		$this->assertSame( 0, $this->sut->claim_guest_notifications( 0 ), 'A guest ID claims nothing' );
		$this->assertSame( 0, $this->sut->claim_guest_notifications( 999999 ), 'An unknown user ID claims nothing' );
		$this->assertSame( 0, $this->reload( $notification )->get_user_id(), 'The sign-up should stay unlinked' );
	}

	/**
	 * @testdox Should fire woocommerce_stock_notification_claimed_by_customer for each claimed sign-up.
	 */
	public function test_fires_action_for_each_claimed_signup(): void {
		$customer_id = $this->factory->user->create( array( 'user_email' => 'shopper@example.com' ) );

		$first  = $this->create_notification( 0, 'shopper@example.com' );
		$second = $this->create_notification( 0, 'shopper@example.com' );

		$claimed_ids = array();
		add_action(
			'woocommerce_stock_notification_claimed_by_customer',
			static function ( $notification_id ) use ( &$claimed_ids ) {
				$claimed_ids[] = $notification_id;
			}
		);

		$this->sut->claim_guest_notifications( $customer_id );

		$this->assertEqualSets( array( $first->get_id(), $second->get_id() ), $claimed_ids, 'Every claimed sign-up should be announced' );
	}

	/**
	 * Create a saved stock notification.
	 *
	 * @param int    $user_id    The owner, or 0 for a guest sign-up.
	 * @param string $user_email The sign-up email.
	 * @return Notification
	 */
	private function create_notification( int $user_id, string $user_email ): Notification {
		$product = WC_Helper_Product::create_simple_product();

		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_user_id( $user_id );
		$notification->set_user_email( $user_email );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->save();

		return $notification;
	}

	/**
	 * Read a notification back from the database.
	 *
	 * @param Notification $notification The notification to reload.
	 * @return Notification
	 */
	private function reload( Notification $notification ): Notification {
		return Factory::get_notification( $notification->get_id() );
	}
}
