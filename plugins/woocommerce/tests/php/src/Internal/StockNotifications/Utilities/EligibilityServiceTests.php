<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Utilities;

use Automattic\WooCommerce\Internal\StockNotifications\Utilities\EligibilityService;
use Automattic\WooCommerce\Internal\StockNotifications\Utilities\StockManagementHelper;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Enums\ProductStockStatus;
use WC_Helper_Product;

/**
 * Tests for NotificationEligibilityService
 */
class EligibilityServiceTests extends \WC_Unit_Test_Case {

	/**
	 * @var EligibilityService
	 */
	private $sut;

	/**
	 * @before
	 */
	public function setUp(): void {
		parent::setUp();
		$stock_management_helper = new StockManagementHelper();
		$this->sut               = new EligibilityService();
		$this->sut->init( $stock_management_helper );
	}

	/**
	 * @after
	 */
	public function tearDown(): void {
		parent::tearDown();
		unset( $this->sut );
		// Clean up all notifications.
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wc_stock_notifications" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wc_stock_notificationmeta" );
	}

	/**
	 * @testdox is_product_eligible returns true for simple product
	 */
	public function test_is_product_eligible(): void {
		$product = WC_Helper_Product::create_simple_product();
		$this->assertTrue( $this->sut->is_product_eligible( $product ) );

		$variable = WC_Helper_Product::create_variation_product();
		$this->assertTrue( $this->sut->is_product_eligible( $variable ) );

		$variation = $variable->get_children()[0];
		$variation = \wc_get_product( $variation );
		$this->assertTrue( $this->sut->is_product_eligible( $variation ) );

		$grouped = WC_Helper_Product::create_grouped_product();
		$this->assertFalse( $this->sut->is_product_eligible( $grouped ) );

		$external = WC_Helper_Product::create_external_product();
		$this->assertFalse( $this->sut->is_product_eligible( $external ) );
	}

	/**
	 * @testdox is_stock_status_eligible returns true for in stock and on backorder
	 */
	public function test_is_stock_status_eligible(): void {
		$this->assertTrue( $this->sut->is_stock_status_eligible( ProductStockStatus::IN_STOCK ) );
		$this->assertTrue( $this->sut->is_stock_status_eligible( ProductStockStatus::ON_BACKORDER ) );
		$this->assertFalse( $this->sut->is_stock_status_eligible( ProductStockStatus::OUT_OF_STOCK ) );
		$this->assertFalse( $this->sut->is_stock_status_eligible( ProductStockStatus::LOW_STOCK ) );
	}

	/**
	 * @testdox has_active_notifications returns false for product with no active notifications
	 */
	public function test_has_active_notifications(): void {
		$product = WC_Helper_Product::create_simple_product();
		$this->assertFalse( $this->sut->has_active_notifications( $product ) );

		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->set_user_email( 'test@test.com' );
		$notification->save();

		$this->assertTrue( $this->sut->has_active_notifications( $product ) );
	}

	/**
	 * @testdox get_target_product_ids returns correct product ids
	 */
	public function test_get_target_product_ids(): void {
		$product = WC_Helper_Product::create_simple_product();
		$this->assertEquals( array( $product->get_id() ), $this->sut->get_target_product_ids( $product ) );

		$variable           = WC_Helper_Product::create_variation_product();
		$managed_variations = array_filter(
			array_map(
				function ( $variation ) {
					$variation = \wc_get_product( $variation );
					if ( 'yes' !== $variation->get_manage_stock() ) {
						return $variation->get_id();
					}
					return null;
				},
				$variable->get_children()
			)
		);

		$this->assertEquals( array( $variable->get_id(), ...$managed_variations ), $this->sut->get_target_product_ids( $variable ) );

		$variation = $variable->get_children()[0];
		$variation = \wc_get_product( $variation );
		// Set managed stock to yes.
		$variation->set_manage_stock( true );
		$variation->set_stock_quantity( 10 );
		$variation->save();

		$this->assertEquals( array( $variation->get_id() ), $this->sut->get_target_product_ids( $variation ) );
	}

	/**
	 * @testdox should_skip_notification returns false for product with no active notifications
	 */
	public function test_should_skip_notification(): void {
		$product      = WC_Helper_Product::create_simple_product();
		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->save();

		$this->assertFalse( $this->sut->should_skip_notification( $notification, $product ) );

		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->set_date_notified( time() );
		$notification->save();

		$this->assertTrue( $this->sut->should_skip_notification( $notification, $product ) );

		// Less than threshold.
		$notification->set_date_notified( time() - EligibilityService::SPAM_THRESHOLD - 1 );
		$this->assertFalse( $this->sut->should_skip_notification( $notification, $product ) );

		// More than threshold.
		$notification->set_date_notified( time() - EligibilityService::SPAM_THRESHOLD + 1 );
		$this->assertTrue( $this->sut->should_skip_notification( $notification, $product ) );
	}
}
