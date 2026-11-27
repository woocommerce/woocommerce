<?php

declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Emails;

use Automattic\WooCommerce\EmailEditor\Bootstrap;
use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\Dependency_Check;
use Automattic\WooCommerce\Internal\EmailEditor\Integration;
use Automattic\WooCommerce\Internal\EmailEditor\Package;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmails;
use Automattic\WooCommerce\Internal\StockNotifications\Emails\CustomerStockNotificationEmail;
use Automattic\WooCommerce\Internal\StockNotifications\Emails\CustomerStockNotificationVerifiedEmail;
use Automattic\WooCommerce\Internal\StockNotifications\Emails\CustomerStockNotificationVerifyEmail;
use Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailTemplatesController;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Frontend\MyAccountEndpoint;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\StockNotifications;
use Automattic\WooCommerce\Tests\Internal\StockNotifications\StockNotificationsFeatureTrait;
use WC_Helper_Product;

/**
 * Block email editor versions of the back-in-stock emails.
 */
class StockNotificationBlockEmailsTests extends \WC_Unit_Test_Case {

	use StockNotificationsFeatureTrait;

	/**
	 * Turn on the block email editor and the back-in-stock feature.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->enable_stock_notifications_feature();
		update_option( 'woocommerce_feature_block_email_editor_enabled', 'yes' );

		wc_get_container()->get( Package::class )->init();
		wc_get_container()->get( Integration::class )->initialize();
		Email_Editor_Container::container()->get( Bootstrap::class )->initialize();
		( new EmailTemplatesController() )->register_template_hooks();
	}

	/**
	 * Restore the feature option.
	 */
	public function tearDown(): void {
		$this->restore_stock_notifications_feature_option();
		parent::tearDown();
		WC()->query->init_query_vars();
	}

	/**
	 * @testdox The back-in-stock emails are listed in the block email editor only when the feature is on.
	 * @testWith ["yes", true]
	 *           ["no", false]
	 *
	 * @param string $feature_enabled Value of the back-in-stock feature option.
	 * @param bool   $expected_listed Whether the back-in-stock emails should be listed.
	 */
	public function test_emails_are_listed_with_the_feature( string $feature_enabled, bool $expected_listed ): void {
		update_option( StockNotifications::ENABLE_OPTION_NAME, $feature_enabled );

		$emails = WCTransactionalEmails::get_core_transactional_emails();

		$this->assertSame( $expected_listed, in_array( 'customer_stock_notification', $emails, true ) );
		$this->assertSame( $expected_listed, in_array( 'customer_stock_notification_verify', $emails, true ) );
		$this->assertSame( $expected_listed, in_array( 'customer_stock_notification_verified', $emails, true ) );
	}

	/**
	 * @testdox The $email_class email is sent in the block design with the product, its action and the sign-up note.
	 * @dataProvider provider_emails
	 *
	 * @param string   $email_class   Email class.
	 * @param string   $heading       Expected heading.
	 * @param string[] $expected_text Text the email should contain.
	 */
	public function test_email_is_sent_in_block_design( string $email_class, string $heading, array $expected_text ): void {
		$this->skip_if_unsupported_environment();

		$notification = $this->create_notification();

		$email = new $email_class();
		$email->prepare_email( $notification );

		$content = $email->get_content();

		$this->assertStringContainsString( $heading, $content );
		$this->assertStringContainsString( 'Blue Mug', $content );
		foreach ( $expected_text as $text ) {
			$this->assertStringContainsString( $text, $content );
		}
		$this->assertStringContainsString( 'Thanks for shopping with us.', $content );
		$this->assertStringContainsString( '. All Rights Reserved.', $content, 'Only the block email template renders this footer.' );
	}

	/**
	 * @testdox A registered customer gets a link to manage notifications in their account instead of an unsubscribe link.
	 */
	public function test_registered_customer_gets_account_link(): void {
		$this->skip_if_unsupported_environment();

		update_option( MyAccountEndpoint::ENDPOINT_OPTION, 'stock-notifications' );
		new MyAccountEndpoint();
		WC()->query->init_query_vars();

		$notification = $this->create_notification();
		$notification->set_user_id( self::factory()->user->create( array( 'role' => 'customer' ) ) );
		$notification->save();

		$email = new CustomerStockNotificationEmail();
		$email->prepare_email( $notification );

		$content = $email->get_content();

		$this->assertStringContainsString( 'To manage your notifications,', $content );
		$this->assertStringContainsString( 'stock-notifications', $content );
		$this->assertStringNotContainsString( 'email_link_action=unsubscribe', $content );
	}

	/**
	 * Create a pending guest notification for a saved product named Blue Mug.
	 *
	 * @return Notification
	 */
	private function create_notification(): Notification {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_name( 'Blue Mug' );
		$product->save();

		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_status( NotificationStatus::PENDING );
		$notification->set_user_email( 'guest@example.com' );
		$notification->save();

		return $notification;
	}

	/**
	 * Skip when the test environment can't run the block email editor.
	 */
	private function skip_if_unsupported_environment(): void {
		if ( ! Email_Editor_Container::container()->get( Dependency_Check::class )->are_dependencies_met() ) {
			$this->markTestSkipped( 'The test environment does not meet the block email editor requirements.' );
		}
	}

	/**
	 * Each back-in-stock email with its heading and the text only it shows.
	 *
	 * @return array<string, array{0: string, 1: string, 2: string[]}>
	 */
	public static function provider_emails(): array {
		return array(
			'back in stock'      => array(
				CustomerStockNotificationEmail::class,
				'It\'s back in stock!',
				array( 'Shop Now', 'email_link_action=unsubscribe', 'To stop receiving these messages,' ),
			),
			'confirm sign-up'    => array(
				CustomerStockNotificationVerifyEmail::class,
				'Confirm sign-up',
				array( 'Confirm', 'email_link_action=verify', 'This link will remain active for', 'Wasn\'t you?' ),
			),
			'sign-up successful' => array(
				CustomerStockNotificationVerifiedEmail::class,
				'Sign-up successful',
				array( 'email_link_action=unsubscribe', 'To stop receiving these messages,' ),
			),
		);
	}
}
