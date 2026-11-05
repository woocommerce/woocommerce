<?php

declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Internal\StockNotifications;

use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\Factory;

/**
 * FactoryTests data tests.
 */
class FactoryTests extends \WC_Unit_Test_Case {

	use StockNotificationsFeatureTrait;

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->enable_stock_notifications_feature();
	}

	/**
	 * Tear down the test.
	 */
	public function tearDown(): void {
		$this->restore_stock_notifications_feature_option();
		parent::tearDown();
	}

	/**
	 * Test the factory.
	 */
	public function test_factory() {

		// Create a notification.
		$notification = new Notification();
		$notification->set_product_id( 2 );
		$notification->set_user_id( 2 );
		$notification->save();

		$notification = Factory::get_notification( $notification->get_id() );
		$this->assertInstanceOf( Notification::class, $notification );
		$this->assertEquals( 2, $notification->get_product_id() );
		$this->assertEquals( 2, $notification->get_user_id() );
	}
}
