<?php

declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Frontend;

use Automattic\WooCommerce\Internal\StockNotifications\Frontend\ProductPageIntegration;
use WC_Helper_Product;

/**
 * Tests for ProductPageIntegration.
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

		$this->sut = new ProductPageIntegration();
	}

	/**
	 * @testdox display_account_required() should link to the My Account page, not a nested "my-account" endpoint.
	 */
	public function test_display_account_required_links_to_my_account_page() {
		$product = WC_Helper_Product::create_simple_product();

		ob_start();
		$this->sut->display_account_required( $product );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'href="' . wc_get_page_permalink( 'myaccount' ) . '"', $output );
		$this->assertStringNotContainsString( 'my-account/my-account', $output );
	}
}
