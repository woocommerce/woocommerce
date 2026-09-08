<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Frontend;

use Automattic\WooCommerce\Internal\StockNotifications\Frontend\ProductPageIntegration;
use Automattic\WooCommerce\Internal\StockNotifications\Frontend\SignupService;
use Automattic\WooCommerce\Internal\StockNotifications\Utilities\EligibilityService;
use WC_Helper_Product;

/**
 * Tests for ProductPageIntegration class.
 */
class ProductPageIntegrationTests extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ProductPageIntegration
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		wc_clear_notices();

		$eligibility_service = $this->createMock( EligibilityService::class );
		$signup_service      = $this->createMock( SignupService::class );

		$this->sut = new ProductPageIntegration();
		$this->sut->init( $eligibility_service, $signup_service );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		wc_clear_notices();
		parent::tearDown();
	}

	/**
	 * @testdox display_account_required prints notice linking to the My Account page permalink without redundant path segments.
	 */
	public function test_display_account_required_login_link_points_to_my_account_page(): void {
		$product = WC_Helper_Product::create_simple_product();

		ob_start();
		$this->sut->display_account_required( $product );
		$output = ob_get_clean();

		$my_account_url = wc_get_page_permalink( 'myaccount' );

		$this->assertStringContainsString( 'href="' . $my_account_url . '"', $output );
		$this->assertStringNotContainsString( '/my-account/my-account/', $output );
	}

	/**
	 * @testdox display_account_required respects custom filter override.
	 */
	public function test_display_account_required_respects_filter(): void {
		$product = WC_Helper_Product::create_simple_product();

		$custom_message = '<div class="custom-account-required">Custom Message</div>';
		add_filter(
			'woocommerce_customer_stock_notifications_account_required_message_html',
			static function () use ( $custom_message ) {
				return $custom_message;
			}
		);

		ob_start();
		$this->sut->display_account_required( $product );
		$output = ob_get_clean();

		$this->assertStringContainsString( $custom_message, $output );
	}
}
