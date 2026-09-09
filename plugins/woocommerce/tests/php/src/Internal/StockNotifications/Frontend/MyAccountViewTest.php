<?php
/**
 * MyAccountViewTest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Frontend;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Frontend\MyAccountEndpoint;
use Automattic\WooCommerce\Internal\StockNotifications\Frontend\MyAccountView;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Tests\Internal\StockNotifications\StockNotificationsFeatureTrait;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the MyAccountView class.
 */
class MyAccountViewTest extends WC_Unit_Test_Case {

	use StockNotificationsFeatureTrait;

	/**
	 * The System Under Test.
	 *
	 * @var MyAccountView
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->enable_stock_notifications_feature();
		$this->sut = new MyAccountView();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		try {
			$this->restore_stock_notifications_feature_option();
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * Create a saved notification for the given product.
	 *
	 * @param int    $product_id Product id.
	 * @param string $status     Notification status.
	 * @return Notification
	 */
	private function create_notification( int $product_id, string $status ): Notification {
		$notification = new Notification();
		$notification->set_product_id( $product_id );
		$notification->set_user_id( 1 );
		$notification->set_user_email( 'customer@example.com' );
		$notification->set_status( $status );
		$notification->save();

		return $notification;
	}

	/**
	 * Default pagination state for a single page.
	 *
	 * @param int $current_page 1-indexed current page.
	 * @param int $total_pages  Total number of pages.
	 * @return array<string,int>
	 */
	private function page( int $current_page = 1, int $total_pages = 1 ): array {
		return array(
			'current_page' => $current_page,
			'total_pages'  => $total_pages,
			'total_items'  => 1,
			'per_page'     => 10,
		);
	}

	/**
	 * @testdox Should flatten a pending notification into a row with resend and cancel URLs.
	 */
	public function test_pending_row_carries_resend_and_cancel_urls(): void {
		$product      = WC_Helper_Product::create_simple_product();
		$notification = $this->create_notification( $product->get_id(), NotificationStatus::PENDING );

		$args = $this->sut->get_template_args( array( $notification ), array(), $this->page() );
		$row  = $args['pending_rows'][0];

		$this->assertSame( array(), $args['active_rows'] );
		$this->assertTrue( $args['has_pending'] );
		$this->assertTrue( $args['has_items'] );
		$this->assertSame( $notification->get_id(), $row['id'] );
		$this->assertSame( NotificationStatus::PENDING, $row['status'] );
		$this->assertSame( $product->get_title(), $row['product_name'] );
		$this->assertSame( $product->get_permalink(), $row['product_url'] );
		$this->assertSame( '', $row['variation'] );
		$this->assertSame( MyAccountEndpoint::get_action_url( MyAccountEndpoint::ACTION_RESEND, $notification->get_id() ), $row['resend_url'] );
		$this->assertSame( 'Resend verification email for ' . $product->get_title(), $row['resend_label'] );
		$this->assertSame( MyAccountEndpoint::get_action_url( MyAccountEndpoint::ACTION_CANCEL, $notification->get_id() ), $row['cancel_url'] );
		$this->assertSame( 'Cancel stock notification for ' . $product->get_title(), $row['cancel_label'] );
		$this->assertSame( $notification, $row['notification'] );
	}

	/**
	 * @testdox Should only offer cancel on an active row.
	 */
	public function test_active_row_has_no_resend_url(): void {
		$product      = WC_Helper_Product::create_simple_product();
		$notification = $this->create_notification( $product->get_id(), NotificationStatus::ACTIVE );

		$args = $this->sut->get_template_args( array(), array( $notification ), $this->page() );
		$row  = $args['active_rows'][0];

		$this->assertFalse( $args['has_pending'] );
		$this->assertTrue( $args['has_items'] );
		$this->assertSame( '', $row['resend_url'] );
		$this->assertSame( '', $row['resend_label'] );
		$this->assertNotSame( '', $row['cancel_url'] );
	}

	/**
	 * @testdox Should leave both action URLs empty on a row that can neither be resent nor cancelled.
	 */
	public function test_sent_row_has_no_action_urls(): void {
		$product      = WC_Helper_Product::create_simple_product();
		$notification = $this->create_notification( $product->get_id(), NotificationStatus::SENT );

		$row = $this->sut->get_template_args( array(), array( $notification ), $this->page() )['active_rows'][0];

		$this->assertSame( '', $row['resend_url'] );
		$this->assertSame( '', $row['cancel_url'] );
	}

	/**
	 * @testdox Should label a row for a variation with the parent title and the attribute list.
	 */
	public function test_variation_row_uses_parent_title_and_attribute_list(): void {
		$variable     = WC_Helper_Product::create_variation_product();
		$variation_id = (int) $variable->get_children()[0];
		$notification = $this->create_notification( $variation_id, NotificationStatus::ACTIVE );

		$row = $this->sut->get_template_args( array(), array( $notification ), $this->page() )['active_rows'][0];

		$this->assertSame( $variable->get_title(), $row['product_name'] );
		$this->assertNotSame( '', $row['variation'] );
		$this->assertSame( 'Cancel stock notification for ' . $variable->get_title() . ' ' . $row['variation'], $row['cancel_label'] );
	}

	/**
	 * @testdox Should fall back to a generic label and empty product fields when the product is gone.
	 */
	public function test_missing_product_row_falls_back(): void {
		$notification = $this->create_notification( 999999, NotificationStatus::ACTIVE );

		$row = $this->sut->get_template_args( array(), array( $notification ), $this->page() )['active_rows'][0];

		$this->assertSame( '', $row['product_name'] );
		$this->assertSame( '', $row['product_url'] );
		$this->assertSame( 'Cancel stock notification for an unavailable product', $row['cancel_label'] );
	}

	/**
	 * @testdox Should carry the current page in the action URLs so the redirect returns there.
	 */
	public function test_action_urls_carry_the_current_page(): void {
		$product      = WC_Helper_Product::create_simple_product();
		$notification = $this->create_notification( $product->get_id(), NotificationStatus::ACTIVE );

		$row = $this->sut->get_template_args( array(), array( $notification ), $this->page( 3, 4 ) )['active_rows'][0];

		$this->assertSame( MyAccountEndpoint::get_action_url( MyAccountEndpoint::ACTION_CANCEL, $notification->get_id(), 3 ), $row['cancel_url'] );
	}

	/**
	 * @testdox Should only build the pagination URLs that lead somewhere.
	 *
	 * @testWith [1, 1, false, false]
	 *           [1, 3, false, true]
	 *           [2, 3, true, true]
	 *           [3, 3, true, false]
	 *
	 * @param int  $current_page 1-indexed current page.
	 * @param int  $total_pages  Total number of pages.
	 * @param bool $has_previous Whether a previous page URL is expected.
	 * @param bool $has_next     Whether a next page URL is expected.
	 */
	public function test_pagination_urls( int $current_page, int $total_pages, bool $has_previous, bool $has_next ): void {
		$args = $this->sut->get_template_args( array(), array(), $this->page( $current_page, $total_pages ) );

		$this->assertSame( $has_previous ? MyAccountEndpoint::get_endpoint_url( $current_page - 1 ) : '', $args['previous_page_url'] );
		$this->assertSame( $has_next ? MyAccountEndpoint::get_endpoint_url( $current_page + 1 ) : '', $args['next_page_url'] );
		$this->assertSame( $current_page, $args['current_page'] );
		$this->assertSame( $total_pages, $args['total_pages'] );
	}

	/**
	 * @testdox Should report an empty state when there are no notifications at all.
	 */
	public function test_empty_state(): void {
		$args = $this->sut->get_template_args( array(), array(), $this->page() );

		$this->assertFalse( $args['has_pending'] );
		$this->assertFalse( $args['has_items'] );
		$this->assertSame( array(), $args['pending_rows'] );
		$this->assertSame( array(), $args['active_rows'] );
		$this->assertSame( wc_get_page_permalink( 'shop' ), $args['shop_url'] );
	}
}
