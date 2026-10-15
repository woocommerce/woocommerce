<?php

declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Emails;

use Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailManager;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\Frontend\MyAccountEndpoint;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Tests\Internal\StockNotifications\StockNotificationsFeatureTrait;
use WC_Helper_Product;

/**
 * Tests for EmailManager wrapper methods.
 */
class EmailManagerTests extends \WC_Unit_Test_Case {

	use StockNotificationsFeatureTrait;

	/**
	 * The System Under Test.
	 *
	 * @var EmailManager
	 */
	private $sut;

	/**
	 * Captured `wp_mail()` recipients.
	 *
	 * @var array<string,bool>
	 */
	private $sent_to = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->enable_stock_notifications_feature();

		// Short-circuit `wp_mail()` so the tests never attempt a real SMTP handoff.
		// Returning a non-null value from `pre_wp_mail` signals WP core to skip the actual send.
		add_filter( 'pre_wp_mail', array( $this, 'capture_pre_wp_mail' ), 10, 2 );

		$this->sut = new EmailManager();
		$this->sut->init();

		// `WC_Emails` is a singleton that keeps whichever email list it built on its first
		// construction for the rest of the process. Reset it so this test's registration
		// via `woocommerce_email_classes` is actually picked up rather than an earlier
		// test's (feature-disabled) build of the list.
		$this->reset_email_singleton();
		WC()->mailer();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_filter( 'pre_wp_mail', array( $this, 'capture_pre_wp_mail' ), 10 );
		$this->sent_to = array();
		$this->restore_stock_notifications_feature_option();
		// Discard this test's `WC_Emails` build so later tests rebuild their own from
		// whatever filters are active for them, rather than inheriting this test's list.
		$this->reset_email_singleton();
		parent::tearDown();
		WC()->query->init_query_vars();
	}

	/**
	 * Reset the `WC_Emails` singleton so the next `WC()->mailer()` call rebuilds it.
	 */
	private function reset_email_singleton(): void {
		$instance_property = ( new \ReflectionClass( \WC_Emails::class ) )->getProperty( 'instance' );
		$instance_property->setAccessible( true );
		$instance_property->setValue( null, null );
	}

	/**
	 * `pre_wp_mail` filter: record the recipient and short-circuit the actual send.
	 *
	 * @param bool|null $short_circuit Null means "keep going", non-null short-circuits.
	 * @param array     $atts          Mail arguments.
	 * @return bool
	 */
	public function capture_pre_wp_mail( $short_circuit, $atts ): bool {
		unset( $short_circuit );
		$recipients = is_array( $atts['to'] ?? null ) ? $atts['to'] : array( $atts['to'] ?? '' );
		foreach ( $recipients as $recipient ) {
			$this->sent_to[ $recipient ] = true;
		}
		return true;
	}

	/**
	 * @testdox Should register the three BIS email classes via woocommerce_email_classes filter.
	 */
	public function test_registers_all_three_bis_email_classes() {
		$emails = WC()->mailer()->get_emails();

		$this->assertArrayHasKey( 'WC_Email_Customer_Stock_Notification', $emails );
		$this->assertArrayHasKey( 'WC_Email_Customer_Stock_Notification_Verify', $emails );
		$this->assertArrayHasKey( 'WC_Email_Customer_Stock_Notification_Verified', $emails );
	}

	/**
	 * @testdox Should dispatch the verify email to the notification's user email when send_verify_email is called.
	 */
	public function test_send_verify_email_prepares_verify_email_for_notification() {
		$notification = $this->build_notification();

		$this->sut->send_verify_email( $notification );

		$emails = WC()->mailer()->get_emails();
		$verify = $emails['WC_Email_Customer_Stock_Notification_Verify'];
		$this->assertSame( $notification->get_user_email(), $verify->get_recipient() );
		// Behavior assertion: the trigger path actually dispatched mail to the expected recipient.
		$this->assertArrayHasKey( $notification->get_user_email(), $this->sent_to );
	}

	/**
	 * @testdox Should dispatch the verified email to the notification's user email when send_verified_email is called.
	 */
	public function test_send_verified_email_prepares_verified_email_for_notification() {
		$notification = $this->build_notification();

		$this->sut->send_verified_email( $notification );

		$emails   = WC()->mailer()->get_emails();
		$verified = $emails['WC_Email_Customer_Stock_Notification_Verified'];
		$this->assertSame( $notification->get_user_email(), $verified->get_recipient() );
		// Behavior assertion: the trigger path actually dispatched mail to the expected recipient.
		$this->assertArrayHasKey( $notification->get_user_email(), $this->sent_to );
	}

	/**
	 * @testdox Should link registered recipients to account management and preserve guest unsubscribe links.
	 * @testWith [false, false, "stock-notifications"]
	 *           [true, false, "stock-notifications"]
	 *           [false, false, "restock-alerts"]
	 *           [true, false, "restock-alerts"]
	 *           [false, true, "stock-notifications"]
	 *           [true, true, "stock-notifications"]
	 *           [false, false, ""]
	 *           [true, false, ""]
	 *           [false, true, ""]
	 *           [true, true, ""]
	 *           [false, false, "stock-notifications", true]
	 *           [true, false, "stock-notifications", true]
	 *           [false, false, "restock-alerts", true]
	 *           [true, false, "restock-alerts", true]
	 *           [false, true, "stock-notifications", true]
	 *           [true, true, "stock-notifications", true]
	 *           [false, false, "", true]
	 *           [true, false, "", true]
	 *           [false, true, "", true]
	 *           [true, true, "", true]
	 *
	 * @param bool   $verified Whether to render the confirmation email.
	 * @param bool   $guest Whether the signup belongs to a guest.
	 * @param string $endpoint The configured account endpoint.
	 * @param bool   $plain_text Whether to render the plain-text email instead of HTML.
	 */
	public function test_email_footer_links( bool $verified, bool $guest, string $endpoint, bool $plain_text = false ): void {
		update_option( MyAccountEndpoint::ENDPOINT_OPTION, $endpoint );
		new MyAccountEndpoint();
		WC()->query->init_query_vars();
		$notification = $this->build_notification();
		if ( ! $guest ) {
			$notification->set_user_id( self::factory()->user->create( array( 'role' => 'customer' ) ) );
			$notification->save();
		}

		$emails = WC()->mailer()->get_emails();
		$sut    = $emails[ $verified ? 'WC_Email_Customer_Stock_Notification_Verified' : 'WC_Email_Customer_Stock_Notification' ];
		$sut->prepare_email( $notification );
		$content = $plain_text ? $sut->get_content_plain() : $sut->get_content_html();
		$pattern = $plain_text ? '/^.*: (https?:\/\/\S+)$/m' : '/href="([^"]+)" id="notification__unsubscribe_link"/';
		$this->assertSame( 1, preg_match( $pattern, $content, $matches ), 'The footer must contain its management link.' );
		$link = html_entity_decode( $matches[1], ENT_QUOTES );
		parse_str( (string) wp_parse_url( $link, PHP_URL_QUERY ), $query );

		$this->assertSame( 'back-in-stock-notifications', $query['utm_source'] );
		$this->assertSame( 'email', $query['utm_medium'] );
		if ( $guest || '' === $endpoint ) {
			$this->assertSame( 'unsubscribe', $query['email_link_action'] );
			$this->assertTrue( ( new Notification( $notification->get_id() ) )->check_unsubscribe_key( $query['email_link_action_key'] ), 'The unsubscribe link must contain the persisted unsubscribe key.' );
			$this->assertSame( (string) $notification->get_id(), $query['notification_id'] );
			$this->assertStringContainsString( 'To stop receiving these messages,', $content );
			$this->assertStringNotContainsString( 'To manage your notifications,', $content );
		} else {
			$this->assertSame( wc_get_account_endpoint_url( $endpoint ), remove_query_arg( array( 'utm_source', 'utm_medium' ), $link ) );
			$this->assertArrayNotHasKey( 'email_link_action', $query );
			$this->assertArrayNotHasKey( 'email_link_action_key', $query );
			$this->assertStringContainsString( 'To manage your notifications,', $content );
			$this->assertStringNotContainsString( 'To stop receiving these messages,', $content );
		}
	}

	/**
	 * Build a persisted notification for an in-stock simple product.
	 *
	 * @return Notification
	 */
	private function build_notification(): Notification {
		$product = WC_Helper_Product::create_simple_product();

		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_status( NotificationStatus::PENDING );
		$notification->set_user_email( 'customer@example.com' );
		$notification->save();

		return $notification;
	}
}
